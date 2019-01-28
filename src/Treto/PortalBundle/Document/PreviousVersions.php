<?php

namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Treto\UserBundle\Annotation\ExtendedPrivileges as Escalated;

/** 
 * @MongoDB\Document
 */
class PreviousVersions
{
  /** @MongoDB\Id(strategy="auto") */
  protected $id;

  /** @MongoDB\String */
  protected $created;
  
  /** @MongoDB\String */
  protected $docUnid;
  
  /** @MongoDB\String */
  protected $collection;
  
  /** @MongoDB\String */
  protected $authorLogin;
  
  /** @MongoDB\Hash */
  protected $doc;
  
  public function __construct($unid, $collection, $authorLogin, $doc) {
    $this->SetCreated();
    $this->SetDocUnid($unid);
    $this->SetCollection($collection);
    $this->SetAuthorLogin($authorLogin);
    $this->SetDoc($doc);
  }
  
  public function GetCreated(){return $this->created;}
  public function SetCreated($created = null){
    if(! $created) {
      $d = new \DateTime();
      
      $this->created = static::dt2iso(new \DateTime(), true);
    } else {
      $this->created = $created;
    }
  }
  
  public function GetDocUnid()        {return $this->docUnid;}
  public function SetDocUnid($v)      {$this->docUnid = $v;}
  public function GetCollection()     {return $this->collection;}
  public function SetCollection($v)   {$this->collection = $v;}
  public function GetAuthorLogin()    {return $this->authorLogin; }
  public function SetAuthorLogin($v)  {$this->authorLogin = $v; }
  public function GetDoc()            {return $this->doc;}
  public function SetDoc($v)          {$this->doc = $v;}
  
  public static function dt2iso(\DateTime $dt, $includeTime = false) {
    if($includeTime) {
      return $dt->format('Ymd').'T'.$dt->format('His');
    }
    return $dt->format('Ymd');
  }
  
  public function toArray() {
    $serializer = new \Treto\PortalBundle\Model\DocumentSerializer($this);
    return $serializer->toArray();
  }
}

?>
