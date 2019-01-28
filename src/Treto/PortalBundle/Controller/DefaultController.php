<?php

namespace Treto\PortalBundle\Controller;
use Symfony\Component\Validator\Constraints\DateTime;
use Treto\PortalBundle\Document\Contacts;
use Treto\PortalBundle\Document\Portal;
use Treto\PortalBundle\Document\Notif;
use Treto\PortalBundle\Document\SecureDocument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Treto\PortalBundle\Document\C1Log;
use Treto\PortalBundle\Document\Tag;
use Exception;
use Treto\PortalBundle\Services\SynchService;

class DefaultController extends AbstractDiscussionController
{
  use \Treto\PortalBundle\Services\StaticLogger;
  public function indexAction($name)
  {
    $this->log(__CLASS__, __METHOD__, 'Default');
    return $this->render('TretoPortalBundle:Default:index.html.twig', array('name' => $name));
  }
    
  protected function getWorkPlan($monthYear, $unid, $repo = null) {
    if($repo == null)
      $repo = $this->getRepo("Portal");
    $search = ["form" => "WorkPlan"];

    $search ['EmplUNID'] = $unid;
    $search ['Year'] = $monthYear[1];
    $search ['Month'] = $monthYear[0];
    $ret = [];
    $rs = $repo->findBy($search);
    if(count($rs) != 0) {
      foreach($rs as $v) {
        foreach( $v->GetDaysData() as $label) {
          $ret[] = ['label'=>$label];
        }
        break;
      }
    }
    else{
      $ret = $this->defaultMonthModel($monthYear);
    }
    return $ret;
  }

  protected function defaultMonthModel($from) {
    $t = sprintf("%s-%s-01",$from[1], $from[0]);
    $s = date_create_from_format("Y-m-d", $t);
    $n = $this->getMonthDayCount($from);
    $ret = [];
    $di = new \DateInterval('P1D');
    foreach(range(0, $n-1) as $q) {
      $f = ((6 + $s->format("w"))%7);
      $R = ['р','р','р','р','р','в','в'];
      //$E = ['b','b','b','b','b','w','w'];
      $ret []= ['label'=>$R[$f]];
      $s = date_add($s, $di);
    }
    return $ret;
  }

  protected function getMonthDayCount($from) {
    $next = $from;
    $next[0]++;
    if($next[0]==13) {
      $next[0]-=12;
      $next[1]++;
    }
    if(strlen($next[0].'') < 2)$next[0] = '0'.$next[0];
    $s = date_create_from_format("Ymd", sprintf("%s%s01",$from[1], $from[0]));
    $e = date_create_from_format("Ymd", sprintf("%s%s01",$next[1], $next[0]));
    $i = date_diff($s, $e);
    return 0+$i->format("%a");
  }
  
  public function periodicAction() {
    if(!in_array($this->getRequest()->getClientIp(), ['127.0.0.1','::1','fe80::1'])) {
      return $this->fail('this action is for localhost only', ['ip' => $this->getRequest()->getClientIp()]);
    }
    ini_set('max_execution_time', 300);
    $result = [];
    $result += $this->periodicTasks();
    $result += $this->periodicVotes();
    $result += $this->periodicCleanup();
    return $this->success($result);
  }
  
  public function periodicTasks() {
    $repoPortal = $this->getRepo('Portal');
    $repoContacts = $this->getRepo('Contacts');
    $result = [];
    /** @var SynchService $synchService */
    $synchService = $this->container->get('synch.service');

    $schedules = array();

    $today = new \DateTime();
    $todayIso = SecureDocument::dt2iso($today, true);
            
    $tasks = $repoPortal->findBy([
      '$and' => [
        [ 'form' => 'formTask' ],
        [ 'TaskStateCurrent' => ['$nin' => [10, 20, 21, 25, 30, 35]]],
        [ 'status' => 'open' ],
        [ 'DocType' => ['$ne' => 'event'] ],
        [ '$or' => [
          [ 'taskDateCompleted' => ['$exists' => false] ],
          [ 'taskDateCompleted' => '' ]
        ] ]
      ] 
    ]);

    $result['uncompleted-tasks'] = count($tasks);
    
    $result['notif-reminders-sent'] = 0;
    $result['notif-boss-told'] = 0;
    $result['boss-told'] = 0;
    $result['reminders'] = 0;
    
    //filtering tasks 
    $expiredTasks = [];
    foreach($tasks as $t) {
      /** @var Portal $t */
      $docTime = $t->GetTaskDateRealEnd()? $t->GetTaskDateRealEnd():($t->GetTaskDateRealStart() ? $t->GetTaskDateRealStart():$t->GetCreated());
      $docTime = SecureDocument::iso2iso($docTime, true, true);
      if($docTime < $todayIso) {
        $expiredTasks[] = $t;
      }
    }

    $result['expired-tasks'] = count($expiredTasks);
    $counter = 0;
    foreach($expiredTasks as $t) {
      /** @var $t Portal */
      $counter++;
      if ($counter >= 1000) {
        $this->getDM()->flush();
        $counter = 0;
      }
      // if ($t->GetUnid() != "B22C6D98-BC51-1FF5-86A1-121EA7C8011C") continue;

      $taskUnid = $t->GetUnid();
//       echo '||'.$taskUnid;
     
      $tParent = $t;
      
      if ($t->GetParentID() != null || strlen($t->GetParentID()) > 1) {
        $parentCandidate = $this->findDoc($t->GetParentID());
        if (is_object($parentCandidate)) $tParent = $parentCandidate;
      }
//       echo '||'.$tParent->GetUnid();
      $performerDoc = null; $bossDoc = null; $authorDoc = null;

      //get current performer
      $performerLat = $t->GetTaskPerformerLat(true);
      
      $performerLatOrig = $t->GetTaskPerformerLat();
      if (!is_array($performerLatOrig)) {
        $t->SetTaskPerformerLat([$performerLatOrig]);
      }

      $performerInDb = $repoPortal->findEmplByNames([$performerLat], [$performerLat], [$performerLat]);
      $performerDoc = reset($performerInDb);

      if($performerDoc && is_object($performerDoc)) {
        $taskEndDate = $t->GetTaskDateRealEnd()? $t->GetTaskDateRealEnd():($t->GetTaskDateRealStart()?$t->GetTaskDateRealStart():$t->GetCreated());
        $taskEndDate = SecureDocument::iso2iso($taskEndDate, true, true);
//         echo '||'.$taskEndDate;
        if ($t->GetStatus() != 'close') { // ============== active ===============
            $bossLat = $this->getBosses($performerDoc);
            if (sizeof($bossLat) > 0) $bossLat = $bossLat[0];
            else $bossLat = $performerDoc->GetLogin();

            $bossDoc = $performerDoc;
            if ($bossLat) $bossDoc = $repoPortal->findEmplByLogin($bossLat);
            
            if (strpos($bossLat, '.') || strpos($performerLat, '.')){ //exterminate old tasks
              echo '|'.$bossLat;
              $t->SetStatus('close');
              $t->SetSecurity(null);
              $this->getDM()->persist($t);
              continue;
            }

            $performerReminded = $t->GetEscalationManagersTime($performerDoc->GetLogin(), 'remind');
            
            ////--------------------------------------------
            $today = new \DateTime();
            $day = $today->format('d');
            $month = $today->format('m');
            $year = $today->format('Y');

            $pLogin = $performerDoc->GetLogin();
            if (!isset($schedules[$pLogin])) {
              $schedules[$pLogin] = array();
            }
            $today = new \DateTime();
            $daysOff = 0;
            $found = 0;
            $yesterdayOffset = 0;
            $threedaysagoOffset = 0;
            $iterated = 0;
            
            if (!isset($schedules[$pLogin][$month.'.'.$year])) {
              $schedules[$pLogin][$month.'.'.$year] = $this->getWorkPlan([$month,$year], $performerDoc->GetUnid());
//               echo '_!_Looking for WP for '.$pLogin.' for '.$month.'.'.$year.'_!_';
            }
            if (isset($isWorkday)) unset($isWorkday);
            $isWorkday = $schedules[$pLogin][$month.'.'.$year][intval($day)-1]['label'] != 'в';

            while ($found < 3 && $iterated < 12) {
              $dayIndex = intval($day)-1;
              $num = $month.'.'.$year;
              if (!isset($schedules[$pLogin][$num])) {
                $schedules[$pLogin][$num] = $this->getWorkPlan([$month,$year], $performerDoc->GetUnid());
//                 echo '_!_Looking for WP for '.$pLogin.' for '.$num.'_!_';
              }

              for ($i = $dayIndex; $i >= 0 && $found < 3; $i--) {
                $schedules[$pLogin][$num][$i]['label'] != 'в'?$found++:$daysOff++;
                  
                if ($found == 1) {
                  $yesterdayOffset = $daysOff;
                  $threedaysagoOffset = $daysOff;
                }
                if ($found == 3) {
                  $threedaysagoOffset = $daysOff;
                }
//                 echo 'i:'.($i+1).'|f:'.$found.'|d:'.$daysOff.'|y:'.$yesterdayOffset.'|t:'.$threedaysagoOffset.'__';
              }
              
              $today->sub(new \DateInterval('P1M'));
              $month = $today->format('m');
              $year = $today->format('Y');
              $day = $this->getMonthDayCount([$month,$year]);
              
              $iterated++;
            }
            
            $today = new \DateTime();
            $yesterday = $today->setTimestamp($today->getTimestamp() - 86400*(1+$yesterdayOffset));
            $today = new \DateTime();
            $threedaysago = $today->setTimestamp($today->getTimestamp() - (86400*(3+$threedaysagoOffset)));

            $yesterdayIso = SecureDocument::dt2iso($yesterday, true);
            $threedaysagoIso = SecureDocument::dt2iso($threedaysago, true);

            ////--------------------------------------------
            
           if ($isWorkday) {
              if($taskEndDate < $threedaysagoIso && $performerReminded) {       //============= 3 days expired =============
                if ($this->isCompleted($t)) continue;
                $done = false;
//                 echo "Boss is ".$bossDoc->GetLogin()."! ";
                do {
                  $bossNotified = $t->GetEscalationManagersTime($bossDoc->GetLogin(), 'notify');
                  if(!$bossNotified) {
                    $this->get('notif.service')->notifAdding($tParent,
                                                             $t,
                                                             $bossDoc->GetLogin(),
                                                             1,
                                                             __FUNCTION__.', '.__LINE__,
                                                             'Added urgent-1 notif to');
                    $result['notif-boss-told']++;
                    
                    if (!$this->get('notif.service')->hasNotif($taskUnid, $performerDoc->GetLogin(), true)) {
                      $this->get('notif.service')->notifAdding($tParent,
                                                               $t,
                                                               $performerDoc->GetLogin(),
                                                               1,
                                                               __FUNCTION__.', '.__LINE__,
                                                               'Added urgent-1 notif to');
                      $result['notif-reminders-sent']++;
                    }

                    $taskHistory = new \Treto\PortalBundle\Document\TaskHistory();
                    $taskHistory->setTaskId($t->getId());
                    $taskHistory->setType('notify');
                    $taskHistory->setValue(['boss' => $bossDoc->GetLogin(), 'bossGroup' => $bossDoc->GetWorkGroup(true)]);
                    $this->getDM()->persist($taskHistory);
                    $synchService->shareTaskHistory($t->getDocument(), $taskHistory->getDocument(), $this->getRequest()->getHost());
//                     echo 'Notified '.$bossDoc->GetLogin().'! ';
                    $done = true;
                    $t->AddEscalationManagers($bossDoc->GetLogin(), 'notify');
                    $result['boss-told']++;
                    $tParent->addReadPrivilege($bossDoc->GetLogin(), '_periodic');
                    $tParent->addSubscribedPrivilege($bossDoc->GetLogin(), '_periodic');
                    $this->getDM()->persist($tParent);
                    $this->getDM()->persist($t);
                  } else {
                    //echo "==".intval(substr($todayIso, 0, 8)).'-'.intval(substr($bossNotified, 0, 8)).' = '.(intval(substr($todayIso, 0, 8)) - intval(substr($bossNotified, 0, 8))).'==';
                    if ($bossNotified && ((intval(substr($todayIso, 0, 8)) - intval(substr($bossNotified, 0, 8))) < 3)) {
//                       echo 'Not enough time has passed! ';
                      $done = true;
                    } else {
                      $oldBoss = $bossDoc;
                      $bossLat = $this->getBosses($bossDoc);
                      if (sizeof($bossLat) > 0) $bossLat = $bossLat[0];
                      else {
//                         echo '|'.$taskUnid.'|';
                        break;
                      }

                      $bossInDb = $repoPortal->findEmplByNames([$bossLat], [$bossLat], [$bossLat]);
                      $bossDoc = reset($bossInDb);
                      
                      if ($bossDoc == $oldBoss) $done = true;
                    }
                  }
                  
                } while (!$done);
                
              } elseif ($taskEndDate < $yesterdayIso) {   //============= 1 day expired ==============
                if ($this->isCompleted($t)) continue;
//                 echo "Performer notified";
                // not fully expired, just notify performer
                if (!$this->get('notif.service')->hasNotif($taskUnid, $performerDoc->GetLogin(), true)) {
                  $this->get('notif.service')->notifAdding($tParent,
                                                           $t,
                                                           $performerDoc->GetLogin(),
                                                           1,
                                                           __FUNCTION__.', '.__LINE__,
                                                           'Added urgent-1 notif to');
                  $result['notif-reminders-sent']++;
                }
 
                //notyfy if added task is new
                
                if(!$performerReminded || $performerReminded != $taskEndDate) {
                  $t->AddEscalationManagers($performerDoc->GetLogin(), 'remind', $taskEndDate);
                  $result['reminders']++;
                  $taskHistory = new \Treto\PortalBundle\Document\TaskHistory();
                  $taskHistory->setTaskId($t->getId());
                  $taskHistory->setType('remind');
                  $taskHistory->setValue(['performer' => $performerDoc->GetLogin()]);
                  $this->getDM()->persist($taskHistory);
                  $this->getDM()->persist($t);
                  $synchService->shareTaskHistory($t->getDocument(), $taskHistory->getDocument(), $this->getRequest()->getHost());
                }
                
              }
              
           }
        }
      }
    }

    $this->getDM()->flush();
    $this->getDM()->clear();

    return $result;
  }
  
  public function periodicVotes() {
    $repoPortal = $this->getRepo('Portal');
    $result = [];
            
    $votes = $repoPortal->findBy([
        'form' => 'formVoting',
        'status' => 'open',
        'PeriodPoll' => ['$exists' => true],
        '$where' => '(new Date(this.created.substr(0, 4)+"-"+this.created.substr(4, 2)+"-"+this.created.substr(6, 2)) <= new Date(new Date().getTime()-((this.PeriodPoll)*24*60*60*1000)))'
      ], ['created' => -1]);
    
    $result['expired-votes'] = count($votes);
    
    $counter = 0;
    $notificationsBuffer = [];
    foreach($votes as $v) {
    
//       if ($v->GetUnid() == "397DE7E7-B733-8CC2-C7C7-AD4D94E9FCB2") echo 'found it! ';

      if ($counter >= 500) {
        $this->getDM()->flush();
        $counter = 0;
      }
      $counter++;
      
      $v->SetStatus('close');
      $this->getDM()->persist($v);
      if ($v->GetAuthorLogin() && strlen($v->GetAuthorLogin()) > 0) {
        $authorInDb = $repoPortal->findEmplByNames([$v->GetAuthorLogin()], [$v->GetAuthorLogin()], [$v->GetAuthorLogin()]);
        $authorDoc = reset($authorInDb);
        
        $this->get('notif.service')->notifAdding($v,
                                                 $v,
                                                 $authorDoc->GetLogin(),
                                                 0,
                                                 __FUNCTION__.', '.__LINE__,
                                                 'Added notif to', '(Опрос мнения окончен)');
        
      }
      
    }

    $this->getDM()->flush();
    $this->getDM()->clear();
    
    $votes = $repoPortal->findBy([
        'form' => 'formVoting',
        'status' => 'open',
        'PeriodPoll' => ['$exists' => true]]);
        
    $result['open-votes'] = count($votes);
    
    return $result;
  }
  
  public function periodicCleanup() {
    $repoNotif = $this->getRepo('Notif');
    $robotNotif = $repoNotif->findBy(['receiver' => 'portalrobot']);
    if ($robotNotif) {
      foreach($robotNotif as $notif) {
        $this->getDM()->remove($notif);
      }
      
      $this->getDM()->flush();
      $this->getDM()->clear();
    }
    
    return ['cleanup successful' => true];
  }
  
  public function oldReferenceAction() {
    $unid = $this->getRequest()->get('highlightunid');
    $url = $this->getRequest()->get('url');
    if($unid || $url){
      $url = ltrim(stristr($url, '/'), '/');
      $url = strpos($url, '?') === false?$url:stristr($url, '?', true);
      $url = strtoupper($url);
      $redirectTo = $unid?$unid:$url;
      $homeUrl = $this->container->get('router')->getContext()->getBaseUrl();
      return $this->redirect($homeUrl."/#/discus/$redirectTo/");
    }
    else {
      $link = $this->getRequest()->get('link');
      // TODO: try to find in "public"
      $uri = trim($this->getRequest()->getRequestUri());
      if(strstr($uri, 'app_dev.php')) {
        $uri = explode('/', $uri);
        array_splice($uri, 0, 2);
        $uri = join('/', $uri);
      }
      $webdir = $this->get('kernel')->getRootDir() . '/../web/public/images/';
      if(file_exists( $webdir.'/'.md5($uri) )) {
        header('Content-type:image/gif');
        passthru ($webdir.'/'.md5($uri) );
      }
      elseif(file_exists( $webdir.''.$link)) {
        header('Content-type:image/gif');
        passthru ($webdir.''.$link);
      }
      else
        print $webdir.''.$link;
      exit;
    }
    throw new \Symfony\Component\Routing\Exception\ResourceNotFoundException('Not Found', 404);
  }
  
  public function batchRequestAction() {
    $r = $this->getRequest();
    $d = $this->fromJson();
    if(! is_array($d) || empty($d['requests'])) {
      return $this->fail('wrong input');
    }
    $d = $d['requests'];
    if(count($d) > 32) { return $this->fail('too much requests'); }
    $responses = [];
    foreach($d as $i => $req) {
      if(empty($req['url'])) {
        $responses[$i] = ['success' => false, 'message' => 'wrong request url'];
      } else {
        $url = ($req['url'][0] != '/') ? '/'.$req['url'] : $req['url'];
        $qParams = [];
        @parse_str(parse_url($url, PHP_URL_QUERY), $qParams);
        if(!empty($qParams)) {
          $url = substr($url, 0, strpos($url,'?'));
        }
        $match = $this->get('router')->match($url);
        if($match) {
          try {
            if(!empty($req['params'])) {
              $match = array_merge($match, $req['params']);
            }
            $resp = $this->forward($match['_controller'], $match, $qParams);
            $data = $resp;
            if(is_object($resp) && ($resp instanceof Response)) {
              $data = $resp->getContent();
              if($resp instanceof JsonResponse) {
                $data = json_decode($data, true);
              }
            }
            $responses[$i] = $data;
          } catch(\Exception $e) {
            $responses[$i] = ['success' => false, 'message' => $e->getMessage()];
          }
        } else {
          $responses[$i] = ['success' => false, 'message' => 'route not found', 'url' => $req['url']];
        }
      }
    }
    return $this->success(['responses' => $responses]);
  }

  public function discountsAction() {
    if(!in_array($this->getRequest()->getClientIp(), ['127.0.0.1','::1','fe80::1'])) {
      return $this->fail('this action is for localhost only', ['ip' => $this->getRequest()->getClientIp()]);
    }
    $result = [];
    $result['acceptedDiscounts'] = $this->acceptedDiscounts();
    $result['prolongationDiscounts'] = $this->prolongationDiscounts();
    return $this->success($result);
  }

  /**
   * Accept discounts for all not accepted contacts factories
   * @return array
   */
  public function acceptedDiscounts () {
    $repo = $this->getRepo('Contacts');
    $result = [];

    $notAcceptedDiscounts = $repo->findBy([
      '$and' => [
        ['form' => 'Contact'],
        ['DocumentType' => 'Organization'],
        ['Group' => 'Фабрики'],
        ['Status' => 'open'],
        ['$and' => [
          ['ResponsibleManager_ID' => ['$exists' => true]],
          ['ResponsibleManager_ID' => ['$ne' => '']]
        ]],
        ['$or' => [
          ['DiscountAccepted' => ['$exists' => false]],
          ['DiscountAccepted' => '0']
        ]]
      ]
    ]);

    $result['notAcceptedDiscounts'] = count($notAcceptedDiscounts);
    $result['countCreatedTasks'] = 0;

    foreach ($notAcceptedDiscounts as $objContact) {
      /** @var Contacts $objContact */
      if($objContact->GetResponsibleManager_ID()){
        $this->createTasksForContact($objContact)?$result['countCreatedTasks']++:false;
      }
    }
    return $result;
  }

  public function prolongationDiscounts() {
    $repo = $this->getRepo('Contacts');

    $result = [];
    $contactsIds = [];

    $date = new \DateTime();
    $date->add(new \DateInterval('P1M'));

    $discounts = $repo->findBy([
      '$and' => [
        ['form' => 'formDiscount'],
        ['OldDiscount' => ['$ne' => '1']],
        ['$and' => [
          [ 'ConditionDuration' => ['$lt' => SecureDocument::dt2iso($date)]],
          [ 'ConditionDuration' => ['$ne' => '']]
        ]],
        ['Status' =>  ['$ne' => "deleted" ]],
        ['conditionunlimited' => ['$ne' => '1']]
      ]
    ]);

    $result['discounts'] = count($discounts);
    $result['countProlongationTasks'] = 0;

    foreach ($discounts as $objDiscount) {
      if (!in_array($objDiscount->getContactId(), $contactsIds)){
        $contactsIds[] = $objDiscount->getContactId();
      }
    }

    if (!count($contactsIds)){
      return $result;
    }

    $organizations = $repo->findBy([
         '$and' => [
          ['unid' => ['$in' => $contactsIds]],
          [ '$and' => [
              [ 'ResponsibleManager_ID' => ['$exists' => true] ],
              [ 'ResponsibleManager_ID' => ['$ne' => ''] ]
          ] ],
        ]
    ]);

    foreach ($organizations as $objContact) {
      /** @var Contacts $objContact */
      if($objContact->GetResponsibleManager_ID()){
        $this->createTasksForContact($objContact, false)?$result['countProlongationTasks']++:false;
      }
    }

    return $result;
  }

  /**
   * Create task for accepted and prolongation
   * ($isAccepted == true - accepted, $isAccepted == false - prolongation)
   * @param $contact
   * @param bool $isAccepted
   * @return bool|mixed
   */
  private function createTasksForContact($contact, $isAccepted = true){
    /** @var Contacts $contact */
    $result = false;
    if($isAccepted){
      $subject = 'Требуется акцептация скидок фабрики "' . $contact->getContactName() . '"';
      $body = $this->renderView('TretoPortalBundle:Portal:accepted.html.twig', array('objContact' => $contact));
    }
    else {
      $subject = 'Окончание срока действия скидки фабрики "' . $contact->getContactName() . '"';
      $body = $this->renderView('TretoPortalBundle:Portal:prolangate.html.twig', array('objContact' => $contact));
    }

    /** @var Portal $empl */
    $empl = $this->getRepo('Portal')->findOneBy(['contactUnid' => $contact->GetResponsibleManager_ID()]);
    if($empl){
      /** @var \Treto\PortalBundle\Services\RoboService $robo */
      $robo = $this->get('service.site_robojson');
      $params = ['document' => [
          'body' => $body,
          'form' => 'formTask',
          'readSecurity' => [$empl->GetLogin()],
          'status' => 'open',
          'subject' => $subject,
          'taskPerformerLat' => $empl->GetLogin(),
          'taskPerformerLatType' => 'logins'
      ]];

      $result = $robo->setTask($params);
    }

    return $result;
  }

  public function getRobotPortal () {
    return $this->get('fos_user.user_manager')->findUserBy(['username' => \Treto\UserBundle\Document\User::ROBOT_PORTAL]);
  }

  public function exportContactsTo1CAction() {
    if(!in_array($this->getRequest()->getClientIp(), ['127.0.0.1','::1','fe80::1'])) {
      // return $this->fail('this action is for localhost only', ['ip' => $this->getRequest()->getClientIp()]);
    }
    $result = $this->exportContactsTo1C();
    return $this->success($result);
  }

  /**
   * @param int $limit
   * @return array
   */
  public function exportContactsTo1C($limit = 30) {
    $strLogSubject = 'Экспорт контактов в 1С';
    $boolStatus = true;
    $objDM = $this->getDM();
    /** @var \Treto\PortalBundle\Services\ExporterTo1C $objExporterTo1C */
    $objExporterTo1C = $this->get('exporterto1c');

    $arrContacts = $objExporterTo1C->getContactsForSend($limit);
    $intCountContacts = count($arrContacts);

    if ($intCountContacts > 0){
      /** @var Contacts $objItem */
      foreach ($arrContacts as $objItem) {
        //Protection against resubmission
        $objItem->SetC1WaitSync('5');
        $objDM->persist($objItem);
      }

      $objDM->flush();
      $objExporterTo1C->addLogText("Найдено контактов для синхронизации: $intCountContacts");
      $arrItems = [];
      foreach ($arrContacts as $objItem) {
        /** @var Contacts $objItem */
        $item = $objItem->getDocument();
        $item['ReadingChange'] = '1'; //1C status edit
        $item = $objExporterTo1C->createSoapParams($item);
        $arrItems[] = $item;
      }

      if(!$objExporterTo1C->connectToSoap($this->container->getParameter('c1_sotr_host'))){
          return ['success' => false, 'strLogSubject' => $strLogSubject, 'strLogText' => $objExporterTo1C->getLogText()];
      }

      $jsonItems = json_encode(['ArrayContacts' => $arrItems], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
      $strResponse = $objExporterTo1C->sendTo1C('GetListOfEmployees', $jsonItems,  "СтрокаJSON");
      if(!$strResponse){
        foreach ($arrContacts as $objItem) {
            /** @var $objItem Contacts */
            $objItem->SetLastFailSynch(date('Ymd\THis'));
            $objDM->persist($objItem);
            $objDM->flush();
        }
        return ['success' => false, 'strLogSubject' => $strLogSubject, 'strLogText' => $objExporterTo1C->getLogText()];
      }

      $result = json_decode($strResponse->return, true);
      $objExporterTo1C->addLogText("Ответ:\n".$strResponse->return);
      $d = new \DateTime();
      $d->add(new \DateInterval('PT1H'));
      foreach ($arrContacts as $objItem) {
        $objItem->SetXml1CResponse($result[$objItem->getUnid()]['C1_Description']);
        $objItem->SetModify1C($d->format('Ymd') . 'T' . $d->format('Gis'));
        $objItem->SetC1WaitSync($result[$objItem->getUnid()]['C1_Result']);
        $objDM->persist($objItem);
      }

      $objExporterTo1C->addLogText('Отправка прошла успешно.');
      $objExporterTo1C->writeLogText($strLogSubject);

      foreach ($arrContacts as $objItem) {
        if($objItem->GetC1WaitSync() == '5'){
          $objItem->SetC1WaitSync('0');
          $objDM->persist($objItem);
        }
      }
    }
    else {
      $objExporterTo1C->addLogText('Не найдено контактов для синхронизации.');
      $boolStatus = false;
    }

    $objDM->flush();

    return ['success' => $boolStatus, 'strLogSubject' => $strLogSubject, 'strLogText' => $objExporterTo1C->getLogText()];
  }

  /**
   * Check and create task from end test period
   * @return JsonResponse
   */
  public function checkTestPeriodAction(){
    $logger = $this->get('monolog.logger.autotask');
    $logger->info('Run autotask command; type = checkTestPeriodAction');
    $repPortal = $this->getRepo('Portal');
    $adapts = $repPortal->findBy(["form" => "formAdapt", "TestPeriod" => date('Ymd')]);
    $people = [];

    $robo = $this->get('service.site_robojson');
    /** @var \Treto\PortalBundle\Services\RoboService $robo */
    $hr = $robo->getAutoTaskPersonByKey('Рекрутер');

    foreach ($adapts as $adapt) {
      $logger->info('Create test period task for '.$adapt->GetLogin());
      $subject = "Завершился испытательный срок ".$adapt->GetFullNameInRus();
      $body = "У сотрудника ".$adapt->GetFullNameInRus()." завершился испытательный срок!<br><b>Принимаем или нет?</b>";
      /** @var Portal $empl */
      $empl = $repPortal->findOneBy(['Login' => $adapt->GetLogin(), 'form' => 'Empl']);

      if($empl && (!$empl->GetDtDismiss() || strtotime($empl->GetDtDismiss()) > time())){
        $robo->setTask(['document' => [
            'body' => $body,
            'form' => 'formTask',
            'readSecurity' => [$hr],
            'status' => 'open',
            'subject' => $subject,
            'taskPerformerLat' => $hr,
            'taskPerformerLatType' => 'logins',
            'subjectID' => $adapt->GetUnid(),
            'parentID' => $adapt->GetUnid(),
        ]]);

        $people[] = $adapt->GetLogin();
      }
    }

    return $this->success(["count" => count($adapts), "tasks" => $people]);
  }

  public function getEmplsUnidAction(){
    $repPortal = $this->getRepo('Portal');
    $repContacts = $this->getRepo('Contacts');
    $people = $repPortal->findBy(['form' => "Empl"]);
    $res = [];

    foreach ($people as $pep) {
      $cont = $repContacts->findOneBy([
          'FirstName' => $pep->GetName(),
          'LastName' => $pep->GetLastName(),
          'MiddleName' => $pep->GetMiddleName(),
          'form' => 'Contact',
          'DocumentType' => 'Person'
      ]);

      $res[] = [
          $pep->GetLogin(),
          $pep->GetEmail(),
          $pep->GetFullName(),
          $cont?$cont->getUnid():''
      ];
    }

    return new JsonResponse($res);
  }
}
