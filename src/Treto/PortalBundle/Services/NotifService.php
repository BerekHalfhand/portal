<?php
namespace Treto\PortalBundle\Services;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Treto\PortalBundle\Document\Portal;
use \Treto\PortalBundle\Document\Contacts;
use \Treto\PortalBundle\Document\Notif;

class NotifService
{
  private $container;
  private $logger;
  private $portalRepo;
  private $notifRepo;

  public function __construct(ContainerInterface $container){
    $this->container = $container;
    $this->logger = $this->container->get('monolog.logger.notif_logger');
    $this->portalRepo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
    $this->notifRepo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Notif');
  }
  
  /** @return \Doctrine\ODM\MongoDB\DocumentManager */
  public function getDM() {
    return $this->container->get('doctrine.odm.mongodb.document_manager');
  }
  
  public function ioNotifyUsers($input, $location = null) {
    if (empty($input))
    {
      return ['success' => false, 'message' => 'wrong input'];
    }
    
    $userList = array();
    $users = array();
    
    if (is_array($input)) {
      $userList = $input;
    } else {
      $userList = [$input];
    }
    
    foreach($userList as $username) {
      $notifArr = [];
      $notifs = $this->notifRepo->findBy(['receiver' => $username, 'status' => 'active']);
      
      foreach($notifs as $notif) {
        $notifArr[$notif->GetParentUnid()] = $notif->toArray();
      }
      
      $users[$username] = $notifArr;
    }
//         file_put_contents('1.txt', print_r($users, true));

    $nodeService = $this->container->get('node.service');
    $this->logger->notice($location.' : Users notified', $userList);

    return $nodeService->ioNotifyUsers(['users' => $users]);
  }
  
  private function logNotifEvent($notif, $type, $unid) {
    if (!is_object($notif)) return false;
    
    $now = new \DateTime();
    $stamp = $now->format('F d, Y H:i:s');
    
    $log = $notif->GetLog();
    $newEntry = ['type'=>$type, 'unid'=>$unid, 'time'=>$stamp];
    array_push($log, $newEntry);
    $notif->SetLog($log);
    
    return $notif;
  }
  
    public function formatNotifItem($parent, $doc, $notifNew) {
      if($parent->GetSendShareFrom() && $parent->GetShareAuthorLogin()){
        /** @var $notifNew Notif */
        $notifNew->SetSendShareFrom($parent->GetSendShareFrom());
        $notifNew->SetShareAuthorLogin($parent->GetShareAuthorLogin());
      }

      $notifNew->SetAuthor($parent->GetAuthor());
      $notifNew->SetAuthorLogin($parent->GetAuthorLogin());

      return $notifNew;
    }

    public function formatNotifContact($parent, $doc, $notifNew) {
      $notifNew->SetSubject($parent->GetContactName() ? $parent->GetContactName() : $parent->GetFullName());
      $notifNew->SetForm('Contact');
      $notifNew->SetDocumentType($parent->getDocumentType());
      $notifNew->SetAuthor(isset($parent->GetAuthor()[0]) ? $parent->GetAuthor()[0] : '');
      $notifNew->SetAuthorLogin(isset($parent->GetAuthor()[0]) ? $parent->GetAuthor()[0] : '');
            
      return $notifNew;
    }
    
    public function formatNotifProfile($parent, $doc, $notifNew, $fields) {
      $notifNew->SetSubject(isset($additionalData['FullNameInRus']) ? $additionalData['FullNameInRus'] : $parent->GetFullNameInRus());
      $notifNew->SetAuthor($doc->GetLastName().' '.$doc->GetName());
      $notifNew->SetAuthorLogin($doc->GetLogin());
      $notifNew->SetFields($fields);
            
      return $notifNew;
    }

    private function addNotif($parent,
                              $doc,
                              $userLogin,
                              $urgency = 0,
                              $from = null,
                              $flag = null,
                              $additionalData = null) {
                              
    if (!is_object($parent) || !is_object($doc)) return false; //wrong input
                             
      $notifNew = new Notif($parent->GetUnid(), $doc->GetUnid(), $userLogin, $urgency);
      $notifNew->SetSubject($parent->GetSubject());
      $notifNew->SetModified($doc->GetModified());
      $notifNew->SetCreated($parent->GetCreated());
      $notifNew->SetEntryOrder($doc->GetModified());
      $notifNew->SetFlag($flag);
      $notifNew->SetAddedWhen();
      $notifNew->SetAddedFrom($from);
      $notifNew->SetForm($doc->GetForm());
      $notifNew->SetParentForm($parent->GetForm());
      $notifNew->SetIsPublic($parent->hasReadPrivilegeFor('all', true));
      $notifNew->SetDocs([$doc->GetUnid() => ['urgency' => $urgency, 'subject' => $doc->GetSubject(), 'timestamp' => $doc->GetModified()]]);
      
      if ($parent instanceof Contacts) {
        $notifNew = $this->formatNotifContact($parent, $doc, $notifNew);
      } else {
        switch($doc->GetForm()) {
          case 'Empl':
            $notifNew = $this->formatNotifProfile($parent, $doc, $notifNew, $additionalData);
            break;
          default:
            $notifNew = $this->formatNotifItem($parent, $doc, $notifNew);
        }
      }

//       file_put_contents('1.txt', $this->GetLogin().'++'.print_r($d, true), FILE_APPEND);
      $notifChanged = false;
      $notifOld = $this->notifRepo->findOneBy(['receiver' => $userLogin, 'parentUnid' => $parent->GetUnid()]);

      if (empty($notifOld)) {
        $notifChanged = true;
//         $notifNew = $this->logNotifEvent($notifNew, 'add', $doc->GetUnid());
        $this->getDM()->persist($notifNew);
      } else {
        $notifOld->SetStatus($notifNew->GetStatus());
        $notifOld->SetModified($notifNew->GetModified());
        $notifOld->SetSubject($notifNew->GetSubject());
        $notifOld->SetEntryOrder($notifNew->GetAddedWhen());
        $notifOld->SetIsPublic($notifNew->GetIsPublic());
        $notifOld->SetAddedWhen($notifNew->GetAddedWhen());
        $notifOld->SetAddedFrom($notifNew->GetAddedFrom());
        $notifOld->SetFlag($notifNew->GetFlag());

        if ($notifOld->GetUrgency() < $notifNew->GetUrgency()) {  //escalate urgency if needed
          $notifChanged = true;
          $notifOld->SetUrgency($notifNew->GetUrgency());
        }

        $docs = $notifOld->GetDocs();
        if (empty($docs)) $docs = [];

        if (!array_key_exists($notifNew->GetUnid(), $docs)) {
          $docs = array_merge($docs, $notifNew->GetDocs());
          $notifChanged = true;
        } else {
          if ($docs[$notifNew->GetUnid()]['urgency'] < $notifNew->GetUrgency()) {
            $notifChanged = true;
            $docs[$notifNew->GetUnid()]['urgency'] = $notifNew->GetUrgency();
          }
          
          $docs[$notifNew->GetUnid()]['timestamp'] = $notifNew->GetModified();
          $docs[$notifNew->GetUnid()]['subject'] = $notifNew->GetDocs()[$notifNew->GetUnid()]['subject'];
        }
        $notifOld->SetDocs($docs);
        
        $fields = $notifNew->GetFields();
        if (!empty($fields)) {
          $notifOld->SetFields(array_merge($notifOld->GetFields(), $fields));
        }
        
//         if ($notifChanged) $notifOld = $this->logNotifEvent($notifOld, 'add', $doc->GetUnid());
        $this->getDM()->persist($notifOld);
      
      }

      $this->getDM()->flush();
      $this->getDM()->clear();
      return $notifChanged;
    }

    private function removeNotif($parentUnid,
                                 $unid,
                                 $userLogin,
                                 $removeUrgent,
                                 $timestamp = false) {
                                
      $notifChanged = false;
      if (!$parentUnid || strlen($parentUnid) == 0) $parentUnid = $unid;
      
      $this->logger->debug('removeNotif for '.$userLogin, [ '$parentUnid = '.$parentUnid,
                                                            '$unid = '.$unid,
                                                            '$removeUrgent = '.$removeUrgent,
                                                            '$timestamp = '.$timestamp]);
      
      $notifOld = $this->notifRepo->findOneBy(['receiver' => $userLogin, 'parentUnid' => $parentUnid]);
      if (empty($notifOld)) $notifOld = $this->notifRepo->findOneBy(['receiver' => $userLogin, 'unid' => $unid]);

      if(!empty($notifOld) && is_object($notifOld)) {
        if ($notifOld->GetStatus() == 'inactive') return false;
        
        $notifUrgency = -1;
        $docs = $notifOld->GetDocs();
        
        if (!empty($docs)) {
          
          foreach($docs as $j => $doc) {
            if (($j == $unid || $doc['urgency'] <= 0) && ($doc['urgency'] <= $removeUrgent)) {
              if (!$timestamp || ($doc['timestamp'] <= $timestamp)) { //is possible to remove
                $this->logger->debug('REMOVING notif for '.$userLogin, ['$parentUnid = '.$parentUnid,
                                                                        '$unid = '.$unid,
                                                                        '$timestamp = '.$timestamp,
                                                                        '$doc[\'timestamp\'] = '.$doc['timestamp'],
                                                                        '$doc[\'urgency\'] = '.$doc['urgency']]);
                unset($docs[$j]);
                $notifChanged = true;
              } else {
                $this->logger->debug('FAILED TO REMOVE notif for '.$userLogin, ['$parentUnid = '.$parentUnid,
                                                                                '$unid = '.$unid,
                                                                                '$timestamp = '.$timestamp,
                                                                                '$doc[\'timestamp\'] = '.$doc['timestamp'],
                                                                                '$doc[\'urgency\'] = '.$doc['urgency']]);
              }
            }
          }
          
          foreach($docs as $j => $doc) {
            if ($doc['urgency'] > $notifUrgency) {
              $notifUrgency = $doc['urgency'];
            }
          }
          
          $notifOld->SetUrgency($notifUrgency);
          
          if (($notifUrgency <= 0 || $removeUrgent > 2) && empty($docs)){
            $notifChanged = true;
            $notifOld->SetStatus('inactive');
          }
          
        } else {
          $isOutdated = ($timestamp && $notifOld->GetEntryOrder() > $timestamp) ? true : false;
          if (($notifOld->GetUrgency() <= $removeUrgent) && !$isOutdated) {
            $notifChanged = true;
            $notifOld->SetStatus('inactive');
          }
        }

        $notifOld->SetDocs($docs);
//         if ($notifChanged) $notifOld = $this->logNotifEvent($notifOld, 'remove', $unid);
        $this->getDM()->persist($notifOld);
        $this->getDM()->flush();
        $this->getDM()->clear();
      }
      
      return $notifChanged;
    }

    public function delayNotif($unid, $userLogin, $time = 30) {
      $now = new \DateTime();
      $now->add(new \DateInterval('PT' . $time . 'M'));
      $stamp = $now->format('F d, Y H:i:s');
      
      $notif = $this->notifRepo->findOneBy(['receiver' => $userLogin, 'parentUnid' => $unid, 'status' => 'active']);

      if (!empty($notif)) {
        $notif->SetNotifyWhen($stamp);
//         $notif = $this->logNotifEvent($notif, 'remove', $unid);
        $this->getDM()->persist($notif);
        $this->getDM()->flush();
        $this->getDM()->clear();
        
        $this->logger->info('Delayed notif for '.$userLogin, [$unid]);
        $this->ioNotifyUsers($userLogin, __FUNCTION__.', '.__LINE__);
      }

      return true;
    }

    public function hasNotif($parentUnid, $userLogin, $inUrgentOnly = false) {
      $notif = $this->notifRepo->findOneBy(['receiver' => $userLogin, 'parentUnid' => $parentUnid, 'status' => 'active']);
      if (!empty($notif)) {
        if ($inUrgentOnly && $notif->GetUrgency() <= 0)
          return false;
        
        return true;
      }
      
      return false;
    }

    public function bumpNotif($parentUnid, $userLogin) {
      $notif = $this->notifRepo->findOneBy(['receiver' => $userLogin, 'parentUnid' => $parentUnid, 'status' => 'active']);
      if (!empty($notif)) {
        $notif->SetEntryOrder();
      }
      $this->getDM()->persist($notif);
      $this->getDM()->flush();
      $this->getDM()->clear();
    }

    public function unurgeNotif($parentUnid, $unid, $userLogin, $location = null) {
      $notifChanged = false;
      $notif = $this->notifRepo->findOneBy(['receiver' => $userLogin, 'parentUnid' => $parentUnid, 'status' => 'active']);
      
      if(!empty($notif)) {
        $notifUrgency = 0;
        $docs = $notif->GetDocs();
        if (sizeof($docs) > 0) {
          foreach($docs as $j => $doc) {
            if ($j === $unid) {
              if ($docs[$j]['urgency'] > 0) {
                $notifChanged = true;
                $docs[$j]['urgency'] = 0;
              }
            }
          }

          foreach($docs as $j => $doc) {
            if ($doc['urgency'] > 0) {
              $notifUrgency = $doc['urgency'];
            }
          }

          if ($notifUrgency <= 0) {
            if ($notif->GetUrgency() > 0) {
              $notifChanged = true;
              $notif->SetUrgency(0);
            }
          }
          
          $notif->SetDocs($docs);
        } else {
          if ($notif->GetUrgency > 0) {
            $notifChanged = true;
            $notif->SetUrgency(0);
          }
        }
        
        if ($notifChanged) {
          $this->getDM()->persist($notif);
          $this->getDM()->flush();
          $this->getDM()->clear();
          $this->logger->info('Unurged notif for '.$userLogin.' at '.$location, [$parentUnid, $unid]);
          $this->ioNotifyUsers($userLogin, $location);
        } else {
          $this->logger->info('Couldn\'t unurge notif for '.$userLogin.' at '.$location, [$parentUnid, $unid]);
        }
      }

      return $notifChanged;
    }

  public function notifRemoval($main, $doc, $user, $urgency, $location = null, $logEntry = null, $timestamp = null, $silent = false) {
    if ((empty($main) || !is_object($main) && !is_string($main)) ||
        (empty($doc) || !is_object($doc) && !is_string($doc))) return false; //wrong input

    if (is_string($main)) {
      $mainObj = $this->portalRepo->findOneBy(['unid' => $main]);
      $mainUnid = $main;
    } else $mainObj = $main;
    if (is_string($doc)) {
      $docObj = $this->portalRepo->findOneBy(['unid' => $doc]);
      $docUnid = $doc;
    } else $docObj = $doc;

    $log_data = [];
    if (!empty($docObj)) {
      $docUnid = $docObj->GetUnid();
      $log_data[] = $docUnid;
      $log_data[] = $docObj->GetSubject();
    }
    if (!empty($mainObj)) {
      $mainUnid = $mainObj->GetUnid();
      $log_data[] = $mainUnid;
      $log_data[] = $mainObj->GetSubject();
    }
    
    $notifChanged = false;
    
    $userLogin = is_string($user)?$user:$user->GetLogin();
    $userNotif = $this->notifRepo->findBy(['receiver' => $userLogin, 'status' => 'active']);
    
    $this->logger->info('Current notifCount = '.sizeof($userNotif).', for '.$userLogin);
    $notifChanged = $this->removeNotif($mainUnid, $docUnid, $userLogin, $urgency, $timestamp);
      
    if ($notifChanged) {
      $this->logger->info($logEntry.' '.$userLogin.' at '.$location, $log_data);
      if (!$silent) $this->ioNotifyUsers($userLogin, $location);
    } else {
      $this->logger->info('Nothing to remove from '.$userLogin.' at '.$location, $log_data);
    }
    
    return $notifChanged;
  }
  
  public function notifAdding($main, $doc, $user, $urgency = 0, $location, $logEntry = null, $flag = null, $additionalData = null) {
    if ((empty($main) || !is_object($main) && !is_string($main)) ||
        (empty($doc) || !is_object($doc) && !is_string($doc))) return false; //wrong input
        
    if (is_string($main)) {
      $mainObj = $this->portalRepo->findOneBy(['unid' => $main]);
      $mainUnid = $main;
    } else $mainObj = $main;
    if (is_string($doc)) {
      $docObj = $this->portalRepo->findOneBy(['unid' => $doc]);
      $docUnid = $doc;
    } else $docObj = $doc;

    $log_data = [];
    if (!empty($docObj)) {
      $log_data[] = $docObj->GetUnid();
      $log_data[] = $docObj->GetSubject();
    }
    if (!empty($mainObj)) {
      $log_data[] = $mainObj->GetUnid();
      $log_data[] = $mainObj->GetSubject();
    }

    $notifChanged = false;

    $userLogin = is_string($user)?$user:$user->GetLogin();
    $userNotif = $this->notifRepo->findBy(['receiver' => $userLogin, 'status' => 'active']);

    $this->logger->info('Current notifCount = '.sizeof($userNotif).', for '.$userLogin);
    $notifChanged = $this->addNotif($mainObj, $docObj, $userLogin, $urgency, $location, $flag, $additionalData);
      
    if ($notifChanged) {
      $this->logger->info($logEntry.' '.$userLogin.' at '.$location, $log_data);
      $this->ioNotifyUsers($userLogin, $location);
      
//     /** @var \Treto\PortalBundle\Services\MailService $mailService */
//     $mailService = $this->container->get('mail.service');
//     $mailService->sendEmail('noreply@tile.expert', 'jdrobkov@treto.ru', '1', '2');
      
    } else {
      $this->logger->info('Nothing to add to '.$userLogin.' at '.$location, $log_data);
    }
    
    return $notifChanged;
  }
  
  public function notifMultipleAdding($main, $doc, $users, $urgency = 0, $location, $logEntry = null, $flag = null, $additionalData = null) {
    if (!is_object($main) && !empty($main)) $main = $this->portalRepo->findOneBy(['unid' => $main]);
    if (!is_object($doc) && !empty($doc)) $doc = $this->portalRepo->findOneBy(['unid' => $doc]);

    $log_data = [$doc->GetUnid(), $doc->GetSubject(), $main->GetUnid(), $main->GetSubject()];
    $usersList = '';
    $added = 0;
    $users = array_unique($users);
    
    foreach($users as $userLogin) {
      $userNotif = $this->notifRepo->findBy(['receiver' => $userLogin, 'status' => 'active']);
        $usersList = $usersList.$userLogin.', ';
        $this->addNotif($main, $doc, $userLogin, $urgency, $location, $flag, $additionalData);
        $added++;
    }
    
    if (strlen($usersList) > 0) {
      $this->logger->info($logEntry.' '.$usersList.' at '.$location, $log_data);
      $this->ioNotifyUsers($users, $location);
    }
    
//         /** @var \Treto\PortalBundle\Services\MailService $mailService */
//     $mailService = $this->container->get('mail.service');
//     $mailService->sendEmail('noreply@tile.expert', 'jdrobkov@treto.ru', '1', '2');
    
    return $added;
  }
}
