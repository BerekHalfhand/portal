<?php

namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Treto\UserBundle\Annotation\ExtendedPrivileges as Escalated;

/**
 * @MongoDB\Document
 */
class TaskHistory extends SecureDocument
{
    /** @MongoDB\Id(strategy="auto") */
    protected $id;

    /** @MongoDB\String */
    protected $unid;

    /** @MongoDB\String */
    protected $domain;

    /** @MongoDB\String */
    private $taskId;
    
    /** @MongoDB\String */
    private $taskUnid;

    /** @MongoDB\String */
    protected $type;

    /** @MongoDB\Hash */
    protected $oldValue;

    /** @MongoDB\Hash */
    protected $value;
    
    /** @MongoDB\Hash */
    protected $flags;

    /** @MongoDB\String */
    protected $created;

    /** @MongoDB\String */
    protected $authorLogin;

    /** @MongoDB\Hash */
    protected $security;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getUnid()
    {
        return $this->unid;
    }

    public function setUnid($unid = null)
    {
        $this->unid = $unid ? $unid : substr(strtoupper(uniqid(time()) . uniqid(time())), 0, 32);
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getOldValue()
    {
        return $this->value;
    }

    public function setOldValue($oldValue)
    {
        $this->oldValue = $oldValue;
    }
    
    public function getFlags()
    {
        return $this->flags;
    }

    public function setFlags($v)
    {
        $this->flags = $v;
    }
    
    public function getAuthorLogin()
    {
        return $this->authorLogin;
    }

    public function setAuthorLogin($AuthorLogin)
    {
        $this->authorLogin = $AuthorLogin;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated($created = null)
    {
        if (!$created) {
            $d = new \DateTime();
            $this->created = static::dt2iso(new \DateTime(), true);
        } else {
            $this->created = $created;
        }
    }

    public function getSecurity()
    {
        return $this->security;
    }

    public function setSecurity($security)
    {
        $this->security = $security;
    }

    public function setTaskId($taskId)
    {
        $this->taskId = $taskId;
    }

    public function getTaskId()
    {
        return $this->taskId;
    }
    
    public function setTaskUnid($v)
    {
        $this->taskUnid = $v;
    }

    public function getTaskUnid()
    {
        return $this->taskUnid;
    }

    public function __construct($user = null)
    {
        $this->setCreated();
        $this->setUnid();
        $this->setAuthorLogin($user ? $user->getPortalData()->getLogin() : null);
        $this->setDefaultSecurity($user);
        $this->setValue([]);
        $this->setOldValue([]);
        $this->setFlags([]);
    }

    /**
     * get document as array
     * @param string $roles, you can pass User::getRoles() result
     * @return array
     */
    public function getDocument($roles = [])
    {
        return (new \Treto\PortalBundle\Model\DocumentSerializer($this, $roles))->toArray();
    }

    /** set document from array 
     * @param \Treto\PortalBundle\Validator\Validator $validator
     * @param string $roles, you can pass User::getRoles() result
     * @return array of validation errors or empty array on success
     */
    public function setDocument($array, $validator = null, $roles = [])
    {
        (new \Treto\PortalBundle\Model\DocumentSerializer($this, $roles))->fromArray($array, ['id']);
        if ($validator) {
            return $validator->validate($this);
        }
        return [];
    }

}

?>
