<?php

namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/** 
 * @MongoDB\Document
 */
class C1Log
{
    /** @MongoDB\Id(strategy="auto") */
    protected $_id;

    /** @MongoDB\String */
    protected $created;

    /** @MongoDB\String */
    protected $type;

    /** @MongoDB\String */
    protected $text;

    /** @MongoDB\String */
    protected $time;

    public function __construct($type, $text, $time) {
        $d = new \DateTime();
        $this->created = $d->format('Ymd').'T'.$d->format('His');
        $this->type = $type;
        $this->text = $text;
        $this->time = $time;
    }

    public function setId($value){$this->_id = $value;}
    public function getId(){return $this->_id;}
    public function setCreated($value){$this->created = $value;}
    public function getCreated(){return $this->created;}
    public function setType($value){$this->type = $value;}
    public function getType(){return $this->type;}
    public function setText($value){$this->text = $value;}
    public function getText(){return $this->text;}
    public function setTime($value){$this->time = $value;}
    public function getTime(){return $this->time;}

    /**
    * Get document as array
    * @param string $roles, you can pass User::getRoles() result
    * @return array
    */
    public function getDocument() {
      return (new \Treto\PortalBundle\Model\DocumentSerializer($this,[]))->toArray();
    }
}
