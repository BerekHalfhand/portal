<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Treto\PortalBundle\Document\Portal;
use Treto\PortalBundle\Services\SynchService;

class AdminController extends Controller
{
  use \Treto\PortalBundle\Services\StaticLogger;

  public function getDictionaryAction() {
    $type = $this->param('type');
    $subtype = $this->param('subtype', false);

    $repo = $this->getRepo('Dictionaries');

    $arrSearch = [
      'type' => $type
    ];
    if ($subtype)
    {
        $arrSearch['subtype.' . $subtype] = ['$exists' => true];
    }

    $records = $repo->findBy($arrSearch);
    if(! is_array($records)) {
      return $this->fail('no dictionary of such type found');
    }
    $recordsRaw = [];
    foreach($records as $r) {
      $recordsRaw[] = $r->getDocument();
    }
    if ($subtype)
    {
        $recordsRaw[] = ['key' => '2', 'value' => 'Блоги', 'subtype' => ['subscription' => 'Блоги']];
        $recordsRaw[] = ['key' => '1', 'value' => 'Новости фабрик', 'subtype' => ['subscription' => 'Новости фабрик']];
        $recordsRaw[] = ['key' => '3', 'value' => 'Обсуждение коллекций', 'subtype' => ['subscription' => 'Обсуждение коллекций']];
        $recordsRaw[] = ['key' => '46', 'value' => 'Новости портала', 'subtype' => ['subscription' => 'Новости портала']];
    }
    return $this->success(['dictionary' => $recordsRaw]);
  }

  public function addPrototypeAction(){
      $data = $this->fromJson();

      $data = isset($data['requests'])&&isset($data['requests'][0])?$data['requests'][0]['data']:[];
      $error = '';

      if(!isset($data['key']) || !isset($data['value'])){
          $error = 'Missing required field.';
      }
      else {
          $key = str_replace(',', '', $data['key']);
          $value = str_replace(',', '', $data['value']);
          $repo = $this->getRepo('Dictionaries');
          $prototypes = $repo->findOneBy(['type' => 'prototype']);
          if($prototypes){
              $values = $prototypes->getValue();
              $keys = $prototypes->getKey();

              $values = explode(', ', trim($values));
              $keys = explode(', ', trim($keys));

              if(count($values) != count($keys)){
                  $error = 'Not the same count values and keys.';
              }
              elseif(in_array($key, $keys) || in_array($value, $values)){
                  $error = 'You entered value or key already exists.';
              }
              else {
                  $values[] = $value;
                  $keys[] = $key;

                  $prototypes->setValue(implode(', ', $values));
                  $prototypes->setKey(implode(', ', $keys));
                  $this->getDM()->persist($prototypes);
                  $this->getDM()->flush();
              }
          }
          else {
              $error = 'Don\'t find prototype in Dictionaries.';
          }
      }

      return $this->success(['error' => $error ]);
  }

  public function setDictionaryAction() {
    $data = $this->fromJson();
    if(empty($data['records'])) {
      return $this->fail('no records specified');
    }
    $records = $data['records'];
    $type = $this->param('type');
    
    $dm = $this->getDM();    
    $repo = $this->getRepo('Dictionaries');
    
    $recordsInBase = $repo->findBy(['type' => $type]);
    // if(empty($recordsInBase)) {
    //   return new JsonResponse(['success' => false, 'message' => 'no dictionary of such type found', 'type' => $type]);
    // }
    $result = ['deleted'=>0,'updated'=>0,'created'=>0];
    
    foreach($records as $recKey => $r) {
      $processed = false;
      foreach($recordsInBase as $rb) {
        if(isset($r['_id']) && ($r['_id'] == $rb->getId())) {
          if(empty($r['key'])) { // DELETE
            $dm->remove($rb);
            $result['deleted']++;
            unset($records[$recKey]);
          } else { // UPDATE
            $rb->setDocument($r);
            $rb->setModified();
            $dm->persist($rb);
            $result['updated']++;
          }
          $processed = true;
          break;
        }
      }
      if(!$processed && $r['key']) { // CREATE
        $rb = new \Treto\PortalBundle\Document\Dictionaries();
        $rb->setDocument($r);
        $dm->persist($rb);
        $result['created']++;
        $records[$recKey]['_id'] = $rb->getId();
      }
      
    }
    $dm->flush();
    
    return new JsonResponse(['success'=>true,'result'=>$result, 'changed' => $records]);
  }
 
 public function setSecurityAction() {
  $id = $this->param('id');
  $data = $this->fromJson();
  if(!$id || !$data['security']) { return $this->fail('wrong input'); }
  $repository = $data['repository'] ? $data['repository'] : 'Portal';
  $repo = $this->getRepo($repository);
  $security = $data['security'];
  $fromSubjMenu = isset($data['fromSubjMenu'])&&$data['fromSubjMenu']?$data['fromSubjMenu']:false;
  $shareSecurity = isset($data['shareSecurity'])?$data['shareSecurity']:false;
  $user = $this->getUser();
  $doc = $repo->findOneBy(['_id' => $id]);
  if(!$doc) { return $this->fail('document not found'); }
  if(! $doc instanceof \Treto\PortalBundle\Document\SecureDocument) {
    return $this->fail('document is not a SecureDocument');
  }

  if(empty($security) || !isset($security['privileges'])) {
    return $this->fail('document must contain security.privileges');
  }
  
  if (!$user->can('read',$doc)) return $this->fail('permission denied');
  
  $currentReadPer = $doc->getPermissionsByType('read');
  $readRoles = array();
  $readPer = array();
  if (isset($currentReadPer['role'])) $readRoles = $currentReadPer['role'];
  if (isset($currentReadPer['username'])) $readPer = $currentReadPer['username'];
  
  $currentSubscribedPer = $doc->getPermissionsByType('subscribed');
  $subscribedPer = array();
  if (isset($currentSubscribedPer['username'])) $subscribedPer = $currentSubscribedPer['username'];
  
  $newReadPer = array();
  $newSubscribedPer = array();
  $newRoles = array();
  
  foreach($security['privileges']['read'] as $rp) {
    if (isset($rp['username']))
      array_push($newReadPer, $rp['username']);
    elseif (isset($rp['role']))
      array_push($newRoles, $rp['role']);
  }
  foreach($security['privileges']['subscribed'] as $sp) {
    if (isset($sp['username'])){
        $newSubscribedPer[] = $sp['username'];
    }
  }

  $toUnread = array_diff($readPer, $newReadPer);
  $toRead = array_diff($newReadPer, $readPer);
  $removeReadRoles = array_diff($readRoles, $newRoles);
  $addReadRoles = array_diff($newRoles, $readRoles);
  $toUnsubscribe = array_diff($subscribedPer, $newSubscribedPer);
  $toSubscribe = array_diff($newSubscribedPer, $subscribedPer);
//   file_put_contents('1.txt', print_r($toSubscribe, true));
//   file_put_contents('1.txt', print_r($toUnsubscribe, true), FILE_APPEND);
  
  $doc->SetModified();
  
  if (sizeof($toUnread)>0) {
    foreach($toUnread as $toUnreadLogin) {
      if ($toUnreadLogin != $user->getPortalData()->GetLogin() && !$user->can('write',$doc)) continue;
      
      $toUnreadEmpl = $repo->findEmplByNames([$toUnreadLogin], [$toUnreadLogin], [$toUnreadLogin]);
      if ($toUnreadEmpl) $toUnreadEmpl = $toUnreadEmpl[0];
      
      if ($toUnreadEmpl instanceof \Treto\PortalBundle\Document\SecureDocument) {
        $this->get('notif.service')->notifRemoval($doc->GetParentID(),
                                                  $doc,
                                                  $toUnreadEmpl->GetLogin(),
                                                  0,
                                                  __FUNCTION__.', '.__LINE__,
                                                  'Removed notif from');
        
        $doc->removeReadPrivilege($toUnreadLogin, $user->getPortalData()->GetLogin());
      }
    }
  }
  
  if (sizeof($toUnsubscribe)>0) {
    foreach($toUnsubscribe as $toUnsubscribeLogin) {
      $doc->removeSubscribedPrivilege($toUnsubscribeLogin, $user->getPortalData()->GetLogin());
    }
  }

  $doc->addActionPrivileges('read', $toRead, $user->getPortalData()->GetLogin());
  $doc->addActionPrivileges('subscribed', $toSubscribe, $user->getPortalData()->GetLogin());
  
  if($user->can('write',$doc)) {
    if (sizeof($removeReadRoles)>0) {
      foreach($removeReadRoles as $removeRole) {
        $doc->removeActionPrivilege('read', 'role', $removeRole, $user->getPortalData()->GetLogin());
      }
    }
    if (sizeof($addReadRoles)>0) {
      foreach($addReadRoles as $addRole) {
        $doc->addActionPrivilege('read', 'role', $addRole, $user->getPortalData()->GetLogin());
        if ($addRole == 'all') {
          $this->addSubscribed($doc, $doc, $user->getPortalData()->GetLogin());
        }
      }
    }
  }

  if ($doc->GetForm() == 'formVoting' && $doc->GetStatus() == 'open') {
    $ps = $doc->getPermissionsByType('subscribed');
    $empls = $repo->findEmplByNames($ps['username'], $ps['username'], $ps['username']);
    $answers = $doc->GetAnswers();
    $users = [];
    foreach($empls as $empl) {
      if ($empl->GetLogin() == $user->getPortalData()->GetLogin()) {
        continue;
      }
      if (empty($answers) || !array_key_exists($empl->GetLogin(), $answers)) {
        $users[] = $empl->GetLogin();
      }
    }
    $this->get('notif.service')->notifMultipleAdding($doc,
                                                     $doc,
                                                     $users,
                                                     0,
                                                     __FUNCTION__.', '.__LINE__,
                                                     'Added notif to');
  }

  $response = ['security' => $doc->GetSecurity()];

  if($repository == 'Portal' && $shareSecurity && $fromSubjMenu){
    /** @var SynchService $synchService */
    $synchService = $this->get('synch.service');
    /** @var $doc Portal */
    $oldShareSecurity = $doc->getShareSecurity();
    $doc->setShareSecurity($shareSecurity);
    $response['shareSecurity'] = $doc->getShareSecurity();
    $synchService->checkShare($doc, $doc, $this->getRequest()->getHost(), false, $oldShareSecurity);
  }

  $this->getDM()->persist($doc);
  $this->getDM()->flush();
  $this->getDM()->clear();

  return $this->success($response);
 }

  public function getC1LogsAction(Request $request){
    $data = json_decode($request->getContent(), true);
    $offset = isset($data['offset'])?(int)$data['offset']:0;
    $limit = isset($data['limit'])&&(int)$data['limit']?(int)$data['limit']:10;
    $C1Repo = $this->getRepo('C1Log');
    $docs = $C1Repo->findBy([], ['_id' => 'DESC'], $limit, $offset);
    $result = [];
    foreach ($docs as $doc) {
      $result[] = $doc->getDocument();
    }
    return $this->success(['docs' => $result]);
  }
}
