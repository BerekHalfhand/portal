<?php
namespace Treto\PortalBundle\Document;

class ContactsRepository extends SecureRepository
{
    public function getForPortal(Portal $portal)
    {
      if($portal->contactData) { return $portal->contactData; }
      return $this->findOneBy(['$or' => [['PortalUser_ID' => $portal->GetUnid()], ['UserNotesName' => $portal->GetFullName()]], 'DocumentType' => 'Person']);
    }
}