<?php

namespace Treto\UserBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Treto\UserBundle\Annotation\ExtendedPrivileges as Escalated;
use Treto\PortalBundle\Document\SecureDocument;

/** 
 * @MongoDB\Document
 */
class Token extends SecureDocument
{
    /** 
     * @MongoDB\Id(strategy="auto") 
     */
    protected $id;
    
    /** @MongoDB\String */
    protected $token;
    /** @MongoDB\Date */
    protected $created;
    /** @MongoDB\String */
    protected $user;
    /** @MongoDB\Boolean */
    protected $activated;
    
    /** @MongoDB\Collection 
    *  @Escalated(set="PM") */
    protected $security;
    
    public function __construct($user = null) {
      $this->created = new \DateTime();
      $this->activated = false;
      $this->setDefaultSecurity($user);
    }

    public function getId() {return $this->id;}
    public function setId($id) {$this->id = $id;}
    public function getToken() {
        return $this->token;
    }
    public function setToken($token) {
        $this->token = $token;
    }
    public function getActivated() {
        return $this->activated;
    }
    public function setActivated($activated) {
        $this->activated = $activated;
    }
    public function getCreated() {
        return $this->created;
    }
    public function setCreated($created) {
        $this->created = $created;
    }
    public function getUser() {
        return $this->user;
    }
    public function setUser($user) {
        $this->user = $user;
    }
}
