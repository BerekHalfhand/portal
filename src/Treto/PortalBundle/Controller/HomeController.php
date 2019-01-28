<?php

namespace Treto\PortalBundle\Controller;


class HomeController extends Controller
{
  use \Treto\PortalBundle\Services\StaticLogger;
  public function IndexAction(){
    $this->log(__CLASS__, __METHOD__);
    return $this->render('TretoPortalBundle:Home:Index.html.twig');
  }
}
