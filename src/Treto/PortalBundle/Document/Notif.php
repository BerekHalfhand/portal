<?php

namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Treto\UserBundle\Annotation\ExtendedPrivileges as Escalated;

/** 
 * @MongoDB\Document
 */
class Notif
{
  /** @MongoDB\Id(strategy="auto") */
  protected $id;

  /** @MongoDB\String */
  protected $created;
  
  /** @MongoDB\String */
  protected $modified;

  /** @MongoDB\String */
  protected $addedWhen;
  
  /** @MongoDB\String */
  protected $addedFrom;
  
  /** @MongoDB\String */
  protected $notifyWhen;

  /** @MongoDB\String */
  protected $parentUnid;
  
  /** @MongoDB\String */
  protected $parentForm;
  
  /** @MongoDB\String */
  protected $unid;
  
  /** @MongoDB\String */
  protected $form;
  
  /** @MongoDB\String */
  protected $subject;
  
  /** @MongoDB\Int */
  protected $urgency;
  //-1 - timeline changed
  // 0 - regular
  // 1 - not urgent
  // 2 - urgent
  
  /** @MongoDB\Int */
  protected $isPublic;
  
  /** @MongoDB\Int */
  protected $taskState;
  
  /** @MongoDB\String */
  protected $flag;
  
  /** @MongoDB\String */
  protected $entryOrder;
  
  /** @MongoDB\String */
  protected $Author;
  
  /** @MongoDB\String */
  protected $AuthorLogin;
  
  /** @MongoDB\String */
  protected $receiver;

  /** @MongoDB\Hash */
  protected $docs;
  
  /** @MongoDB\Hash */
  protected $fields;
  
  /** @MongoDB\String */
  protected $documentType;
  
  /** @MongoDB\String */
  protected $status;
  
  /** @MongoDB\Collection */
  protected $log;

  /** @MongoDB\String */
  protected $sendShareFrom;

  /** @MongoDB\String */
  protected $shareAuthorLogin;

  public function GetSendShareFrom(){ return $this->sendShareFrom; }
  public function SetSendShareFrom($sendFrom){ $this->sendShareFrom = $sendFrom; }
  public function GetShareAuthorLogin(){ return $this->shareAuthorLogin; }
  public function SetShareAuthorLogin($authorLogin){ $this->shareAuthorLogin = $authorLogin; }

  public function __construct($parentUnid, $unid, $receiver, $urgency) {
    $this->SetCreated();
    $this->SetModified();
    $this->SetStatus('active');
    
    $this->SetParentUnid($parentUnid);
    $this->SetUnid($unid);
    $this->SetReceiver($receiver);
    $this->SetUrgency($urgency);
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
  
  public function GetAddedWhen(){return $this->addedWhen;}
  public function SetAddedWhen($v = null){
    if(!$v) {
      $this->addedWhen = static::dt2iso(new \DateTime(), true);
    } else {
      $this->addedWhen = $v;
    }
  }
  
  public function GetEntryOrder(){return $this->entryOrder;}
  public function SetEntryOrder($v = null){
    if(!$v) {
      $this->entryOrder = static::dt2iso(new \DateTime(), true);
    } else {
      $this->entryOrder = $v;
    }
  }
  
  public function GetAddedFrom()      {return $this->addedFrom;}
  public function SetAddedFrom($v)    {$this->addedFrom = $v;}
  public function GetNotifyWhen()     {return $this->notifyWhen;}
  public function SetNotifyWhen($v)   {$this->notifyWhen = $v;}
  public function GetParentUnid()     {return $this->parentUnid;}
  public function SetParentUnid($v)   {$this->parentUnid = $v;}
  public function GetParentForm()     {return $this->parentForm;}
  public function SetParentForm($v)   {$this->parentForm = $v;}
  public function GetUnid()           {return $this->unid;}
  public function SetUnid($v)         {$this->unid = $v;}
  public function GetForm()           {return $this->form;}
  public function SetForm($v)         {$this->form = $v;}
  public function GetSubject()        {return $this->subject;}
  public function SetSubject($v)      {$this->subject = $v;}
  public function GetUrgency()        {return $this->urgency;}
  public function SetUrgency($v)      {$this->urgency = $v;}
  public function GetIsPublic()       {return $this->isPublic;}
  public function SetIsPublic($v)     {$this->isPublic = $v;}
  public function GetTaskState()      {return $this->taskState;}
  public function SetTaskState($v)    {$this->taskState = $v;}
  public function GetFlag()           {return $this->flag;}
  public function SetFlag($v)         {$this->flag = $v;}
  public function GetAuthor()         {return $this->Author;}
  public function SetAuthor($v)       {$this->Author = $v;}
  public function GetAuthorLogin()    {return $this->AuthorLogin;}
  public function SetAuthorLogin($v)  {$this->AuthorLogin = $v;}
  public function GetReceiver()       {return $this->receiver;}
  public function SetReceiver($v)     {$this->receiver = $v;}
  public function GetDocs()           {return $this->docs;}
  public function SetDocs($v)         {$this->docs = $v;}
  public function GetFields()         {return $this->fields;}
  public function SetFields($v)       {$this->fields = $v;}
  public function GetDocumentType()   {return $this->documentType;}
  public function SetDocumentType($v) {$this->documentType = $v;}
  public function GetStatus()         {return $this->status;}
  public function SetStatus($v) {
    if ($v == 'inactive') {
      $this->addedWhen = null;
      $this->addedFrom = null;
      $this->notifyWhen = null;
      $this->docs = new \stdClass();
    }
    $this->status = $v;
  }
  public function GetLog()            {return $this->log?$this->log:[];}
  public function SetLog($v)          {$this->log = $v;}
  
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
