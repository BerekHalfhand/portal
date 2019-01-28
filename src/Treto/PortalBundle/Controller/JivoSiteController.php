<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Treto\PortalBundle\Document\JivoSite;
use Treto\PortalBundle\Services\RoboJsonService;

class JivoSiteController extends Controller
{
  private $loggerSynch;

  /**
   * Write all JivoSite events to DB
   * @return JsonResponse
   */
  public function webhookAction(){
    $this->loggerSynch = $this->get('monolog.logger.sync');
    $consultService = $this->get('consult.service');
    $data = $this->fromJson();
    $this->loggerSynch->info('('.__CLASS__.' '.__FUNCTION__.') Params: '.json_encode($data, JSON_UNESCAPED_UNICODE));
    $dm = $this->getDM();
    $doc = new JivoSite();

    if(isset($data['event_name']) && $data['event_name'] == 'chat_finished'){
      $consultService->checkMissingMessage($data);
    }
    $doc->setDocument($data);
    $dm->persist($doc);
    $dm->flush();

    return new JsonResponse(['result' => 'ok']);
  }
}
?>