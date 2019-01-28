<?php
namespace Treto\PortalBundle\Services;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class CorsListener
{
  protected $allowedDomains;
  public function __construct(array $allowedDomains = []) {
    $this->allowedDomains = $allowedDomains;
  }
  public function onKernelResponse(FilterResponseEvent $event)
  {   
      $responseHeaders = $event->getResponse()->headers;

      if($this->allowedDomains) {
        $responseHeaders->set('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Cookie');
        $responseHeaders->set('Access-Control-Allow-Origin', implode(' ',$this->allowedDomains));
        $responseHeaders->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, OPTIONS');
        $responseHeaders->set('Access-Control-Allow-Credentials', 'true');
      }
  }   
}