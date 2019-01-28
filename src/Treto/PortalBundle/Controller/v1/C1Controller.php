<?php

namespace Treto\PortalBundle\Controller\v1;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Treto\PortalBundle\Services\RoboService;

class C1Controller extends ApiController
{
  public function receptionAction(Request $request){
    $logger = $this->get('monolog.logger.sync');
    $logger->info('('.__CLASS__.' '.__FUNCTION__.') Params: '.json_encode($this->params, JSON_UNESCAPED_UNICODE));

    $result = '';
    $parentUnid = [];
    switch($this->params['document']['type']){
      case 'message':
        $result = $this->createMessage($this->params['document'], $this->params['document']['ParentUnID']);
        break;
      case 'topic':
        $result = $this->robo->setTheme($this->params);
        break;
      case 'task':
        $result = $this->robo->setTask($this->params);
        break;
      case 'voting':
        $result = $this->robo->createVoting($this->params);
        foreach ($this->params['childs'] as $child) {
          if (isset($child['message'])){
            var_dump($child['message']);
            $parentUnid[] = $this->createMessage($child['message'], $result);
          }
        }
        break;
      case 'contact':
        $contact = $this->robo->updateContact($this->params, RoboService::UPDATE_FROM_1C);
        $result = $contact?$contact->GetUnid():'';
        break;
      case 'jivoSite':
        $consultService = $this->get('consult.service');
        $result = $consultService->getStatistics(isset($this->params['document'])?$this->params['document']:[]);
        break;
      case 'email':
        $result = $this->robo->getMailByName($this->params);
        break;
    }

    echo $result?$result:'error';
    exit;
  }

  public function createMessage($message, $parent){
    $message['form'] = 'message';
    $message['subjectID'] = $message['parentID'] = $parent;
    return $this->robo->setTheme(['document' => $message]);
  }

}
