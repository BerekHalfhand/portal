<?php
namespace Treto\PortalBundle\Services;

class RoboJsonService extends RoboService
{

  public function getString()
  {
    return $this->string . 'JSON';
  }
}