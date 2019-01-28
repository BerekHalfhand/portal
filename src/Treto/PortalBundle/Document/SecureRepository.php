<?php

namespace Treto\PortalBundle\Document;

/** have access to User for privileges check in SecureDocument */
class SecureRepository extends \Doctrine\ODM\MongoDB\DocumentRepository
{
    protected $user;
  
    public function find($id, $lockMode = 0, $lockVersion = null) {
      return $this->securityFilter(parent::find($id,$lockMode,$lockVersion));
    }

    public function findAll() {
      return $this->securityFilter(parent::findAll());
    }

    public function findBy(array $criteria, array $sort = null, $limit = null, $skip = null) {
      return $this->securityFilter(parent::findBy($criteria,$sort,$limit,$skip));
    }

    public function findOneBy(array $criteria) {
      return $this->securityFilter(parent::findOneBy($criteria));
    }
    
    public function securityFilter($documents) {
      if(!$this->getUser() || empty($documents)) { return $documents; }
      $array = true;
      if(!is_array($documents)) {
        $documents = [$documents];
        $array = false;
      }
      $u = $this->getUser();
      $secureResult = [];
      foreach($documents as $doc) {
        if($u->can('read',$doc)) {
          $secureResult[] = $doc;
        }
      }
      return $array ? $secureResult : reset($secureResult);
    }
    
    public function setUser(\Treto\UserBundle\Document\User $user) {
      $this->user = $user;
    }
    
    public function releaseUser() {
      $this->user = null;
    }
    
    public function getUser() {
      return $this->user;
    }

    public function findEmplByNames($usernames = [], $fullnames = [], $ldaps = []) {
      $usernames = is_array($usernames) ? $usernames : [$usernames];
      $fullnames = is_array($fullnames) ? $fullnames : [$fullnames];
      $ldaps = is_array($ldaps) ? $ldaps : [$ldaps];
      foreach($fullnames as $n) {
        $ldaps[] = Portal::FullNameToLdap($n);
      }
      return $this->findBy([
        '$or' => [
          ['Login' => ['$in' => $usernames]],
          ['FullName' => ['$in' => array_merge($fullnames, $ldaps)]]
        ],
        'form' => 'Empl'
      ]);
    }
    
    public function findEmplByLogin($login) {
      if (!$login) return false;
      
      return $this->findOneBy([
        'Login' => $login,
        'form' => 'Empl',
      ]);
    }
}
