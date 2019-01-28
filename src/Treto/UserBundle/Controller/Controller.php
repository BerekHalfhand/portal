<?php
namespace Treto\UserBundle\Controller;

class Controller extends \Treto\PortalBundle\Controller\Controller {
  
  public function getRepo($shortDocumentName) {
    return $this->get('doctrine_mongodb')->getRepository('TretoUserBundle:'.$shortDocumentName);
  }
}