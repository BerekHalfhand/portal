<?php
namespace Treto\PortalBundle\Services;

class RoboXmlService extends RoboService
{

  public function getString()
  {
    return $this->string . 'XML';
  }
}