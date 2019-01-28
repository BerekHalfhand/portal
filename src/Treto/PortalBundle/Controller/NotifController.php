<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Treto\PortalBundle\Document\HistoryLog;
use Treto\PortalBundle\Document\Portal;
use Treto\PortalBundle\Document\Notif;
use Treto\PortalBundle\Document\SecureDocument;

class NotifController extends Controller
{
  public function notifLoadAction() {
    $login = $this->param('login');
    if ($this->getUserPortalData()->GetLogin() != $login)
      return $this->fail('Access denied');
    
    $notifArr = [];
    $repoNotif = $this->getRepo("Notif");
    $notifs = $repoNotif->findBy(['receiver' => $login, 'status' => 'active']);
    
    foreach($notifs as $notif) {
      $notifArr[$notif->GetParentUnid()] = $notif->toArray();
    }
    
    $result = ['notif' => $notifArr];

    return $this->success($result);
  }
  
  public function notifDelayAction($id, $time) {
    if (isset($id)) {
      $login = $this->getUser()->getPortalData()->GetLogin();
      $notifSvc = $this->get('notif.service');
      $notifSvc->delayNotif($id, $login, $time);

      return $this->success();
    } else {
      return $this->fail('No unid');
    }
  }
  
  public function markAsReadAction(Request $request) {
    $data = $this->fromJson(); // source data
    $docsToRead = $data['docs'];
    $user = $this->getUserPortalData();

    $logger = $this->get('monolog.logger.notif_logger');
    $now = new \DateTime();
    $nowISO = $now->format('Ymd').'T'.$now->format('His');

    $mainDocsToLogRead = []; // will be the array of unids of themes

    foreach ($docsToRead as $doc) {
      $this->setReadByTime($doc['unid'], (isset($doc['time']) ? $doc['time'] : null), false, true);
      $this->get('notif.service')->notifRemoval($doc['parentUnid'],
                                                $doc['unid'],
                                                $user->GetLogin(),
                                                0,
                                                __FUNCTION__.', '.__LINE__,
                                                'Removed notif from',
                                                (isset($doc['timeISO']) ? $doc['timeISO'] : null),
                                                true);
      
      // save the unid of the theme (only once per theme)
      $mainDocsToLogRead[ $doc['parentUnid'] ] = $doc['parentUnid'];
    }

    // increasing the reads of each theme if marked read in notificator and do nothing in live-chat (ignore auto-read of comments those are onscreen)
    if ( (int) $data['fromNotificator'] ) {
      $readWriteLog = $this->GetRepo('MainStat')->findReadWriteLog();
      foreach ($mainDocsToLogRead as $unid) {
        $readWriteLog->LogRead( $this->GetUser()->GetUserName(), $unid );
      }
      $this->getDM()->persist($readWriteLog);
      $this->getDM()->flush();
    }
    
    $this->get('notif.service')->ioNotifyUsers($user->GetLogin(), __FUNCTION__.', '.__LINE__);
    
    return $this->success([]);
  }
}
