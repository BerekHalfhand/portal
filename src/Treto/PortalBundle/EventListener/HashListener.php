<?php
namespace Treto\PortalBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
class HashListener{
    public function onKernelController(FilterControllerEvent $event){
        $controller = $event->getController();

        if (!is_array($controller)){
            return;
        }

        if ($controller[0] instanceof \Treto\PortalBundle\Controller\v1\CheckHashInterface){
            $logger = $controller[0]->get('monolog.logger.sync');
            $request = $event->getRequest();

            if($event->getRequest()->server->get('PATH_INFO') != '/mailToContact'){
                $logger->info(
                    '('.__CLASS__.' '.__FUNCTION__.') Path info: "'.$event->getRequest()->server->get('PATH_INFO').
                    "\n\rRoute: ".json_encode($request->attributes->get('_route')).
                    "\n\rController: ".json_encode($request->attributes->get('_controller')).
                    "\n\rParams: ".json_encode($controller[0]->params, JSON_UNESCAPED_UNICODE)
                );
            }

            $roboService = $controller[0]->get('service.site_robojson');

            if(!isset($controller[0]->params['hash']) || !$roboService->checkHash($controller[0]->params['hash'])){
                throw new AccessDeniedHttpException('Invalid hash!');
            }
            else {
                unset($controller[0]->params['hash']);
            }
        }
    }

    public function onKernelException(GetResponseForExceptionEvent $event){
        $exception = $event->getException();
        if($exception->getFile() == __FILE__){
            $data = ['success' => false, 'message' => $exception->getMessage()];
            $response = new JsonResponse($data);
            $event->setResponse($response);
        }
    }
}