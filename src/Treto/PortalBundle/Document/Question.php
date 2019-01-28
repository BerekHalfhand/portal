<?php

namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Treto\UserBundle\Annotation\ExtendedPrivileges as Escalated;

/** 
 * @MongoDB\Document
 */
class Question extends SecureDocument
{
  /** @MongoDB\Id(strategy="auto") */
  protected $id;

  /** @MongoDB\String */
  protected $unid;

  /** @MongoDB\String */
  protected $created;

  /** @MongoDB\String */
  protected $modified;

  /** @MongoDB\String */
  protected $revised;

  /** @MongoDB\String */
  protected $lastaccessed;

  /** @MongoDB\String */
  protected $addedtofile;

  /** @MongoDB\String */
  protected $form;

  /** @MongoDB\String */
  protected $Status;

  /** @MongoDB\String */
  protected $Started;

  /** @MongoDB\String */
  protected $Duration;

  /** @MongoDB\String */
  protected $Author;

  /** @MongoDB\String */
  protected $AuthorRus;

  /** @MongoDB\String */
  protected $CurrentGroup;

  /** @MongoDB\String */
  protected $CurrentQuestionNumber;

  /** @MongoDB\String */
  protected $CurrentQuestionTypeIndex;

  /** @MongoDB\String */
  protected $CurrentQuestionName;

  /** @MongoDB\String */
  protected $CurrentQuestionID;

  /** @MongoDB\String */
  protected $CurrentQuestionCopyID;

  /** @MongoDB\String */
  protected $CurrentQuestionSort;

  /** @MongoDB\Collection */
  protected $QuestionsList;

  /** @MongoDB\String */
  protected $TypeIndex;

  /** @MongoDB\String */
  protected $QUESTIONID;

  /** @MongoDB\String */
  protected $name;

  /** @MongoDB\String */
  protected $Group;

  /** @MongoDB\String */
  protected $ListName;

  /** @MongoDB\String */
  protected $PortalLink;

  /** @MongoDB\Collection */
  protected $TotalList;

  /** @MongoDB\String */
  protected $SelectionType;

  /** @MongoDB\Collection */
  protected $CorrectList;

  /** @MongoDB\String */
  protected $Number;

  /** @MongoDB\Collection */
  protected $Answers;

  /** @MongoDB\String */
  protected $Supplier;

  /** @MongoDB\String */
  protected $Properties;

  /** @MongoDB\String */
  protected $Editors;

  /** @MongoDB\String */
  protected $repeat;

  /** @MongoDB\Collection */
  protected $Criterions;

  /** @MongoDB\String */
  protected $Description;

  /** @MongoDB\String */
  protected $ANSWEREDCOUNT;

  /** @MongoDB\Collection */
  protected $GroupsScore;

  /** @MongoDB\String */
  protected $Finished;

  /** @MongoDB\String */
  protected $DurationReal;

  /** @MongoDB\String */
  protected $TotalScore;

  /** @MongoDB\String */
  protected $Picture;

  /** @MongoDB\String */
  protected $PropertiesExclude;

  /** @MongoDB\String */
  protected $V2AttachmentOptions;

  /** @MongoDB\String */
  protected $PropertiesForSelect;

  /** @MongoDB\String */
  protected $OriginalModTime;

  /** @MongoDB\String */
  protected $IsDraft;

  /** @MongoDB\String */
  protected $Property;

  /** @MongoDB\String */
  protected $VariantsNumber;

  /** @MongoDB\String */
  protected $CorrectVariantsNumber;

  /** @MongoDB\Collection */
  protected $Type_E;

  /** @MongoDB\String */
  protected $Type;

  /** @MongoDB\String */
  protected $DISABLED;

  /** @MongoDB\String */
  protected $Sort;

  /** @MongoDB\String */
  protected $KeepPrivate;

  /** @MongoDB\String */
  protected $Index;

  /** @MongoDB\String */
  protected $WebFlags;

  /** @MongoDB\String */
  protected $Copy;

  /** @MongoDB\String */
  protected $Content;

  /** @MongoDB\String */
  protected $Descriptions;

  /** @MongoDB\String */
  protected $CorrectList_M;

  /** @MongoDB\String */
  protected $CorrectList_S;

  /** @MongoDB\Collection */
  protected $ListTotal;

  /** @MongoDB\String */
  protected $Shop;

  /** @MongoDB\String */
  protected $TimeScore;

  /** @MongoDB\String */
  protected $Answered;

  /** @MongoDB\String */
  protected $Score;

  /** @MongoDB\String */
  protected $ScorePercent;

  /** @MongoDB\String */
  protected $PictureName;

  /** @MongoDB\Collection */
  protected $SelectList;

  /** @MongoDB\Collection */
  protected $Collections;

  /** @MongoDB\Collection */
  protected $Suppliers;

  /** @MongoDB\String */
  protected $Answers_M;

  /** @MongoDB\String */
  protected $Answers_S;

  /** @MongoDB\String */
  protected $ItemName;

  /** @MongoDB\Collection */
  protected $TestList;

  /** @MongoDB\String */
  protected $Reader;

  /** @MongoDB\String */
  protected $ReaderRole;

  /** @MongoDB\Hash */
  protected $security;

  public function getId() {return $this->id;}
  public function setId($id) {$this->id = $id;}
  public function getForm() {return $this->form;}
  public function setForm($form) {$this->form = $form;}
  public function getSecurity() {return $this->security;}
  public function setSecurity($security) {$this->security = $security;}
  public function getUnid() {return $this->unid;}
  public function setUnid($unid = null) {
      if($unid) { $this->unid = $unid; }
      else { $this->unid = substr(strtoupper(uniqid(time()).uniqid(time())),0,32); }
    }
  public function getCreated() {return $this->created;}
  public function getModified() {return $this->modified;}
  public function getRevised() {return $this->revised;}
  public function setRevised($revised) {$this->revised = $revised;}
  public function getLastaccessed() {return $this->lastaccessed;}
  public function setLastaccessed($lastaccessed) {$this->lastaccessed = $lastaccessed;}
  public function getAddedtofile() {return $this->addedtofile;}
  public function setAddedtofile($addedtofile) {$this->addedtofile = $addedtofile;}
  public function getStatus() {return $this->Status;}
  public function setStatus($Status) {$this->Status = $Status;}
  public function getStarted() {return $this->Started;}
  public function setStarted($Started) {$this->Started = $Started;}
  public function getDuration() {return $this->Duration;}
  public function setDuration($Duration) {$this->Duration = $Duration;}
  public function getAuthor() {return $this->Author;}
  public function setAuthor($Author) {$this->Author = $Author;}
  public function getAuthorRus() {return $this->AuthorRus;}
  public function setAuthorRus($AuthorRus) {$this->AuthorRus = $AuthorRus;}
  public function getCurrentGroup() {return $this->CurrentGroup;}
  public function setCurrentGroup($CurrentGroup) {$this->CurrentGroup = $CurrentGroup;}
  public function getCurrentQuestionNumber() {return $this->CurrentQuestionNumber;}
  public function setCurrentQuestionNumber($CurrentQuestionNumber) {$this->CurrentQuestionNumber = $CurrentQuestionNumber;}
  public function getCurrentQuestionTypeIndex() {return $this->CurrentQuestionTypeIndex;}
  public function setCurrentQuestionTypeIndex($CurrentQuestionTypeIndex) {$this->CurrentQuestionTypeIndex = $CurrentQuestionTypeIndex;}
  public function getCurrentQuestionName() {return $this->CurrentQuestionName;}
  public function setCurrentQuestionName($CurrentQuestionName) {$this->CurrentQuestionName = $CurrentQuestionName;}
  public function getCurrentQuestionID() {return $this->CurrentQuestionID;}
  public function setCurrentQuestionID($CurrentQuestionID) {$this->CurrentQuestionID = $CurrentQuestionID;}
  public function getCurrentQuestionCopyID() {return $this->CurrentQuestionCopyID;}
  public function setCurrentQuestionCopyID($CurrentQuestionCopyID) {$this->CurrentQuestionCopyID = $CurrentQuestionCopyID;}
  public function getCurrentQuestionSort() {return $this->CurrentQuestionSort;}
  public function setCurrentQuestionSort($CurrentQuestionSort) {$this->CurrentQuestionSort = $CurrentQuestionSort;}
  public function getQuestionsList() {return $this->QuestionsList;}
  public function setQuestionsList($QuestionsList) {$this->QuestionsList = $QuestionsList;}
  public function getTypeIndex() {return $this->TypeIndex;}
  public function setTypeIndex($TypeIndex) {$this->TypeIndex = $TypeIndex;}
  public function getQUESTIONID() {return $this->QUESTIONID;}
  public function setQUESTIONID($QUESTIONID) {$this->QUESTIONID = $QUESTIONID;}
  public function getName() {return $this->name;}
  public function setName($name) {$this->name = $name;}
  public function getGroup() {return $this->Group;}
  public function setGroup($Group) {$this->Group = $Group;}
  public function getListName() {return $this->ListName;}
  public function setListName($ListName) {$this->ListName = $ListName;}
  public function getPortalLink() {return $this->PortalLink;}
  public function setPortalLink($PortalLink) {$this->PortalLink = $PortalLink;}
  public function getTotalList() {return $this->TotalList;}
  public function setTotalList($TotalList) {$this->TotalList = $TotalList;}
  public function getSelectionType() {return $this->SelectionType;}
  public function setSelectionType($SelectionType) {$this->SelectionType = $SelectionType;}
  public function getCorrectList() {return $this->CorrectList;}
  public function setCorrectList($CorrectList) {$this->CorrectList = $CorrectList;}
  public function getNumber() {return $this->Number;}
  public function setNumber($Number) {$this->Number = $Number;}
  public function getAnswers() {return $this->Answers;}
  public function setAnswers($Answers) {$this->Answers = $Answers;}
  public function getSupplier() {return $this->Supplier;}
  public function setSupplier($Supplier) {$this->Supplier = $Supplier;}
  public function getProperties() {return $this->Properties;}
  public function setProperties($Properties) {$this->Properties = $Properties;}
  public function getEditors() {return $this->Editors;}
  public function setEditors($Editors) {$this->Editors = $Editors;}
  public function getRepeat() {return $this->repeat;}
  public function setRepeat($repeat) {$this->repeat = $repeat;}
  public function getCriterions() {return $this->Criterions;}
  public function setCriterions($Criterions) {$this->Criterions = $Criterions;}
  public function getDescription() {return $this->Description;}
  public function setDescription($Description) {$this->Description = $Description;}
  public function getANSWEREDCOUNT() {return $this->ANSWEREDCOUNT;}
  public function setANSWEREDCOUNT($ANSWEREDCOUNT) {$this->ANSWEREDCOUNT = $ANSWEREDCOUNT;}
  public function getGroupsScore() {return $this->GroupsScore;}
  public function setGroupsScore($GroupsScore) {$this->GroupsScore = $GroupsScore;}
  public function getFinished() {return $this->Finished;}
  public function setFinished($Finished) {$this->Finished = $Finished;}
  public function getDurationReal() {return $this->DurationReal;}
  public function setDurationReal($DurationReal) {$this->DurationReal = $DurationReal;}
  public function getTotalScore() {return $this->TotalScore;}
  public function setTotalScore($TotalScore) {$this->TotalScore = $TotalScore;}
  public function getPicture() {return $this->Picture;}
  public function setPicture($Picture) {$this->Picture = $Picture;}
  public function getPropertiesExclude() {return $this->PropertiesExclude;}
  public function setPropertiesExclude($PropertiesExclude) {$this->PropertiesExclude = $PropertiesExclude;}
  public function getV2AttachmentOptions() {return $this->V2AttachmentOptions;}
  public function setV2AttachmentOptions($V2AttachmentOptions) {$this->V2AttachmentOptions = $V2AttachmentOptions;}
  public function getPropertiesForSelect() {return $this->PropertiesForSelect;}
  public function setPropertiesForSelect($PropertiesForSelect) {$this->PropertiesForSelect = $PropertiesForSelect;}
  public function getOriginalModTime() {return $this->OriginalModTime;}
  public function setOriginalModTime($OriginalModTime) {$this->OriginalModTime = $OriginalModTime;}
  public function getIsDraft() {return $this->IsDraft;}
  public function setIsDraft($IsDraft) {$this->IsDraft = $IsDraft;}
  public function getProperty() {return $this->Property;}
  public function setProperty($Property) {$this->Property = $Property;}
  public function getVariantsNumber() {return $this->VariantsNumber;}
  public function setVariantsNumber($VariantsNumber) {$this->VariantsNumber = $VariantsNumber;}
  public function getCorrectVariantsNumber() {return $this->CorrectVariantsNumber;}
  public function setCorrectVariantsNumber($CorrectVariantsNumber) {$this->CorrectVariantsNumber = $CorrectVariantsNumber;}
  public function getType_E() {return $this->Type_E;}
  public function setType_E($Type_E) {$this->Type_E = $Type_E;}
  public function getType() {return $this->Type;}
  public function setType($Type) {$this->Type = $Type;}
  public function getDISABLED() {return $this->DISABLED;}
  public function setDISABLED($DISABLED) {$this->DISABLED = $DISABLED;}
  public function getSort() {return $this->Sort;}
  public function setSort($Sort) {$this->Sort = $Sort;}
  public function getKeepPrivate() {return $this->KeepPrivate;}
  public function setKeepPrivate($KeepPrivate) {$this->KeepPrivate = $KeepPrivate;}
  public function getIndex() {return $this->Index;}
  public function setIndex($Index) {$this->Index = $Index;}
  public function getWebFlags() {return $this->WebFlags;}
  public function setWebFlags($WebFlags) {$this->WebFlags = $WebFlags;}
  public function getCopy() {return $this->Copy;}
  public function setCopy($Copy) {$this->Copy = $Copy;}
  public function getContent() {return $this->Content;}
  public function setContent($Content) {$this->Content = $Content;}
  public function getDescriptions() {return $this->Descriptions;}
  public function setDescriptions($Descriptions) {$this->Descriptions = $Descriptions;}
  public function getCorrectList_M() {return $this->CorrectList_M;}
  public function setCorrectList_M($CorrectList_M) {$this->CorrectList_M = $CorrectList_M;}
  public function getCorrectList_S() {return $this->CorrectList_S;}
  public function setCorrectList_S($CorrectList_S) {$this->CorrectList_S = $CorrectList_S;}
  public function getListTotal() {return $this->ListTotal;}
  public function setListTotal($ListTotal) {$this->ListTotal = $ListTotal;}
  public function getShop() {return $this->Shop;}
  public function setShop($Shop) {$this->Shop = $Shop;}
  public function getTimeScore() {return $this->TimeScore;}
  public function setTimeScore($TimeScore) {$this->TimeScore = $TimeScore;}
  public function getAnswered() {return $this->Answered;}
  public function setAnswered($Answered) {$this->Answered = $Answered;}
  public function getScore() {return $this->Score;}
  public function setScore($Score) {$this->Score = $Score;}
  public function getScorePercent() {return $this->ScorePercent;}
  public function setScorePercent($ScorePercent) {$this->ScorePercent = $ScorePercent;}
  public function getPictureName() {return $this->PictureName;}
  public function setPictureName($PictureName) {$this->PictureName = $PictureName;}
  public function getSelectList() {return $this->SelectList;}
  public function setSelectList($SelectList) {$this->SelectList = $SelectList;}
  public function getCollections() {return $this->Collections;}
  public function setCollections($Collections) {$this->Collections = $Collections;}
  public function getSuppliers() {return $this->Suppliers;}
  public function setSuppliers($Suppliers) {$this->Suppliers = $Suppliers;}
  public function getAnswers_M() {return $this->Answers_M;}
  public function setAnswers_M($Answers_M) {$this->Answers_M = $Answers_M;}
  public function getAnswers_S() {return $this->Answers_S;}
  public function setAnswers_S($Answers_S) {$this->Answers_S = $Answers_S;}
  public function getItemName() {return $this->ItemName;}
  public function setItemName($ItemName) {$this->ItemName = $ItemName;}
  public function getTestList() {return $this->TestList;}
  public function setTestList($TestList) {$this->TestList = $TestList;}
  public function getReader() {return $this->Reader;}
  public function setReader($Reader) {$this->Reader = $Reader;}
  public function getReaderRole() {return $this->ReaderRole;}
  public function setReaderRole($ReaderRole) {$this->ReaderRole = $ReaderRole;}

  public function __construct($user = null) {
    $this->setCreated();
    $this->setModified();
    $this->setUnid();
    $this->setAuthor($user->getPortalData()->getFullName(true));
    $this->setDefaultSecurity($user);
  }

  public function setCreated($created = null){
    if(! $created) {
      $d = new \DateTime();
      $this->created = static::dt2iso(new \DateTime(), true);
    } else {
      $this->created = $created;
    }
  }
  public function setModified($modified = null){
    if(! $modified) {
      $this->modified = static::dt2iso(new \DateTime(), true);
    } else {
      $this->modified = $modified;
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
