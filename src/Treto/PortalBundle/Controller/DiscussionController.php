<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Treto\PortalBundle\Document\Portal;
use Treto\PortalBundle\Document\Contacts;
use Treto\PortalBundle\Document\Tag;
use Treto\PortalBundle\Document\PreviousVersions;
use Treto\PortalBundle\Services\RoboService;
use Treto\PortalBundle\Services\SiteService;
use \Treto\PortalBundle\Services\StaticLogger;
use Treto\PortalBundle\Document\Guest;
use Treto\PortalBundle\Services\SynchService;
use Treto\PortalBundle\Services\TaskService;
use Treto\UserBundle\Document\User;

class DiscussionController extends AbstractDiscussionController
{
  private $recursive = [];

  public function listAction(Request $request) {
    $limit = $this->param('limit');
    $offset = $this->param('offset');
    $categories = $this->param('categories') ? explode(",", $this->param('categories')) : NULL;
    $type = $this->param('type');
    $waitPerformer = $this->param('waitperformer');
    $author = $this->param('author');
    $fromDate = $this->param('fromDate', false);
    $withComments = $this->param('withComments', false);

    $search_array = [
      'parentID' => ['$exists' => false],
      'status' => ['$ne' => 'deleted'],
      'form' => ['$nin' => ['Empl', 'WorkPlan']]
    ];

    $usr = $this->getUser();
    $portalData = $usr->getPortalData();
    $roles = $portalData->GetRole();
    
    if (!empty($usr) && !empty($portalData)) {
      $userName = $usr->getUserName();
      $search_array['$or'] = [
        ["security.privileges.read.role" => ['$in' => $roles]],
        ["security.privileges.read.username" => $userName],
      ];
    }

    if ($categories) {
      $i = 1;
      foreach ($categories as $cat) {
        if ($cat == 'new') {
          $search_array['form'] = ['$in'=>['formProcess', 'formTask', 'formVoting']];
        } else {
          $search_array["C".strval($i)] = $cat;
          ++$i;
        }
      }
    }

    if ($type) { $search_array["type"] = $type; }
    if ($author) { $search_array["Author"] = $author; }
    if ($fromDate) { $search_array["created"] = ['$gte' => $fromDate]; }
    if ($waitPerformer) { $search_array["WaitPerformer"] = "1"; $search_array["status"] = "open"; unset($search_array['parentID']); }
    if($withComments) {
      $search_array['$and'][]['countMess']['$exists'] = true;
      $search_array['$and'][]['countMess']['$ne'] = '0';
      $search_array['$and'][]['countMess']['$ne'] = 0;
      $search_array['$and'][]['countMess']['$ne'] = '';
    }
    $portals = $this->getSecureRepo('Portal')->findBy($search_array, array('created' => "DESC"), $limit, $offset);
    $themes = array();

    foreach ($portals as $theme) {
        $taskHistories = [];
        if($theme->getForm()=='formTask'){
            foreach ($this->getSecureRepo('TaskHistory')->findBy(['$or' => [['taskId'=>$theme->GetId()],['taskUnid'=>$theme->GetUnid()]]], array('created' => "ASC")) as $history){
                $taskHistories[] = $history->getDocument();
            }
        }
        /** @var $theme Portal */
        array_push($themes, array(
                'id' => $theme->getId(),
                '_id' => $theme->getId(),
                'unid' => $theme->GetUnid(),
                'subject' => $theme->getSubject(),
                'form' => $theme->getForm(),
                'created' => $theme->getCreated(),
                'modified' => $theme->getModified(),
                'author' => $theme->getAuthorRus(),
                'authorLastMess' => $theme->getAuthorLastMess(),
                'dateLastMess' => $theme->getDateLastMess(),
                'countOpen' => $theme->getCountOpen(),
                'countMess' => $theme->getCountMess(),
                'authorLogin' => $theme->GetAuthorLogin(),
                'Author' => $theme->GetAuthor(),
                // 'body' => $theme->getBody(),
                'taskHistories' => $taskHistories
        ));
    }
    return new JsonResponse($themes);
  }

  /**
   * Share and close mail
   * @return JsonResponse
   */
  public function shareMailAction(){
    $result = false;
    $commentUnid = $this->param('unid');
    $status = $this->param('status');
    /** @var Portal $commentMail */
    $commentMail = $this->getRepo('Portal')->findOneBy(["unid" => $commentUnid]);
    $dm = $this->getDM();
    if($commentMail && in_array($this->getUserPortalData()->GetLogin(), $commentMail->GetMailAccess())){
      /** @var Contacts $parentDoc */
      $parentDoc = $this->getRepo('Contacts')->findOneBy(["unid" => $commentMail->GetParentID()]);
      if($parentDoc){
        $robo = $this->get('service.site_robojson');
        $commentMail->SetMailStatus($status == 'open'?'open':'close');
        $dm->persist($commentMail);
        $dm->flush();

        /** @var $robo \Treto\PortalBundle\Services\RoboService */
        $participants = $robo->getParticipantsByUnid($parentDoc->GetUnid(), true);
        if($status == 'open' && isset($participants['result']) && $participants['result']){
          $notifyList = [];
          foreach ($participants['result'] as $participant) {
            if(!in_array($participant, $commentMail->GetMailAccess())){
              $this->get('notif.service')->notifAdding($parentDoc,
                                                       $commentMail,
                                                       $participant,
                                                       0,
                                                       __FUNCTION__.', '.__LINE__,
                                                       'Added notif to');
            }
          }
        }
      }
      $result = true;
    }

    return new JsonResponse(['success' => $result]);
  }

  public function getChainAction(Request $request) {
    $id = $this->param('id');
    $quantity = $this->param('quantity');
    $offset = $this->param('offset');
    $locale = $this->param('locale');
    $archive = $this->param('archive');
    $user = $this->getUser()->getPortalData();
    
    $from = $this->param('from');
    $to = $this->param('to');
    
    $isInitial = empty($to);
    if ($offset < 0) $offset = 0;

    $dd = $this->get('doctrine_mongodb.odm.default_configuration')->getDefaultDB();
    $db = $this->get('doctrine_mongodb.odm.default_connection')->selectDatabase($dd);
    $rep = $db->{'Portal'};

    $document = $this->findDoc($id);
    if(!$document) return $this->fail('document not found');

    $main = $document;
    if($document->HasParent()) {
      $main = $this->getMainDocFor($document);
      if(!$main) {
        return $this->fail('parent document not found or access denied');
      }
    }

    if(!$this->getUser()->can('read', $main, false) && !$this->getUser()->hasRole('PM')) {
      return $this->fail('permission denied');
    }

    $main_doc = $main->getDocument();

    $isLoaded = $main->GetUnid() == $id || $main->GetId() == $id;
    $comments = [];
    $archived = array();
    $iterator = 0;

    if ($isInitial) {

      $this->get('notif.service')->notifRemoval($main,
                                                $main,
                                                $user->GetLogin(),
                                                0,
                                                __FUNCTION__.', '.__LINE__,
                                                'Removed notif from');

      if ($main->GetUnid() != $id) {
        $searchParams = ['$or' => [
            ["subjectID"=> $main->getUnid()],
            ["parentUnid"=> $main->getUnid()],
            ["unid" => $id]]
        ];
      } else {
        $searchParams = ['$or' => [
            ["subjectID"=> $main->getUnid()],
            ["parentUnid"=> $main->getUnid()]]
        ];
      }
      
      if($locale){
        $searchParams['locale'] = $locale;
      }
      $search = $rep->find($searchParams);
      $commentsTmp = $search->sort(['created' => 1]);//->skip($offset)->limit($quantity);
      $count = $commentsTmp->count();
      
      $offset = $count - $quantity;

      $number = 0;
      $lastArchived = 0;
      $firstNonArchived = false;

      while($commentsTmp->hasNext()) {
        $commentsTmp->next();
        $cur = $commentsTmp->current();
        
        $cur['threadNum'] = ++$number;
        
        if ($number > $offset && sizeof($comments) <= $quantity) {
          array_push($comments, $cur);
          if (!$firstNonArchived) {
            $firstNonArchived = true;
            $archived[$cur['unid']]['from'] = $lastArchived;
            $lastArchived = $number;
            $archived[$cur['unid']]['to'] = $number - 1;
            $archived[$cur['unid']]['count'] = $archived[$cur['unid']]['to'] - $archived[$cur['unid']]['from'];
          }
        }
        else if (($cur['form'] == 'formTask' && $cur['status'] == 'open') || //open task
                 (!empty($cur['AttachedDoc'])) ||                            //archived post
                 ($cur['unid'] == $id)) {                                    //specific post
          array_push($comments, $cur);
          $archived[$cur['unid']]['from'] = $lastArchived;
          $lastArchived = $number;
          $archived[$cur['unid']]['to'] = $number - 1;
          $archived[$cur['unid']]['count'] = $archived[$cur['unid']]['to'] - $archived[$cur['unid']]['from'];
          $quantity++;
        }
        
        if ($cur['unid'] == $id || ((string) $cur['_id']) == $id) $isLoaded = true;
      }
    } else {
      $searchParams = [ '$or' => [
                          ["subjectID"=> $main->getUnid()],
                          ["parentUnid"=> $main->getUnid()]
                        ]
                      ];
      
      if ($to != '-1') {
        $searchParams['$and'][]['created']['$gt'] = $from;
        $searchParams['$and'][]['created']['$lt'] = $to;
      }

      if($locale){
        $searchParams['locale'] = $locale;
      }
      $search = $rep->find($searchParams);
      $commentsTmp = $search->sort(['created' => 1])->skip($offset)->limit($quantity);
      $count = 0;
      
      while($commentsTmp->hasNext()) {
        $commentsTmp->next();
        $cur = $commentsTmp->current();
        array_push($comments, $cur);
        $count++;
      }
    
    }

    usort($comments, function($a, $b)
    {
      return strcmp($a['created'], $b['created']);
    });

    if ($main->GetForm() == 'Contact'){
      $profile = $this->getRepo('Portal')->findOneBy(['name' => $main->getFirstName(),
                                                      'LastName' => $main->getLastName(),
                                                      'MiddleName' => $main->getMiddleName(),
                                                      'form' => 'Empl']);
      if (!empty($profile)){
        if (count(array_intersect(['Директорат', 'HR отдел', 'Бухгалтерия'], $this->getUser()->getPortalData()->getSection())) == 0 && !in_array($this->getUser()->getUserName(), $this->getBosses($profile))) {
          unset($main_doc['Salary']);
        }
      }

      $main_doc['Employee'] = [];
      $main_doc['EmployeeId'] = [];
      $orgEmpls = $this->getRepo('Contacts')->findBy(["OrganizationID"=>$main_doc['unid']]);

      if (count($orgEmpls) > 0 ){
        foreach ($orgEmpls as $empl) {
          $main_doc['Employee'][] = $empl->getContactName();
          $main_doc['EmployeeId'][] = $empl->getUnid();
        }
      }
    }

    $res = ['main_doc' => $main_doc, 'comments' => []];
    if($res['main_doc']['form'] === 'formTask'){
      $histories = $this->getSecureRepo('TaskHistory')->findBy(['$or' => [['taskId'=>$main->GetId()],['taskUnid'=>$main->GetUnid()]]], array('created' => "ASC"));
      foreach ($histories as $history){
        $res['main_doc']['taskHistories'][] = $history->getDocument();
      }
    }

    $commentsIds = []; $commentsUnids = []; $taskIdsNeeded = [];

    foreach($comments as $comment) {
      $comment['_id'] = (string) $comment['_id'];

      if(empty($comment['status']) || $comment['status'] != 'deleted' && (!isset($comment['mailHash']) ||
        $comment['mailStatus'] == 'open' || in_array($this->getUser()->getUserName(), $comment['mailAccess']))
      ){
        $res['comments'][] = $comment;
        $commentsIds[] = $comment['_id'];
        $commentsUnids[] = $comment['unid'];

        if($isInitial && $comment['form'] == 'messagebb' && isset($comment['taskID'])) {
          if (!in_array($comment['taskID'], $taskIdsNeeded)) $taskIdsNeeded[] = $comment['taskID'];
        }
      }
    }

    if($isInitial && sizeof($taskIdsNeeded)>0) {
      $res['tasksSubjects'] = [];
      $tasks = $this->getSecureRepo('Portal')->findBy(['unid' => ['$in' => array_values($taskIdsNeeded)]]);
      foreach($tasks as $task) {
        $res['tasksSubjects'][$task->GetUnid()]=$task->GetSubject();
      }
    }

    $histories = $this->getSecureRepo('TaskHistory')->findBy(['$or' => [['taskId' => ['$in' => $commentsIds]],['taskUnid' => ['$in' => $commentsUnids]]]], array('created' => "ASC"));
    $commentsIds = array_flip($commentsIds);
    $commentsUnids = array_flip($commentsUnids);
    foreach ($histories as $history){
      if ($history->getTaskId())
        $key = $commentsIds[$history->getTaskId()];
      else
        $key = $commentsUnids[$history->getTaskUnid()];
      if(empty($res['comments'][$key]['taskHistories'])){
        $res['comments'][$key]['taskHistories'] = [];
      }
      $res['comments'][$key]['taskHistories'][] = $history->getDocument();
    }

    $readAt = '';
    if (empty($archive)){
      if($main->GetForm() != 'Contact'){
        $main->SetCountOpen($main->GetCountOpen() + 1);
      }
      else{
          $main->SetLastaccessed();
      }

      $readAt = $this->setReadByTime($main, null, true);

      // On opening a theme increase reads by user
      $readWriteLog = $this->GetRepo('MainStat')->findReadWriteLog();
      $readWriteLog->LogRead( $this->GetUser()->GetUserName(), $main->GetUnid() );
      $this->getDM()->persist($readWriteLog);

      $this->getDM()->persist($main);
      $this->getDM()->flush();
      $this->getDM()->clear();
    }

    if(isset($res['comments'])){
      foreach ($res['comments'] as $cKey => $comment) {
        $res['comments'][$cKey] = $this->replaceOldLinks($comment);
      }
    }

    if(isset($res['main_doc'])){
      $res['main_doc'] = $this->replaceOldLinks($res['main_doc']);
    }

    return $this->success(['documents' => $res, 'readAt' => $readAt, 'countMess' => $count, 'archived' => $archived]);
  }

  /**
   * Replace old links
   * @param $comment
   * @return mixed
   */
  private function replaceOldLinks($comment){
    if(isset($comment['attachments']) && !empty($comment['attachments'])){
      foreach ($comment['attachments'] as $key => $attachment) {
        foreach ($attachment as $iKey => $item) {
          if(preg_match('#^(\/v0\/.*\/)(.*)$#Ui', $item['link'], $m) && isset($m[1]) &&  isset($m[2])){
            $comment['attachments'][$key][$iKey]['link'] = $m[1].$m[2];
          }
          if(isset($item['thumbnail']) && preg_match('#^(\/v0\/.*\/)(.*)$#Ui', $item['thumbnail'], $m) && isset($m[1]) && isset($m[2])){
            $comment['attachments'][$key][$iKey]['thumbnail'] = $m[1].$m[2];
          }
        }
      }
    }

    return $comment;
  }

  public function tasksAction(Request $request) {
    $query = json_decode(html_entity_decode(preg_replace("/%u([0-9a-f]{3,4})/i", "&#x\\1;", (urldecode(base64_decode($this->param('query'))))), null, "UTF-8"), true);
    $limit = $this->param('limit', null);
    $offset = $this->param('offset', null);
    $sort = $this->param('sort', null);
    $portal_rep = $this->getSecureRepo('Portal');

    $query_string = array("form" => "formTask", "status"=>['$nin'=>["cancelled", "deleted"]], '$or'=> [["DocType" => ""], ["DocType"=> ['$exists' => false]]]);
    if (is_array($query)) {
      foreach ($query as $key => $value) {
        if($key == 'form' || $key == 'DocType') { continue; }
        if($key == 'limit') {
          $limit = $value;
          continue;
        }
        if($key == 'offset') {
          $offset = $value;
          continue;
        }
        $query_string[$key] = $value;
      }
    }

    $portals = $portal_rep->findBy($query_string, array($sort['predicate'] => ($sort['reverse']?"DESC":"ASC")), $limit, $offset);
    $docs = [];
    foreach($portals as $p) {
      $docs[] = [
        '_id' => $p->GetId(),
        'unid' => $p->GetUnid(),
        'status' => $p->GetStatus(),
        'subject' => $p->GetSubject(),
        'Author' => $p->GetAuthor(), /* obsolete */
        'authorLogin' => $p->GetAuthorLogin(),
        'taskPerformerLat' => $p->GetTaskPerformerLat(),
        'created' => $p->GetCreated(),
        'taskDateEnd' => $p->GetTaskDateEnd(),
        'Priority' => $p->GetPriority(),
        'taskDateRealEnd' => $p->GetTaskDateRealEnd(),
        'taskDateCompleted' => $p->GetTaskDateCompleted(),
        'tags' => (sizeof($p->GetTags()) > 0 ? $p->GetTags() : []),
        'TaskStateCurrent' => $p->GetTaskStateCurrent(),
        'TaskStatePrevious' => $p->GetTaskStatePrevious(),
        'EscalationManagers' => $p->GetEscalationManagers(),
      ];
    }
    return $this->success(['documents'=>$docs]);
  }

  public function loadCommentsSince($document, $dt, $unids = [], $reverse = false) {
    $res = array();
      if ((sizeof($document->GetParentID()) > 10) && ($document->GetParentID() != $document->GetUnid())) {
        $parent = $this->getRepo('Portal')->findOneBy(['unid' => $document->GetParentID()]);
      } else {
        $parent = $document;
      }

      $comments = $this->getRepo('Portal')->findBy(['$or' => [
          ["subjectID"=> $parent->getUnid()],
          ["parentUnid"=> $parent->getUnid()],
          ["taskID"=> $document->getUnid()],
          ["unid" => ['$in' => $unids]]
      ]], array('created' => "ASC"));

      $commentsIds = [];
      $commentsUnids = [];
      $skippedMyComment = false;
      foreach($comments as $comment) {
        if ($comment->GetStatus() != 'deleted') {
        
          $comparator1 = $reverse ? strcmp($comment->GetCreated(), $dt) < 0 : strcmp($comment->GetCreated(), $dt) > 0;
          $comparator2 = $reverse ? true : $comment->GetAuthorLogin() != $this->getUserPortalData()->GetLogin();
          
          if(($comparator1 && $comparator2) || in_array($comment->GetUnid(), $unids)) {
            if($comment->GetUnid() != $document->GetUnid()) {
              $com = $comment->getDocument();

              if (!$skippedMyComment) {
                $skippedMyComment = true;
                if (isset($com['authorLogin']) &&
                    $com['authorLogin'] == $this->getUserPortalData()->GetLogin() &&
                    !in_array($com['unid'], $unids)) {
                  continue;
                }
              }

              $res[] = $com;
              $commentsIds[] = $comment->GetId();
              $commentsUnids[] = $comment->GetUnid();
            }
          }
        }
      }
      $histories = $this->getSecureRepo('TaskHistory')->findBy(['$or' => [['taskId' => ['$in' => $commentsIds]],['taskUnid' => ['$in' => $commentsUnids]]]], array('created' => "ASC"));
      $commentsIds = array_flip($commentsIds);
      $commentsUnids = array_flip($commentsUnids);
      foreach ($histories as $history){
        if ($history->getTaskId())
          $key = $commentsIds[$history->getTaskId()];
        else
          $key = $commentsUnids[$history->getTaskUnid()];
        if(empty($res[$key]['taskHistories'])){
          $res[$key]['taskHistories'] = [];
        }
        $res[$key]['taskHistories'][] = $history->getDocument();
      }
      usort($res, function($a, $b)
      {
        return strcmp($a['created'], $b['created']);
      });

      return $res;
  }

  public function existsAction(Request $request) {
    $id = $this->param('id');
    $document = $this->isUnid($id) ? $this->getRepo('Portal')->findOneBy(['unid' => $id]) : $this->getRepo('Portal')->find($id);
    if(!$document) return $this->fail('document does not exist');

    return $this->success([]);
  }

  public function getAction(Request $request) {
    $id = $this->param('id');
    $timestamp = $this->param('timestamp');
    $prev = $this->param('prev');
    $taskOther = [];

    $document = $this->findDoc($id);
    if(!$document) return $this->fail('document not found');

    $taskHistories = $this->get('task.service')->getHistories($document);
    foreach ($this->getRepo('Portal')->findBy(['taskID'=>$document->GetUnid(), 'typeDoc'=>[ '$in' => [ 'task', 'result' ] ]], array('created' => "ASC")) as $task){
      $taskOther[] = $task->getDocument();
    }
    if(!$this->getUser()->can('read', $document)) {
      return $this->fail('permission denied');
    }
    if($document->GetStatus() == 'deleted') {
      return $this->fail('the document is marked as deleted');
    }
    $res = ['document' => $document->getDocument()];
    $res['document']['taskHistories'] = $taskHistories;
    $res['document']['taskOther'] = $taskOther;
    $res['comments'] = array();
    if ($timestamp) {
      if ($prev) {
        $tmp = $this->loadCommentsSince($document, $timestamp, [], true);
        if (!empty($tmp)) array_push($res['comments'], end($tmp));
      }
      else $res['comments'] = $this->loadCommentsSince($document, $timestamp);
    }

    return $this->success($res);
  }

  public function getWithUnreadedAction() {
    $id = $this->param('id');
    $repo = $this->getRepo('Portal');
    $data = $this->fromJson(); // source data
    $docsToRead = isset($data['docs']) ? $data['docs'] : false;
    $unids = [];
    if ($docsToRead) $unids = array_keys($docsToRead);

    $document = $this->findDoc($id);
    if(!$document) return $this->fail('document not found');

    $taskHistories = [];
    $taskOther = [];
    $taskHistories = $this->get('task.service')->getHistories($document);
    foreach ($repo->findBy(['taskID'=>$document->GetUnid(), 'typeDoc'=>[ '$in' => [ 'task', 'result' ] ]], array('created' => "ASC")) as $task){
      $taskOther[] = $task->getDocument();
    }

    if(!$this->getUser()->can('read', $document)) {
      return $this->fail('permission denied');
    }
    if($document->GetStatus() == 'deleted') {
      return $this->fail('the document is marked as deleted');
    }
    $res = ['document' => $document->getDocument()];
    $res['document']['taskHistories'] = $taskHistories;
    $res['document']['taskOther'] = $taskOther;
    $res['comments'] = array();

    $since = $this->getReadedTime($document);
    if (!$since)
      $since = "01.01.2000 01:01:01";
    $dt = \DateTime::createFromFormat('d.m.Y H:i:s', $since);
    $dt = $dt->format('Ymd').'T'.$dt->format('His');

    $res['comments'] = $this->loadCommentsSince($document, $dt, $unids);

    return $this->success($res);
  }

  private function getLinkInfo($doc, $isComment = false) {
    $tmp = array();
    $var = null;

    if ($doc->GetUnid() === $doc->GetParentID() || sizeof($doc->GetParentID()) === 0) {
      $var = $doc;
      $tmp['head'] = true;
    } else {
      $var = $this->getRepo('Portal')->findOneBy(['unid' => $doc->GetUnid()]);
      if (!$var) $var = $this->getRepoAndFindOneByAnyId($this->getRepo('Contacts'), $doc->GetUnid());
      $tmp['head'] = false;

      $parent = $this->getRepo('Portal')->findOneBy(['unid' => $doc->GetParentID()]);
      if (!$parent) $parent = $this->getRepo('Contacts')->findOneBy(['unid' => $doc->GetParentID()]);

      if ($parent) {
        $tmp['subject'] = $var->GetSubject() ? $var->GetSubject() : $parent->GetSubject();
        $tmp['messages'] = $parent->getCountMess();
      }
    }

    $tmp['subject'] = isset($tmp['subject']) ? $tmp['subject'] : $var->GetSubject();
    $tmp['messages'] = isset($tmp['messages']) ? $tmp['messages'] : $var->getCountMess();
    $tmp['unid'] = $var->GetUnid();
    $tmp['linkedTo'] = $isComment ? $var->GetSubID() : $var->GetLinkedUNID();
    $tmp['form'] = $var->GetForm();

    if ($tmp['form'] && sizeof($tmp['form']) > 0 && $tmp['form'] != 'Contact' && $tmp['form'] != 'Organization')
      $tmp['contact'] = false;
    else {
      $tmp['contact'] = true;
      if (!$tmp['subject']) $tmp['subject'] = $var->GetContactName();
      $tmp['form'] = $var->GetDocumentType();
    }

    return $tmp;
  }

  private function getLinkedParent($unid) {
    $res = null;

    $parent = $this->isUnid($unid) ? $this->getRepo('Portal')->findOneBy(['unid' => $unid]) : $this->getRepo('Portal')->find($unid);
    if (!$parent) $parent = $this->getRepo('Contacts')->findOneBy(['unid' => $unid]);

    if (sizeof($parent->GetLinkedUNID()) > 5) {
      $res = $this->getLinkedParent($parent->GetLinkedUNID());
    } else { //the highest level reached
      $res = $parent;
    }
    return $res;
  }

  private function getLinkedChildren($unid) {
    $res = [];
    $this->recursive[] = $unid;

    $documents = $this->getRepo('Portal')->findBy(['linkedUNID' => $unid]);
    $contacts = $this->getRepo('Contacts')->findBy(['linkedUNID' => $unid]);

    $res = $documents?$this->getLinkedChildrenParam($documents, $res):$res;
    $res = $contacts?$this->getLinkedChildrenParam($contacts, $res):$res;

    return $res;
  }

  private function getLinkedChildrenParam($doc, $res){
    foreach($doc as $child) {
      if(!in_array($child->GetUnid(), $this->recursive)){
        $res[$child->GetUnid()] = array();
        $res[$child->GetUnid()]['self'] = $this->getLinkInfo($child);
        $res[$child->GetUnid()]['children'] = $this->getLinkedChildren($child->GetUnid());
      }
    }

    return $res;
  }

  public function loadLinksAction(Request $request) {
    $unid = $this->param('unid'); //linked UNID
    $data = $this->fromJson(); // source data
    $docUnid = $data['docUnid']; // source document

    $res = array();

    if ($unid != '0') { //document got a parent
      $parent = $this->getLinkedParent($unid);
      $docUnid = $parent->GetUnid();
    } else {
      $parent = $this->isUnid($docUnid) ? $this->getRepo('Portal')->findOneBy(['unid' => $docUnid]) : $this->getRepo('Portal')->find($docUnid);
      if (!$parent) $parent = $this->getRepo('Contacts')->findOneBy(['unid' => $docUnid]);
    }

    $this->recursive = [];
    $res['parent'] = array();
    $res['parent'] = $this->getLinkInfo($parent);
    $res['children'] = array();
    $res['children'] = $this->getLinkedChildren($docUnid);

    return $this->success($res);
  }

  public function inviteGuestAction(Request $request){
    $data = $this->fromJson();
    $error = '';
    $key = '';
    
    if(isset($data['email']) && isset($data['main']) && isset($data['doc'])){
      $portalRepo = $this->getRepo('Portal');
      $contactsRepo = $this->getRepo('Contacts');
      
      $guestInstance = new Guest( $data['main'],
                                  $this->getUserPortalData()->GetLogin(),
                                  $data['email'],
                                  $data['doc']);
      
      $this->getDM()->persist($guestInstance);
      $this->getDM()->flush();
      $key = $guestInstance->GetKey();
      

      /** @var \Treto\PortalBundle\Services\MailService $mailService */
      $mailService = $this->container->get('mail.service');
      $mailService->sendEmail('noreply@tile.expert', 'jdrobkov@treto.ru', '1', $key);
      
    } else {
      return $this->fail('Wrong input');
    }

    return $this->success(['error' => $error, 'key' => $key]);
  }
  
  /**
   * Link to doc from menu
   * @param Request $request
   * @return JsonResponse
   */
  public function linkedToAction(Request $request){
    $data = $this->fromJson();
    $error = '';
    if(isset($data['from']) && isset($data['to'])){
      $portalRepo = $this->getRepo('Portal');
      $contactsRepo = $this->getRepo('Contacts');

      /** @var Portal $to */
      /** @var Portal $from */
      $to = $portalRepo->findOneBy(['unid' => $data['to']]);
      $to = $to?$to:$contactsRepo->findOneBy(['unid' => $data['to']]);
      $from = $portalRepo->findOneBy(['unid' => $data['from']]);
      $from = $from?$from:$contactsRepo->findOneBy(['unid' => $data['from']]);

      if($to && $from){
        if(($to->GetParentID() || $to->GetSubjectID())){
          $parentUnid = $to->GetParentID()?$to->GetParentID():$to->GetSubjectID();
          $repo = $to->GetParentDbName() && $to->GetParentDbName() == 'Contacts'?$contactsRepo:$portalRepo;
          $to = $repo->findOneBy(['unid' => $parentUnid]);
        }

        if($to){
          if($to->GetUnid() != $from->GetUnid() && (!$to->GetParentID() && !$from->GetParentID() ||
                  $to->GetParentID() != $from->GetParentID()) && $from->GetParentID() != $to->GetUnid()){
            $to->SetLinkedUNID($from->GetUnid());
            $to->SetSubID($from->GetUnid());
            $from->SetIsLinked(1);
            $this->getDM()->persist($to);
            $this->getDM()->persist($from);
            $this->getDM()->flush();
          }
          else {
            $error = 'Не возможно привязать документ.';
          }
        }
        else {
          $error = 'Родитель привязуемого документа не найден.';
        }
      }
      else {
        $error = 'Не найден документ по ссылке.';
      }
    }
    else {
      $error = 'Отсутствуют обязательные параметры.';
    }

    return $this->success(['error' => $error]);
  }

  public function loadCommLinksAction(Request $request) {
    $data = $this->fromJson(); // source data
    $messages = $data['messages']; // comments in discus

    $res = array();

    $res['mesLinks'] = array();

    $mesLinksPortal = $this->getRepo('Portal')->findBy(['SubID' => ['$in' => $messages]]);
    $mesLinksContacts = $this->getRepo('Contacts')->findBy(['SubID' => ['$in' => $messages]]);
    foreach ($mesLinksPortal as $mesLink) {
      $res['mesLinks'][$mesLink->GetUnid()] = array();
      $res['mesLinks'][$mesLink->GetUnid()]['self'] = $this->getLinkInfo($mesLink, true);
      $res['mesLinks'][$mesLink->GetUnid()]['children'] = $this->getLinkedChildren($mesLink->GetUnid());
    }
    foreach ($mesLinksContacts as $mesLink) {
      $res['mesLinks'][$mesLink->GetUnid()] = array();
      $res['mesLinks'][$mesLink->GetUnid()]['self'] = $this->getLinkInfo($mesLink, true);
      $res['mesLinks'][$mesLink->GetUnid()]['children'] = $this->getLinkedChildren($mesLink->GetUnid());
    }

    return $this->success($res);
  }

  public function markAsReadForcedAction(Request $request) {
    $data = $this->fromJson(); // source data
    $unid = $this->param('unid');
    $login = $this->param('login');

    return $this->success(['success' => 'true', 'removed' => $this->markAsReadForced($unid, $login)]);
  }

  private function markAsReadForced($unid, $login){
    $user = $this->getRepo('Portal')->findEmplByLogin($login);
    
    $doc = $this->findDoc($unid);
    $main = $this->getMainDocFor($doc);
    $mainUnid = $unid;
    if ($main) $mainUnid = $main->GetUnid();
    
    if($user){
      $result = $this->get('notif.service')->notifRemoval($mainUnid,
                                                          $unid,
                                                          $user->GetLogin(),
                                                          3,
                                                          __FUNCTION__.', '.__LINE__,
                                                          'Forcefully removed notif from');
    } else {
      $result = 'Not found empl by login '.$login;
    }

    return $result;
  }

  public function removeMailNotifyAction(){
    $result = [];
    $contactrepo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
    $portalrepo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
    $robo = $this->container->get('service.site_robojson');
    $mails = $portalrepo->findBy(['mailHash' => ['$exists' => true]]);
    $contacts = [];
    if($mails){
      foreach ($mails as $item) {
        if(!isset($contacts[$item->GetParentID()]) || !isset($contacts[$item->GetParentID()]['parent'])){
          /** @var $item Portal */
          $contacts[$item->GetParentID()] = [
              'parent' => $contactrepo->findOneBy(['unid' => $item->GetParentID()]),
              'logins' => $item->GetMailAccess()
          ];
        }
      }

      foreach ($contacts as $key => $contact) {
        $accessLogins = isset($contact['logins']) && $contact['logins'] ? $contact['logins']:false;
        $accessLogins = is_array($accessLogins)?$accessLogins:[$accessLogins];
        $contact = isset($contact['parent']) && $contact['parent'] ? $contact['parent']:false;
        /** @var $contact Contacts */
        if($contact){
          $r = $robo->getParticipantsByUnid($contact->GetUnid(), true);
          if(isset($r['result']) && $r['result']){
            foreach ($accessLogins as $accessLogin) {
              $r['result'][] = $accessLogin;
            }

            foreach ($r['result'] as $login) {
              $result[] = ['contact' => $key, 'login' => $login, 'result' => $this->markAsReadForced($key, $login)];
            }
          }
        }
      }
    }

    return $this->success($result);
  }

  public function unurgeForcedAction(Request $request) {
    $data = $this->fromJson(); // source data
    $unid = $this->param('unid');
    $login = $this->param('login');
    $doc = $this->findDoc($unid);
    $main = $this->getMainDocFor($doc);
    if (!$main) $main = $doc;

    $logger = $this->get('monolog.logger.notif_logger');

    $success = $this->get('notif.service')->unurgeNotif($main->GetUnid(), $unid, $login);
    $logger->info('Forcefully unurged notif for '.$login);

    return $this->success(['success' => true, 'unurged' => $success]);
  }

  public function taskAction($unid = false, $code = false, $data = false, $fromCode = false) {
    /** @var TaskService $taskService */
    $taskService = $this->get('task.service');
    $unid = $unid ? $unid : $this->param('unid');
    $code = $code ? $code : $this->param('code');
    $code = intval($code);
    $data = $data ? $data : $this->fromJson();
    $result = $taskService->task($unid, $code, $data, $this->getUser(), $this->getRequest()->getHost());

    if(isset($result['error']) && $result['error']){
      return $this->fail($result['error']);
    }
    elseif($fromCode){
      return $result;
    }
    else {
      return $this->success($result);
    }
  }
  
  public function diffAction($unid = false) {
    $unid = $unid ? $unid : $this->param('unid');
    $versRepo = $this->getRepo('PreviousVersions');
    $result = [];
    
    if (!$unid) return $this->fail($result['error']);
    else {
      $previousVersions = $versRepo->findBy(['docUnid' => $unid]);
      
      foreach($previousVersions as $version) {
        $result['oldVersions'][] = $version->toArray();
      }
    }
    
    return $this->success($result);
  }

  private function assignTaskPerformers($doc) { //creates multiple tasks if TaskPerformers is array of logins
    $takeOut = false;

    if(!isset($doc['_id']) || !$doc['_id']){
      if (((!isset($doc['TaskPerformers']) || empty($doc['TaskPerformers'])) && isset($doc['taskPerformerLat'])) &&
        (!isset($doc['TaskSharePerformers']) || !$doc['TaskSharePerformers'])){
        return $doc;
      }

      if (((isset($doc['TaskPerformers']) &&
          is_array($doc['TaskPerformers']) &&
          !empty($doc['TaskPerformers']))) ||
        (isset($doc['TaskSharePerformers']) &&
          $doc['TaskSharePerformers'])){
        if(isset($doc['TaskPerformers']) && $doc['TaskPerformers']){
          foreach ($doc['TaskPerformers'] as $key => $item) {
            $newDoc = $doc;
            $newDoc['TaskPerformers'] = [$item];
            $newDoc['taskPerformerLat'] = [$item];

            if(isset($newDoc['TaskSharePerformers'])){
              unset($newDoc['TaskSharePerformers']);
            }
            $result = $this->setAction($newDoc, true);

            if(isset($result['takeOut']) && $result['takeOut'] && !$takeOut){
              $takeOut = $result['takeOut']?$result['takeOut']:false;
            }

            if($key == 0){
              if(!isset($doc['subjectID']) || !$doc['subjectID']){
                $doc['parentID'] = $doc['unid'];
                $doc['subjectID'] = $doc['unid'];
              }

              unset($doc['unid']);
            }
          }
        }

        if(isset($doc['TaskSharePerformers']) && $doc['TaskSharePerformers']){
          foreach ($doc['TaskSharePerformers'] as $key => $taskSharePerformer) {
            $newDoc = $doc;
            $newDoc['sharePerformers'] = [$taskSharePerformer];
            $newDoc['taskPerformerLat'] = ['shareTask'];
            $newDoc['TaskPerformers'] = ['shareTask'];

            $result = $this->setAction($newDoc, true);
            $this->container->get('monolog.logger.autotask')->info('---86787687686787----222-'.json_encode($result));

            if(isset($result['takeOut']) && $result['takeOut'] && !$takeOut){
              $takeOut = $result['takeOut']?$result['takeOut']:false;
            }

            if($key == 0){
              if(!isset($doc['subjectID']) || !$doc['subjectID']){
                $doc['parentID'] = $doc['unid'];
                $doc['subjectID'] = $doc['unid'];
              }

              unset($doc['unid']);
            }
          }
        }

        if($takeOut){
          return ['takeOut' => $takeOut];
        }
        else {
          return true;
        }
      }
    }
    elseif(isset($doc['TaskSharePerformers']) && isset($doc['TaskSharePerformers'][0])) {
      $doc['sharePerformers'] = [$doc['TaskSharePerformers'][0]];
      $doc['taskPerformerLat'] = ['shareTask'];
      $doc['TaskPerformers'] = ['shareTask'];

      return $doc;
    }
    elseif(isset($doc['TaskPerformers']) && isset($doc['TaskPerformers'][0])){
      $doc['sharePerformers'] = [];
      $doc['taskPerformerLat'] = [$doc['TaskPerformers'][0]];
      $doc['TaskPerformers'] = [$doc['TaskPerformers'][0]];

      return $doc;
    }

    return false;
  }

  private function formSpecificProcessing($doc, &$docInBase, $main, $performer, $empl = false) {
    $user = $this->getUserPortalData();
    $repo_contacts = $this->getRepo('Contacts');
    $result = [];

    switch($doc['form']) {
      case 'formTask':
        if($doc['taskPerformerLat'][0] == 'shareTask' && isset($doc['TaskSharePerformers']) && $doc['TaskSharePerformers']){
          foreach ($doc['TaskSharePerformers'] as $sharePerformer) {
            $main->addSharePrivileges($sharePerformer['domain'], 'read', 'username', $sharePerformer['login']);
            $main->addSharePrivileges($sharePerformer['domain'], 'subscribed', 'username', $sharePerformer['login']);
            $docInBase->addSharePrivileges($sharePerformer['domain'], 'read', 'username', $sharePerformer['login']);
            $docInBase->addSharePrivileges($sharePerformer['domain'], 'subscribed', 'username', $sharePerformer['login']);
          }
        }
        elseif($performer){
          $docInBase->addReadPrivilege($performer->GetLogin(), $user->GetLogin());
          $docInBase->addSubscribedPrivilege($performer->GetLogin(), $user->GetLogin());
          $main->addReadPrivilege($performer->GetLogin(), $user->GetLogin());
          $main->addSubscribedPrivilege($performer->GetLogin(), $user->GetLogin());

          $this->get('notif.service')->notifAdding($main,
           $docInBase,
           $performer->GetLogin(),
           1,
           __FUNCTION__.', '.__LINE__,
           'Added urgent-1 notif to');
        }
        if($docInBase->GetType() == 'Blog'){
          $docInBase->SetType('');
        }
        break;
      case 'formVoting':
        if ($docInBase->GetShowOnIndex() != 1) {
          $docInBase->removeActionPrivilege('vote', 'role', 'all');

          $privileges = $docInBase->getVotePrivileges();

          $userList = array();
          foreach ($privileges as $priv) {
            if (isset($priv['username'])) array_push($userList, $priv['username']);
          }
          if ($userList && sizeof($userList) > 0)
            $this->addNotifToUsersAction($docInBase->GetUnid(), $main->GetUnid(), $userList, true, 1, $docInBase, $main);
        } else {
          if (empty($docInBase->GetPeriodPoll())) $docInBase->SetPeriodPoll('5');
        }
        break;
      case 'messagebb':
        if (isset($doc['action']) && isset($doc['taskID'])) {
          switch($doc['action']) {
            case 'reject':
              $this->taskAction($doc['taskID'], 15, ['messagebbUnid' => $doc['unid']]);
              break;
            case 'check':
              if(isset($doc['CheckerLat']) && isset($doc['CheckerLat'][0])){
                $param = ['user' => $doc['CheckerLat'][0]];
              }
              elseif(isset($doc['shareChecker']) && isset($doc['shareChecker'][0]) && isset($doc['shareChecker'][0]['domain'])){
                $param = ['shareUser' => $doc['shareChecker'][0]];
              }

              if(isset($param)){
                $taskResult = $this->taskAction($doc['taskID'], 20, $param, true);

                if(isset($taskResult['takeOut'])&&$taskResult['takeOut']){
                  /** @var $docInBase Portal */
                  $docInBase->SetSubjectID($docInBase->GetTaskID());
                  $docInBase->SetParentID($docInBase->GetTaskID());
                  $result['takeOut'] = $taskResult['takeOut'];
                }
              }
              break;
          }
        }
        break;
      case 'formAdapt':
        /** @var Portal $docInBase */
        if ($docInBase->GetPrivateEmail()){
          /** @var Contacts $contact */
          $contact = $repo_contacts->findOneBy(["EmailValues"=>$docInBase->GetPrivateEmail()]);
        }

        if (!isset($contact) || !$contact){
          $contact = new Contacts($this->GetUser(), '1');
          $contact->setAuthor([$user->GetFullName(false)]);
          $contact->setAuthorRus($user->GetFullNameInRus());
        }
        unset($doc['unid']);
        $contact->setDocument($doc);
        $contact->SetDocumentType("Person");
        $contact->setForm("Contact");
        $contact->SetStatus('open');
        $contact->SetGroup([]);
        $contact->SetContactStatus([14]);
        $contact->SetLastName($docInBase->GetLastName());
        $contact->SetFirstName($docInBase->GetName());
        $contact->SetMiddleName($docInBase->GetMiddleName());
        $contact->SetBirthDay($docInBase->GetBirthday());
        $contact->SetEmail($docInBase->GetEmail());
        $contact->SetRank($docInBase->GetWorkGroup());
        $contact->SetSection($docInBase->GetSection());
        $contact->SetCountry([$docInBase->GetCountry()]);
        $contact->SetSalary($docInBase->GetSalary());
        $contact->SetCompanyName($docInBase->GetCompanyName());
        $contact->SetIsHomeOrganization('1');
        $contact->SetC1WaitSync('1');
        $contact->SetAccessOption('1');

        $contact->prePersist();
        $this->getDM()->persist($contact);

        if($empl) {
          /** @var $empl Portal */
          $empl->SetContactUnid($contact->GetUnid());
          $this->getDM()->persist($empl);
        }
        $this->getDM()->flush();
        break;
    }

    return $result;
  }

  private function generateTasks($doc, $docInBase, $main) {
    $repo_dict = $this->getRepo('Dictionaries');
    $user = $this->getUserPortalData();
    $teamLead = $repo_dict->findOneBy(['type' => 'AutoTaskPersons', 'key' => 'Teamlead портала']);
    $sysAdmin = $repo_dict->findOneBy(['type' => 'AutoTaskPersons', 'key' => 'Системный администратор']);
    $managerHR = $docInBase->GetManagerHR();
    $authorLogin = $docInBase->GetAuthorLogin();
    $userLogin = $docInBase->GetLogin();

    $perfLogins = [];
    $perfLogins[] = $teamLead->getValue();
    $perfLogins[] = $sysAdmin->getValue();
    $perfLogins[] = $managerHR;
    $perfLogins[] = $authorLogin;
    $perfLogins[] = $userLogin;

    $perfLogins = array_unique($perfLogins);

    $content = [];

    $content[$teamLead->getValue()]['body'] = 'Добавить в чат "'.$docInBase->GetFullNameInRus().'".';
    $content[$teamLead->getValue()]['subject'] = 'Добавить в чат "'.$docInBase->GetFullNameInRus().'".';
    $content[$sysAdmin->getValue()]['body'] = 'Создать почту для нового сотрудника "'.$docInBase->GetFullNameInRus().'".';
    $content[$sysAdmin->getValue()]['subject'] = 'Создать почту для нового сотрудника "'.$docInBase->GetFullNameInRus().'".';
    $content[$managerHR]['body'] = 'Новый сотрудник '.$docInBase->GetFullNameInRus().' принят на работу на должность '.$docInBase->GetWorkGroup(true).'. Нужно оформить все документы.';
    $content[$managerHR]['subject'] = 'Оформить нового сотрудника "'.$docInBase->GetFullNameInRus().'".';
    $content[$authorLogin]['body'] = 'Внести резюме и хит-лист в личную карточку нового сотрудника "'.$docInBase->GetFullNameInRus().'".';
    $content[$authorLogin]['subject'] = 'Заполнить контакт "'.$docInBase->GetFullNameInRus().'"';
    $content[$userLogin]['body'] = 'Загрузить фото в свой профиль на портале, заполнить поле с номером телефона.';
    $content[$userLogin]['subject'] = 'Загрузить фото в свой профиль на портале, заполнить поле с номером телефона.';

    foreach($perfLogins as $perfLogin) {
      $task = $content[$perfLogin];
      $task['form'] = 'formTask';
      $task['parentUnid'] = $docInBase->GetUnid();
      $task['subjectID'] = $docInBase->GetUnid();
      $task['parentID'] = $docInBase->GetUnid();
      $task['authorLogin'] = $user->GetLogin();
      $task['Author'] = [$user->GetFullName(false)];
      $task['AuthorRus'] = $user->GetFullNameInRus();
      $task['Priority'] = 0;
      $task['status'] = 'open';
      $task['TaskStateCurrent'] = 0;

      $task['TaskPerformers'] = [$perfLogin];

      $this->setAction($task);
    }
  }

  private function processMentioned($main, $docInBase, $doc) {
    $robo = $this->get('service.site_robojson');
    $mentions = $robo->checkMentions($doc, $this->getRequest()->getHost());

    if (sizeof($mentions) > 0) {
      return $this->notifyMentioned($main, $docInBase, $mentions);
    }
    return ['No mentions'];
  }

  private function clearMentions($main, $docInBase, $readAtISO = null) {
    $repoPortal = $this->getRepo('Portal');
    $repoMention = $this->getRepo('Mention');
    $user = $this->getUserPortalData();
    
    $mentions = $repoMention->findBy(['parent' => $main->GetUnid(), 'receiver' => $user->GetLogin(), 'status' => 'active']);
    
    foreach($mentions as $mention) {
      if ($readAtISO && $readAtISO < $mention->GetCreated()){continue;}
      
      $notifChanged = $this->get('notif.service')->notifRemoval($mention->GetParent(),
                                                                $mention->GetDoc(),
                                                                $user->GetLogin(),
                                                                1,
                                                                __FUNCTION__.', '.__LINE__,
                                                                'Removed mention from',
                                                                $readAtISO);
      if ($notifChanged) {
        $mention->SetStatus('inactive');
        $mention->SetModified();
        $this->getDM()->persist($mention);
      }
    }
    $this->getDM()->flush();
    
    return ['clearMentions' => 'success'];
  }
  
  private function clearDocMentions($docInBase) {
    $repoPortal = $this->getRepo('Portal');
    $repoMention = $this->getRepo('Mention');
    
    $mentions = $repoMention->findBy(['doc' => $docInBase->GetUnid(), 'status' => 'active']);
    
    foreach($mentions as $mention) {
      $notifChanged = $this->get('notif.service')->notifRemoval($mention->GetParent(),
                                                                $mention->GetDoc(),
                                                                $mention->GetReceiver(),
                                                                1,
                                                                __FUNCTION__.', '.__LINE__,
                                                                'Removed mention from');
      if ($notifChanged) {
        $mention->SetStatus('inactive');
        $mention->SetModified();
        $this->getDM()->persist($mention);
      }
    }
    $this->getDM()->flush();
    
    return ['clearDocMentions' => 'success'];
  }

    /**
     * Find and replace link to doc subject
     * @param $body
     * @return mixed
     */
  private function findLink($body){
      $portalRepo = $this->getRepo('Portal');
      $contactsRepo = $this->getRepo('Contacts');
      $result = preg_replace_callback('#https?\:\/\/(.*)\#\/discus\/(.*)\/(contact\?client\=1C|contact|)(?!\"|\')#Ui',
          function($matches) use ($portalRepo, $contactsRepo){
              $result = $matches[0];
              if(isset($matches[2])){
                  $doc = $portalRepo->findOneBy(['unid' => $matches[2]]);
                  if(!$doc){
                      /** @var Portal $doc */
                      $doc = $contactsRepo->findOneBy(['unid' => $matches[2]]);
                  }
              }

              if(isset($doc) && $doc && $doc->GetSubject()){
                  $result = '<a target="_blank" href="#/discus/'.$doc->GetUnid().'/">'.$doc->GetSubject().'</a>';
              }

              return $result;
      }, $body);

      return $result;
  }

  public function setAction($doc = null, $recursive = false) {
    $id = $this->param('id'); // get request id if exists
    $data = $this->fromJson(); // source data
    $doc = $doc ? $doc : $data['document']; // source document

    if(!$doc || !isset($doc['form']) || !$doc['form']) { return $this->fail('wrong input'); }
    if(!$this->getUser())  { return $this->fail('unautorized access'); }
    $dontNotifyParticipants = isset($data['silent']) ? $data['silent'] : false; // if silent set
    $explicitEditing = isset($data['explicitEditing']) ? $data['explicitEditing'] : false;
    $docInBase = null; // initial doc var
    $errors = [];
    $result = ['debug' => []];
    $main = false;
    $notifChanged = false;
    $repo = $this->getRepo('Portal');
    $versRepo = $this->getRepo('PreviousVersions');
    $user = $this->getUserPortalData();
    $performer = null;
    $isCreating = false;
    $synchService = $this->get('synch.service');

//     file_put_contents('1.txt', print_r($doc, true));

    if (($doc['form'] == 'message' || $doc['form'] == 'messagebb') && (!isset($doc['parentID']) || !isset($doc['subjectID']))) {
      return $this->fail('Corrupted document');
    }

    if ((isset($doc['status']) && $doc['status'] != 'deleted') && $doc['form'] == 'formTask') { //setting performers
//       file_put_contents('1.txt', print_r($doc, true), FILE_APPEND);
      if(!$recursive){
        $doc = $this->assignTaskPerformers($doc);

        if(isset($doc['takeOut']) && $doc['takeOut']){
          $result['takeOut'] = $doc['takeOut'];
          $doc = true;
        }
      }

      if ($doc === false) return $this->fail('Corrupted document');
      if ($doc === true) return $this->success($result);

      if (isset($doc['taskPerformerLat']) && $doc['taskPerformerLat'][0] != 'shareTask'){
        $userArr = $repo->findEmplByNames($doc['taskPerformerLat'],$doc['taskPerformerLat'],$doc['taskPerformerLat']);
        if($userArr){
          $performer = $userArr[0];
        }
      }
    }

    if(isset($doc['body'])){
        $doc['body'] = $this->findLink($doc['body']);
    }

    if(! $id) { // ========== CREATE DOC ==========
      $isCreating = true;
      $docInBase = new \Treto\PortalBundle\Document\Portal($this->getUser()); // create new document
      $errors = $docInBase->setDocument($doc, $this->get('treto.validator'), $this->getUser()->getRoles());
      if(! $this->isUnid($docInBase->GetUnid())) {
        $docInBase->SetUnid();
      }

      $docInBase->setAuthor($this->getUserPortalData()->GetFullName(false));
      $docInBase->setAuthorLogin($this->getUserPortalData()->GetLogin());
      $docInBase->setAuthorRus($this->getUserPortalData()->GetLastName().' '.$this->getUserPortalData()->GetName());
      $docInBase->SetForm($doc['form']);
      if(empty($doc['security'])) {
        $docInBase->setDefaultSecurity($this->getUser());
      }
      $main = $this->getMainDocFor($docInBase, true);
      if(!$main){
          $main = $docInBase;
      }
      else {
          $oldShareSecurity = $main->getShareSecurity();
      }

      if ($doc['form'] == 'formAdapt') {
        $createdUser = $this->createUserFromAdaptation($docInBase);
        if(!$createdUser['user'] || is_string($createdUser['user'])) {
          return $this->fail($createdUser['user']);
        }
      }

      $fspResult = $this->formSpecificProcessing($doc, $docInBase, $main, $performer, isset($createdUser['empl'])?$createdUser['empl']:false);

      if(isset($doc['linkedUNID'])) {
        $linkedDoc = $repo->findOneBy(['unid' => $doc['linkedUNID']]);
        if(!$linkedDoc){
          $linkedDoc = $this->getRepo('Contacts')->findOneBy(['unid' => $doc['linkedUNID']]);
        }
        if($linkedDoc) {
          $linkedDoc->SetIsLinked(1);
        }
        $this->getDM()->persist($linkedDoc);
        if(isset($doc['quote']) && $doc['quote']){ //REPLACED QUOTE BY LINK IN LINKED-DOC
          $this->replaceQuote($doc['quote'], $doc['linkedUNID'], $docInBase->GetUnid());
        }
      }

      if($docInBase->HasSubject()) { // ========== CREATE COMMENT DOC ==========
        /** @var $main Portal */
        $main = $this->getMainDocFor($docInBase, true);
        if(!$main) { return $this->fail('parent document not found or access denied'); }

        $readAt = null;
        $readAtISO = null;
        if(isset($doc['readAt'])) $readAt = $doc['readAt'];
        if(isset($doc['readAtISO'])) $readAtISO = $doc['readAtISO'];

        $main->IncrementCountMess($this->getUser()); // increment message count and add author
        $main->addReadPrivilege($user->GetLogin(), $user->GetLogin()); // subscribe user to updates (if not subscribed)
        $main->addSubscribedPrivilege($user->GetLogin(), $user->GetLogin());

        $this->setReadByTime($main, $readAt, true);

        $this->get('service.site_robojson')->createHistoryLog($main->GetUnid(), $main->GetSubject(), $main->GetForm(), $this->getUser());
          $tpl = $docInBase->GetTaskPerformerLat(true);
          if($tpl && $tpl != 'shareTask') {
          $main->addReadPrivilege($tpl, $user->GetLogin());
          $main->addSubscribedPrivilege($tpl, $user->GetLogin()); // subscribe task performer to updates (if not subscribed)
        }
        if($shp = $docInBase->GetSharePerformers()){
          foreach ($shp as $sharePerformer) {
            $main->addSharePrivileges($sharePerformer['domain'], 'read', 'username', $sharePerformer['login']);
            $main->addSharePrivileges($sharePerformer['domain'], 'subscribed', 'username', $sharePerformer['login']);
          }
        }

        if ($docInBase->GetParentID() && sizeof($docInBase->GetParentID()) > 0) {
          $this->setReadByTime($docInBase->GetParentID(), $readAt);
        }

        $result += $this->clearMentions($main, $docInBase, $readAtISO);

        if ($this->get('notif.service')->hasNotif($main->GetUnid(), $user->GetLogin(), true)) {
          $this->get('notif.service')->bumpNotif($main->GetUnid(), $user->GetLogin());
        }

        $this->getDM()->persist($main);
        $this->getDM()->flush();

        $result += $this->processNotifications($main, $docInBase, true, true, $dontNotifyParticipants, $readAtISO);

      } else { // ========== CREATE PARENT DOC ==========
        $result += $this->addSubscribed($docInBase, $docInBase, $user->GetLogin());
        $result += $this->processNotifications($docInBase, $docInBase, true, false, false);
      }

      $result += $this->processMentioned($main, $docInBase, $doc);
      $docInBase->SetCreateHost($this->getRequest()->getHost());
    } else { // ========== EDIT DOC ==========
      /** @var $docInBase Portal */
      $docInBase = $this->getRepoAndFindOneByAnyId('Portal', $id, false);

      if(!$docInBase) { return $this->fail('document not found'); }
      /** @var $main Portal */
      $main = $this->getMainDocFor($docInBase, true);
      if (!$main) { $main = $docInBase; }

      $oldShareSecurity = $main->getShareSecurity();
      $oldStatus = $docInBase->GetStatus();
      $this->get('service.site_robojson')->createHistoryLog($main->GetUnid(), $main->GetSubject(), $main->GetForm(), $this->getUser());
      $originalDocInBase = clone $docInBase;
      unset($doc['security']);
      $docInBase->setUser($this->getUser());
      $fieldsChanged = array();

      if (isset($doc['ToSite']) && $doc['ToSite'] == '1' && $docInBase->GetToSite() != '1'){
        $this->setCommentsNoForSite($doc['unid']);
      }

      if(!$this->getUser()->can('write', $docInBase)) {
        if (isset($doc['AttachedDoc'])) {//it's ok, anybody can change AttachedDoc
          $fieldsChanged['AttachedDoc'] = $docInBase->fromArray(['AttachedDoc'=>$doc['AttachedDoc']]);
        } elseif (!isset($fieldsChanged['LikeDate']) && !isset($fieldsChanged['LikeNotDate'])){
          return $this->fail('permission denied');
        }
      } else {
        if($explicitEditing) {
        
          $docHist = new PreviousVersions($originalDocInBase->GetUnid(), 'Portal', $user->GetLogin(), $originalDocInBase->toArray());
          $this->getDM()->persist($docHist);
          $this->getDM()->flush();
        
          $result += $this->clearDocMentions($docInBase);
          $robo = $this->get('service.site_robojson');
          $confirmedMentions = $robo->checkMentions($doc, $this->getRequest()->getHost());

          $nextComments = $this->loadCommentsSince($main, $doc['created']);
          $nextPosters = [];
          foreach ($nextComments as $nextComment) {
            $nextPosters[] = $nextComment['authorLogin'];
          }
          $confirmedMentions = array_diff($confirmedMentions, $nextPosters);
          $confirmedMentions = array_values($confirmedMentions);

          $result += $this->notifyMentioned($main, $docInBase, $confirmedMentions);

          //Change performer from edit window
          if (isset($doc['taskPerformerLat'][0]) && $docInBase->GetTaskPerformerLat(true) != $doc['taskPerformerLat'][0]
            && $doc['taskPerformerLat'][0] != 'shareTask') {
            $this->taskAction($doc['unid'], 3, ['performer' => $doc['taskPerformerLat'][0]]);
          }
          else {
            $newSharePerf = isset($doc['sharePerformers'][0]) && $doc['sharePerformers'][0]?$doc['sharePerformers'][0]:[];
            $newSharePerf = $newSharePerf?$newSharePerf['domain'].$newSharePerf['login']:'';
            $oldSharePerf = isset($docInBase->GetSharePerformers()[0])&&$docInBase->GetSharePerformers()[0]?$docInBase->GetSharePerformers()[0]:[];
            $oldSharePerf = $oldSharePerf?$oldSharePerf['domain'].$oldSharePerf['login']:'';

            if($newSharePerf && $newSharePerf != $oldSharePerf){
              $this->taskAction($doc['unid'], 3, ['sharePerformer' => [$doc['sharePerformers'][0]['domain'] => [$doc['sharePerformers'][0]['login']]]]);
            }
          }
        }

        $fieldsChanged = $docInBase->fromArray($doc, ['user','Author','AuthorRus','modified','readBy']);
      }
      $result['fieldsChanged'] = $fieldsChanged;
      $errors = $this->get('treto.validator')->validate($docInBase);
      if(!empty($errors)) {
        return $this->fail($errors);
      }

      if(!( sizeof($fieldsChanged) <= 2 && isset($fieldsChanged['countMess']) && $docInBase->GetCountMess() == 0 )) //Just started thread
        $this->setReadByTime($main, null, true);

      $result = array_merge_recursive($result, $this->processEditDocAfter($docInBase, $originalDocInBase, $main, false, $fieldsChanged));

      if (!isset($doc['AttachedDoc'])) {
        if($explicitEditing) {
          $docInBase->SetDateModified(\Treto\PortalBundle\Document\SecureDocument::dt2iso(new \DateTime(), true));
          $result += $this->processNotifications($main, $docInBase, true, false);
        } else {
          $result += $this->processNotifications($main, $docInBase, $notifChanged, false);
        }
      }

      if(isset($doc['tiedDoc']) && !$doc['tiedDoc']){
        $tiedResult = $this->tiedDoc($docInBase); //Unbind from the parent task
        $docInBase = $tiedResult['docInBase'];
      }

      if($this->isChildren($main, $docInBase)){
        if(isset($doc['status'])){
          if($oldStatus == 'deleted' && $doc['status'] == 'open'){
            $main->IncrementCountMess(false, true);
          }
          elseif(($oldStatus == 'open' || !$oldStatus) && $doc['status'] == 'deleted' && $main->GetForm() != 'Contact'){
            $main->DecrementCountMess();
          }
        }
        else {
          $doc['status'] = 'open';
        }
      }
    }

    if ($this->isChildren($main, $docInBase)) { //merge participants
      $privileges = $docInBase->getReadPrivileges();
      foreach ($privileges as $privilege) {
        if(isset($privilege['username'])) {
          $main->addReadPrivilege($privilege['username'], $user->GetLogin());
          $main->addSubscribedPrivilege($privilege['username'], $user->GetLogin());
        };
      }
      $privileges = $docInBase->getSubscribedPrivileges();
      foreach ($privileges as $privilege) {
        if(isset($privilege['username'])) {
          $main->addSubscribedPrivilege($privilege['username'], $user->GetLogin());
        };
      }
      $this->getDM()->persist($main);
    }

    // on new comment\theme or editing increase reads (like read in notificator) and writes by user
    $readWriteLog = $this->GetRepo('MainStat')->findReadWriteLog();
    $readWriteLog->LogRead( $user->GetLogin(), $main->GetUnid() );
    $readWriteLog->LogWrite( $user->GetLogin(), $main->GetUnid() );
    $this->getDM()->persist($readWriteLog);

    $this->getDM()->persist($docInBase);
    $this->getDM()->flush();
    $this->getDM()->clear();

    if (isset($createdUser)) {
      $nodeService = $this->get('node.service');
      $result['chatrefresh'] = $nodeService->refreshUsers();
      $this->generateTasks($doc, $docInBase, $main);
    }

    $result['document'] = $docInBase->getDocument(false,false,$this->getUser()->getRoles());

    if($result['document']['form'] === 'formTask'){
      $histories = $this->getSecureRepo('TaskHistory')->findBy(['$or' => [
        ['taskId'=>$docInBase->GetId()],
        ['taskUnid'=>$docInBase->GetUnid()]
      ]], array('created' => "ASC"));

      if($histories){
        foreach ($histories as $history){
          $result['document']['taskHistories'][] = $history->getDocument();
        }
      }
    }

    if($docInBase->HasSubject()) {
      if (!$main) {
        $main = $this->getMainDocFor($docInBase, true);
      }
      if ($main) {
        $nodeService = $this->get('node.service');
        $nodeService->addComent($main->getUnid(), $result['document']);
      }
      /** @var $main Contacts */
      if ($main && ($main->GetToSite() == '1' ||
        ($main->GetForm() == 'Contact' && (
        in_array(7, $main->GetContactStatus(), true) ||
        in_array(10, $main->GetContactStatus(), true) ||
        in_array('7', $main->GetContactStatus(), true) ||
        in_array('10', $main->GetContactStatus(), true)))) &&
        $docInBase->GetNotForSite() !='1') {
        $this->sendCommentToSite($main, $docInBase);
      }
    } elseif ($docInBase->GetToSite() == '1') {
      $this->sendDocToSite($docInBase);
    }

    /** @var $synchService SynchService */
    $checkResult = $synchService->checkShare(
        $docInBase,
        $main,
        $this->getRequest()->getHost(),
        $isCreating,
        isset($oldShareSecurity)&&$oldShareSecurity?$oldShareSecurity:false
    );

    if(isset($checkResult['takeOut']) && $checkResult['takeOut']){
      $result['takeOut'] = $result['document']['unid'];
    }
    elseif(isset($fspResult)&&isset($fspResult['takeOut'])&&$fspResult['takeOut']){
      $result['takeOut'] = $fspResult['takeOut'];
    }

    if($recursive){
      return $result;
    }
    else {
      return $this->success($result);
    }
  }

  private function isChildren($main, $docInBase){
    return $main && $main->GetUnid() != $docInBase->GetUnid();
  }

  /**
   * Replaced quote by link in linked-doc
   * @param $quote
   * @param $docUNID
   * @param $linkedUNID
   */
  private function replaceQuote($quote, $linkedUNID, $docUNID){
    $linkTo = $this->getRepo('Portal')->findOneBy(['unid' => $linkedUNID]);
    /** @var $linkTo \Treto\PortalBundle\Document\Portal */
    if($linkTo){
      $linkToBody = $linkTo->GetBody();
      $linkHtml = '<a href="#/discus/'.$docUNID.'/">'.$quote.'</a>';
      $quote = trim($quote);
      if(strpos($linkToBody, $quote) !== false){
        $linkToBody = str_replace($quote, $linkHtml, $linkToBody);
      }
      else {
        $linkToBody = "$linkToBody<br/>$linkHtml";
      }
      $linkTo->SetBody($linkToBody);
      $this->getDM()->persist($linkTo);
    }
  }

  public function voteAction() {
    $unid = $this->param('unid');
    $data = $this->fromJson();
    if(empty($data['answers'])) {
      return $this->fail('wrong input');
    }
    $answers = $data['answers'];
    $dm = $this->getDM();

    $portal_rep = $this->getRepo('Portal');
    $docInBase = $portal_rep->findOneBy(['unid' => $unid]);
    $parent = $portal_rep->findOneBy(['unid' => $docInBase->GetParentID()]);
    if (!$parent) $parent = $docInBase;

    if(! $docInBase) {
      return $this->fail('document not found');
    }
    if(!$this->getUser()->can('read', $docInBase)) {
      return $this->fail('permission denied');
    }
    if($docInBase->GetForm() != 'formVoting') {
      return $this->fail('wrong document type');
    }
    if($docInBase->votingHasMember($this->getUser())) {
      return $this->fail('user already voted');
    }

    $timeISO = null;
    $time = null;

    if (isset($data['timeISO'])) {
      $timeISO = $data['timeISO'];
      $time = $data['time'];
    } else {
      $now = new \DateTime();
      $timeISO = $now->format('Ymd').'T'.$now->format('His');
    }

    $result = $docInBase->votingSetMember($this->getUser(), $answers);
    if($result) {
      $docInBase->addReadPrivilege($this->getUser()->getPortalData()->GetLogin(), $this->getUser()->getPortalData()->GetLogin());
      $docInBase->addSubscribedPrivilege($this->getUser()->getPortalData()->GetLogin(), $this->getUser()->getPortalData()->GetLogin());
    } else {
      return $this->fail('the specified user cannot vote or already in voted list');
    }

    $user = $this->getUser()->getPortalData();
    if ($this->get('notif.service')->hasNotif($docInBase->GetUnid(), $user->GetLogin(), true)) {
      $this->get('notif.service')->notifRemoval($docInBase,
                                                $docInBase,
                                                $user->GetLogin(),
                                                1,
                                                __FUNCTION__.', '.__LINE__,
                                                'Removed urgent-1 notif from',
                                                $timeISO);
    } else {
      $this->get('notif.service')->notifRemoval($parent,
                                                $docInBase,
                                                $user->GetLogin(),
                                                1,
                                                __FUNCTION__.', '.__LINE__,
                                                'Removed urgent-1 notif from',
                                                $timeISO);
    }

    $this->setReadByTime($parent, $time, true);


    $usersToVote = $docInBase->getVotePrivileges();
    if (sizeof($usersToVote)>0) {
      $isPublic = false;
      $loginsToVote = [];
      $loginsVoted = [];
      foreach($usersToVote as $userToVote) {
        if (isset($userToVote['username'])) array_push($loginsToVote, $userToVote['username']);
        if (isset($userToVote['role']) && $userToVote['role'] == 'all') $isPublic = true;
      }
      if (!$isPublic) {
        $loginsVoted = array_keys($docInBase->GetAnswers());

        if (sizeof(array_diff($loginsToVote, $loginsVoted))==0) {
          $authorInDb = $portal_rep->findEmplByNames([$docInBase->GetAuthorLogin()], [$docInBase->GetAuthorLogin()], [$docInBase->GetAuthorLogin()]);
          $authorDoc = reset($authorInDb);

          if ($authorDoc) {
            $this->get('notif.service')->notifAdding($parent,
                                                     $docInBase,
                                                     $authorDoc->GetLogin(),
                                                     0,
                                                     __FUNCTION__.', '.__LINE__,
                                                     'Removed notif from',
                                                     '(Голосование окончено)');
          }
        }
      }
    }

    $dm->persist($docInBase);
    $dm->flush();
    $dm->clear();

    return $this->success();
  }
  
  public function likeAction() {
    $id = $this->param('id');
    $data = $this->fromJson();
    if(!isset($data['isLike']) || empty($data['login'])) {
      return $this->fail('wrong input');
    }
    $dm = $this->getDM();
    $now = \Treto\PortalBundle\Document\SecureDocument::dt2iso(new \DateTime(), true);

    $portal_rep = $this->getRepo('Portal');
    $docInBase = $portal_rep->findOneBy(['unid' => $id]);

    if (!$docInBase) {
      return $this->fail('document not found');
    }

    $likes = $docInBase->GetLikes();
    if (empty($likes)) $likes = [];
    
    $found = false;
    foreach ($likes as $login => $like) {
      if ($login == $data['login']) {
        $found = true;
        if ($data['isLike'] == -1) {  //remove like
          unset($likes[$login]);
        } else if ($like['isLike'] != $data['isLike']) {
          $likes[$login] = ['isLike' => $data['isLike'], 'timestamp' => $now];
          if ($data['isLike'] == 0) $docInBase->SetLikeNotDate($now);
          else if ($data['isLike'] == 1) $docInBase->SetLikeDate($now);
        }
      }
    }
    
    if (!$found) {
      $likes[$data['login']] = ['isLike' => $data['isLike'], 'timestamp' => $now];
      if ($data['isLike'] == 0) $docInBase->SetLikeNotDate($now);
      else if ($data['isLike'] == 1) $docInBase->SetLikeDate($now);
    }

    $likesAndDislikes = $this->GetRepo('MainStat')->findBy(['$and' => [['type' => 'LiveList'], ['subType' => ['$in' => ['likes', 'dislikes']]]]]);
    foreach ($likesAndDislikes as $list) {
      $list->SetUpdateNeeded();
      $this->getDM()->persist($list);
    }

    $docInBase->SetLikes($likes);
    $dm->persist($docInBase);
    $dm->flush();
    $dm->clear();

    return $this->success(['document' => $docInBase->getDocument()]);
  }

  public function watchVoteAction() {
    $unid = $this->param('unid');
    $data = $this->fromJson();
    $user = $this->getUser()->getPortalData();
    $dm = $this->getDM();

    $portal_rep = $this->getRepo('Portal');
    $docInBase = $portal_rep->findOneBy(['unid' => $unid]);

    if(! $docInBase) {
      return $this->fail('document not found');
    }
    if(!$this->getUser()->can('read', $docInBase)) {
      return $this->fail('permission denied');
    }
    if($docInBase->GetForm() != 'formVoting') {
      return $this->fail('wrong document type');
    }

    $watching = $docInBase->GetWatchedBy($user->GetLogin());
    if (!$watching)
      $docInBase->AddWatchedBy($user->GetLogin());
    else
      $docInBase->RemoveWatchedBy($user->GetLogin());

    $dm->persist($docInBase);
    $dm->flush();
    $dm->clear();

    return $this->success(['watching' => !$watching, 'watchedBy' => $docInBase->GetWatchedBy()]);
  }

  public function adaptationListAction() {
    $params = [
        'form'=>'formAdapt',
        'status' => ['$ne' => 'deleted']
    ];

    if(!$this->getUser()->hasRole('PM')){
      $params['Author'] = ['$in' => $this->getUser()->getNames()];
    }

    $list = $this->getSecureRepo('Portal')->findBy($params);
    $lraw = [];
    foreach($list as $doc) {
      $lraw[] = $doc->toArray();
    }
    return $this->success(['documents' => $lraw]);
  }

  /**
   * Send public document to site
   * @param $doc
   */
  private function sendDocToSite($doc){
    /** @var \Treto\PortalBundle\Services\SiteService $siteService */
    $siteService = $this->get('site.service');
    /** @var $doc Portal */
    $document = $doc->getDocument(false,false,$this->getUser()->getRoles());
    $author = $this->getUser()->getPortalData()->GetFullName();

    if ($doc->getType() == 'Blog') {
      $siteService->sendBlog(['document'=> $document, 'author' => $author]);
    }elseif ($doc->getC2() == 'Вакансии') {
      $siteService->sendVacancy(['document' => $document, 'author' => $this->getUser()->getPortalData()->getLogin()]);
    }elseif ($doc->getC1() == 'Для сайта') {
      $siteService->sendSpecPage(['document' => $document, 'author' => $author]);
    }
  }

  public function addNotifToUsersAction($docUnid = null,
                                        $parentUnid = null,
                                        $userList = null,
                                        $toMyself = null,
                                        $urgency = 0,
                                        $docInstance = null,
                                        $parentInstance = null){
    $data = $this->fromJson();

    $parentUnid = $parentUnid ? $parentUnid : $data['doc'];
    $docUnid = $docUnid ?       $docUnid    : $data['doc'];
    $userList = $userList ?     $userList   : $data['userList'];
    $urgency = $urgency ?       $urgency    : $data['urgency'];
    $repo = $this->getRepo('Portal');
    $res = array();
    $res['Users notified'] = 0;
    $notify = array();
    $notifLog = array();

    if (!$docInstance) {
      $doc = $repo->findOneBy(['unid' => $docUnid]);
      if (!$doc)
        $doc = $this->getRepo('Contacts')->findOneBy(['unid' => $docUnid]);

      if (!$doc) return $this->fail('Document not found');
    } else {
      $doc = $docInstance;
    }
    if (!$parentInstance) {
      if ($parentUnid != $docUnid)
        $parent = $repo->findOneBy(['unid' => $parentUnid]);
      else
        $parent = $doc;
    } else {
      $parent = $parentInstance;
    }

    if (!$toMyself) {
      if (($key = array_search($this->getUser()->getUsername(), $userList)) !== false) {
        unset($userList[$key]);
      }
    }
    $res['Users notified'] = $this->get('notif.service')->notifMultipleAdding($parent,
                                                                              $doc,
                                                                              $userList,
                                                                              $urgency,
                                                                              __FUNCTION__.', '.__LINE__,
                                                                              'Added'.($urgency > 0 ? ' urgent-'.$urgency : '').' notif to');

    return $this->success($res);
  }

  /**
   * Send comment to site
   * $type:
   * 1 - публикации
   * 2 - вакансии
   * 3 - коллекции
   * 4 - спец страницы
   * 5 - заказы
   * @param $main
   * @param $doc
   */
  private function sendCommentToSite($main, $doc){
    /** @var SiteService $siteService */
    $siteService = $this->get('site.service');
    $siteService->sendCommentToSite($main, $doc, $this->getUser());
  }

  /**
   * Unbind from the parent task
   * @param $docInBase Portal
   * @return mixed
   */
  private function tiedDoc($docInBase){
    /** @var RoboService $robo */
    $robo = $this->get('service.site_robojson');
    return $robo->takeOutTask($docInBase, $this->getUserPortalData()->GetLogin());
  }

  private function setCommentsNoForSite($main_unid){
    $repo = $this->getRepo('Portal');
    $dm = $this->getDM();
    $comments = $repo->findBy(["form"=>"message", '$or'=>[["parentID"=>$main_unid],["subjectID"=>$main_unid]]]);
    foreach ($comments as $comment) {
      $comment->SetNotForSite("1");
      $dm->persist($comment);
    }
    $dm->flush();
  }
}
