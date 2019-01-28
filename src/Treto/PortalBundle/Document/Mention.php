<?php

namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Treto\UserBundle\Annotation\ExtendedPrivileges as Escalated;

/** 
 * @MongoDB\Document
 */
class Mention
{
  /** @MongoDB\Id(strategy="auto") */
  protected $id;

  /** @MongoDB\String */
  protected $created;
  
  /** @MongoDB\String */
  protected $modified;

  /** @MongoDB\String */
  protected $author;
  
  /** @MongoDB\String */
  protected $receiver;

  /** @MongoDB\String */
  protected $parent;  //UNID
  
  /** @MongoDB\String */
  protected $doc;     //UNID
  
  /** @MongoDB\String */
  protected $status;

  public function __construct($parent, $doc, $author, $receiver, $status = 'active') {
    $this->SetCreated();
    $this->SetModified();
    
    $this->SetParent($parent);
    $this->SetDoc($doc);
    $this->SetAuthor($author);
    $this->SetReceiver($receiver);
    $this->SetStatus($status);
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
  
  public function GetModified(){return $this->modified;}
  public function SetModified($modified = null){
    if(! $modified) {
      $this->modified = static::dt2iso(new \DateTime(), true);
    } else {
      $this->modified = $modified;
    }
  }
    
  public function GetId()         {return $this->id;}
  public function SetId($v)       {$this->id = $v;}
  
  public function GetAuthor()     {return $this->author;}
  public function SetAuthor($v)   {$this->author = $v;}
  
  public function GetReceiver()   {return $this->receiver;}
  public function SetReceiver($v) {$this->receiver = $v;}
  
  public function GetParent()     {return $this->parent;}
  public function SetParent($v)   {$this->parent = $v;}
  
  public function GetDoc()        {return $this->doc;}
  public function SetDoc($v)      {$this->doc = $v;}
  
  public function GetStatus()     {return $this->status;}
  public function SetStatus($v)   {$this->status = $v;}
  
  public static function dt2iso(\DateTime $dt, $includeTime = false) {
    if($includeTime) {
      return $dt->format('Ymd').'T'.$dt->format('His');
    }
    return $dt->format('Ymd');
  }
}

?>
