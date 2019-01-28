<?php

namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Treto\UserBundle\Annotation\ExtendedPrivileges as Escalated;

/** 
 * @MongoDB\Document
 */
class JivoSite extends SecureDocument
{
  /** @MongoDB\Id(strategy="auto") */
  protected $id;

  /** @MongoDB\String */
  protected $unid;

  /** @MongoDB\String */
  protected $created;

  /** @MongoDB\String 
   * Тип события
   * По умолчанию: chat_finished
   */
  protected $event_name;

  /** @MongoDB\String
   * Идентификатор чата
   */
  protected $chat_id;

  /** @MongoDB\String
   * Публичный идентификатор чата
   */
  protected $widget_id;

  /** @MongoDB\Hash
   * Объект с информацией о посетителе
   */
  protected $visitor;
 
  /** @MongoDB\Collection
   * Массив объектов с информацией об операторах
   */
  protected $agents;

  /** @MongoDB\Hash
   * Объект с информацией о переписке
   */
  protected $chat;

  public function getId() {return $this->id;}
  public function setId($id) {$this->id = $id;}
  public function getEventName() {return $this->event_name;}
  public function setEventName($value) {$this->event_name = $value;}
  public function getChatId() {return $this->chat_id;}
  public function setChatId($value) {$this->chat_id = $value;}
  public function getWidgetId() {return $this->widget_id;}
  public function setWidgetId($value) {$this->widget_id = $value;}
  public function getVisitor() {return $this->visitor;}
  public function setVisitor($value) {$this->visitor = $value;}
  public function getAgents() {return $this->agents;}
  public function setAgents($value) {$this->agents = $value;}
  public function getСhat() {return $this->chat;}
  public function setСhat($value) {$this->chat = $value;}

  public function __construct($user = null) {
    $this->setCreated();
    $this->setUnid();
  }

  public function getUnid() {return $this->unid;}
  public function setUnid($unid = null) {
    if($unid) { $this->unid = $unid; }
    else { $this->unid = substr(strtoupper(uniqid(time()).uniqid(time())),0,32); }
  }

  public function getCreated() {return $this->Created;}
  public function setCreated($created = null){
    if(! $created) {
      $d = new \DateTime();
      $this->created = static::dt2iso(new \DateTime(), true);
    } else {
      $this->created = $created;
    }
  }

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