<?php
namespace Treto\PortalBundle\Document;

class HistoryLogRepository extends \Doctrine\ODM\MongoDB\DocumentRepository
{
    public function getForUser(\Treto\UserBundle\Document\User $user)
    {
        return $this->findOneBy(['userId' => $user->getId()]);
    }
    
}
