<?php
namespace Treto\PortalBundle\Document;

class PortalRepository extends SecureRepository
{  
    public function getForUser(\Treto\UserBundle\Document\User $user)
    {
      if($user->portalData) { return $user->portalData; }    
      return $this->findOneBy(['Login' => $user->getUsername(), 'form' => 'Empl']);
    }
    
    public function getForContact(Contacts $contact)
    {
      if($contact->portalData) { return $contact->portalData; }
      return $this->findOneBy(['$or' => [['unid' => $contact->GetPortalUser_ID()], ['FullName' => $contact->getUserNotesName()]], 'form' => 'Empl']);
    }
}
