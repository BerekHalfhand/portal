<?php

namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Treto\UserBundle\Annotation\ExtendedPrivileges as Escalated;

/** 
 * @MongoDB\Document
 */
class Tag extends SecureDocument
{
  /** @MongoDB\Id(strategy="auto") */
  protected $id;

  /** @MongoDB\Hash 
   *  @Escalated(set="PM") */
  protected $security;

  /** @MongoDB\String */
  protected $created;
  
  /** @MongoDB\String */
  protected $modified;

  /** @MongoDB\String */
  protected $name;

  /** @MongoDB\Hash */
  protected $usedBy;
  
  /** @MongoDB\Int */
  protected $count;

  public function __construct($user = null) {
    $this->SetCreated();
    $this->SetModified();
    $this->setDefaultSecurity($user);
  }
  
  public function GetCreated(){
    return $this->created; 
  }
  public function SetCreated($created = null){
    if(! $created) {
      $d = new \DateTime();
      
      $this->created = static::dt2iso(new \DateTime(), true);
    } else {
      $this->created = $created;
    }
  }
  public function GetModified(){
    return $this->modified; 
  }
  public function SetModified($modified = null){
    if(! $modified) {
      $this->modified = static::dt2iso(new \DateTime(), true);
    } else {
      $this->modified = $modified;
    }
  }
    
  public function GetId() {return $this->id;}
  public function SetId($id) {$this->id = $id;}
  public function GetName() {return $this->name;}
  public function SetName($name) {$this->name = $name;}
  public function GetCount() {return $this->count;}
  public function SetCount($count) {$this->count = $count;}
  public function IncrementCount() {$this->count = $this->count + 1;}
  public function GetUsedBy() {return $this->usedBy;}
  public function SetUsedBy($v) {$this->usedBy = $v;}
  public function AddUsedBy($users) {
    if (!isset($this->usedBy)) $this->usedBy = array();
    if (!is_array($users)) $users = [$users];

    foreach ($users as $username) {
      if (isset($this->usedBy[$username])) {
        $this->usedBy[$username] = $this->usedBy[$username]+1;
      } else {
        $this->usedBy[$username] = 1;
      }
    }
    $this->modified = static::dt2iso(new \DateTime(), true);
    return true;
  }
  public function RemoveUsedBy($username) {
    if (!isset($this->usedBy)) $this->usedBy = array();

    if (isset($this->usedBy[$username])) {
      $this->usedBy[$username] = $this->usedBy[$username]-1;
      if ($this->usedBy[$username] < 0) {//somehow...
        $this->usedBy[$username] = 0;
      }
      return true;
    } else {
      return false;
    }
  }
  
  /**
  * Get document as array
  * @param string $roles, you can pass User::getRoles() result
  * @return array
  */
  public function getDocument($roles = []) {
    return (new \Treto\PortalBundle\Model\DocumentSerializer($this,$roles))->toArray();
  }
  
  /** Set document from array 
  * @param \Treto\PortalBundle\Validator\Validator $validator
  * @param string $roles, you can pass User::getRoles() result
  * @return array of validation errors or empty array on success
  */
  public function setDocument($array, $validator = null, $roles = []) {
    (new \Treto\PortalBundle\Model\DocumentSerializer($this,$roles))->fromArray($array,['id']);
    if($validator) {
      return $validator->validate($this);
    }      
    return [];
  }
}

?>
