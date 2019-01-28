<?php

namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Treto\UserBundle\Annotation\ExtendedPrivileges as Escalated;

/** 
 * @MongoDB\Document(repositoryClass="DailyStatRepository")
 */
class DailyStat extends SecureDocument
{
  /** @MongoDB\Id(strategy="auto") */
  protected $id;

  /** @MongoDB\String */
  protected $created;
  
  /** @MongoDB\String */
  protected $modified;

  /** @MongoDB\String */
  protected $name;

  /** @MongoDB\Hash */
  protected $messagesUsers;
  
  /** @MongoDB\Hash */
  protected $likes;
  
  /** @MongoDB\Hash */
  protected $dislikes;
  
  /** @MongoDB\Int */
  protected $messagesCount;
  
  /** @MongoDB\Int */
  protected $tasksCount;
  
  /** @MongoDB\Int */
  protected $themesCount;
  
  /** @MongoDB\Int */
  protected $statUpdateCount;
  
  /** @MongoDB\Hash */
  protected $employed;
  
  /** @MongoDB\Hash */
  protected $fired;

  /** @MongoDB\Hash */
  protected $working;
  
  /** @MongoDB\Boolean */
  protected $statIncludesDaily;
  
  /** @MongoDB\Hash */
  protected $tasksByUsers;
  
  /** @MongoDB\Hash */
  protected $themesByUsers;
  
  /** @MongoDB\Hash */
  protected $tasksEndedByUsers;
  
  /** @MongoDB\Hash */
  protected $rocketChatMsgs;

  /** @MongoDB\Hash */
  protected $fastSlowWorkers;

  public function __construct($user = null) {
    $this->SetCreated();
    $this->SetModified();
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
  public function GetMessagesUsers() {return $this->messagesUsers;}
  public function SetMessagesUsers($messagesUsers) {$this->messagesUsers = $messagesUsers;}
  public function GetLikes() {return $this->likes;}
  public function SetLikes($likes) {$this->likes = $likes;}
  public function GetDislikes() {return $this->dislikes;}
  public function SetDislikes($dislikes) {$this->dislikes = $dislikes;}
  public function GetMessagesCount() {return $this->messagesCount;}
  public function SetMessagesCount($messagesCount) {$this->messagesCount = $messagesCount;}
  public function GetTasksCount() {return $this->tasksCount;}
  public function SetTasksCount($tasksCount) {$this->tasksCount = $tasksCount;}
  public function GetThemesCount() {return $this->themesCount;}
  public function SetThemesCount($themesCount) {$this->themesCount = $themesCount;}
  public function GetStatUpdateCount() {return $this->statUpdateCount;}
  public function SetStatUpdateCount($statUpdateCount) {$this->statUpdateCount = $statUpdateCount;}
  public function UpdateCount() {$this->statUpdateCount++;}
  public function GetStatIncludesDaily() {return $this->statIncludesDaily;}
  public function SetStatIncludesDaily($statIncludesDaily) {$this->statIncludesDaily = $statIncludesDaily;}
  public function GetEmployed() {return $this->employed;}
  public function SetEmployed($employed) {$this->employed = $employed;}
  public function GetFired() {return $this->fired;}
  public function SetFired($fired) {$this->fired = $fired;}
  public function GetWorking() {return $this->working;}
  public function SetWorking($working) {$this->working = $working;}
  public function GetTasksByUsers() {return $this->tasksByUsers;}
  public function SetTasksByUsers($tasksByUsers) {$this->tasksByUsers = $tasksByUsers;}
  public function GetThemesByUsers() {return $this->themesByUsers;}
  public function SetThemesByUsers($themesByUsers) {$this->themesByUsers = $themesByUsers;}
  public function GetTasksEndedByUsers() {return $this->tasksEndedByUsers;}
  public function SetTasksEndedByUsers($tasksEndedByUsers) {$this->tasksEndedByUsers = $tasksEndedByUsers;}
  public function GetRocketChatMsgs() {return $this->rocketChatMsgs;}
  public function SetRocketChatMsgs($rocketChatMsgs) {$this->rocketChatMsgs = $rocketChatMsgs;}
  public function GetFastSlowWorkers() {return $this->fastSlowWorkers;}
  public function SetFastSlowWorkers($fastSlowWorkers) {$this->fastSlowWorkers = $fastSlowWorkers;}
    
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