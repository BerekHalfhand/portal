<?php
namespace Treto\PortalBundle\Services;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ExceptionListener {
  
  public function onKernelException(GetResponseForExceptionEvent $event)
  {
    $exception = $event->getException();
    $event->setResponse(new JsonResponse($exception->getMessage(), 500));
  }
}