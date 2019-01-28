<?php
namespace Treto\UserBundle\Document;

class UserRepository extends \Doctrine\ODM\MongoDB\DocumentRepository
{
    public function getForPortal(\Treto\PortalBundle\Document\Portal $portal)
    {
      if(!$portal->getLogin()) {
        return null;
      }
      if($portal->userData) { return $portal->userData; }
      return $this->findOneBy(['username' => $portal->GetLogin()]);
    }
}