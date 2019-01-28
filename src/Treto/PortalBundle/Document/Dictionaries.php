<?php
namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Treto\UserBundle\Annotation\ExtendedPrivileges as Escalated;

/** 
 * @MongoDB\Document
 */
class Dictionaries extends SecureDocument
{
    /** 
     * @MongoDB\Id(strategy="auto") 
     */
    protected $_id;
    
    /** @MongoDB\Hash 
     *  @Escalated(set="PM") */
    protected $security;
    
    /** @MongoDB\Date */
    protected $created;
    
    /** @MongoDB\Date */
    protected $modified;
    
    /** @MongoDB\String */
    protected $type;
    
    /** @MongoDB\Hash */
    protected $subtype;
    
    /** @MongoDB\String */
    protected $key;
    
    /** @MongoDB\String */
    protected $parentKey;
    
    /** @MongoDB\String */
    protected $value;
    
    public function __construct($user = null) {
      $this->created = new \DateTime();
      $this->modified = new \DateTime();
      $this->setDefaultSecurity($user);
    }
    
    public function getId() { return $this->_id; return $this; }
    public function getSecurity() { return $this->security; }
    public function setSecurity($v) { $this->security = $v; return $this; }
    public function getCreated() { return $this->created; }
    public function setCreated($v) { $this->created = $v; return $this; }
    public function getModified() { return $this->modified; }
    public function setModified($v = null) { $this->modified = $v ? $v : new \DateTime(); return $this; }
    public function getType() { return $this->type; }
    public function setType($v) { $this->type = $v; return $this; }
    public function getSubtype() { return $this->subtype; }
    public function setSubtype($v) { $this->subtype = $v; return $this; }
    public function getKey() { return $this->key; }
    public function setKey($v) { $this->key = $v; return $this; }
    public function getParentKey() { return $this->parentKey; }
    public function setParentKey($v) { $this->parentKey = $v; return $this; }
    public function getValue() { return $this->value; }
    public function setValue($v) { $this->value = $v; return $this; }
    
    /**
    * Get document as array
    * @param string $roles, you can pass User::getRoles() result
    * @return array
    */
    public function getDocument($roles = []) {
        $document = (new \Treto\PortalBundle\Model\DocumentSerializer($this,$roles))->toArray();
        return $document;
    }
    
    /** Set document from array 
    * @param \Treto\PortalBundle\Validator\Validator $validator
    * @param string $roles, you can pass User::getRoles() here
    * @return array of validation errors or empty array on success
    */
    public function setDocument($array, $validator = null, $roles = []) {
        (new \Treto\PortalBundle\Model\DocumentSerializer($this,$roles))->fromArray($array,['_id','modified','created']);
        if($validator) {
            return $validator->validate($this);
        }
        return [];
    }
}