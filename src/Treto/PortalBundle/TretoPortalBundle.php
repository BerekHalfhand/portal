<?php

namespace Treto\PortalBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TretoPortalBundle extends Bundle
{
  public static function getInstance() {
    return $GLOBALS['kernel']->getContainer();
  }
}
