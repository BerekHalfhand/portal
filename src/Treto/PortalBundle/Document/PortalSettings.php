<?php

namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/** 
 * @MongoDB\Document
 */
class PortalSettings
{
    /** @MongoDB\Id(strategy="auto") */
    protected $_id;

    /** @MongoDB\String */
    protected $created;

    /** @MongoDB\String */
    protected $type;

    /** @MongoDB\String */
    protected $companyName;

    /** @MongoDB\String */
    protected $domain;

    /** @MongoDB\Bool */
    protected $https;

    /** @MongoDB\String */
    protected $lastSynch;

    /** @MongoDB\String */
    protected $salt;

    /** Example:
     * [{
     *  "username" : "SDavydova",
     *  "LastName" : "Давыдова",
     *  "MiddleName" : "Алексеевна",
     *  "name" : "Светлана",
     *  "WorkGroup" : [ "Главный бухгалтер" ],
     *  "section" : [ "Бухгалтерия" ] }] */
    /** @MongoDB\Hash */
    protected $users;

    /** @MongoDB\String */
    protected $status;

    /** @MongoDB\String */
    protected $value;
    
    /** @MongoDB\String */
    protected $name;
    
    /** @MongoDB\String */
    protected $blockId;

    /** @MongoDB\String */
    protected $environment;

    public function __construct() {
        $d = new \DateTime();
        $this->created = $d->format('Ymd').'T'.$d->format('His');
    }

    public function setId($value){$this->_id = $value;}
    public function getId(){return $this->_id;}
    public function setHttps($https){$this->https = $https;}
    public function getHttps(){return $this->https;}
    public function setEnvironment($value){$this->environment = $value;}
    public function getEnvironment(){return $this->environment;}
    public function setCreated($value){$this->created = $value;}
    public function getCreated(){return $this->created;}
    public function setCompanyName($companyName){$this->companyName = $companyName;}
    public function getCompanyName(){return $this->companyName;}
    public function setLastSynch($lastSynch){$this->lastSynch = $lastSynch;}
    public function getLastSynch(){return $this->lastSynch;}
    public function setDomain($domain){$this->domain = $domain;}
    public function getDomain(){return $this->domain;}
    public function setValue($value){$this->value = $value;}
    public function getValue(){return $this->value;}
    public function setSalt($salt){$this->salt = $salt;}
    public function getSalt(){return $this->salt;}
    public function setUsers($users){$this->users = $users;}
    public function getUsers(){return $this->users;}
    public function setStatus($status){$this->status = $status;}
    public function getStatus(){return $this->status;}
    public function setType($value){$this->type = $value;}
    public function getType(){return $this->type;}
    public function setName($v){$this->name = $v;}
    public function getName(){return $this->name;}
    public function setBlockId($v){$this->blockId = $v;}
    public function getBlockId(){return $this->blockId;}


    /**
    * Get document as array
    * @param string $roles, you can pass User::getRoles() result
    * @return array
    */
    public function getDocument() {
      return (new \Treto\PortalBundle\Model\DocumentSerializer($this,[]))->toArray();
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
