<?php
namespace Treto\PortalBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Treto\PortalBundle\Document\Dictionaries;
use \Treto\PortalBundle\Document\Portal;
use \Treto\PortalBundle\Document\Contacts;
use Treto\PortalBundle\Document\PortalSettings;
use Treto\PortalBundle\Document\TaskHistory;
use Treto\UserBundle\Document\User;
use Treto\PortalBundle\Document\HistoryLog;

abstract class RoboService
{
  private $container;
  const UPDATE_FROM_1C = 1;
  const UPDATE_FROM_SITE = 2;

  public function __construct(ContainerInterface $container){
    $this->container = $container;
  }

  /**
   * Find mentions in document
   * @param $doc
   * @param $host
   * @return array
   */
  public function checkMentions($doc, $host){
    if (!is_array($doc)) return [];
    $body = $doc['body'];
    $result = [];

    if(preg_match_all("#\<span id\=\"mention_(.*)\".*\<\/span\>#Ui", $body, $matches, PREG_SET_ORDER)){
      foreach ($matches as $match) {
        if(isset($match[0]) && isset($match[1])){
          if(preg_match('#data-domain\=\"(.*)\"#Ui', $match[0], $matchesDomain) &&
              isset($matchesDomain[1]) && $host != $matchesDomain[1]){
            continue;
          }

          $result[] = $match[1];
        }

      }
    }

    return $result;
  }

  /**
   * @param $parent
   * @param $doc
   * @param $mentions
   * @return array
   */
  public function notifyMentioned($parent, $doc, $mentions){
    if(sizeof($mentions)==0){return ['No mentions'];}

    $result = ['mentioned' => 0];
    $portalRepo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
    $mentioned = $portalRepo->findBy(['form' => 'Empl', 'Login' => ['$in' => $mentions]]);
    if(sizeof($mentioned)==0){return ['No mentioned users found'];}

    foreach ($mentioned as $user) {
      $mentionObj = new \Treto\PortalBundle\Document\Mention(
          $parent->GetUnid(),
          $doc->GetUnid(),
          $doc->GetAuthorLogin(),
          $user->GetLogin()
      );
      $this->getDM()->persist($mentionObj);

      $parent->addReadPrivilege($user->GetLogin(), '_mention');
      $parent->addSubscribedPrivilege($user->GetLogin(), '_mention');
      $this->container->get('notif.service')->notifAdding(
          $parent,
          $doc,
          $user->GetLogin(),
          1,
          __FUNCTION__.', '.__LINE__,
          'Added mention to');
      $result['mentioned']++;
      $this->getDM()->flush();
    }

    $this->getDM()->clear();

    return $result;
  }

  public function prepareShareTask($doc, $host){
     $shareTempData = false;
      if($doc['taskPerformerLat'][0] == 'shareTask' && $doc['sharePerformers'][0]['domain'] == $host){
          $doc['taskPerformerLat'] = [$doc['sharePerformers'][0]['login']];
          unset($doc['sharePerformers'][0]);
          if(!$doc['sharePerformers']){
            unset($doc['sharePerformers']);
          }
      }
      elseif($doc['taskPerformerLat'][0] != 'shareTask') {
          $doc['sharePerformers'] = [['domain' => $doc['sendShareFrom'], 'login' => $doc['taskPerformerLat'][0]]];
          $doc['taskPerformerLat'] = ['shareTask'];
      }

      if(isset($doc['shareType']) && isset($doc['shareTempData'])){
        $shareTempData = json_decode($doc['shareTempData'], true);
      }

      //Converting share performer from other portals
      $changePerformerStatus = [TaskService::TASK_STATUS_CHANGE_PERFORMER_3, TaskService::TASK_STATUS_CHANGE_PERFORMER_4];
      if(isset($shareTempData['code']) && in_array($shareTempData['code'], $changePerformerStatus)){
        if(isset($shareTempData['data']['sharePerformer']) && $shareTempData['data']['sharePerformer']){
          $peformLogin = reset($shareTempData['data']['sharePerformer']);
          $domain = key($shareTempData['data']['sharePerformer']);
          if($host == trim($domain)){
            $shareTempData['data']['performer'] = isset($peformLogin[0])?$peformLogin[0]:$peformLogin;
            $shareTempData['data']['sharePerformer'] = [];
          }
        }
        elseif(isset($shareTempData['data']['performer']) && $shareTempData['data']['performer']){
          $perf = is_array($shareTempData['data']['performer'])&&isset($shareTempData['data']['performer'][0])?$shareTempData['data']['performer'][0]:$shareTempData['data']['performer'];
          $shareTempData['data']['sharePerformer'] = [$doc['sendShareFrom'] => [$perf]];
          unset($shareTempData['data']['performer']);
        }
      }

      if(isset($doc['CheckerLat']) && isset($doc['CheckerLat'][0])){
        $doc['shareChecker'] = [['domain' => $doc['sendShareFrom'], 'login' => $doc['CheckerLat']]];
        unset($doc['CheckerLat']);
      }

      if(isset($doc['shareChecker']) && isset($doc['shareChecker'][0]) && $doc['shareChecker'][0]['domain'] == $host){
        $doc['CheckerLat'] = [$doc['shareChecker'][0]['login']];
        unset($doc['shareChecker']);
      }

      if(isset($doc['EscalationManagers'])){
        unset($doc['EscalationManagers']);
      }

      //Converting share checker from other portals
      $checkerStatus = [TaskService::TASK_STATUS_SEND_FOR_REVIEW_20, TaskService::TASK_STATUS_SEND_FOR_REVIEW_21];
      if(isset($shareTempData['code']) && in_array($shareTempData['code'], $checkerStatus)){
        if(isset($shareTempData['data']['shareUser']) && $shareTempData['data']['shareUser']){
          $shareChecker = $shareTempData['data']['shareUser']['login'];
          $domain = $shareTempData['data']['shareUser']['domain'];
          if($host == trim($domain)){
            $shareTempData['data']['user'] = $shareChecker;
            unset($shareTempData['data']['shareUser']);
          }
        }
        elseif(isset($shareTempData['data']['user']) && $shareTempData['data']['user']) {
          $shareChecker = $shareTempData['data']['user'];
          unset($shareTempData['data']['user']);
          $shareTempData['data']['shareUser'] = ['login' => $shareChecker, 'domain' => $doc['sendShareFrom']];
        }
      }

      $doc['shareTempData'] = $shareTempData;

      return $doc;
  }

    /**
   * Prepare theme params
   * @param $params
   * @param bool $host
   * @return mixed
   */
  public function prepareThemeParams($params, $host = false){
    $splitChar = '|';
    if(isset($params['category'])){
      $params['category'] = trim($params['category'], $splitChar);
      if(strpos($params['category'], $splitChar)){
        $params['category'] = explode($splitChar, $params['category']);
        foreach ($params['category'] as $key => $cat) {
          $params['C'.($key+1)] = trim($cat);
        }
      }
      else {
        $params['C1'] = $params['category'];
      }
      unset($params['category']);
    }

    if(isset($params['subjectID']) && isset($params['unid']) && $params['subjectID'] == $params['unid']){
      unset($params['subjectID']);
    }
    if(isset($params['parentID']) && isset($params['unid']) && $params['parentID'] == $params['unid']){
      unset($params['parentID']);
    }

    if(isset($params['SelectRegion'])){
      $params['SelectRegion'] = trim($params['SelectRegion'], $splitChar);
      $params['SelectRegion'] = explode($splitChar, $params['SelectRegion']);
    }

    $host =  str_replace(['http://', 'https://', ' '], '', $host);

    if($params['form'] == 'formTask' && isset($params['sendShareFrom']) && $host){
      $params = $this->prepareShareTask($params, $host);
    }

    if($params['form'] == 'messagebb' && $params['action'] == 'check' && $host){
      if(isset($params['CheckerLat']) && isset($params['CheckerLat'][0])){
        $params['shareChecker'] = [['domain' => $params['sendShareFrom'], 'login' => $params['CheckerLat']]];
        unset($params['CheckerLat']);
      }

      if(isset($params['shareChecker']) && isset($params['shareChecker'][0]) && $params['shareChecker'][0]['domain'] == $host){
        $params['CheckerLat'] = [$params['shareChecker'][0]['login']];
        unset($params['shareChecker']);
      }
    }

    if($host && isset($params['shareSecurity']) && $params['shareSecurity'] && is_array($params['shareSecurity'])){
      foreach($params['shareSecurity'] as $shortDomain => $shareSecurity){
        $domain = str_replace(['http://', 'https://', ' '], '', $shareSecurity['domain']);

        if(isset($shareSecurity['domain']) && $domain == $host){
          $params['security'] = $shareSecurity;
          unset($params['shareSecurity'][$shortDomain]);
        }
      }
    }

    return $params;
  }

  /**
   * Update portal doc
   * @param $params
   * @param $type
   * @param $host
   * @return array
   */
  public function updatePortalDoc($params, $type, $host){
    $result = ['error' => true];

    if(isset($params['unid']) && $params['unid']){
      $dontNotify = false;

      if(isset($params['dontNotify'])){
        $dontNotify = true;
        unset($params['dontNotify']);
      }

      $portal = $this->getRepo('Portal');
      /** @var Portal $docInBase */
      $docInBase = $portal->findOneBy(['unid' => $params['unid']]);
      if($docInBase){
        if($params['form'] == 'formTask' &&
          isset($params['shareType']) &&
          $params['shareType'] == 'taskService' &&
          isset($params['shareTempData'])){
          $std = $params['shareTempData'];
          $user = isset($std['domain'])&&isset($std['username'])?['domain' => $std['domain'], 'username' => $std['username']]:User::ROBOT_PORTAL;
          $result = $this->container->get('task.service')->task($params['unid'], $std['code'], $std['data'], $user, $host, true);
        }
        else {
          if(in_array($docInBase->GetForm(), $type)){
            $newValues = [];

            if(isset($params['Participants']) && $type == 'formProcess') {
              $docInBase = $this->addParticipants($docInBase, $params['Participants']);
            }
            if(isset($params['removeParticipants']) && $type == 'formProcess'){
              $docInBase = $this->removeParticipants($docInBase, $params['removeParticipants']);
            }
            if(isset($params['body'])){
              $newValues['body'] = $params['body'];
            }
            if(isset($params['subject'])){
              $newValues['subject'] = $params['subject'];
            }
            if(isset($params['locale'])){
              $newValues['locale'] = $params['locale'];
            }
            if(isset($params['subjectID'])){
              $newValues['subjectID'] = $params['subjectID'];
              $newValues['parentID'] = $params['subjectID'];
            }
            if(isset($params['taskPerformerLat'])){
              $newValues['taskPerformerLat'] = $params['taskPerformerLat'];
            }
            if(isset($params['sharePerformers'])){
              $newValues['sharePerformers'] = $params['sharePerformers'];
            }
            if(isset($params['shareSecurity'])){
              $newValues['shareSecurity'] = $params['shareSecurity'];
              $currentSecurity = $docInBase->getSecurity();
              $currentSecurity['privileges'] = $params['security']['privileges'];
              $newValues['security'] = $currentSecurity;
            }

            $docInBase->setDocument($newValues);
            $docInBase->SetModified();
            $docInBase->SetDateModified($docInBase->GetModified());

            if($docInBase->GetSubjectID()) {
              $main = $this->GetMainDocFor($docInBase);
              if(!$main) {
                $errors[] = 'parent document not found or access denied';
              }

              $unrDoc = $main;
            } else {
              $unrDoc = $docInBase;
            }

            if(!$dontNotify){
              $this->addUnreadedToAllUsers($unrDoc, $docInBase, true);
            }

            $this->getDM()->persist($docInBase);
            $this->getDM()->flush();
            $result = ['error' => false];
          }
          else {
            $result['error'] = 'Wrong type, document form must be "'.$type.'".';
          }
        }
      }
      else {
        $result['error'] = 'No documents found by "unid".';
      }
    }
    else {
      $result['error'] = 'Required field "unid" is missing.';
    }

    return $result;
  }

  /**
   * Create new document
   * @param $params
   * @param bool $fromSite
   * @param bool $host
   * @return mixed
   */
  public function setTheme($params, $fromSite = false, $host = false) {
    if(!$params) { return false; }

    if(isset($params['unid']) && $this->getRepo('Portal')->findOneBy(['unid' => $params['unid']])){
      return $params['unid'];
    }

    $dontNotify = isset($params['document']['dontNotify']);
    unset($params['document']['dontNotify']);
    $doc = $params['document'];
    $docInBase = null;
    $errors = [];

    if(isset($doc['Author']) && $doc['Author']) {
      $objUser = $this->getUserObj($doc['Author']);
    }
    else {
      $doc['AuthorLogin'] = isset($doc['AuthorLogin'])&&$doc['AuthorLogin']?$doc['AuthorLogin']:\Treto\UserBundle\Document\User::ROBOT_PORTAL;
      $objUser = $this->getUserByLogin($doc['AuthorLogin']);
    }

    if(!$objUser) {
      $errors[] = 'user not found';
    }

    /** @var Portal $docInBase */
    $docInBase = new Portal($objUser);
    $errors[] = $docInBase->setDocument($doc, $this->container->get('treto.validator'), $objUser->getRoles());

    if(!$this->isUnid($docInBase->GetUnid())) {
      $docInBase->SetUnid();
    }
    $authorRus = isset($doc['AuthorRus'])&&$doc['AuthorRus']?$doc['AuthorRus']:$objUser->getPortalData()->GetFullNameInRus();
    if(isset($doc['sendShareFrom']) && $doc['sendShareFrom']){
      $authorRus .= ' ('.$doc['sendShareFrom'].')';
    }
    else {
      if(array_key_exists('AccessType', $doc) && $doc['AccessType'] == '1') {
        $docInBase->setDefaultWriteSecurity($objUser);
      } else {
        $docInBase->setDefaultSecurity($objUser);
      }
    }

    $docInBase->setAuthorRus($authorRus);
    $docInBase->SetAuthor($objUser->getPortalData()->GetFullName(false));
    $docInBase->SetAuthorLogin($objUser->getPortalData()->GetLogin());
    if (!isset($doc['form'])) {
      $doc['form'] = 'formProcess';
    }
    $docInBase->SetForm($doc['form']);

    if(isset($doc['Participants'])) {
      $docInBase = $this->addParticipants($docInBase, $doc['Participants'], '|', $fromSite);
    }

    if($docInBase->GetSubjectID()) {
      $main = $this->GetMainDocFor($docInBase);
      if(!$main) {
        $errors[] = 'parent document not found or access denied';
      }

      $main->IncrementCountMess($objUser);

      if($docInBase->GetShareAuthorLogin() && $docInBase->GetSendShareFrom() && in_array($docInBase->GetForm(), SynchService::$enableShareType) ){
        /** @var $main Portal */
        $main->addSharePrivileges($docInBase->GetSendShareFrom(), 'read', 'username', $docInBase->GetShareAuthorLogin());
        $main->addSharePrivileges($docInBase->GetSendShareFrom(), 'subscribed', 'username', $docInBase->GetShareAuthorLogin());
      }
      else {
        $main->addReadPrivilege($objUser->getPortalData()->GetLogin(), '_roboservice');
        $main->addSubscribedPrivilege($objUser->getPortalData()->GetLogin(), '_roboservice');
      }

      $this->getDM()->persist($main);
      $unrDoc = $main;
    } else {
      $unrDoc = $docInBase;
    }

    if(!$dontNotify){
      $this->addUnreadedToAllUsers($unrDoc, $docInBase, true);
      
      if($host){
        $this->notifyMentioned($unrDoc, $docInBase, $this->checkMentions($docInBase->getDocument(), $host));
      }
    }

    if($docInBase->GetForm() == 'formTask'){
      if(isset($doc['taskPerformerLat'][0]) && $doc['taskPerformerLat'][0] != 'shareTask'){
        $notifLogin = $doc['taskPerformerLat'][0];
      }
      elseif(isset($doc['CheckerLat']) && isset($doc['CheckerLat'][0])){
        $notifLogin = $doc['CheckerLat'][0];
      }

      if(isset($notifLogin)){
        $this->container->get('notif.service')->notifAdding(
          isset($main)&&$main?$main:$docInBase,
          $docInBase,
          $notifLogin,
          1,
          __FUNCTION__.', '.__LINE__,
          'Added urgent-1 notif to');
      }

      if(isset($doc['taskHistory']) && $host && isset($doc['sendShareFrom'])){
        $this->setTaskHistory($doc['taskHistory'], $host, $doc['sendShareFrom']);
      }
    }

    $this->getDM()->persist($docInBase);
    $this->getDM()->flush();

    if(isset($main)){
      $this->container->get('node.service')->addComent($main->getUnid(), $docInBase->getDocument());
    }

    return $docInBase->GetUnid();
  }

  /**
   * Create history from share task
   * @param $taskHistory
   * @param $host
   * @param $shareFrom
   */
  public function setTaskHistory($taskHistory, $host, $shareFrom){
    /** @var $taskHistoryRepo \Treto\PortalBundle\Document\SecureRepository */
    $taskHistoryRepo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:TaskHistory');

    if($taskHistory && is_array($taskHistory)){
      foreach ($taskHistory as $history) {
        if(isset($history['id'])){
          unset($history['id']);
        }

        if(isset($history['unid'])){
          $existHistory = $taskHistoryRepo->findOneBy(['unid' => $history['unid']]);
          if($existHistory){
            continue;
          }
        }

        if(!isset($history['domain'])){
          $history['domain'] = $shareFrom;
        }

        if(isset($history['oldValue'])){
          $history['oldValue'] = $this->convertHistoryValue($history['oldValue'], $host, $shareFrom);
        }
        if(isset($history['value'])){
          $history['value'] = $this->convertHistoryValue($history['value'], $host, $shareFrom);
        }
        if(isset($history['security'])){
          unset($history['security']);
        }

        /** @var TaskHistory $newHistory */
        $newHistory = new TaskHistory();
        $newHistory->setDocument($history);
        $newHistory->setDefaultSecurity();
        $this->getDM()->persist($newHistory);
      }
      $this->getDM()->flush();
    }
  }

  private function convertHistoryValue($value, $host, $shareFrom){
    if((isset($value['login']) && $value['login']) || isset($value['performer'])){
      if(!isset($value['domain']) || !$value['domain']){
        $value['domain'] = $shareFrom;
      }
      elseif($value['domain'] == $host){
        unset($value['domain']);
      }
    }

    return $value;
  }

  /**
   * Get share user by login and domain
   * @param $domain
   * @param $login
   * @return array
   */
  public function getShareUser($domain, $login){
    $result = [];
    $portalSettings = $this->getRepo('PortalSettings');
    /** @var PortalSettings $settings */
    $settings = $portalSettings->findOneBy(['type' => 'sharePortal', 'domain' => trim($domain)]);
    if($settings){
      $users = $settings->getUsers();
      foreach ($users as $key => $user) {
        if(isset($user['username']) && $user['username'] == $login){
          $result = $users[$key];
          $result['login'] = $login;
          $result['domain'] = $domain;
          break;
        }
      }
    }

    return $result;
  }

    /**
     * Add participants to doc
     * @param $docInBase
     * @param $participantsList
     * @param string $delimiter
     * @param bool $fromSite
     * @return Portal
     */
  private function addParticipants($docInBase, $participantsList, $delimiter = '|', $fromSite = false){
    if(is_string($participantsList)){
      /** @var $docInBase Portal */
        $participantsList = explode($delimiter, $participantsList);
    }

    if($participantsList) {
      foreach ($participantsList as $val) {
          if(!$fromSite){
              /** @var Portal $empl */
              $empl = $this->getRepo('Portal')->findOneBy(['contactUnid' => $val, 'form' => 'Empl']);
              $login = $empl?$empl->GetLogin():'';
          }
          else {
              $login = $val;
          }

        if($login){
          $docInBase->addReadPrivilege($login, '_roboservice');
          $docInBase->addSubscribedPrivilege($login, '_roboservice');
        }
      }
    }

    return $docInBase;
  }

  /**
   * Remove participants from doc
   * @param $docInBase
   * @param $participantsList
   * @param string $delimiter
   * @return Portal
   */
  private function removeParticipants($docInBase, $participantsList, $delimiter = '|'){
    /** @var $docInBase Portal */
    $arrPart = explode($delimiter, $participantsList);
    if(count($arrPart)) {
      foreach ($arrPart as $id) {
        $objPrivUser = $this->getUserByLogin($id);
        if($objPrivUser){
          $docInBase->removeActionPrivilege('subscribed', 'username', $objPrivUser->getPortalData()->GetLogin());
        }
      }
    }
    return $docInBase;
  }

  /**
   * Create task
   * Example params:
   * Author: "ikonovalov"
   * AuthorRus: "Коновалов Илья Михайлович"
   * C1: "Общекорпоративные"
   * Difficulty: "1 Легко"
   * body: "123"
   * form: "formTask"
   * security: {privileges: {read: [{role: "all"}, {username: "ikonovalov"}, {username: "gpestov"}],…}}
   * status: "open"
   * subject: "123"
   * taskPerformerLat: "gpestov"
   * Participants : B1FEF7C4001395FFDSF7C32577FF0072988A | 54f0651ef1DGSDG569990198b48ec
   * @param $params
   * @return mixed
   */
  public function setTask($params){
    if(!$params || !count($params)){
      return false;
    }

    $doc = isset($params['document'])?$params['document']:$params;
    $docInBase = null;
    $errors = [];
    if(!isset($doc['Author']) || empty($doc['Author'])){
      $errors[] = 'user not found';
    }
    if (isset($doc['subjectID'])){
      $doc['parentID'] = $doc['subjectID'];
    }
    if(!isset($doc['TaskStateCurrent'])){
      $doc['TaskStateCurrent'] = 0;
    }

    $repoContact = $this->getRepo('Contacts');
    $repoPortal = $this->getRepo('Portal');
    $objUser = false;

    if (isset($doc['Author'])){
        $objUser = $this->getUserObj($doc['Author']);
    }

    if(!$objUser){
      $objUser = $this->getRobot();
    }

    $docInBase = new Portal($objUser);

    $errors[] = $docInBase->setDocument($doc, $this->container->get('treto.validator'), $objUser->getRoles());

    if(! $this->isUnid($docInBase->GetUnid())) {
      $docInBase->SetUnid();
    }

    if(array_key_exists('AccessType', $doc) && $doc['AccessType'] == '1'){
      $docInBase->setDefaultWriteSecurity($objUser);
    }
    else{
      $docInBase->setDefaultSecurity($objUser);
    }

    if(isset($doc['readSecurity']) && is_array($doc['readSecurity'])){
      foreach ($doc['readSecurity'] as $securityLogin) {
        $docInBase->addReadPrivilege($securityLogin, '_roboservice');
        $docInBase->addSubscribedPrivilege($securityLogin, '_roboservice');
      }
    }
  /** @var $docInBase Portal */
    $docInBase->setAuthor($objUser->getPortalData()->GetFullName(false));
    $docInBase->setAuthorRus($objUser->getPortalData()->GetFullNameInRus());
    $docInBase->setAuthorLogin($objUser->getPortalData()->GetLogin());
    $docInBase->SetForm('formTask');
    $docInBase->SetDifficulty('1 Легко');
    $docInBase->SetStatus('open');
    $docInBase->SetTaskDateStart(date('Ymd'));

    $doc['taskPerformerLat'] = is_array($doc['taskPerformerLat'])?$doc['taskPerformerLat']:[$doc['taskPerformerLat']];
    $params = [];
    if(isset($doc['taskPerformerLatType']) && $doc['taskPerformerLatType'] == 'logins'){
      foreach ($doc['taskPerformerLat'] as $login) {
        $params[] = ['form' => 'Empl', 'Login' => $login];
      }
    }
    else {
      $perfs = $repoContact->findBy(['unid' => ['$in' => $doc['taskPerformerLat']]]);
      foreach ($perfs as $perf) {
        $params[] = [
            'name' => $perf->getFirstName(),
            'LastName' => $perf->getLastName(),
            'MiddleName' => $perf->getMiddleName(),
            'form' => 'Empl'
        ];
      }
    }

    $perfLogins = [];
    $perfObjs = [];
    foreach ($params as $param) {
      $objPortal = $repoPortal->findOneBy($param);
      if ($objPortal){
        //If empl is dismissed
        if($objPortal->GetDtDismiss() && strtotime($objPortal->GetDtDismiss()) < time()){
          $bossLogins = $this->getBosses($objPortal);
          if($bossLogins && isset($bossLogins[0])){
            $objPortal = $repoPortal->findOneBy([
                'Login' => $bossLogins[0],
                'form' => 'Empl'
            ]);
          }
        }

        if($objPortal){
          $perfLogins[] = $objPortal->GetLogin();
          $perfObjs[] = $objPortal;
          $docInBase->addReadPrivilege($objPortal->GetLogin(), '_roboservice');
          $docInBase->addSubscribedPrivilege($objPortal->GetLogin(), '_roboservice');
        }
      }
    }

    $docInBase->SetTaskPerformerLat($perfLogins);

    if(array_key_exists('Participants', $doc)){
		$doc["Participants"] = !is_array($doc["Participants"])?$doc["Participants"]:implode('|', $doc["Participants"]);
		$doc["Participants"] = $doc['taskPerformerLat'][0]."|".$doc["Participants"];
	}else{
		$doc["Participants"] = $doc['taskPerformerLat'][0];
	}
	if(!empty($doc['Author'])){
		$doc["Participants"] = $doc['Author']."|".$doc["Participants"];
	}
	$arrPart = explode('|', $doc['Participants']);

	if(count($arrPart)){
    if(isset($doc['participantsType']) && $doc['participantsType'] == 'logins'){
      foreach ($arrPart as $login) {
        $docInBase->addReadPrivilege($login, '_roboservice');
        $docInBase->addSubscribedPrivilege($login, '_roboservice');
      }
    }
    else {
      $usersArr = $repoContact->findBy(['unid' => ['$in' => $arrPart]]);

      foreach ($usersArr as $u){
        $objPortal = $repoPortal->findOneBy([
          '$or' => [
            ['unid' => $u->GetPortalUser_ID()],
            ['FullName' => $u->getUserNotesName()],
            [
              'name' => $u->GetFirstName(),
              'LastName' => $u->GetLastName(),
              'MiddleName' => $u->GetMiddleName(),
            ]
          ],
          'form' => 'Empl'
        ]);
        if ($objPortal){
          $docInBase->addReadPrivilege($objPortal->GetLogin(), '_roboservice');
          $docInBase->addSubscribedPrivilege($objPortal->GetLogin(), '_roboservice');
        }
      }
    }
	}

    if($docInBase->getParentUnID() || ($docInBase->GetSubjectID() && ($docInBase->GetSubjectID() != $docInBase->GetUnid()))){
      $main = $this->getMainDocFor($docInBase);
      if(!$main) {
        $errors[] = 'parent document not found or access denied';
      }

      $main->IncrementCountMess($objUser);
      $main->addReadPrivilege($objUser->getPortalData()->GetLogin(), '_roboservice');
      $main->addSubscribedPrivilege($objUser->getPortalData()->GetLogin(), '_roboservice');
      
      if($tpl = $docInBase->GetTaskPerformerLat(true)) {
        $main->addReadPrivilege($tpl, '_roboservice');
        $main->addSubscribedPrivilege($tpl, '_roboservice');
      }
      $this->getDM()->persist($main);
      $this->addUnreadedToAllUsers($main, $docInBase, false);
    }
    else {
      $this->addUnreadedToAllUsers($docInBase, $docInBase, false);
    }

    if(!isset($main) || !$main) $main = $docInBase;

    foreach ($perfObjs as $prf){
      $this->container->get('notif.service')->notifAdding($main,
                                                          $docInBase,
                                                          $prf->GetLogin(),
                                                          1,
                                                          __FUNCTION__.', '.__LINE__,
                                                          'Added urgent-1 notif to');
    }

    $this->getDM()->persist($docInBase);
    $this->getDM()->flush();

    return $docInBase->GetUnid();
  }

  public function isUnid($id) {
    return $id && (strlen($id) >= 32);
  }

    /**
     * Get user object by contact unid
     * @param $contactUnid
     * @return User
     */
  private function getUserObj($contactUnid){
    /** @var $empl \Treto\PortalBundle\Document\Portal */
    $empl = $this->getRepo('Portal')->findOneBy([
        'contactUnid' => $contactUnid,
        'form' => 'Empl'
    ]);

    if($empl) {
      $objUser = $this->getUserByLogin($empl->GetLogin());
    }

    return isset($objUser) && $objUser?$objUser:false;
  }

  /**
   * Get user object by login
   * @param $login
   * @return \FOS\UserBundle\Model\UserInterface
   */
  private function getUserByLogin($login){
    $userManager = $this->getUM();
    $user = $userManager->findUserBy(['username' => $login]);
    return $user;
  }

    /**
     * $contactArr = [
     * LastName - Фамилия
     * FirstName - Имя
     * ContactStatus:
     * 6(Соискатель),
     * 7(Покупатель),
     * 9(Поставщик услуг),
     * 11(Поставщик товара),
     * 14(Сотрудник),
     * 15(Конкурент),
     * 16(Жаба)
     * ]
     * @param $contactArr
     * @param bool $objUser
     * @return bool|object|Contacts
     */
  public function createContact($contactArr, $objUser = false){
    $docInBase = false;
    if (!$objUser) {
      $objUser = $this->getRobot();
    }

    if (isset($contactArr['EmailValues'])){
      $repoContact = $this->getRepo('Contacts');
      $docInBase = $repoContact->findOneBy(["EmailValues" => ['$in' => $contactArr['EmailValues']]]);
    }

    if((isset($contactArr['AddressZipCode_ForDelivery']) ||
        isset($contactArr['AddressCityName_ForDelivery']) ||
        isset($contactArr['AddressStreetName_ForDelivery']) ||
        isset($contactArr['AddressHouseNumber_ForDelivery'])) &&
        !isset($contactArr['DeliveryAddressIsDiff'])){
        $contactArr['DeliveryAddressIsDiff'] = '1';
    }

    if(isset($contactArr['DocumentType']) && $contactArr['DocumentType'] == 'Person' &&
        (!isset($contactArr['Sex']) || !$contactArr['Sex'])){
        $contactArr['Sex'] = '0';
    }

    if (!$docInBase){
      $docInBase = new Contacts($objUser);
      $docInBase->setAuthor([$objUser->getPortalData()->GetFullName(false)]);
      $docInBase->setAuthorRus($objUser->getPortalData()->GetFullNameInRus());
      $docInBase->setForm("Contact");
      $docInBase->setAccessOption('3');
      $docInBase->setDocument($contactArr);
      $this->getDM()->persist($docInBase);
      $this->getDM()->flush();
    }
    return $docInBase;
  }

  /**
   * @param $organizationContact
   * @return bool
   */
  public function changePersonOrganizationName($organizationContact){
    /** @var  Contacts $organizationContact */
    $change = false;
    $contactsRepo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
    $persons = $contactsRepo->findBy(['DocumentType' => 'Person', 'OrganizationID' => $organizationContact->GetUnid()]);
    if($persons){
      foreach($persons as $person){
        /** @var $person Contacts */
        $organizationId = $person->GetOrganizationID();
        if(is_array($organizationId)){
          $key = array_search($organizationContact->GetUnid(), $organizationId);
          $organizationName = $person->GetOrganization();
          if(isset($organizationName[$key])){
            $organizationName[$key] = $organizationContact->GetContactName();
            $person->SetOrganization($organizationName);
            $this->getDM()->persist($person);
            $change = true;
          }
        }
      }

      if($change){
        $this->getDM()->flush();
      }
    }

    return $change;
  }

    /**
     * $contactArr = [
     * LastName - Фамилия
     * FirstName - Имя
     * ContactStatus:
     * 6(Соискатель),
     * 7(Покупатель),
     * 9(Поставщик услуг),
     * 11(Поставщик товара),
     * 14(Сотрудник),
     * 15(Конкурент),
     * 16(Жаба)
     * ]
     * @param $params
     * @param bool $updateFrom
     * @return bool|Contacts
     */
  public function updateContact($params, $updateFrom = false){
    if(!$params || !count($params)){
      return false;
    }
    $contactArr = $params['document'];
    if($updateFrom && $updateFrom == self::UPDATE_FROM_1C){
      $contactArr = $this->prepare1cUpdate($contactArr);
    }
    $docInBase = false;

    if(isset($contactArr['unid'])){
      $repoContact = $this->getRepo('Contacts');
      /** @var $docInBase \Treto\PortalBundle\Document\Contacts */
      $docInBase = $repoContact->findOneBy(["unid"=>$contactArr['unid']]);

      if ($docInBase){
        /** From the site you can not change an employee contact (ContactStatus == 14) */
        if($updateFrom && $updateFrom == self::UPDATE_FROM_SITE){
          $contactStatus = $docInBase->GetContactStatus();
          $contactStatus = is_array($contactStatus)?$contactStatus:[$contactStatus];
          if(in_array(14, $contactStatus)){
            return false;
          }
        }

        if(isset($contactArr['ContactStatus'])){
            $currentStatus = $docInBase->GetContactStatus();
            $contactArr['ContactStatus'] = is_array($contactArr['ContactStatus'])?$contactArr['ContactStatus']:[$contactArr['ContactStatus']];
            foreach ($contactArr['ContactStatus'] as $status) {
                if(!in_array($status, $currentStatus)){
                    $currentStatus[] = $status;
                }
            }
            $contactArr['ContactStatus'] = $currentStatus;
        }

        if($updateFrom && in_array($updateFrom, [self::UPDATE_FROM_1C, self::UPDATE_FROM_SITE])){
          $contactArr['FirstName'] = isset($contactArr['FirstName'])?$contactArr['FirstName']:$docInBase->GetFirstName();
          $contactArr['MiddleName'] = isset($contactArr['MiddleName'])?$contactArr['MiddleName']:$docInBase->GetMiddleName();
          $contactArr['LastName'] = isset($contactArr['LastName'])?$contactArr['LastName']:$docInBase->GetLastName();
          $contactArr = \Treto\PortalBundle\Document\Contacts::editContactName($contactArr, $docInBase->GetDocumentType());
        }

        if(!in_array($updateFrom, [self::UPDATE_FROM_SITE, self::UPDATE_FROM_1C]) || !$docInBase->GetBanApi()){
            $docInBase->setDocument($contactArr);
            $docInBase->SetModified();
            $this->getDM()->persist($docInBase);
            $this->getDM()->flush();
        }
      }
    }

    return $docInBase;
  }

  /**
   * Prepare 1C update
   * @param $document
   * @return mixed
   */
  private function prepare1cUpdate($document){
    $document['unid'] = $document['Author'];
    unset($document['Author']);
    unset($document['Action']);
    unset($document['Type']);

    if(isset($document['EmailValues']) && !is_array($document['EmailValues'])){
       $document['EmailValues'] = $document['EmailValues']?[$document['EmailValues']]:[];
    }
    if(isset($document['PhoneValues']) && !is_array($document['PhoneValues'])){
      $document['PhoneValues'] = $document['PhoneValues']?[$document['PhoneValues']]:[];
    }
    if(isset($document['PhoneCellValues']) && !is_array($document['PhoneCellValues'])){
      $document['PhoneCellValues'] = $document['PhoneCellValues']?[$document['PhoneCellValues']]:[];
    }
    if(isset($document['DeliveryAddressIsDiff'])){
      $document['DeliveryAddressIsDiff'] = $document['DeliveryAddressIsDiff'] == '1' || $document['DeliveryAddressIsDiff'] == 'true';
    }
    if(isset($document['LegalAddressIsDiff'])){
      $document['LegalAddressIsDiff'] = $document['LegalAddressIsDiff'] == '1' || $document['LegalAddressIsDiff'] == 'true';
    }

    return $document;
  }

  /**
   * $commentArr = [
   *    subject - заголовок
   *    body - тело
   *    subjectID - unid головного документа
   * ]
   * @param $commentArr
   * @param bool $objUser
   * @param null $unid
   * @param bool $dontNotify
   * @return Portal'
   */
  public function createComment($commentArr, $objUser = false, $unid = null, $dontNotify = false){
    if (!$objUser){
      $objUser = $this->getRobot();
    }
    if(isset($commentArr['shareSecurity'])){
      unset($commentArr['shareSecurity']);
    }

    $commentArr['status'] = !isset($commentArr['status'])?'open':$commentArr['status'];
    $commentArr['parentID'] = $commentArr['subjectID'];

    $docInBase = new \Treto\PortalBundle\Document\Portal($objUser);
    $docInBase->setAuthor($objUser->getPortalData()->GetFullName(false));
    $authorRus = isset($commentArr['AuthorRus'])&&$commentArr['AuthorRus']?$commentArr['AuthorRus']:$objUser->getPortalData()->GetFullNameInRus();
    if(isset($commentArr['sendShareFrom']) && $commentArr['sendShareFrom']){
      $authorRus .= ' ('.$authorRus['sendShareFrom'].')';
    }
    $docInBase->setAuthorRus($authorRus);
    $docInBase->SetAuthorLogin($objUser->getPortalData()->GetLogin());
    $docInBase->SetUnid($unid);
    $docInBase->SetForm('message');
    $docInBase->setDocument($commentArr);

    $this->container->get('doctrine.odm.mongodb.document_manager')->persist($docInBase);
    $main = $this->getMainDocFor($docInBase);
    $main = $main?$main:$docInBase;
    if(!$dontNotify){
      $this->addUnreadedToAllUsers($main, $docInBase, true, true);
    }
    else {
      $docInBase->setDefaultSecurity();
    }

    $main->IncrementCountMess($objUser);

    $this->container->get('doctrine.odm.mongodb.document_manager')->persist($main);
    $this->container->get('doctrine.odm.mongodb.document_manager')->flush();

    if(isset($main)){
      $this->container->get('node.service')->addComent($main->getUnid(), $docInBase->getDocument());
    }

    return $docInBase;
  }

  /**
   * AnswersData: ["1", "2", "3"]
   * 0: "1"
   * 1: "2"
   * 2: "3"
   * AnswersLim: 1
   * Author: "ikonovalov"
   * AuthorRus: "Коновалов Илья Михайлович"
   * _id: 0
   * _meta: null
   * answers: []
   * attachments: []
   * body: ""
   * form: "formVoting"
   * security: {privileges: {read: [{role: "all"}, {username: "ikonovalov"}], write: [{username: "ikonovalov"}],…}}
   * status: "open"
   * subject: "123"
   * unid: "510F2A61-D1FB-E782-3FA0-56711C0F403A"
   * @param $params
   * @return mixed
   * @throws \Doctrine\ODM\MongoDB\LockException
   */
  public function createVoting($params) {
    if(!$params || !count($params)){
      return false;
    }

    $doc = $params['document'];
    $docInBase = null;
    $errors = [];

    if(!isset($doc['Author']) || empty($doc['Author'])){
      $errors[] = 'user not found';
    }
    $repoContact = $this->getRepo('Contacts');
    $repoPortal = $this->getRepo('Portal');
    $objContact = $this->isUnid($doc['Author']) ? $repoContact->findOneBy(['unid' => $doc['Author']]) : $repoContact->find($doc['Author']);
    $objUser = false;
    if($objContact){
      $objPortal = $repoPortal->findOneBy(['$or' => [['unid' => $objContact->GetPortalUser_ID()], ['FullName' => $objContact->getUserNotesName()]], 'form' => 'Empl']);
      if($objPortal){
        $userManager = $this->getUM();
        $objUser = $userManager->findUserBy(['username' => $objPortal->getLogin()]);
      }
    }

    if(!$objUser){
      $errors[] = 'user not found';
    }

    $docInBase = new Portal($objUser);
    $errors[] = $docInBase->setDocument($doc, $this->container->get('treto.validator'), $objUser->getRoles());

    if(! $this->isUnid($docInBase->GetUnid())){
      $docInBase->SetUnid();
    }

    if(array_key_exists('AccessType', $doc) && $doc['AccessType'] == '1'){
      $docInBase->setDefaultWriteSecurity($objUser);
    }else{
      $docInBase->setDefaultSecurity($objUser);
    }

    $docInBase->setAuthor($objUser->getPortalData()->GetFullName(false));
    $docInBase->setAuthorRus($objUser->getPortalData()->GetFullNameInRus());
    $docInBase->SetForm('formVoting');
    if(isset($doc['AnswersData'])){
      $docInBase->setAnswersData(explode(';', $doc['AnswersData']));
    }else{
      $docInBase->setAnswersData([]);
    }

    $main = $this->getMainDocFor($docInBase, true);
    if(!$main) {
      $errors[] = 'parent document not found or access denied';
    }else{
      $main->IncrementCountMess($objUser);
      $main->addReadPrivilege($objUser->getPortalData()->GetLogin(), '_roboservice');
      $main->addSubscribedPrivilege($objUser->getPortalData()->GetLogin(), '_roboservice');
      $this->getDM()->persist($main);
    }

    $this->getDM()->persist($docInBase);
    $this->getDM()->flush();

    return $docInBase->GetUnid();
  }

  protected function getRobot()
  {
    $userManager = $this->container->get('fos_user.user_manager');
    return $userManager->findUserBy(['username' => 'portalrobot']);
  }

  /** @return \Treto\PortalBundle\Document\SecureRepository */
  public function getRepo($shortDocumentName) {
    $repo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:'.$shortDocumentName);
    if($repo instanceof \Treto\PortalBundle\Document\SecureRepository) {
      $repo->releaseUser();
    }
    return $repo;
  }

  /** @return \Doctrine\ODM\MongoDB\DocumentManager */
  public function getDM() {
    return $this->container->get('doctrine.odm.mongodb.document_manager');
  }

  /** @return \FOS\UserBundle\Model\UserManagerInterface */
  public function getUM() {
    return $this->container->get('fos_user.user_manager');
  }

  /** returns getUser()->getPortalData() */
  public function getUserPortalData($user) {
    return $user ? $user->getPortalData() : null;
  }

  public function setReadByTime($t, $time = null, $isDocument = false, $isNotMain = false, $userLogin) {
    if (!isset($t) || sizeof($t) == 0) return false;

    $dm = $this->getDM();

    $repPortal = $this->getRepo('Portal');
    if (!$isDocument) {
      $main = $repPortal->findOneBy(['unid' => $t]);
      if (!$main){
        $main = $this->getRepo('Contacts')->findOneBy(['unid' => $t]);
      }
    } else {
      $main = $t;
    }

    if (!$main) return false;

    $isContact = $main->GetForm() == 'Contact';

    if ($isNotMain && $main->GetParentID()) {
      $parentID = $main->GetParentID();
      $main = $repPortal->findOneBy(['unid' => $parentID]);
      if (!$main){
        $main = $this->getRepo('Contacts')->findOneBy(['unid' => $parentID]);
      }

      if (!$main) return false;
    }

    $docs = $repPortal->findBy(['parentID' => $main->GetUnid(), 'form' => 'formTask']);
    array_unshift($docs, $main);
    
    foreach ($docs as $doc) {
      $readBy = $doc->GetReadBy();

      if ($time == null) {
        $now = new \DateTime();
        $stamp = $now->format('d.m.Y H:i:s');
      } else {
        $stamp = $time;
      }
      $readBy[$userLogin] = $stamp;
      $doc->SetReadBy($readBy);

      $dm->persist($doc);
    }
    
    if(isset($isContact) && $isContact){
      $main->setNotSynch(true);
      $dm->persist($main);
    }
    
    $dm->flush();
    $dm->clear();

    return $stamp;
  }


  public function addUnreadedToAllUsers(
      $parent,
      $doc,
      $andAuthor = true,
      $andDeputy = false,
      $except = [],
      $createContact = false,
      $selfUser = false,
      $timeline = false
  ) {
    /** @var User $selfUser */
    $result = [];

    $ps = $parent->getPermissionsByType('subscribed');
    if ($doc->GetForm() == 'formVoting' || $doc->GetForm() == 'formTask') {
      $psDoc = $doc->getPermissionsByType('subscribed');
      $ps['username'] = array_merge($ps['username'], $psDoc['username']);
    }
    if($doc->GetUnid() != $parent->GetUnid() || $createContact){
      $ps['username'] = $this->addContactDefaultNotif($ps['username'], $parent);
    }

    $users = array_diff($ps['username'], $except);
    $newUsers = []; //Fix mongo bug. convert from assoc array
    foreach ($users as $user) {
      $newUsers[] = $user;
    }
    $users = $newUsers;

    $userPortalDatas = $this->getRepo('Portal')->findEmplByNames($users,$users,$users);

    foreach($userPortalDatas as $p) {
      /** @var Portal $p */
      $notifyEmpls = [$p];

      if($andDeputy && $deputyLogin = $this->getDeputy($p->GetLogin())){
        if($deputyPortalEmpl = $this->getRepo('Portal')->findOneBy(['form' => 'Empl', 'Login' => $deputyLogin])){
          $notifyEmpls[] = $deputyPortalEmpl;
        }
      }

      foreach ($notifyEmpls as $notifyEmpl) {
        /** @var $notifyEmpl Portal */
        if($doc->GetAuthorLogin() == $notifyEmpl->GetLogin() && !$andAuthor){
          continue;
        }
        $users[] = $notifyEmpl->GetLogin();
      }
    }

    if ($selfUser) {
      foreach ($users as $key => $user) {
        if($user == $selfUser->getPortalData()->GetLogin()){
          unset($users[$key]);
        }
      }
    }

    $result['reallyAdded'] = $this->container->get('notif.service')->notifMultipleAdding($parent,
                                                                                         $doc,
                                                                                         $users,
                                                                                         $timeline ? -1 : 0,
                                                                                         __FUNCTION__.', '.__LINE__,
                                                                                         'Added notif to'
                                                                                        );
    
    return $result;
  }

  public function processNotifications(
      $parent,
      $doc,
      $selfUser,
      $notifyAllParticipants = false,
      $removeFromMyself = false,
      $silent = false,
      $readAt = false,
      $createContact = false,
      $timeline = false
  ){
    /** @var Portal  $doc */
    /** @var Portal  $empl */
    /** @var User $selfUser */

    $user = $selfUser->getPortalData();

    $result = [];
    if($silent) {
      $result['notified'] = ['silent' => true];
      $result['debug'][] = __FUNCTION__.': silent set.';
    } else {
      if($notifyAllParticipants) {
        $except = $removeFromMyself ? [$user->GetLogin()] : [];
        $result['notified'] = [];
        $result['notified'] = $this->addUnreadedToAllUsers(
            $parent,
            $doc,
            true,
            false,
            $except,
            $createContact,
            $selfUser,
            $timeline
        );
        $result['debug'][] = __FUNCTION__.': added notif '.$parent->GetUnid().'=>'.$doc->GetUnid().' to all users.';
      }
    }

    if($removeFromMyself && $this->container->get('notif.service')->hasNotif($parent->GetUnid(), $user->GetLogin())) {
      $this->container->get('notif.service')->notifRemoval(
          $parent,
          $doc,
          $user->GetLogin(),
          0,
          __FUNCTION__.', '.__LINE__,
          'Removed notif from',
          $readAt
      );
    }

    return $result;
  }

  public function addUnreadedToAuthor($main, $doc, $selfUser, $andParticipant = false, $fieldsChanged = []) {
    $result = [];
    /** @var Portal  $doc */
    /** @var Portal  $empl */
    /** @var User $selfUser */
    $names = [$doc->GetAuthor()];

    if($andParticipant && $doc->GetTaskPerformerLat(true) && !$selfUser->mynameis($doc->GetTaskPerformerLat(true))) {
      $names[] = $doc->GetTaskPerformerLat(true);
    }

    if(empty($names)) { return []; }
    $result['names'] = ['requested' => $names, 'found' => []];
    $empls = $this->getRepo('Portal')->findEmplByNames($names,$names,$names);
    if(!empty($empls)) {
      foreach($empls as $empl) {
        if(!$selfUser->mynameis($doc->GetAuthor())) {
          $result['names']['found'][] = $empl->GetLogin();
          $this->container->get('notif.service')->notifAdding($main,
              $doc,
              $empl->GetLogin(),
              0,
              __FUNCTION__.', '.__LINE__,
              'Added notif to');
        }
        if ((isset($fieldsChanged['taskDateCompleted']) && $fieldsChanged['taskDateCompleted']) ||
            (isset($fieldsChanged['status']) && $fieldsChanged['status'])) {
          if ($doc->GetTaskDateCompleted() && $doc->GetStatus() != 'close' && $doc->GetStatus() != 'cancelled') {
            $this->container->get('notif.service')->notifAdding($main,
                $doc,
                $empl->GetLogin(),
                1,
                __FUNCTION__.', '.__LINE__,
                'Added urgent-1 notif to');
          } else {
            $this->container->get('notif.service')->unurgeNotif($main->GetUnid(), $doc->GetUnid(), $empl->GetLogin());
          }
        }
      }

      return $result;
    }

    return [];
  }

  /**
   * @param $users
   * @param $parent
   * @return mixed
   */
  private function addContactDefaultNotif($users, $parent){
    /** @var $parent Contacts */
    if($parent->GetForm() == 'Contact' &&
        $parent->GetDocumentType() == 'Organization' &&
        in_array('Фабрики', $parent->GetGroup()) &&
        (in_array(11, $parent->GetContactStatus()) || in_array('11', $parent->GetContactStatus()))){
      $repDict = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Dictionaries');
      $dict = $repDict->findBy(['type'=>'AutoTaskPersons']);
      /** @var $d Dictionaries */
      if($dict){
        foreach($dict as $d){
          if($d->getKey() == 'Уведомления по фабрикам'){
            if(!in_array($d->getValue(), $users)){
              $users[] = $d->getValue();
            }
            break;
          }
        }
      }
    }
    return $users;
  }

  /**
   * Create history log
   * @param $docid
   * @param $subject
   * @param $type
   * @param $user
   */
  public function createHistoryLog($docid, $subject, $type, $user) {
    /** @var User $user */
    $state = 'body.discus';

    if ($type === 'profile'){
      $state = 'body.profileDisplay';
    }
    else {
      if ($type == 'Contact') $subject = 'Контакт: '.$subject;
      $type = '';
    }

    $repHistory = $this->getRepo('HistoryLog');
    $log = $repHistory->findOneBy(['userId' => $user->getUserName(), 'label' => $subject]);
    if (!$log){
      $log = new HistoryLog();
    }

    if (!$log->getId()){
      $log->setDocument(['userId' => $user->getUserName(), 'label'=> $subject]);
    }

    $log->setDocument(['time' => new \DateTime(), 'stateParams' => ['id' => $docid, 'type' => $type], 'state' => $state]);

    $this->getDM()->persist($log);
    $this->getDM()->flush();
  }

  /**
   * Replace unread note
   * @param $parent
   * @param $doc
   * @param $oldParentId
   */
  public function replaceUnreadNote($parent, $doc, $oldParentId, $author = false){
    /** @var $doc Portal */
    if($doc->GetForm() == 'formTask'){
      $loginNotify = [];

      if(!in_array((integer) $doc->GetTaskStateCurrent(), [10, 20, 21])){ //10 - notified, 20 21 - Sent for review
        if($doc->isExpiredTask()){
          $performerLats = $doc->GetTaskPerformerLat();
          $loginNotify = is_array($performerLats)?$performerLats:[$performerLats];
        }
      }
      else {
        $loginNotify = $doc->GetCheckerLat()?$doc->GetCheckerLat():$doc->GetAuthorLogin();
        $loginNotify = is_array($loginNotify)?$loginNotify:[$loginNotify];
      }

      $emplsNotifs = $this->getRepo('Notif')->findBy(['status' => 'active',
                                                      'parentUnid' => $oldParentId,
                                                      'docs.'.$doc->GetUnid() => ['$exists' => true]]);
      $empls = array();
      foreach ($emplsNotifs as $emplsNotif) {
        $empls[] = $emplsNotif->GetReceiver();
      };
      
      foreach ($empls as $key => $empl) {
        /** @var $empl Portal */
        $this->container->get('notif.service')->unurgeNotif($oldParentId,
                                                            $doc->GetUnid(),
                                                            $empl,
                                                            __FUNCTION__.', '.__LINE__,
                                                            'Unurged separated task notif from');
        $urgency = in_array($empl, $loginNotify) ? 1 : 0;
        if ($empl !== $author || $urgency) {
          $this->container->get('notif.service')->notifAdding($parent,
                                                              $doc,
                                                              $empl,
                                                              $urgency,
                                                              __FUNCTION__.', '.__LINE__,
                                                              'Added'.($urgency>0?' urgent-1':'').' notif to');
        }
      }

    }
  }

  public function getMainDocFor($doc) {
    $main = $this->getRepo('Portal')->findOneBy(array('unid' => $doc->GetSubjectID()));
    if (!$main) $main = $this->getRepo('Contacts')->findOneBy(array('unid' => $doc->GetSubjectID()));
    return $main;
  }

  /**
   * Get comment for document by UNID
   * @param $param
   * @return array
   */
  public function getCommentsByUnid($param){
    $result = ['data' => '', 'error' => false];
    if(isset($param['unid'])){
      $criteria = [
          '$or' => [['parentID' => $param['unid']], ['subjectID' => $param['unid']]],
          'status' => ['$ne' => 'deleted']
      ];
      $docs = $this->getRepo('Portal')->findBy($criteria);
      $result['data']['countAll'] = count($docs);
      $result['data']['count'] = 0;
      foreach ($docs as $doc) {
        /** @var Portal $doc */
        $subject = $doc->GetSubject()?$doc->GetSubject():$doc->GetMessageSubject();
        if($subject){
          $result['data']['count']++;
          $data = ['subject' => $subject, 'form' => $doc->GetForm(), 'unid' => $doc->GetUnid()];
          if($doc->GetForm() == 'formTask'){
            $data['TaskStateCurrent'] = $doc->GetTaskStateCurrent();
          }
          $result['data']['docs'][] = $data;
        }
      }
    }
    else {
      $result['error'] = 'Missing required UNID param.';
    }
    return $result;
  }

  /**
   * Dismiss user. Redirect active tasks on boss.
   * @param $user
   * @param array $result
   * @return array
   */
  public function dismissUser($user, $result = []){
    /** @var $user \Treto\UserBundle\Document\User */
    $user->setDismissUser();
    $repo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
    
    $tasks = $repo->findBy([
        '$and' => [
            ['form' => 'formTask'],
            ['status' => 'open'],
            ['DocType' => ['$ne' => 'event']],
            ['$or' => [
                ['taskDateCompleted' => ['$exists' => false]],
                ['taskDateCompleted' => '']
            ]],
            ['$or' => [
                ['taskPerformerLat' => ['$in' => [$user->getPortalData()->GetFullName(), $user->getPortalData()->GetFullNameRaw(), $user->getPortalData()->GetLogin()]]],
                ['Author' => ['$in' => [$user->getPortalData()->GetFullName(), $user->getPortalData()->GetFullNameRaw(), $user->getPortalData()->GetLogin()]]]
            ]]
        ]
    ]);
    $result['tasks'] = count($tasks);
    foreach ($tasks as $t) {
      $main = $repo->findOneBy(['unid' => $t->GetParentID()]);
      if (!$main) $main = $t;
      
      $bossLat = $this->getBosses($user->getPortalData());
      $bossLat = isset($bossLat[0])?$bossLat[0]:false;
      if ($bossLat) { // change task performer to boss
        $bossInDb = $repo->findEmplByNames([$bossLat], [$bossLat], [$bossLat])[0];
        if ($bossInDb) {
          if (in_array($t->GetTaskPerformerLat(true), [$user->getPortalData()->GetFullName(), $user->getPortalData()->GetLogin(), $user->getPortalData()->getFullName(false)])) {

            $taskHistory = new \Treto\PortalBundle\Document\TaskHistory();
            $taskHistory->setType('const');
            $taskHistory->setValue(['text' => $user->getPortalData()->GetFullNameInRus() . ' уволен. ' . $bossInDb->GetFullNameInRus() . ' автоматически назначен новым исполнителем.']);
            $taskHistory->setTaskId($t->GetId());
            $this->getDM()->persist($taskHistory);

            $t->SetTaskPerformerLat([$bossLat]);
            $t->SetTaskPerformer([$bossInDb->GetFullNameInRus()]);
            $t->setTaskDateRealEnd('');
            
            $this->container->get('notif.service')->notifAdding($main,
                                                                $t,
                                                                \Treto\UserBundle\Document\User::ROBOT_PORTAL,
                                                                1,
                                                                __FUNCTION__.', '.__LINE__,
                                                                'Added urgent-1 notif to');
          }

          $result['result'] = $this->addUnreadedToAllUsers($t, $t);
          $this->getDM()->persist($t);
        }
      }
    }
    $this->getDM()->flush();
    $result['tasksCreated'] = $this->createDismissTasks($user->getPortalData()->getLogin());

    return ['user' => $user, 'result' => $result];
  }

  /**
   * Return bosses for section
   * @param $Empl
   * @return array
   */
  public function getBosses($Empl){
    $repo_dict = $this->getRepo('Dictionaries');
    $boss = false;
    $theBoss = $repo_dict->findOneBy(['type' => 'AutoTaskPersons', 'key' => 'Генеральный директор']);
    if ($theBoss) $theBoss = $theBoss->getValue();
    else $theBoss = 'SKukresh';
    
    if ($Empl->GetLogin() == $theBoss || $Empl->GetLogin() == "vbykov") return [];
    if ($Empl->GetRegionID() !== '' && $Empl->GetRegionID() && $Empl->GetRegionID() !== 'Moscow' ) return [];

    $repoPortal = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');

    if($Empl->GetSection()){
      $params = [
          "form"=>"Empl",
          "DepSubmiss"=> [
              '$regex' => is_array($Empl->GetSection())?$Empl->GetSection()[0]:$Empl->GetSection()
          ],
          '$or' => [
              ['DtDismiss'=>''],
              ['DtDismiss'=>['$exists'=>false]]
          ]
      ];
      $boss = $repoPortal->findOneBy($params);
    }

    if($boss && $Empl->GetLogin() != $boss->GetLogin()) {
      return [$boss->GetLogin(), $theBoss];
    }
    return [$theBoss];
  }

  /**
   * Creating tasks for dismiss employees
   * @param $login
   * @return array
   */
  public function createDismissTasks($login){
    $repPortal = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
    $repDict = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Dictionaries');

    $dict = $repDict->findBy(['type'=>'AutoTaskPersons']);
    $adapt = $repPortal->findOneBy(['form'=>"formAdapt", "Login" => $login]);
    $empl = $repPortal->findOneBy(['form'=>"Empl", "Login" => $login]);
    $redirectMailLogin = $empl->GetRedirectMailTo();
    $redirectMailLogin = $redirectMailLogin&&is_array($redirectMailLogin)&&$redirectMailLogin[0]?$redirectMailLogin[0]:false;

    if($redirectMailLogin){
      /** @var Portal $redirectMailEmpl */
      $redirectMailEmpl = $repPortal->findOneBy(['form'=>"Empl", "Login" => $redirectMailLogin]);
      if($redirectMailEmpl){
        $redirectMailName = $redirectMailEmpl->GetLastName().' '.$redirectMailEmpl->GetName().' '.$redirectMailEmpl->GetMiddleName();
      }
    }

    if(!$adapt){
      $name = $empl?$empl->GetFullNameInRus():$login;
    }
    else {
      $name = $adapt->GetFullNameInRus();
    }

    $tasks = [
        'Начальник ИТ'=> [
            'subject' => 'Увольнение сотрудника '.$name,
            'body'=>'Убрать доступ в 1С для уволенного сотрудника '.$name
        ],
        'Системный администратор' => [
            'subject'=>'Увольнение сотрудника '.$name,
            'body'=>'Убрать доступ в домен и цирикс для уволенного сотрудника '.$name
        ],
        'Teamlead портала' => [
            'subject'=>'Увольнение сотрудника '.$name,
            'body'=>'Отключить от чата '.$name.'('.$login.')'
        ],
        'Рекрутер' => [
            'subject'=>'Увольнение сотрудника '.$name,
            'body'=>'Сотрудник '.$name.' уволен. Перенаправить его почту.'
        ]
    ];

    if(isset($redirectMailName) && $redirectMailName){
      $tasks['Рекрутер'] = [
          'subject'=>'Увольнение сотрудника '.$name,
          'body'=>'Сотрудник '.$name.' уволен. Перенаправить его почту на сотрудника '.$redirectMailName
      ];
    }

    $task = [ 'Author'=> 'portalrobot',
        'C1'=> "Общекорпоративные",
        'Difficulty'=> "1 Легко",
        'form'=> "formTask",
        'status'=> "open"
    ];

    if($adapt){
      $task['subjectID'] = $adapt->GetUnid();
      $task['parentID'] = $adapt->GetUnid();
    }
    else {
      $task['readSecurity'] = [$login];
    }
    $resTasks = [];
    foreach ($dict as $row) {
      if (isset($tasks[$row->getKey()])) {
        $task['subject'] = $tasks[$row->getKey()]['subject'];
        $task['body'] = $tasks[$row->getKey()]['body'];
        $task['taskPerformerLat'] = [$row->getValue()];
        $task['taskPerformerLatType'] = 'logins';
        $res = $this->setTask(['document'=>$task]);
        $resTasks[] = $res;
      }
    }
    return $resTasks;
  }

  /**
   * Get deputy login by username and date
   * date format must be [31, 12, 2016] or 31.12.2016
   * @param bool $login
   * @param bool $emplUnid
   * @param bool $date
   * @param bool $returnStatus
   * @return bool
   */
  public function getDeputy($login = false, $emplUnid = false, $date = false, $returnStatus = false){
    $result = false;
    if(!$date){
      $day = date('j');
      $month = date('m');
      $year = date('Y');
    }
    else {
      if(is_string($date)){
        $date = explode('.', $date);
      }
      $day = ltrim($date[0], '0');
      $month = date('m');
      $year = date('Y');
    }

    if($login || $emplUnid){
      $repPortal = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
      if($login && !$emplUnid){
        /** @var Portal $empl */
        $empl = $repPortal->findOneBy(['form' => 'Empl', 'Login' => $login]);
        if($empl){
          $emplUnid = $empl->GetUnid();
        }
      }

      if($emplUnid){
        $repo = $this->getRepo('Portal');
        /** @var Portal $wp */
        $wp = $repo->findOneBy([
            'EmplUNID' => $emplUnid,
            'Year'=> $year,
            'Month'=> $month,
            'form'=>'WorkPlan'
        ]);

        if($wp){
          $dd = $wp->GetDaysData();

          if(isset($dd[$day-1])){
            if(isset($dd[$day-1]['deputyLogin'])){
              $result = $dd[$day-1]['deputyLogin'];
            }
            elseif($returnStatus){
              $result = $dd[$day-1];
            }
          }
        }
      }
    }

    return $result;
  }

  /**
   * Get participants list by document unid
   * @param $unid
   * @param bool $isContact
   * @return array
   */
  public function getParticipantsByUnid($unid, $isContact = false){
    $response = ['error' => false, 'result' => []];
    $repName = $isContact?'Contacts':'Portal';
    $repository = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:'.$repName);
    /** @var Portal $doc */
    $doc = $repository->findOneBy(['unid' => $unid]);
    if($doc){
      $subscribed = $doc->getPermissionsByType('subscribed');
      if($subscribed && isset($subscribed['username'])){
        $response['result'] = $subscribed['username'];
      }
      else {
        $response['error'] = 'Not found participants';
      }
    }
    else {
      $response['error'] = 'Not found document by UNID:'.$unid;
    }
    return $response;
  }

  /**
   * Get empls emails by fname, lname, mname
   * @param $params
   * @return string
   */
  public function getMailByName($params){
    $criteria = [];
    $result = '';

    if(isset($params['document'])){
      if(isset($params['document']['DocumentFirstName']) && $params['document']['DocumentFirstName']){
        $criteria['name'] = $params['document']['DocumentFirstName'];
      }
      if(isset($params['document']['DocumentLastName']) && $params['document']['DocumentLastName']){
        $criteria['LastName'] = $params['document']['DocumentLastName'];
      }
      if(isset($params['document']['DocumentMiddleName']) && $params['document']['DocumentMiddleName']){
        $criteria['MiddleName'] = $params['document']['DocumentMiddleName'];
      }
    }

    if($criteria){
      $repPortal = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
      $criteria['$or'] = [['DtDismiss' => ['$exists' => false]], ['DtDismiss' => '']];
      $criteria['form'] = 'Empl';
      $response = $repPortal->findBy($criteria);

      if($response){
        foreach ($response as $key => $item) {
          /** @var $item Portal */
          $email = $item->GetEmail();
          if($email){
            $result .= $email.'~';
          }
        }
        $result = trim($result, '~');
      }
    }

    return $result;
  }

  /**
   * Get login autoTask by key
   * @param $position
   * @return bool
   */
  public function getAutoTaskPersonByKey($position){
    $result = false;
    $repDict = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Dictionaries');
    $dict = $repDict->findBy(['type'=>'AutoTaskPersons']);

    if($dict){
      foreach($dict as $d){
        /** @var $d Dictionaries */
        if($d->getKey() == $position){
          $result = $d->getValue();
          break;
        }
      }
    }

    return $result;
  }

  /**
   * Take out task from parent document
   * @param $task Portal
   * @param string $userLogin
   * @return array
   */
  public function takeOutTask($task, $userLogin = User::ROBOT_PORTAL){
    $unid = $task->GetUnid();
    $parentId = $task->GetSubjectID();
    if($unid != $parentId){
      $portal = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
      $criteria = ['form' => 'messagebb', 'parentID' => $parentId, 'taskID' => $unid];
      $messagebbs = $portal->findBy($criteria);

      foreach($messagebbs as $messagebb){
        /** @var $messagebb Portal*/
        $messagebb->SetTaskID($unid);
        $messagebb->SetSubjectID($unid);
        if($messagebb->GetParentDbName()){
          $messagebb->SetParentDbName('Portal');
        }
        $this->getDM()->persist($messagebb);
      }

      $task->SetParentID($unid);
      $task->SetSubjectID($unid);

      if($task->GetParentDbName()){
        $task->SetParentDbName('Portal');
      }

      $this->replaceUnreadNote($task, $task, $parentId, $userLogin);

      $fieldsChanged['parentID'] = ['parentID' => 1];
      $fieldsChanged['subjectID'] = ['subjectID' => 1];
      $this->getDM()->persist($task);
      $this->getDM()->flush();
    }

    return ['docInBase' => $task, 'fieldsChanged' => isset($fieldsChanged)?$fieldsChanged:[]];
  }

  /**
   * Run command
   * example: $this->runCommand('testCommand:run', ['synchPass']);
   * @param $command
   * @param array $arguments
   * @param string $logName
   * @return string
   */
  public function runCommand($command, $arguments = [], $logName = 'commands'){
    $root_dir = $this->container->get('kernel')->getRootDir();
    $cmd = sprintf('%s/console %s', 'php '.$root_dir, $command);
    if (count($arguments) > 0) {
      $cmd = sprintf($cmd . ' %s', implode(' ', $arguments));
    }
    $cmd = sprintf(
        "%s --env=%s >> %s 2>&1 & echo $!",
        $cmd,
        $this->container->get('kernel')->getEnvironment(),
        sprintf('%s/logs/%s.log', $root_dir, $logName)
    );

    $process = new \Symfony\Component\Process\Process($cmd);
    $process->run();
    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    $pid = $process->getOutput();

    return $pid;
  }

    /**
     * Send http request
     * @param $addr
     * @param $params
     * @return array
     */
  public function sendRequest($addr, $params = []){
      $ch = curl_init($addr);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
      $result = curl_exec($ch);

      $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      curl_close($ch);

      return ['status' => $status, 'result' => json_decode($result, true) ];
  }

    /**
     * @param $salt
     * @param array $params
     * @return string
     */
    public function encodeAccessKey($salt, $params = []){
        $salt = !empty($params)?serialize($params).$salt:$salt;
        return md5($salt.date('Y.m.d'));
    }

    /**
     * @param $hash
     * @param $params
     * @return bool
     */
  public function checkHash($hash, $params = []){
      $result = false;
      /** @var \Doctrine\ODM\MongoDB\DocumentRepository $portalSettingsRepo */
      $portalSettingsRepo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:PortalSettings');
      /** @var PortalSettings $salt */
      $salt = $portalSettingsRepo->findOneBy(['type' => 'selfSalt']);
      if($salt && $salt->getValue()){
          $result = $this->encodeAccessKey($salt->getValue(), $params) == $hash;
      }

      return $result;
  }

  public function getMailAccess($login){
      $mailAccess = false;
      $mdHost = $this->container->getParameter('mongodb_host');
      $mdPort = $this->container->getParameter('mongodb_port');
      $mdUsername = $this->container->getParameter('mongodb_username');
      $mdPass = $this->container->getParameter('mongodb_password');
      $dbName = $this->container->getParameter('mongodb_db');

      $m = new \MongoClient("mongodb://$mdUsername:$mdPass@$mdHost:$mdPort/$dbName");
      $collection = new \MongoCollection($m->selectDB($dbName), 'User');
      $response = $collection->findOne(array('username' => $login));

      if($response){
          $mailAccess = isset($response['mailAccess'])?$response['mailAccess']:[];
      }

      return $mailAccess;
  }

    /**
     * Sort user array by sections
     * @param $users
     * @return array
     */
    private function sortBySection($users){
        $result = [];
        foreach ($users as $user) {
            foreach ($user['section'] as $section) {
                if(!isset($result[$section])){
                    $result[$section] = ['data' => []];
                }
                $result[$section]['data'][] = $user;
            }
        }
        return $result;
    }

  /**
   * Get all active share users
   * @param bool $byCategory
   * @return array
   */
    public function getAllActiveShareUsers($byCategory = true){
        $result = [];

        /** @var \Doctrine\ODM\MongoDB\DocumentRepository $portalSettingsRepo */
        $portalSettingsRepo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:PortalSettings');
        $allSettings = $portalSettingsRepo->findBy(['status' => 'active', 'type' => 'sharePortal']);

        if($allSettings){
            foreach ($allSettings as $allSetting) {
                /** @var PortalSettings $allSetting */
                $users = $allSetting->getUsers();

                if($users){
                  if($byCategory){
                    $result[$allSetting->getDomain()] = [
                        'data' => $this->sortBySection($users),
                        'environment' => $allSetting->getEnvironment(),
                        'name' => $allSetting->getCompanyName()
                    ];
                  }
                  else {
                    $result[$allSetting->getDomain()] = $users;
                  }
                }
            }
        }

        return $result;
    }
}
