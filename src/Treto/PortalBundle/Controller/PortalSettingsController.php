<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Treto\PortalBundle\Document\PortalSettings;
use Treto\PortalBundle\Services\SynchService;

class PortalSettingsController extends Controller{
    /**
     * Get all portal settings
     * @return JsonResponse
     */
    public function getAction(){
        $response = [];
        $portalSettings = $this->getRepo('PortalSettings')->findAll();
        foreach ($portalSettings as $portalSetting) {
            /** @var PortalSettings $portalSetting */
            if(!isset($response[$portalSetting->getType()])){
                $response[$portalSetting->getType()] = [];
            }

            $doc = $portalSetting->getDocument();
            $doc['edit'] = false;
            $response[$portalSetting->getType()][] = $doc;
        }

        return $this->success(['response' => $response]);
    }

    /**
     * Save and edit new settings
     * @return JsonResponse
     */
    public function setAction(){
        $response = [];
        $data = $this->fromJson();
        $portalSettingsRepo = $this->getRepo('PortalSettings');

        foreach ($data['data'] as $type => $items) {
            if(!isset($response[$type])){
                $response[$type] = [];
            }
            foreach ($items as $item) {
                $doc = false;
                $edit = false;
                if(isset($item['_id'])){
                    /** @var PortalSettings $doc */
                    $doc = $portalSettingsRepo->findOneBy(['_id' => new \MongoId($item['_id'])]);
                    if($doc){ //edit
                        if($item['status'] != 'delete'){
                            $doc->setDocument($item);
                            $edit = true;
                        }
                        else { //remove
                            $this->getDM()->remove($doc);
                        }
                    }
                }
                elseif($item['status'] == 'active'){ //create
                    $doc = new \Treto\PortalBundle\Document\PortalSettings();
                    $item['type'] = $type;
                    $doc->setDocument($item);
                    $edit = true;
                }

                if($doc && $edit){
                    $document = $doc->getDocument();
                    $document['edit'] = false;
                    $response[$type][] = $document;
                    $this->getDM()->persist($doc);
                }
            }
        }

        foreach ($response as $item) {
            $item['edit'] = false;
        }

        $this->getDM()->flush();
        return $this->success(['response' => $response]);
    }

    /**
     * Check share settings (test request)
     * @return JsonResponse
     */
    public function checkAction(){
        $data = $this->fromJson();
        /** @var SynchService $synchService */
        $synchService = $this->container->get('synch.service');
        return $this->success(['result' => $synchService->checkShareSettings($data['data']['settings'])]);
    }
}
