<?php

namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Treto\UserBundle\Annotation\ExtendedPrivileges as Escalated;

/** 
 * @MongoDB\Document
 */
class Guest
{
  /** @MongoDB\Id(strategy="auto") */
  protected $id;

  /** @MongoDB\String */
  protected $created;
  
  /** @MongoDB\String */
  protected $modified;

  /** @MongoDB\String */
  protected $firstName;
  
  /** @MongoDB\String */
  protected $lastName;
  
  /** @MongoDB\String */
  protected $email;
  
  /** @MongoDB\String */
  protected $position;
  
  /** @MongoDB\String */
  protected $birthday;
  
  /** @MongoDB\String */
  protected $sex;
  
  /** @MongoDB\String */
  protected $phone;
  
  /** @MongoDB\String */
  protected $location;
  
  /** @MongoDB\String */
  protected $about;
  
  /** @MongoDB\String */
  protected $main;

  /** @MongoDB\String */
  protected $doc;
  
  /** @MongoDB\String */
  protected $author;
  
  /** @MongoDB\String */
  protected $key;
  
  /** @MongoDB\String */
  protected $status;

  public function __construct($main, $author, $email, $doc = null) {
    $this->SetCreated();
    $this->SetModified();
    $this->SetStatus('active');
    
    $this->SetMain($main);
    $this->SetAuthor($author);
    $this->SetEmail($email);
    $this->SetDoc($doc);
    $this->SetKey($this->generateRandomString(32));
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
  
  public function GetFirstName()      {return $this->firstName;}
  public function SetFirstName($v)    {$this->firstName = $v;}
  public function GetLastName()       {return $this->lastName;}
  public function SetLastName($v)     {$this->lastName = $v;}
  public function GetEmail()          {return $this->email;}
  public function SetEmail($v)        {$this->email = $v;}
  public function GetPosition()       {return $this->position;}
  public function SetPosition($v)     {$this->position = $v;}
  public function GetBirthday()       {return $this->validThru;}
  public function SetBirthday($v)     {$this->validThru = $v;}
  public function GetSex()            {return $this->sex;}
  public function SetSex($v)          {$this->sex = $v;}
  public function GetPhone()          {return $this->phone;}
  public function SetPhone($v)        {$this->phone = $v;}
  public function GetLocation()       {return $this->location;}
  public function SetLocation($v)     {$this->location = $v;}
  public function GetAbout()          {return $this->about;}
  public function SetAbout($v)        {$this->about = $v;}
  
  public function GetMain()           {return $this->main;}
  public function SetMain($v)         {$this->main = $v;}
  public function GetDoc()            {return $this->doc;}
  public function SetDoc($v)          {$this->doc = $v;}
  public function GetAuthor()         {return $this->author;}
  public function SetAuthor($v)       {$this->author = $v;}
  public function GetKey()            {return $this->key;}
  public function SetKey($v)          {$this->key = $v;}
  public function GetStatus()         {return $this->status;}
  public function SetStatus($v)       {$this->status = $v;}
  
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
  
  public function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
  }
}

?>
