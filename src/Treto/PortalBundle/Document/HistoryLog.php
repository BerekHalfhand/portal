<?php

namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/** 
 * @MongoDB\Document(repositoryClass="HistoryLogRepository")
 */
class HistoryLog {

    /** @MongoDB\Id(strategy="auto") */
    protected $_id;

    /** @MongoDB\String */
    protected $userId;
    
    /** @MongoDB\Date */
    protected $time;

    /** @MongoDB\String */
    protected $label;

    /** @MongoDB\String */
    protected $state;

    /** @MongoDB\Hash */
    protected $stateParams;


    /*
        get/set methods
    */
    public function getId(){ return $this->_id; }
    public function setId ($id) { $this->_id = $_id; return $this; }
    public function getUserId(){ return $this->userId; } 
    public function setUserId ($userId) { $this->userId = $userId; return $this; }
    public function getTime(){ return $this->time; } 
    public function setTime ($time) { $this->time = $time; return $this; }
    public function getLabel() { return $this->label; }
    public function setLabel ($label) { $this->label = $label; return $this; }
    public function getState() { return $this->state; }
    public function setState($state) { $this->state = $state; return $this; }
    public function getStateParams() { return $this->stateParams; }
    public function setStateParams($stateParams) { $this->stateParams = $stateParams; return $this; }

    /**
    * Get document as array
    * @param string $roles, you can pass User::getRoles() result
    * @return array
    */
    public function getDocument($roles = []) {
      $document = (new \Treto\PortalBundle\Model\DocumentSerializer($this,$roles))->toArray();
      #$document['id'] = $this->getId(); // TODO: IMPORTANT! remove 'id' and work with '_id'
      return $document;
    }
    
    /** Set document from array 
    * @param \Treto\PortalBundle\Validator\Validator $validator
    * @param string $roles, you can pass User::getRoles() result
    * @return array of validation errors or empty array on success
    */
    public function setDocument($array, $validator = null, $roles = []) {
      (new \Treto\PortalBundle\Model\DocumentSerializer($this,$roles))->fromArray($array,['_id']);
      if($validator) {
        return $validator->validate($this);
      }
      
      return [];
    }
}

