<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Treto\PortalBundle\Document\Contacts;
use Treto\PortalBundle\Document\HistoryLog;

class HistoryLogController extends Controller
{
  use \Treto\PortalBundle\Services\StaticLogger;
  public function listAction(Request $request)
  {
    $limit = $this->param('limit', 20);
    $offset = $this->param('offset', 0);
    $user = $this->getUser();

    $qb = $this->getDM()->createQueryBuilder('TretoPortalBundle:HistoryLog');

    $qb = $qb->field('userId') -> equals($user->getUserName());
    $query = $qb->limit($limit)->skip($offset)->sort(array('time'=>-1))->getQuery();
    $contacts = $query->execute();
    $res = array();
    foreach ($contacts as $value) {
        array_push($res,$value->getDocument());
    };
    return new JsonResponse( $res );
  }

  public function addHistoryAction(){
    $data = $this->fromJson();
    $subject = $data['subject'];
    $user = $this->getUser();

    $request = $this->get('request');
    $cookies = $request->cookies;

    $state = $cookies->get('currentStateName');
    $stateParams = json_decode($cookies->get('currentStateParams'), true);

    $repHistory = $this->getRepo('HistoryLog');
    $log = $repHistory->findOneBy(['userId' => $user->getUserName(), 'label' => $subject]);
    if (!$log) $log = new HistoryLog();

    if (!$log->getId()) {
      $log->setDocument(['userId' => $user->getUserName(), 'label'=> $subject]);
    }
    $log->setDocument(['time' => new \DateTime(), 'stateParams' => $stateParams, 'state' => $state]);

    $this->getDM()->persist($log);
    $this->getDM()->flush();

    return $this->success();
  }
  
  public function addHistoryFullAction($type, $docid){
    $data = $this->fromJson();
    $subject = $data['subject'];
    $updateDiscus = isset($data['updateDiscus']) ? $data['updateDiscus'] : false;

    $this->get('service.site_robojson')->createHistoryLog($docid, $subject, $type, $this->getUser());
    if ($updateDiscus)
      $this->setReadByTime($updateDiscus);

    return $this->success();
  }
  
  public function getHistoryAction($docid){
    $user = $this->getUser()->getUserName();
    $repHistory = $this->getRepo('HistoryLog');
    $log = $repHistory->findOneBy(['userId' => $user, 'stateParams.id' => $docid]);
    
    if ($log) {
	return $this->success(['lastTime' => $log->getTime()->format('Ymd\THis')]);
    }
    return $this->fail('No history found');
  }
}
