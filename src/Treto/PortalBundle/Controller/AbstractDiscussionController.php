<?php

namespace Treto\PortalBundle\Controller;

use MongoDBODMProxies\__CG__\Treto\PortalBundle\Document\Contacts;
use MongoDBODMProxies\__CG__\Treto\PortalBundle\Document\Portal;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Treto\PortalBundle\Document\Dictionaries;
use Treto\PortalBundle\Services\RoboService;
use \Treto\PortalBundle\Services\StaticLogger;
use \Treto\PortalBundle\Document\SecureDocument;
use Treto\UserBundle\Document\User;

abstract class AbstractDiscussionController extends Controller
{
  /* ========== COMMON DISCUS FUNCTIONS ========== */
  
  /** Get parent doc for the specified doc */
  public function getMainDocFor($doc, $secureRepo = false, $forceContact = false) {
    /** @var $doc \Treto\PortalBundle\Document\Portal */
    if ($doc) {
      $db = $forceContact || $doc->GetParentDbName() == 'Contacts' ? 'Contacts' : 'Portal';
      $repo = $secureRepo ? $this->getSecureRepo($db) : $this->getRepo($db);
      if($doc->GetSubjectID() && ($doc->GetSubjectID() != $doc->GetUnid())) {
        $result = $repo->findOneBy(array('unid' => $doc->GetSubjectID()));
        if(!$result){
          $db = $db == 'Contacts'?'Portal':'Contacts';
          $repo = $secureRepo ? $this->getSecureRepo($db) : $this->getRepo($db);
          $result = $repo->findOneBy(array('unid' => $doc->GetSubjectID()));
        }

        return $result;
      }
    }
    return null;
  }
  
  /** Get repo and find one by any id (unid or _id) */
  public function getRepoAndFindOneByAnyId($repoNameOrObject, $anyId, $secureRepo = false) { // DEPRECATED, use findDoc instead
    if(is_string($repoNameOrObject)) {
      $repoNameOrObject = $secureRepo ? $this->getSecureRepo($repoNameOrObject) : $this->getRepo($repoNameOrObject);
    }
    return $this->isUnid($anyId) ? $repoNameOrObject->findOneBy(['unid' => $anyId]) : $repoNameOrObject->find($anyId);
  }
  
  public function findDoc($id, $explicitRepo = null) {
    $document = null;
    $repos = $explicitRepo ? [$explicitRepo] : ['Portal', 'Contacts'];
    foreach ($repos as $repo) {
      $document = $this->GetRepo($repo)->findOneBy(['$or' => [['unid' => $id], ['_id' => $id]]]);
      if ($document) break;
    }
    return $document;
  }

  public function processChecker($main, $docInBase, $checker, $toAdd) {
    $result = [];
    
    if(isset($checker)) {
      $result[] = __FUNCTION__.': checker is '.$checker->GetLogin();
      if ($toAdd === 'add') {
        $this->get('notif.service')->notifAdding($main,
                                                 $docInBase,
                                                 $checker->GetLogin(),
                                                 1,
                                                 __FUNCTION__.', '.__LINE__,
                                                 'Added urgent-1 notif to');
      } else {
        if ($this->get('notif.service')->hasNotif($main->GetUnid(), $checker->GetLogin())) {
          $this->get('notif.service')->notifRemoval($main,
                                                    $docInBase,
                                                    $checker->GetLogin(),
                                                    1,
                                                    __FUNCTION__.', '.__LINE__,
                                                    'Removed urgent-1 notif from');
        }
      }
      
      $this->getDM()->persist($checker);
      $this->getDM()->flush();
      $this->getDM()->clear();
    }
    return $result;
  }
  
  public function processEditDocAfter($doc, $docOrig, $main, $performer, $fieldsChanged) {
    if(!$fieldsChanged || isset($fieldsChanged['AttachedDoc']) ) { return []; }
    
    if(isset($fieldsChanged['status']) && $doc->GetStatus() == 'deleted') {
      return $this->processEditDocAfter_deleted($doc, $docOrig, $main, $fieldsChanged);
    }
    
    switch($doc->GetForm()) {
      case 'formTask':
        return $this->processEditDocAfter_formTask($doc, $docOrig, $main, $performer, $fieldsChanged);
    }
    return [];
  }
  
  public function processEditDocAfter_deleted($doc, $docOrig, $main, $fieldsChanged) {
    $result = [];
    $result['users processed'] = 0;
    $repo = $this->getRepo('Portal');
    $users = $repo->findBy(['form' => 'Empl', '$or' => [['DtDismiss'=>''], ['DtDismiss' => ['$exists'=>false]]]]);
    
    foreach($users as $user) {
      $result['users processed']++;
      $this->get('notif.service')->unurgeNotif($main->GetUnid(), $doc->GetUnid(), $user->GetLogin());
    }
    
    return $result;
  }
  
  public function processEditDocAfter_formTask($doc, $docOrig, $main, $oldPerformer = false, $fieldsChanged) {
//     file_put_contents('1.txt', print_r($doc, true), FILE_APPEND);
    $result = [];
    $result['authorNotified'] = $this->addUnreadedToAuthor($main, $doc, true, $fieldsChanged);

    return $result;
  }
  
  public function isCompleted($t) {
    $history = $this->getSecureRepo('TaskHistory')->findBy(['taskId'=>$t->GetId()], array('created' => "ASC"));
    $completed = false;
    foreach($history as $h){
      if ($h->getType() == 'completed') $completed = true;
      if ($h->getType() == 'reject' || $h->getType() == 'taskPerformer') $completed = false;
    }

    return $completed;
  }
  
  /* ========== NOTIFICATIONS FUNCTIONS ========== */

    public function processNotifications(
     SecureDocument $parent,
     SecureDocument $doc,
     $notifyAllParticipants = false,
     $removeFromMyself = false,
     $silent = false,
     $readAt = false,
     $createContact = false
  ){
      $robo = $this->get('service.site_robojson');

      if($this->getUser()){
        $user = $this->getUser();
      }
      else {
        $user = $this->get('fos_user.user_manager')->findUserBy([
            'username' => \Treto\UserBundle\Document\User::ROBOT_PORTAL
        ]);
      }

      /** @var RoboService $robo */
      $result = $robo->processNotifications(
          $parent,
          $doc,
          $user,
          $notifyAllParticipants,
          $removeFromMyself,
          $silent,
          $readAt,
          $createContact
      );

    return $result;
  }

  public function addUnreadedToAuthor($main, $doc, $andParticipant = false, $fieldsChanged = []) {
    /** @var RoboService $robo */
    $robo = $this->get('service.site_robojson');
    return $robo->addUnreadedToAuthor($main, $doc, $this->getUser(), $andParticipant, $fieldsChanged);
  }
  
  public function notifyMentioned($parent, $doc, $mentions) {
    return $this->get('service.site_robojson')->notifyMentioned($parent, $doc, $mentions);
  }
  
  /** ========== convert discussion ids to discussion subjects ========== */
  public function idToSubjectAction() {  //Should be DEPRECATED in due course
      $data = $this->fromJson();
      if(!isset($data['ids'])) { return $this->fail('wrong input'); }
      $ids = [];
      $unids = [];
      $subjects = [];
      foreach($data['ids'] as $id) {
        if(is_array($id)) { $id = reset($id); }
        if(!$id) { continue; }
        if($this->isUnid($id)) {
          $unids[] = $id;
        } else {
          $ids = $id;
        }
      }      
      $portal_rep = $this->getRepo('Portal');
      
      $portalDatasId = $portal_rep->findBy(['_id' => ['$in' => $ids]]);
      $portalDatasUnid = $portal_rep->findBy(['unid' => ['$in' => $unids]]);
      
      foreach($portalDatasId as $doc) {
        $s = $doc->getSubject() ? $doc->getSubject() : ($doc->GetSubjVoting() ? $doc->GetSubjVoting() : null);
        if($s) {
          $subjects[$doc->getId()] = $subjects[$doc->getUnid()] = $s;
        }
      }
      foreach($portalDatasUnid as $doc) {
        $s = $doc->getSubject() ? $doc->getSubject() : ($doc->GetSubjVoting() ? $doc->GetSubjVoting() : null);
        if($s) {
          $subjects[$doc->getId()] = $subjects[$doc->getUnid()] = $s;
        }
      }
      if(empty($subjects)) {
        return $this->fail('no documents with specified subjects found', ['ids' => $ids, 'unids' => $unids]);
      }
      return $this->success(['subjects' => $subjects]);
  }


  private function isNotifyAuthor($doc)
  {
    if ($this->getUserPortalData()){
      $arrNames = [$this->getUserPortalData()->GetLogin(), $this->getUserPortalData()->GetFullName(), $this->getUserPortalData()->GetFullNameInRus(), $this->getUserPortalData()->GetFullName(false)];
      return $doc->getForm() === 'formTask' && in_array($doc->GetTaskPerformerLat(true), $arrNames) && in_array($doc->GetAuthor(), $arrNames);
    }else{
      return false;
    }

  }
  
}
