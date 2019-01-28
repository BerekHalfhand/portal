<?php
namespace Treto\PortalBundle\Services;

use FOS\UserBundle\Doctrine\UserManager;
use MongoDBODMProxies\__CG__\Treto\UserBundle\Document\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Treto\PortalBundle\Document\Dictionaries;
use \Treto\PortalBundle\Document\Portal;
use \Treto\PortalBundle\Document\Contacts;
use Treto\PortalBundle\Document\PortalSettings;
use Treto\PortalBundle\Document\TaskHistory;
// $this->container->get('monolog.logger.autotask')->info('--------'.json_encode($this->performerDoc));
class TaskService
{
  /** @var  \Symfony\Component\DependencyInjection\Container */
  private $container;
  /** @var \Treto\PortalBundle\Document\TaskHistory */
  private $taskHistoryObj;
  /** @var \Treto\PortalBundle\Document\SecureRepository */
  private $portalRepo;
  /** @var \Treto\UserBundle\Document\User */
  private $user;
  /** @var Portal */
  private $task;
  /** @var Portal */
  private $main;
  /** @var Portal */
  private $checker;
  /** @var Portal */
  private $shareUser;
  /** @var Portal */
  private $shareChecker;
  private $performerDoc;
  private $logger;
  private $result;
  private $oldShareSecurity;
  private $data;
  private $time;
  private $selfHost;

  const TASK_STATUS_CHANGE_PERFORMER_3 = 3; //сменить исполнителя
  const TASK_STATUS_CHANGE_PERFORMER_4 = 4; //сменить исполнителя
  const TASK_STATUS_SET_TERMS = 5; //установить сроки
  const TASK_STATUS_CHANGE_PRIORITY = 7; //сменить приоритет
  const TASK_STATUS_COMPLETE = 10; //уведомить об исполнении
  const TASK_STATUS_REQUEST_PULL = 12; //запросить накат
  const TASK_STATUS_NOTIFY_PULL = 13; //уведомить о выполнении наката
  const TASK_STATUS_RETURN_REWORK = 15; //вернуть на доработку
  const TASK_STATUS_SEND_FOR_REVIEW_20 = 20; //отправить на проверку
  const TASK_STATUS_SEND_FOR_REVIEW_21 = 21; //отправить на проверку
  const TASK_STATUS_ACCEPT = 25; //принять выполнение
  const TASK_STATUS_HANDING = 30; //подвесить
  const TASK_STATUS_CANCEL = 35; //отменить

  public function __construct(ContainerInterface $container)
  {
    $this->container = $container;
  }

  /**
   * Init all properties
   * @param $unid
   * @param $code
   * @param $username
   * @param $taskData
   * @param $selfHost
   */
  public function initStep($unid, $code, $username, $taskData, $selfHost)
  {
    $this->result = ['error' => '', 'debug' => '', 'result' => []];
    $this->portalRepo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
    $this->logger = $this->container->get('monolog.logger.notif_logger');
    $this->task = $this->portalRepo->findOneBy(['unid' => $unid]);
    $this->time = ['timeISO' => null, 'time' => null];
    $this->shareUser = is_array($username)?$username:false; //['domain' => '', 'username' => ''] || false
    $this->shareChecker = false;
    $this->selfHost = $selfHost;

    if($this->shareUser){
      $username = User::ROBOT_PORTAL;
    }

    if (isset($data['timeISO'])) {
      $this->time['timeISO'] = $data['timeISO'];
      $this->time['time'] = $data['time'];
    } else {
      $now = new \DateTime();
      $this->time['timeISO'] = $now->format('Ymd') . 'T' . $now->format('His');
    }

    if ($taskData && isset($taskData['additionalData'])) {
      $this->data = $taskData['additionalData'];
    } else {
      $this->data = $taskData;
    }

    $performerLat = $this->task->GetTaskPerformerLat(true);
    $this->performerDoc = [];

    if($performerLat != 'shareTask'){
      $this->performerDoc = ['username' => $performerLat];
    }
    elseif($this->task->GetSharePerformers()) {
      $sharePerformers = isset($this->task->GetSharePerformers()[0])?$this->task->GetSharePerformers()[0]:$this->task->GetSharePerformers();
      $this->performerDoc = $this->container->get('service.site_robojson')->getShareUser(
        $sharePerformers['domain'],
        $sharePerformers['login']
      );
      $this->performerDoc['domain'] = $sharePerformers['domain'];
      $this->performerDoc['login'] = $sharePerformers['login'];
    }

    if($this->task->GetShareChecker() || ($this->task->GetCreateHost() != $selfHost && $this->task->GetShareAuthorLogin())){
      $shareChecker = $this->task->GetShareChecker();
      if(isset($shareChecker['domain']) && isset($shareChecker['login'])){
        $this->shareChecker = $shareChecker;
      }
      else {
        $this->shareChecker = ['domain' => $this->task->GetCreateHost(), 'login' => $this->task->GetShareAuthorLogin()];
      }
    }

    if (is_string($username)) {
      /** @var UserManager $userManager */
      $userManager = $this->container->get('fos_user.user_manager');
      $this->user = $userManager->findUserBy(['username' => $username]);
      if (!$this->user) {
        $this->user = $userManager->findUserBy(['username' => \Treto\UserBundle\Document\User::ROBOT_PORTAL]);
      }
    } else if (is_object($username)) {
      $this->user = $username;
    } else {
      $this->result['error'] = 'not found selfUser';
    }

    if ($this->user) {
      $this->taskHistoryObj = new \Treto\PortalBundle\Document\TaskHistory();
      $this->taskHistoryObj->setTaskUnid($unid);
      $this->taskHistoryObj->SetUnid();
      if($this->shareUser){
        $this->taskHistoryObj->setDomain($this->shareUser['domain']);
      }

      $this->taskHistoryObj->setAuthorLogin($this->shareUser?$this->shareUser['username']:$this->user->getPortalData()->GetLogin());
      $this->taskHistoryObj->setDefaultSecurity($this->user);

      if (!$unid || !$code) {
        $this->result['error'] = 'wrong input. missing unid or code.';
      } else {
        $this->checker = $this->getChecker($code);

        if (!$this->task) {
          $this->result['error'] = 'document not found';
        } elseif ($this->task->GetSubjectID() && $this->task->GetSubjectID() != $unid) {
          $contactsRepo = false;
          $repo = $this->portalRepo;
          if ($this->task->GetParentDbName() == 'Contacts') { //some old tasks do not have this field
            $contactsRepo = true;
            $repo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
          }

          $this->main = $repo->findOneBy(['unid' => $this->task->GetSubjectID()]);
          if (!$this->main) {
            if (!$contactsRepo){
              $repo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
            }

            $this->main = $repo->findOneBy(['unid' => $this->task->GetSubjectID()]);

            if (!$this->main) {
              $this->result['error'] = 'not found parent document';
            }
          }
        } else {
          $this->main = $this->task;
        }

        $this->oldShareSecurity = $this->main->getShareSecurity();
      }
    }
  }

  /**
   * Main task function
   * @param $unid
   * @param $code
   * @param $taskData
   * @param $username
   * @param $selfHost
   * @param bool $fromShare
   * @return array
   */
  public function task($unid, $code, $taskData, $username, $selfHost, $fromShare = false)
  {
    $code = intval($code);
    $this->initStep($unid, $code, $username, $taskData, $selfHost);

    /** @var RoboService $robo */
    $robo = $this->container->get('service.site_robojson');

    if ($this->result['error']) {
      return $this->result;
    }

    if (isset($this->data['expectedStatus']) && $this->data['expectedStatus'] != $this->task->GetTaskStateCurrent() && !$fromShare) {
      $taskHistories = $this->getHistories($this->task);
      return [
        'success' => 'true',
        'task' => $this->task->getDocument(),
        'histories' => $taskHistories,
        'result' => ['unexpected status']
      ];
    }

    if (!$this->user->can('write', $this->task) &&
      $this->user->getPortalData()->GetFullName() != $this->task->GetTaskPerformerLat(true) &&
      $this->user->getPortalData()->GetLogin() != $this->task->GetTaskPerformerLat(true) &&
      $this->user->getPortalData()->GetLogin() != $this->checker->GetLogin() &&
      !$this->user->isEscalManager($this->task) && !$this->shareUser
    ) {
      return ['error' => 'permission denied'];
    }

    $this->task->setTaskStatePrevious($this->task->getTaskStateCurrent());
    $this->task->setTaskStateCurrent($code);

    switch ($code) {
      case self::TASK_STATUS_CHANGE_PERFORMER_3:
        $this->changePerformer();
        break;
      case self::TASK_STATUS_SET_TERMS:
        $this->setTerms();
        break;
      case self::TASK_STATUS_CHANGE_PRIORITY:
        $this->changePriority();
        break;
      case self::TASK_STATUS_REQUEST_PULL:
        $this->requestPullMerge();
        break;
      case self::TASK_STATUS_NOTIFY_PULL:
        $this->notifyPullMerge();
        break;
      case self::TASK_STATUS_HANDING:
        $this->hanging();
        break;
      case self::TASK_STATUS_CANCEL:
        $this->cancel();
        break;
      case self::TASK_STATUS_RETURN_REWORK:
        $this->returnForRework();
        break;
      case self::TASK_STATUS_SEND_FOR_REVIEW_20:
        $this->sendForReview();
        break;
      case self::TASK_STATUS_ACCEPT:
        $this->accept();
        break;
      case self::TASK_STATUS_COMPLETE:
        $this->completeTask();
        break;
    }

    if ($this->result['error']) {
      return $this->result;
    }

    $this->result['result'] += $robo->processNotifications(
      $this->main,
      $this->task,
      $this->user,
      true,
      true,
      false,
      null,
      null,
      ($code == self::TASK_STATUS_SET_TERMS)
    );

    $robo->createHistoryLog($this->task->GetUnid(), $this->task->GetSubject(), $this->task->GetForm(), $this->user);
    $robo->setReadByTime($this->main, $this->time['time'], true, false, $this->user->getPortalData()->GetLogin());
    $tempShareData = [
      'code' => $code,
      'data' => $this->data,
    ];

    if(!$fromShare && $selfHost && $username != User::ROBOT_PORTAL){
      $tempShareData['domain'] = $selfHost;
      $tempShareData['username'] = $this->user->getPortalData()->GetLogin();
    }

    $this->task->SetShareTempData(json_encode($tempShareData));

    $this->getDM()->persist($this->task);
    $this->getDM()->persist($this->taskHistoryObj);
    $this->getDM()->flush();
    $this->getDM()->clear();

    if(!$fromShare && $selfHost){
      $synchService = $this->container->get('synch.service');
      /** @var $synchService SynchService */
      $checkResult = $synchService->checkShare(
        $this->task,
        $this->main,
        $selfHost,
        false,
        isset($this->oldShareSecurity)&&$this->oldShareSecurity?$this->oldShareSecurity:false,
        'taskService'
      );
    }

    $taskHistories = $this->getHistories($this->task);
    $taskDoc = $this->task->getDocument();
    $taskDoc['taskHistories'] = $taskHistories;
    $nodeService = $this->container->get('node.service');
    $nodeService->addComent($this->main->getUnid(), $taskDoc);

    if ($this->main->GetToSite() == '1' && $this->task->GetNotForSite() != '1' && !$this->shareUser) {
      /** @var SiteService $siteService */
      $siteService = $this->container->get('site.service');
      $siteService->sendCommentToSite($this->main, $this->task, $this->user);
    }

    return [
      'success' => 'true',
      'task' => $this->task->getDocument(),
      'histories' => $taskHistories,
      'result' => $this->result,
      'takeOut' => isset($checkResult['takeOut'])&&$checkResult['takeOut']?$this->task->GetUnid():false
    ];
  }

  /**
   * Change task performer(изменить исполнителя)
   * TASK_STATUS_CHANGE_PERFORMER code: 3, 4
   */
  public function changePerformer()
  {
    if (!isset($this->data['performer']) && !isset($this->data['sharePerformer'])) {
      $this->result['error'] = 'wrong input. missing performer';
    } else {
      $sharePerformer = false;

      if(isset($this->data['sharePerformer']) && $this->data['sharePerformer'] && !isset($this->data['performer'])){
        $login = reset($this->data['sharePerformer']);
        $sharePerformer = ['login' => isset($login[0])?$login[0]:$login, 'domain' => key($this->data['sharePerformer'])];
      }
      if ($this->task->getTaskStatePrevious() == self::TASK_STATUS_CHANGE_PERFORMER_3) {
        $this->task->setTaskStateCurrent(self::TASK_STATUS_CHANGE_PERFORMER_4);
      }

      $this->taskHistoryObj->setType('taskPerformer');

      if($this->task->GetTaskPerformerLat(true) != 'shareTask'){
        $oldValue = ['login' => $this->task->GetTaskPerformerLat(true)];
      }
      else {
        $sharePerformers = $this->task->GetSharePerformers();
        $sharePerformers = isset($sharePerformers[0])?$sharePerformers[0]:$sharePerformers;
        $oldValue = ['login' => $sharePerformers['login'], 'domain' => $sharePerformers['domain']];
      }

      $this->taskHistoryObj->setOldValue($oldValue);
      $this->taskHistoryObj->setValue(!$sharePerformer?['login' => $this->data['performer']]:$sharePerformer);
      if(!$sharePerformer){
        $this->task->SetTaskPerformerLat([$this->data['performer']]);
        $this->task->SetSharePerformers([]);
      }
      else {
        $this->task->SetTaskPerformerLat(['shareTask']);
        $this->task->SetSharePerformers([$sharePerformer]);
      }

      $this->task->SetWaitPerformer('0');
      $this->debugLog('Performer changed by ' . $this->getDebugLogin() . ' at ' . __FUNCTION__ . ', ' . __LINE__);

      if ($this->task->GetTaskStatePrevious() == self::TASK_STATUS_COMPLETE) {
        $this->result['debug'][] = $this->processChecker('remove');
      }

      if(!$sharePerformer){
        $newPerformerDoc = $this->portalRepo->findEmplByNames(
          [$this->data['performer']],
          [$this->data['performer']],
          [$this->data['performer']]
        );
        if (isset($newPerformerDoc[0])) {
          $newPerformerDoc = $newPerformerDoc[0];
        }

        if (isset($newPerformerDoc) && is_object($newPerformerDoc)) {
          $this->main->addReadPrivilege($newPerformerDoc->GetLogin(), $this->user->getPortalData()->GetLogin());
          $this->main->addSubscribedPrivilege($newPerformerDoc->GetLogin(), $this->user->getPortalData()->GetLogin());
          $this->getDM()->persist($this->main);

          $this->task->setTaskDateRealEnd('');
          $this->task->addReadPrivilege($newPerformerDoc->GetLogin(), $this->user->getPortalData()->GetLogin());
          $this->task->addSubscribedPrivilege($newPerformerDoc->GetLogin(), $this->user->getPortalData()->GetLogin());

          $this->container->get('notif.service')->notifAdding($this->main,
            $this->task,
            $newPerformerDoc->GetLogin(),
            1,
            __FUNCTION__ . ', ' . __LINE__,
            'Added urgent-1 notif to');

          $this->result['debug'][] = $this->processEscalation();
        }
      }
      else {
        $this->main->addSharePrivileges($sharePerformer['domain'], 'read', 'username', $sharePerformer['login']);
        $this->main->addSharePrivileges($sharePerformer['domain'], 'subscribed', 'username', $sharePerformer['login']);
        $this->task->addSharePrivileges($sharePerformer['domain'], 'read', 'username', $sharePerformer['login']);
        $this->task->addSharePrivileges($sharePerformer['domain'], 'subscribed', 'username', $sharePerformer['login']);
        $this->task->setTaskDateRealEnd('');
        $this->getDM()->persist($this->main);
      }

      if (isset($this->performerDoc) && isset($this->performerDoc['username']) && !isset($this->performerDoc['domain'])) {
        if ($this->performerDoc['username'] != $this->task->GetAuthorLogin()) {
          $this->task->removeActionPrivilege('write', 'username', $this->performerDoc['username']);
        }
        $this->container->get('notif.service')->notifRemoval($this->main,
          $this->task,
          $this->performerDoc['username'],
          1,
          __FUNCTION__ . ', ' . __LINE__,
          'Removed urgent-1 notif from',
          $this->time['timeISO']);
      }
      $this->container->get('notif.service')->notifRemoval($this->main,
        $this->task,
        $this->user->getPortalData()->GetLogin(),
        0,
        __FUNCTION__ . ', ' . __LINE__,
        'Removed notif from',
        $this->time['timeISO']);
    }
  }

  /**
   * Set task terms(установить сроки)
   * TASK_STATUS_SET_TERMS code: 5
   */
  public function setTerms()
  {
    if (!isset($this->data['dEndFinish']) || !isset($this->data['difficulty'])) {
      $this->result['error'] = 'wrong input. missing dEndFinish';
    } else {
      if ($this->data['dEndFinish'] != $this->task->GetTaskDateRealEnd() && $this->data['difficulty'] != $this->task->GetDifficulty()) {
        $this->taskHistoryObj->setType('dateAndDiff');
        
        if (!$this->task->GetTaskDateRealEnd()) {
          $this->taskHistoryObj->setFlags(['initialTimeline' => true]);
        }
        if (!$this->task->GetDifficulty()) {
          $this->taskHistoryObj->setFlags(['initialDifficulty' => true]);
        }
        $this->taskHistoryObj->setOldValue([
          'start' => $this->task->GetTaskDateRealStart(),
          'end' => $this->task->GetTaskDateRealEnd()
        ]);
        $this->taskHistoryObj->setValue([
          'start' => $this->task->GetTaskDateRealStart(),
          'end' => $this->data['dEndFinish'],
          'difficulty' => $this->data['difficulty'],
        ]);
      } else if ($this->data['dEndFinish'] != $this->task->GetTaskDateRealEnd()) {
        $this->taskHistoryObj->setType('taskDateReal');

        if (!$this->task->GetTaskDateRealEnd()) {
          $this->taskHistoryObj->setFlags(['initialTimeline' => true]);
        }

        $this->taskHistoryObj->setOldValue([
          'start' => $this->task->GetTaskDateRealStart(),
          'end' => $this->task->GetTaskDateRealEnd()
        ]);
        $this->taskHistoryObj->setValue([
          'start' => $this->task->GetTaskDateRealStart(),
          'end' => $this->data['dEndFinish']
        ]);
      } else if ($this->data['difficulty'] != $this->task->GetDifficulty()) {
        $this->taskHistoryObj->setType('difficulty');
        $this->taskHistoryObj->setOldValue(['text' => $this->task->GetDifficulty()]);

        $this->taskHistoryObj->setValue(['text' => $this->data['difficulty']]);
      }
      
      $dateRealStart = $this->task->GetTaskDateRealStart() ? $this->task->GetTaskDateRealStart() : $this->task->dt2iso(new \DateTime(), true);
      $this->task->SetTaskDateRealStart($dateRealStart);
      $this->task->SetTaskDateRealEnd($this->data['dEndFinish']);
      $this->task->SetDifficulty($this->data['difficulty']);
      $this->debugLog('Timeline changed by ' . $this->getDebugLogin() . ' at ' . __FUNCTION__ . ', ' . __LINE__);

      if ($this->task->isExpiredTask()) {
        $this->container->get('notif.service')->notifAdding($this->main,
          $this->task,
          $this->user->getPortalData()->GetLogin(),
          1,
          __FUNCTION__ . ', ' . __LINE__,
          'Added urgent-1 notif to');
      } else {
        $this->container->get('notif.service')->notifRemoval($this->main,
          $this->task,
          $this->user->getPortalData()->GetLogin(),
          1,
          __FUNCTION__ . ', ' . __LINE__,
          'Removed urgent-1 notif from',
          $this->time['timeISO']);

        $this->result['debug'][] = $this->processEscalation();
      }
    }
  }

  /**
   * Change task priority(сменить приритет)
   * TASK_STATUS_CHANGE_PRIORITY code: 7
   */
  public function changePriority()
  {
    if (!isset($this->data['priority'])) {
      $this->result['error'] = 'wrong input. missing priority.';
    } else {
      $this->taskHistoryObj->setType('priority');
      $this->taskHistoryObj->setOldValue(array('weight' => $this->task->GetPriority()));
      $this->taskHistoryObj->setValue(array('weight' => $this->data['priority']));
      $this->debugLog('Task priority changed by ' . $this->getDebugLogin() . ' at ' . __FUNCTION__ . ', ' . __LINE__);
      $this->task->SetPriority($this->data['priority']);
    }
  }

  /**
   * Complete task(уведомить об исполнении)
   * TASK_STATUS_COMPLETE code: 10
   */
  public function completeTask()
  {
    if (!$this->task->GetTaskDateRealEnd()) $this->task->SetTaskDateRealEnd($this->task->dt2iso(new \DateTime(), true));
    if (!$this->task->GetTaskDateRealStart()) $this->task->SetTaskDateRealStart($this->task->dt2iso(new \DateTime(), true));
    $this->taskHistoryObj->setType('completed');
    $this->taskHistoryObj->setValue(array('checker' => $this->task->GetCheckerLat(true)));

    //notify task author that the task is completed
    $this->debugLog('Task claimed to be completed by ' . $this->getDebugLogin() . ' at ' . __FUNCTION__ . ', ' . __LINE__);
    $this->container->get('notif.service')->notifRemoval(
      $this->main,
      $this->task,
      $this->user->getPortalData()->GetLogin(),
      1,
      __FUNCTION__ . ', ' . __LINE__,
      'Removed urgent-1 notif from',
      $this->time['timeISO']
    );

    $this->result['debug'][] = $this->processEscalation();
    $this->result['debug'][] = $this->processChecker('add');
  }

  /**
   * Request pull merge(запросить накат)
   * TASK_STATUS_REQUEST_PULL code: 12
   */
  public function requestPullMerge()
  {
    if (!isset($this->data['section'])) {
      $this->result['error'] = 'wrong input. missing section.';
    } else {
      /** @var \Treto\PortalBundle\Document\SecureRepository $repo_dict */
      $repo_dict = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Dictionaries');
      /** @var Dictionaries $responsible */
      $responsible = $repo_dict->findOneBy(['type' => 'toApply', 'key' => $this->data['section']]);

      if ($responsible) {
        $this->taskHistoryObj->setType('toApply');
        $this->taskHistoryObj->setValue(array('responsible' => $responsible->getValue()));
        $this->task->SetResponsible($responsible->getValue());
        $this->debugLog('Task sent to apply by ' . $this->getDebugLogin() . ' at ' . __FUNCTION__ . ', ' . __LINE__);
        $this->main->addReadPrivilege($responsible->getValue(), $this->user->getPortalData()->GetLogin());
        $this->main->addSubscribedPrivilege($responsible->getValue(), $this->user->getPortalData()->GetLogin());

        $this->container->get('notif.service')->notifRemoval($this->main,
          $this->task,
          $this->user->getPortalData()->GetLogin(),
          1,
          __FUNCTION__ . ', ' . __LINE__,
          'Removed urgent-1 notif from',
          $this->time['timeISO']);

        $this->container->get('notif.service')->notifAdding($this->main,
          $this->task,
          $responsible->getValue(),
          1,
          __FUNCTION__ . ', ' . __LINE__,
          'Added urgent-1 notif to');

        $this->result['debug'][] = $this->processEscalation(true);
      } else {
        $this->result['error'] = 'responsible not found';
      }
    }
  }

  /**
   * Notify pull merge(уведомить о выполнении наката)
   * TASK_STATUS_NOTIFY_PULL code: 13
   */
  public function notifyPullMerge()
  {
    if (!isset($this->data['section'])) {
      $this->result['error'] = 'wrong input. missing section.';
    }

    /** @var \Treto\PortalBundle\Document\SecureRepository $repo_dict */
    $repo_dict = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Dictionaries');
    /** @var Dictionaries $responsible */
    $responsible = $repo_dict->findOneBy(['type' => 'toApply', 'key' => $this->data['section']]);

    if ($responsible && $responsible->getValue() == $this->user->getPortalData()->GetLogin()) {
      $this->taskHistoryObj->setType('toApplyCompleted');
      $this->taskHistoryObj->setValue(['responsible' => $responsible->getValue()]);
      $this->result['debug'][] = $this->processEscalation();
      $this->debugLog('Task applied by ' . $this->getDebugLogin() . ' at ' . __FUNCTION__ . ', ' . __LINE__);

      $this->container->get('notif.service')->notifRemoval($this->main,
        $this->task,
        $responsible->getValue(),
        1,
        __FUNCTION__ . ', ' . __LINE__,
        'Removed urgent-1 notif from');

      $this->container->get('notif.service')->notifAdding($this->main,
        $this->task,
        $this->performerDoc['username'],
        0,
        __FUNCTION__ . ', ' . __LINE__,
        'Added notif to',
        $this->time['timeISO']);

    } else {
      $this->result['error'] = 'responsible not found';
    }
  }

  /**
   * Send task to rework(вернуть на доработку)
   * TASK_STATUS_RETURN_REWORK code: 15
   */
  public function returnForRework()
  {
    $this->task->SetTaskDateCompleted('');
    $this->task->SetTaskDateRealEnd('');
    $this->task->SetTaskDateRealStart('');
    $this->task->SetStatus('open');

    $this->taskHistoryObj->setType('reject');
    if (isset($this->data['messagebbUnid'])) {
      $this->taskHistoryObj->setValue([
        'messagebbUnid' => $this->data['messagebbUnid'],
        'newDate' => $this->task->GetTaskDateEnd()
      ]);
    }

    //remove notif when the task is rejected
    $this->debugLog('Task rejected by ' . $this->getDebugLogin() . ' at ' . __FUNCTION__ . ', ' . __LINE__);

    $this->result['debug'][] = $this->processChecker('remove');
    $this->result['debug'][] = $this->processEscalation();

    if (isset($this->performerDoc) && isset($this->performerDoc['username']) && !isset($this->performerDoc['domain'])) {
        $this->container->get('notif.service')->notifAdding(
        $this->main,
        $this->task,
        $this->performerDoc['username'],
        1,
        __FUNCTION__ . ', ' . __LINE__,
        'Added urgent-1 notif to'
      );
    }

    if ($this->task->GetCheckerLat(true) == $this->user->getPortalData()->GetLogin()) {
      $this->task->SetCheckerLat([]);
      $this->task->SetChecker([]);
    }
  }

  /**
   * Send task for review(отправить на проверку)
   * TASK_STATUS_SEND_FOR_REVIEW code: 20, 21
   */
  public function sendForReview()
  {
    if (!isset($this->data['user']) && !isset($this->data['shareUser'])) {
      $this->result['error'] = 'wrong input. missing user.';
    } else {
      if ($this->task->getTaskStatePrevious() == self::TASK_STATUS_SEND_FOR_REVIEW_20) {
        $this->task->setTaskStateCurrent(self::TASK_STATUS_SEND_FOR_REVIEW_21);
      }

      $oldValue = ['login' => ''];
      if($this->task->GetCheckerLat(true)){
        $oldValue['login'] = $this->task->GetCheckerLat(true);
      }
      elseif($this->task->GetShareChecker()){
        $sc = $this->task->GetShareChecker();
        if(isset($sc['login']) && isset($sc['domain'])){
          $oldValue = $sc;
        }
      }

      $this->taskHistoryObj->setType('checker');
      $this->taskHistoryObj->setOldValue($oldValue);
      $taskOrig = clone $this->task;

      if(isset($this->data['user'])){
        $checker = $this->portalRepo->findEmplByLogin($this->data['user']);
        $newValue = ['login' => $this->data['user']];
        $this->task->SetCheckerLat([$checker->GetLogin()]);
        $this->task->SetShareChecker([]);
        $checkerName = $checker->GetLastName() . ' ' . $checker->GetName();

        $this->task->addReadPrivilege($checker->GetLogin(), $this->user->getPortalData()->GetLogin());
        $this->task->addSubscribedPrivilege($checker->GetLogin(), $this->user->getPortalData()->GetLogin());
        $this->main->addReadPrivilege($checker->GetLogin(), $this->user->getPortalData()->GetLogin());
        $this->main->addSubscribedPrivilege($checker->GetLogin(), $this->user->getPortalData()->GetLogin());
      }
      else {
        $newValue = $this->data['shareUser'];
        $this->task->SetCheckerLat([]);
        $this->task->SetShareChecker([$this->data['shareUser']]);
        /** @var RoboService $robo */
        $robo = $this->container->get('service.site_robojson');

        $shareChecker = $robo->getShareUser($this->data['shareUser']['domain'], $this->data['shareUser']['login']);
        if($shareChecker){
          $checkerName = $shareChecker['LastName'].' '.$shareChecker['name'];

          $this->main->addSharePrivileges($shareChecker['domain'], 'read', 'username', $shareChecker['login']);
          $this->main->addSharePrivileges($shareChecker['domain'], 'subscribed', 'username', $shareChecker['login']);
          $this->task->addSharePrivileges($shareChecker['domain'], 'read', 'username', $shareChecker['login']);
          $this->task->addSharePrivileges($shareChecker['domain'], 'subscribed', 'username', $shareChecker['login']);
        }
      }

      $this->taskHistoryObj->setValue($newValue);
      $this->task->SetChecker(isset($checkerName)?[$checkerName]:[]);

      $this->debugLog('Checker changed by ' . $this->getDebugLogin() . ' at ' . __FUNCTION__ . ', ' . __LINE__);

      $this->container->get('notif.service')->notifRemoval($this->main,
        $this->task,
        $this->user->getPortalData()->GetLogin(),
        1,
        __FUNCTION__ . ', ' . __LINE__,
        'Removed urgent-1 notif from',
        $this->time['timeISO']
      );

      if($this->task->GetCheckerLat(true)) {
        /** @var $CheckerLat Portal */
        $this->container->get('notif.service')->notifAdding($this->main,
          $this->task,
          $this->task->GetCheckerLat(true),
          1,
          __FUNCTION__ . ', ' . __LINE__,
          'Added urgent-1 notif to'
        );
      }
      if($taskOrig->GetCheckerLat(true)) {
        /** @var $CheckerLat Portal */
        $this->container->get('notif.service')->notifRemoval($this->main,
          $this->task,
          $taskOrig->GetCheckerLat(true),
          1,
          __FUNCTION__ . ', ' . __LINE__,
          'Removed urgent-1 notif from',
          $this->time['timeISO']
        );
      }
    }
  }

  /**
   * Accept task(принять выполнение)
   * TASK_STATUS_ACCEPT code: 25
   */
  public function accept()
  {
    $this->task->SetTaskDateCompleted($this->task->dt2iso(new \DateTime(), true));

    if (!$this->task->GetTaskDateRealEnd()) {
      $this->task->SetTaskDateRealEnd($this->task->dt2iso(new \DateTime(), true));
    };
    if (!$this->task->GetTaskDateRealStart()) {
      $this->task->SetTaskDateRealStart($this->task->dt2iso(new \DateTime(), true));
    };
    if (!$this->task->GetTaskDateEnd()) {
      $this->task->SetTaskDateEnd($this->task->dt2iso(new \DateTime(), true));
    };
    if (!$this->task->GetTaskDateStart()) {
      $this->task->SetTaskDateStart($this->task->dt2iso(new \DateTime(), true));
    };

    $this->taskHistoryObj->setType('status');
    $this->taskHistoryObj->setValue(array('type' => 'close'));
    $this->task->SetStatus('close');

    //remove notif when the task is accepted
    $this->debugLog('Task accepted by ' . $this->getDebugLogin() . ' at ' . __FUNCTION__ . ', ' . __LINE__);

    $this->result['debug'][] = $this->processEscalation();
    $this->result['debug'][] = $this->processChecker('remove');

    if (isset($this->performerDoc) && isset($this->performerDoc['username']) && !isset($this->performerDoc['domain'])) {
      $this->result['debug']['unurged'] = $this->container->get('notif.service')->unurgeNotif(
        $this->main->GetUnid(),
        $this->task->GetUnid(),
        $this->performerDoc['username']
      );
    }
  }

  /**
   * hanging task(подвесить)
   * TASK_STATUS_HANDING code: 30
   */
  public function hanging()
  {
    $this->task->SetWaitPerformer('1');
    $this->taskHistoryObj->setType('status');
    $this->taskHistoryObj->setValue(array('type' => 'wait'));

    //notify task author that the task is suspended
    $this->debugLog('Task dumped by ' . $this->getDebugLogin() . ' at ' . __FUNCTION__ . ', ' . __LINE__);

    if (isset($this->performerDoc) && isset($this->performerDoc['username']) && !isset($this->performerDoc['domain'])) {
      $this->container->get('notif.service')->notifRemoval($this->main,
        $this->task,
        $this->performerDoc['username'],
        1,
        __FUNCTION__ . ', ' . __LINE__,
        'Removed urgent-1 notif from',
        $this->time['timeISO']
      );
    }

    $this->task->SetTaskPerformerLat(['Просьба подвешена']);

    $this->result['debug'][] = $this->processEscalation();
    $this->result['debug'][] = $this->processChecker('remove');
  }

  /**
   * Cancel task(отменить)
   * TASK_STATUS_CANCEL code: 35
   */
  public function cancel()
  {
    $this->task->SetStatus('cancelled');
    $this->taskHistoryObj->setType('status');
    $this->taskHistoryObj->setValue(array('type' => 'cancelled'));
    $this->debugLog('Task cancelled by ' . $this->getDebugLogin() . ' at ' . __FUNCTION__ . ', ' . __LINE__);

    $this->container->get('notif.service')->notifRemoval($this->main,
      $this->task,
      $this->user->getPortalData()->GetLogin(),
      1,
      __FUNCTION__ . ', ' . __LINE__,
      'Removed urgent-1 notif from',
      $this->time['timeISO']
    );

    if (isset($this->performerDoc) && isset($this->performerDoc['username']) && !isset($this->performerDoc['domain']) &&
      $this->performerDoc['username'] != $this->user->getPortalData()->GetLogin()
    ) {
      $this->container->get('notif.service')->unurgeNotif(
        $this->main->GetUnid(),
        $this->task->GetUnid(),
        $this->performerDoc['username']
      );
    }

    $this->result['debug'][] = $this->processChecker('remove');
    $this->result['debug'][] = $this->processEscalation();
  }

  /**
   * Get task history by doc
   * @param $task
   * @return array
   */
  public function getHistories($task)
  {
    /** @var Portal $task */
    $res = [];
    /** @var $taskHistoryRepo \Treto\PortalBundle\Document\SecureRepository */
    $taskHistoryRepo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:TaskHistory');
    $or = [];
    if(is_array($task)){
      if(isset($task['_id'])){ $or[] = ['taskId' => $task['_id']]; }
      if(isset($task['unid'])){ $or[] = ['taskUnid' => $task['unid']]; }
    }
    else {
      if($task->GetId()){ $or[] = ['taskId' => $task->GetId()]; }
      if($task->GetUnid()){ $or[] = ['taskUnid' => $task->GetUnid()]; }
    }
    $histories = $taskHistoryRepo->findBy(['$or' => $or], ['created' => "ASC"]);

    foreach ($histories as $history) {
      /** @var TaskHistory $history */
      $res[] = $history->getDocument();
    }

    return $res;
  }

  /**
   * Return task checker (taking into account the dismissed employee)
   * @param $code
   * @return null|Portal
   */
  private function getChecker($code)
  {
    /** @var $task Portal */
    if ($this->task->GetCheckerLat(true)) { //Explicit checker or author
      $checker = $this->portalRepo->findEmplByNames(
        $this->task->GetCheckerLat(),
        $this->task->GetCheckerLat(),
        $this->task->GetCheckerLat()
      );
    } else {
      $checker = $this->portalRepo->findEmplByNames(
        [$this->task->GetAuthorLogin()],
        [$this->task->GetAuthor()],
        [$this->task->GetAuthorFullNotesName()]
      );
    }

    if ($checker) {
      $checker = $checker[0];
    } else {
      return null;
    }

    /** @var $checker Portal */
    if ($checker->GetDtDismiss() && strtotime($checker->GetDtDismiss()) < time()) {
      /** @var RoboService $robo */
      $robo = $this->container->get('service.site_robojson');
      $bosses = $robo->getBosses($checker);
      if ($bosses && isset($bosses[0])) {
        /** @var Portal $bossEmpl */
        $bossEmpl = $this->portalRepo->findOneBy(['form' => 'Empl', 'Login' => $bosses[0]]);
        if ($bossEmpl) {
          if ($code == self::TASK_STATUS_COMPLETE) {
            $this->task->SetCheckerLat([$bossEmpl->GetLogin()]);
            $this->getDM()->persist($this->task);
            $taskHistory = new \Treto\PortalBundle\Document\TaskHistory();
            $taskHistory->setType('const');
            $taskHistory->setValue(['text' => 'Сотрудник ' . $checker->GetFullNameInRus() . ' уволен. ' .
              $bossEmpl->GetFullNameInRus() . ' автоматически назначен проверяющим.']);
            $taskHistory->setTaskId($this->task->GetId());
            $this->getDM()->persist($taskHistory);
          }

          $checker = $bossEmpl;
        }
      }
    }

    return $checker;
  }

  /**
   * @param bool $ignoreResponsible
   * @return array
   */
  public function processEscalation($ignoreResponsible = false)
  {
    $result = [];
    $escManagers = $this->task->GetEscalationManagers();

    if ($escManagers) {
      foreach ($escManagers as $manager) {
        if ($manager['type'] == 'notify') {
          $managerDoc = $this->portalRepo->findEmplByLogin($manager['login']);

          if (is_object($managerDoc)) {
            if ($manager['login'] == $this->user->getPortalData()->GetLogin()) {
              $this->container->get('notif.service')->notifRemoval($this->main,
                $this->task,
                $managerDoc->GetLogin(),
                1,
                __FUNCTION__ . ', ' . __LINE__,
                'Removed urgent-1 notif from',
                $this->time['timeISO']
              );
            } else {
              $result['unurged ' . $managerDoc->GetLogin()] = $this->container->get('notif.service')->unurgeNotif(
                $this->main->GetUnid(),
                $this->task->GetUnid(),
                $managerDoc->GetLogin()
              );
            }
          }
        }
      }
    }

    $this->task->SetEscalationManagers([]);

    if (!$ignoreResponsible) {
      $responsible = $this->task->GetResponsible();
      if ($responsible) {
        $this->container->get('notif.service')->unurgeNotif(
          $this->main->GetUnid(),
          $this->task->GetUnid(),
          $responsible,
          __FUNCTION__ . ', ' . __LINE__
        );
        $this->task->SetResponsible(null);
      }
    }

    $this->getDM()->persist($this->task);
    $this->getDM()->flush();
    $this->getDM()->clear();

    return $result;
  }

  /**
   * @param $toAdd
   * @return array
   */
  public function processChecker($toAdd)
  {
    $result = [];

    if (isset($this->checker)) {
      $result[] = __FUNCTION__ . ': checker is ' . $this->checker->GetLogin();
      if ($toAdd === 'add') {
        $this->container->get('notif.service')->notifAdding(
          $this->main,
          $this->task,
          $this->checker->GetLogin(),
          1,
          __FUNCTION__ . ', ' . __LINE__,
          'Added urgent-1 notif to'
        );
      } else {
        if ($this->container->get('notif.service')->hasNotif($this->main->GetUnid(), $this->checker->GetLogin())) {
          $this->container->get('notif.service')->notifRemoval($this->main,
            $this->task,
            $this->checker->GetLogin(),
            1,
            __FUNCTION__ . ', ' . __LINE__,
            'Removed urgent-1 notif from',
            $this->time['timeISO']
          );
        }
      }

      $this->getDM()->persist($this->checker);
      $this->getDM()->flush();
      $this->getDM()->clear();
    }

    return $result;
  }

  private function getDM()
  {
    return $this->container->get('doctrine.odm.mongodb.document_manager');
  }

  private function debugLog($log)
  {
    $logger = $this->container->get('monolog.logger.notif_logger');
    $logger->debug($log, [
      $this->task->GetUnid(),
      $this->task->GetSubject(),
      $this->main->GetUnid(),
      $this->main->GetSubject()
    ]);
  }

  private function getDebugLogin(){
    return $this->shareUser?$this->shareUser['domain'].' ('.$this->shareUser['username'].')':$this->user->getPortalData()->GetLogin();
  }
}
