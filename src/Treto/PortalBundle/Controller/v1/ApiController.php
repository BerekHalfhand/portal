<?php

namespace Treto\PortalBundle\Controller\v1;

use Treto\PortalBundle\Controller\AbstractDiscussionController;
use Treto\PortalBundle\Controller\v1\FormatDeterminationController;

abstract class ApiController extends AbstractDiscussionController implements FormatDeterminationController
{
    /** @var \Treto\PortalBundle\Services\RoboService $robo */
    public $robo;
    public $params;
}