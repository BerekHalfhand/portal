<?php

namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Treto\UserBundle\Annotation\ExtendedPrivileges as Escalated;
use Treto\UserBundle\Document\User;

/**
 * @MongoDB\Document(repositoryClass="PortalRepository") @MongoDB\HasLifecycleCallbacks
 */
class Portal extends SecureDocument
{
    public $manualModified = false;
    /*
    * ==================== form: "Common" ====================
    */

    /** @MongoDB\Id(strategy="auto") */
    protected $_id;

    /** @MongoDB\String
      * @Escalated(set="PM") */
    protected $form;

    /** @MongoDB\String */
    protected $unid;

    /** @MongoDB\Int */
    protected $CONVERTED;

    /** @MongoDB\String */
    protected $linkedUNID;
    
    /** @MongoDB\Int */
    protected $isLinked;

    /** @MongoDB\String */
    protected $SubID;

    /** @MongoDB\String */
    protected $noteid;

    /** @MongoDB\String */
    protected $sequence;

    /** @MongoDB\String */
    protected $created;

    /** @MongoDB\String */
    protected $modified;

    /** @MongoDB\String */
    protected $employeer;

    /** @MongoDB\String */
    protected $lastaccessed;

    /** @MongoDB\String */
    protected $body;

    /** @MongoDB\Collection */
    protected $attachments = [];

    /** @MongoDB\Collection */
    protected $mailAccess;

    /** @MongoDB\String */
    protected $mailStatus; //close, open

    /** @MongoDB\String */
    protected $Author;

    /** @MongoDB\String */
    protected $AuthorRus;

    /** @MongoDB\String */
    protected $currency;

    /** @MongoDB\Collection */
    protected $Tags;

    /** @MongoDB\String */
    protected $typeDoc;

    /** @MongoDB\String */
    protected $status;

    /** @MongoDB\Collection  */
    protected $redirectMailTo;

    /** @MongoDB\String */
    protected $parentID;

    /** @MongoDB\String */
    protected $ParentDbName;

    /** @MongoDB\String */
    protected $subjectID;

    /** @MongoDB\String */
    protected $V2AttachmentOptions;

    /** @MongoDB\String */
    protected $id;

    /** @MongoDB\String  */
    protected $Department;

    /** @MongoDB\String */
    protected $SubDivision;

    /** @MongoDB\String */
    protected $RegionID;

    /** @MongoDB\Collection */
    protected $AttachedDoc;

    /** @MongoDB\String */
    protected $subject;

    /** @MongoDB\Int */
    protected $countOpen;

    /** @MongoDB\String */
    protected $createdDate;

    /** @MongoDB\Int */
    protected $countMess = 0;

    /** @MongoDB\String */
    protected $authorLastMess;

    /** @MongoDB\String */
    protected $dateLastMess;

    /** @MongoDB\String */
    protected $isArchive;

    /** @MongoDB\String */
    protected $dateModified;

    /** @MongoDB\String */
    protected $mailHash;

    /** @MongoDB\Collection */
    protected $READEDBY; /*DEPRECATED*/
    
    /** @MongoDB\Hash */
    protected $readBy;

    /** @MongoDB\String */
    protected $messageSubject; /*DEPRECATED*/

    /** @MongoDB\String */
    protected $bodyWeb;

    /** @MongoDB\String */
    protected $AuthorFullNotesName;

    /** @MongoDB\String */
    protected $smallfies;

    /** @MongoDB\String */
    protected $C1;

    /** @MongoDB\String */
    protected $type;

    /** @MongoDB\String */
    protected $RATING;

    /** @MongoDB\String */
    protected $CreationDate;

    /** @MongoDB\String */
    protected $body2;

    /** @MongoDB\String */
    protected $countOpenRating;

    /** @MongoDB\String */
    protected $countOpenRatingDate;

    /** @MongoDB\String */
    protected $Creator;

    /** @MongoDB\String */
    protected $DateBegin;

    /** @MongoDB\String */
    protected $Place;

    /** @MongoDB\String */
    protected $RemindMeIfCompleted;

    /** @MongoDB\String */
    protected $IsSubmitted;

    /** @MongoDB\String */
    protected $USR;

    /** @MongoDB\String */
    protected $UsersRegion;

    /** @MongoDB\String */
    protected $DocType;

    /** @MongoDB\String */
    protected $FreqDays;

    /** @MongoDB\String */
    protected $FreqMonth;

    /** @MongoDB\String */
    protected $FreqWeek;

    /** @MongoDB\String */
    protected $Country;

    /** @MongoDB\String */
    protected $FreqYear;

    /** @MongoDB\String */
    protected $taskDateStart;

    /** @MongoDB\String */
    protected $taskDateEnd;

    /** @MongoDB\String */
    protected $taskDateRealStart;

    /** @MongoDB\String */
    protected $taskDateRealEnd;

    /** @MongoDB\String */
    protected $taskDateCompleted;
    /** @MongoDB\String */
    protected $taskDateCompletedH;
    /** @MongoDB\String */
    protected $taskDateCompletedM;

    /** @MongoDB\String */
    protected $taskID;
    
    /** @MongoDB\String */
    protected $cat1;

    /** @MongoDB\String */
    protected $Header;

    /** @MongoDB\String */
    protected $Adress;

//     /** @MongoDB\Hash */
//     protected $Notif;

     /** @MongoDB\Hash */
     protected $userSettings;

    /** @MongoDB\Collection */
    protected $PASSWORD_1;

    /** @MongoDB\String */
    protected $showUnreadedTree;

    /** @MongoDB\String */
    protected $FullName;

    /** @MongoDB\String */
    protected $ForeignPassport;

    /** @MongoDB\String */
    protected $AddressToSite;

    /** @MongoDB\String */
    protected $WorkPhoneToSite;

    /** @MongoDB\String */
    protected $HomePhoneToSite;

    /** @MongoDB\String */
    protected $MobileFhoneToSite;

    /** @MongoDB\String */
    protected $MobilePhoneToSite_1;

    /** @MongoDB\String */
    protected $MobilePhoneToSite_2;

    /** @MongoDB\String */
    protected $EmailToSite;

    /** @MongoDB\String */
    protected $Login;

    /** @MongoDB\String */
    protected $contactUnid;

    /** @MongoDB\String */
    protected $Organosation;

    /** @MongoDB\Collection  */
    protected $WorkGroup;

    /** @MongoDB\Collection */
    protected $section;

    /** @MongoDB\Collection */
    protected $section_1;

    /** @MongoDB\String */
    protected $DtDismiss;

    /** @MongoDB\String */
    protected $level;

    /** @MongoDB\Collection
      * @Escalated(set="PM") */
    protected $role = ['all'];

    /** @MongoDB\Collection */
    protected $ContactWithMobileFhone;

    /** @MongoDB\Collection */
    protected $ContactWithMobileFhone_1;

    /** @MongoDB\Collection */
    protected $ContactWithMobileFhone_2;

    /** @MongoDB\String */
    protected $Email;

    /** @MongoDB\String */
    protected $PrivateEmail;

    /** @MongoDB\String */
    protected $ICQ;

    /** @MongoDB\String */
    protected $Skype;

    /** @MongoDB\String */
    protected $Birthday;

    /** @MongoDB\String */
    protected $DocID;

    /** @MongoDB\Collection */
    protected $Manager;

    /** @MongoDB\Collection */
    protected $Coucher;

    /** @MongoDB\String */
    protected $FullNameInRus;

    /** @MongoDB\Collection */
    protected $ListOfWorkGroups;

    /** @MongoDB\Collection */
    protected $TownID;

    /** @MongoDB\Collection */
    protected $Personal_Data;

    /** @MongoDB\String */
    protected $name;

    /** @MongoDB\String */
    protected $LastName;

    /** @MongoDB\String */
    protected $pushToken;

    /** @MongoDB\Collection */
    protected $role_1;

    /** @MongoDB\Collection */
    protected $Reader;

    /** @MongoDB\String */
    protected $Priority;

    /** @MongoDB\String */
    protected $Dep;

    /** @MongoDB\String */
    protected $SelectSubjectNotes;

    /** @MongoDB\Collection */
    protected $SelectRegion;

    /** @MongoDB\String */
    protected $Count;

    /** @MongoDB\String */
    protected $query;

    /** @MongoDB\String */
    protected $ScriptRTF;

    /** @MongoDB\String */
    protected $fromSite;

    /** @MongoDB\String */
    protected $viewBody;

    /** @MongoDB\String */
    protected $HideFilesToHomePage;

    /** @MongoDB\String */
    protected $C2;

    /** @MongoDB\String */
    protected $C3;

    /** @MongoDB\String */
    protected $C4;

    /** @MongoDB\String */
    protected $DtWork;

    /** @MongoDB\String */
    protected $EmplUNID;

    /** @MongoDB\String */
    protected $MiddleName;

    /** @MongoDB\Collection */
    protected $Subscribe;

    /** @MongoDB\String */
    protected $authorLogin;

    /** @MongoDB\String */
    protected $WaitPerformer;

    /** @MongoDB\String */
    protected $LikeDate;

    /** @MongoDB\String */
    protected $LikeNotDate;
    
    /** @MongoDB\Hash */
    protected $likes;

    /** @MongoDB\String */
    protected $companyName;
    /*
    * ==================== form: "message" ====================
    */

    /** @MongoDB\String */
    protected $locale;

    /*
    * ==================== form: "MessageLite" ====================
    */

    /** @MongoDB\String */
    protected $Query_String_Decoded;

    /** @MongoDB\String */
    protected $parentUnid;

    /** @MongoDB\String */
    protected $subjectUnid;

    /*
    * ==================== form: "messagebb" ====================
    */

    /*
    * ==================== form: "Empl" ====================
    */

    /** @MongoDB\String */
    protected $SortType;

    /** @MongoDB\String */
    protected $lastConnect;

    /** @MongoDB\Collection */
    protected $boss;

    /** @MongoDB\String */
    protected $SubDivision_2;

    /** @MongoDB\String */
    protected $lastDiv;

    /** @MongoDB\Collection */
    protected $bossLat;

    /** @MongoDB\Collection */
    protected $About = [];

    /** @MongoDB\Collection */
    protected $Resume = [];

    /** @MongoDB\Collection */
    protected $favorites;

    /** @MongoDB\Collection */
    protected $geoCity;

    /** @MongoDB\Collection */
    protected $geoCoord;

    /*
    * ==================== form: "formTask" ====================
    */

    /** @MongoDB\String */
    protected $RepeatLimitDate;

    /** @MongoDB\Collection */
    protected $EscalationManagers;

    /*
    * ==================== form: "formProcess" ====================
    */

    /** @MongoDB\String */
    protected $messageBody;

    /** @MongoDB\String */
    protected $ToSite;

    /** @MongoDB\String */
    protected $NotForSite;

    /*
    * ==================== form: "decisionAfter" ====================
    */

    /** @MongoDB\String */
    protected $fileDisplayDes;

    /*
    * ==================== form: "formVoting" ====================
    */

    /** @MongoDB\Hash
      * @Escalated(set="PM") */
    protected $answers;

    /** @MongoDB\Hash */
    protected $AnswersData;

    /** @MongoDB\String */
    protected $AnswersLim;

    /** @MongoDB\String */
    protected $AuthorCN;

    /** @MongoDB\String */
    protected $subjVoting;

    /** @MongoDB\String */
    protected $PostFinishProcess;

    /** @MongoDB\String */
    protected $ShowRating;

    /** @MongoDB\Collection */
    protected $Refuses;

    /** @MongoDB\String */
    protected $PeriodPoll;

    /** @MongoDB\Int */
    protected $ShowOnIndex;
    
    /** @MongoDB\Collection */
    protected $watchedBy;

    /*
    * ==================== form: "formTask" ====================
    */

    /** @MongoDB\Collection */
    protected $taskPerformerLat;

    /** @MongoDB\Collection */
    protected $taskPerformer;
    
    /** @MongoDB\String */
    protected $responsible;

    /** @MongoDB\String */
    protected $Difficulty;

    /** @MongoDB\String */
    protected $action;

    /** @MongoDB\Collection */
    protected $CheckerLat;

    /** @MongoDB\Collection */
    protected $Checker;

    /** @MongoDB\Int */
    protected $TaskStateCurrent;

    /** @MongoDB\Int */
    protected $TaskStatePrevious;

//     Создана - 0
//     Смена исполнителя - 3-4
//     Установлен срок - 5
//     Смена приоритета - 7
//     Уведомлена - 10
//     Отправлена на накат - 12
//     Накат выполнен - 13
//     Возвращена на доработку - 15
//     Отправлена на проверку - 20-21
//     Принята - 25
//     Подвешена - 30
//     Отменена - 35

    /*
    * ==================== form: "UnreadedStub" ====================
    */

    /** @MongoDB\String */
    protected $subjectDB;

    /** @MongoDB\String */
    protected $SUBJECTFORM;

    /*
    * ==================== form: "WorkPlan" ====================
    */

    /** @MongoDB\String */
    protected $Year;

    /** @MongoDB\String */
    protected $Month;

    /** @MongoDB\Collection */
    protected $DaysData;

    /** @MongoDB\String */
    protected $Region;

    /** @MongoDB\String */
    protected $ADDITIONAL;

    /** @MongoDB\String */
    protected $WeekEndsCount;

    /** @MongoDB\Collection */
    protected $History;

    /*
    * ==================== form: "formProcess" ====================
    */

    /** @MongoDB\String */
    protected $cat2;
    protected $cat3;

    /** @MongoDB\String */
    protected $SEOTitle;
    /** @MongoDB\String */
    protected $SEODescription;
    /** @MongoDB\String */
    protected $SEOKeywords;
    /** @MongoDB\String */
    protected $VacAnnotation;
    /** @MongoDB\Collection */
    protected $VacManager;
    /** @MongoDB\String */
    protected $archiveVacUnid;

    /*
    * ==================== form: "formAdapt" ====================
    */

    /** @MongoDB\String */
    protected $Sex;

    /** @MongoDB\Collection */
    protected $WorkGroupEng;

    /** @MongoDB\String */
    protected $TestPeriod;

    /** @MongoDB\String */
    protected $PayTerms;

    /** @MongoDB\String */
    protected $Password;

    /** @MongoDB\Collection */
    protected $AccessType;

    /** @MongoDB\String */
    protected $Recruter;

    /** @MongoDB\String */
    protected $Reference;

    /** @MongoDB\String */
    protected $HeadIT;

    /** @MongoDB\String */
    protected $ManagerHR;

    /** @MongoDB\String */
    protected $HeadFin;

    /** @MongoDB\String */
    protected $HeadHR;

    /** @MongoDB\Collection */
    protected $Questionary;

    /** @MongoDB\String */
    protected $QuestionaryID;

    /** @MongoDB\Collection */
    protected $DepSubmiss;

    /** @MongoDB\String */
    protected $Salary;

    /* DISABLED
     * @MongoDB\ReferenceOne(
     *     targetDocument="Treto\UserBundle\Document\User",
     *     mappedBy="portalData",
     *     repositoryMethod="getForPortal"
     * )
     */
    public $userData;

    /* DISABLED
    * @MongoDB\ReferenceOne(
    *     targetDocument="Treto\PortalBundle\Document\Contacts",
    *     mappedBy="portalData",
    *     repositoryMethod="getForPortal"
    * )
    */
    public $contactData;

    /** @MongoDB\Hash */
    protected $security;

    /** @MongoDB\Hash */
    protected $shareSecurity;

    /** @MongoDB\String */
    protected $shareTempData;

    /** @MongoDB\Collection */
    protected $sharePerformers;

    /** @MongoDB\Collection */
    protected $shareChecker;

    /** @MongoDB\String */
    protected $commentMail;

    /** @MongoDB\String */
    protected $sendShareFrom;

    /** @MongoDB\String */
    protected $createHost;

    /** @MongoDB\String */
    protected $shareAuthorLogin;

    /* =============================================================
    ==================== Getters and Setters ====================
    ============================================================= */

    public function __construct($user = null) {
      $this->SetCreated();
      $this->SetModified();
      $this->setDefaultSecurity($user);
    }
    public function GetShareTempData(){ return $this->shareTempData; }
    public function SetShareTempData($shareTempData){ $this->shareTempData = $shareTempData; }
    public function GetUserSettings(){ return $this->userSettings; }
    public function SetUserSettings($userSettings){ $this->userSettings = $userSettings; }
    public function GetSharePerformers(){ return $this->sharePerformers; }
    public function SetSharePerformers($sharePerformers){ $this->sharePerformers = $sharePerformers; }
    public function GetCreateHost(){ return $this->createHost; }
    public function SetCreateHost($createHost){ $this->createHost = $createHost; }
    public function GetShareChecker(){ return $this->shareChecker; }
    public function SetShareChecker($shareChecker){ $this->shareChecker = $shareChecker; }
    public function GetSendShareFrom(){ return $this->sendShareFrom; }
    public function SetSendShareFrom($sendFrom){ $this->sendShareFrom = $sendFrom; }
    public function GetShareAuthorLogin(){ return $this->shareAuthorLogin; }
    public function SetShareAuthorLogin($authorLogin){ $this->shareAuthorLogin = $authorLogin; }
    public function GetRedirectMailTo(){ return $this->redirectMailTo; }
    public function SetRedirectMailTo($redirectMailTo){ $this->redirectMailTo = $redirectMailTo; }
    public function GetСurrency(){ return $this->currency; }
    public function SetСurrency($currency){ $this->currency = $currency; }
    public function GetContactUnid() { return $this->contactUnid; }
    public function SetContactUnid($contactUnid) { $this->contactUnid = $contactUnid; }
    /* form: "Common" */
    public function Get_id() { return $this->_id; }
    public function Set_id($_id) { $this->_id = $_id; }
    public function GetMailHash() { return $this->mailHash; }
    public function SetMailHash($mailHash) { $this->mailHash = $mailHash; }
    public function GetId() { return $this->_id; }
    public function SetId($_id) { $this->_id = $_id; }
    public function GetForm() { return $this->form; }
    public function SetForm($form) { $this->form = $form; }
    public function GetUnid() { return $this->unid; }
    public function SetUnid($unid = null) {
      if($unid) { $this->unid = $unid; }
      else { $this->unid = substr(strtoupper(uniqid(time()).uniqid(time())),0,32); }
    }
    public function GetCONVERTED() { return $this->CONVERTED; }
    public function SetCONVERTED($v) {
      $this->CONVERTED = $v;
    }
    public function GetLinkedUNID() { return $this->linkedUNID; }
    public function SetLinkedUNID($linkedUNID) { $this->linkedUNID = $linkedUNID; }
    public function GetIsLinked() { return $this->isLinked; }
    public function SetIsLinked($v) { $this->isLinked = $v; }
    public function GetSubID() { return $this->SubID; }
    public function SetSubID($SubID) { $this->SubID = $SubID; }
    public function GetNoteid() { return $this->noteid; }
    public function SetNoteid($noteid) { $this->noteid = $noteid; }
    public function GetSequence() { return $this->sequence; }
    public function SetSequence($sequence) { $this->sequence = $sequence; }
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
      if(!$modified) {
        $this->modified = static::dt2iso(new \DateTime(), true);
      } else {
        $this->modified = $modified;
      }
    }
    public function GetLastaccessed() { return $this->lastaccessed; }
    public function SetLastaccessed($lastaccessed) { $this->lastaccessed = $lastaccessed; }
    public function GetBody() { return $this->body; }
    public function SetBody($body) { $this->body = $body; }
    public function GetAuthor() { return $this->Author; }
    public function SetAuthor($Author) { $this->Author = $Author; }
    public function GetToSite() { return $this->ToSite; }
    public function SetToSite($toSite) { $this->ToSite = $toSite; }
    public function GetNotForSite() { return $this->NotForSite; }
    public function SetNotForSite($NotForSite) { $this->NotForSite = $NotForSite; }
    public function GetAuthorRus() { return $this->AuthorRus; }
    public function SetAuthorRus($AuthorRus) { $this->AuthorRus = $AuthorRus; }
    public function GetTags() { return $this->Tags; }
    public function SetTags($Tags) { $this->Tags = $Tags; }
    public function GetTypeDoc() { return $this->typeDoc; }
    public function SetTypeDoc($typeDoc) { $this->typeDoc = $typeDoc; }
    public function GetStatus() { return $this->status; }
    public function SetStatus($status) { $this->status = $status; }
    public function GetParentID() { return $this->parentID; }
    public function SetParentID($parentID) { $this->parentID = $parentID; }
    public function HasParent() { return $this->parentID && ($this->parentID != $this->unid); }
    public function GetSubjectID() { return $this->subjectID; }
    public function SetSubjectID($subjectID) { $this->subjectID = $subjectID; }
    public function HasSubject() { return $this->subjectID && ($this->subjectID != $this->unid); }
    public function GetV2AttachmentOptions() { return $this->V2AttachmentOptions; }
    public function SetV2AttachmentOptions($V2AttachmentOptions) { $this->V2AttachmentOptions = $V2AttachmentOptions; }
    public function GetDepartment() { return $this->Department; }
    public function SetDepartment($Department) { $this->Department = $Department; }
    public function GetSubDivision() { return $this->SubDivision; }
    public function SetSubDivision($SubDivision) { $this->SubDivision = $SubDivision; }
    public function GetRegionID() { return $this->RegionID; }
    public function SetRegionID($RegionID) { $this->RegionID = $RegionID; }
    public function GetAttachedDoc() { return $this->AttachedDoc;}
    public function SetAttachedDoc($v) { $this->AttachedDoc = $v; }
    public function GetSubject() { return $this->subject; }
    public function SetSubject($subject) { $this->subject = $subject; }
    public function GetCountOpen() { return $this->countOpen; }
    public function SetCountOpen($countOpen) { $this->countOpen = $countOpen; }
    public function GetCreatedDate() { return $this->createdDate; }
    public function SetCreatedDate($createdDate) { $this->createdDate = $createdDate; }
    public function GetCountMess() { return $this->countMess; }
    public function SetCountMess($countMess) { $this->countMess = $countMess; }
    public function IncrementCountMess($fromUser, $simple = false) {
      if(($fromUser && !$simple) || $simple){
        if($fromUser){
          $this->authorLastMess = $fromUser->getPortalData()->GetFullNameInRus();
          $this->dateLastMess = static::dt2iso(new \DateTime(), true);
        }
        $this->countMess++;
      }
    }
    public function DecrementCountMess(){
      if($this->countMess > 0){
        $this->countMess--;
      }
    }
    public function GetAuthorLastMess() { return $this->authorLastMess; }
    public function SetAuthorLastMess($authorLastMess) { $this->authorLastMess = $authorLastMess; }
    public function GetDateLastMess() { return $this->dateLastMess; }
    public function SetDateLastMess($dateLastMess) { $this->dateLastMess = $dateLastMess; }
    public function GetDateModified() { return $this->dateModified; }
    public function SetDateModified($dateModified) { $this->dateModified = $dateModified; }
    public function GetREADEDBY() { return $this->READEDBY; }
    public function SetREADEDBY($READEDBY) { $this->READEDBY = $READEDBY; }
    public function GetReadBy() { return $this->readBy ? $this->readBy : []; }
    public function SetReadBy($v) { $this->readBy = $v; }
    public function GetMessageSubject() { return $this->messageSubject; }
    public function SetMessageSubject($messageSubject) { $this->messageSubject = $messageSubject; }
    public function GetBodyWeb() { return $this->bodyWeb; }
    public function SetBodyWeb($bodyWeb) { $this->bodyWeb = $bodyWeb; }
    public function GetEmployeer() { return $this->employeer; }
    public function SetEmployeer($employeer) { $this->employeer = $employeer; }
    public function GetAuthorFullNotesName() { return $this->AuthorFullNotesName; }
    public function SetAuthorFullNotesName($AuthorFullNotesName) { $this->AuthorFullNotesName = $AuthorFullNotesName; }
    public function GetSmallfies() { return $this->smallfies; }
    public function SetSmallfies($smallfies) { $this->smallfies = $smallfies; }
    public function GetC1() { return $this->C1; }
    public function SetC1($C1) { $this->C1 = $C1; }
    public function GetC3() { return $this->C3; }
    public function SetC3($C3) { $this->C3 = $C3; }
    public function GetC4() { return $this->C4; }
    public function SetC4($C4) { $this->C4 = $C4; }
    public function GetType() { return $this->type; }
    public function SetType($type) { $this->type = $type; }
    public function GetRATING() { return $this->RATING; }
    public function SetRATING($RATING) { $this->RATING = $RATING; }
    public function GetCreationDate() { return $this->CreationDate; }
    public function SetCreationDate($CreationDate) { $this->CreationDate = $CreationDate; }
    public function GetBody2() { return $this->body2; }
    public function SetBody2($body2) { $this->body2 = $body2; }
    public function GetCountOpenRating() { return $this->countOpenRating; }
    public function SetCountOpenRating($countOpenRating) { $this->countOpenRating = $countOpenRating; }
    public function GetCountOpenRatingDate() { return $this->countOpenRatingDate; }
    public function SetCountOpenRatingDate($countOpenRatingDate) { $this->countOpenRatingDate = $countOpenRatingDate; }
    public function GetCreator() { return $this->Creator; }
    public function SetCreator($Creator) { $this->Creator = $Creator; }
    public function GetDateBegin() { return $this->DateBegin; }
    public function SetDateBegin($DateBegin) { $this->DateBegin = $DateBegin; }
    public function GetPlace() { return $this->Place; }
    public function SetPlace($Place) { $this->Place = $Place; }
    public function GetRemindMeIfCompleted() { return $this->RemindMeIfCompleted; }
    public function SetRemindMeIfCompleted($RemindMeIfCompleted) { $this->RemindMeIfCompleted = $RemindMeIfCompleted; }
    public function GetIsSubmitted() { return $this->IsSubmitted; }
    public function SetIsSubmitted($IsSubmitted) { $this->IsSubmitted = $IsSubmitted; }
    public function GetUSR() { return $this->USR; }
    public function SetUSR($USR) { $this->USR = $USR; }
    public function GetUsersRegion() { return $this->UsersRegion; }
    public function SetUsersRegion($UsersRegion) { $this->UsersRegion = $UsersRegion; }
    public function GetDocType() { return $this->DocType; }
    public function SetDocType($DocType) { $this->DocType = $DocType; }
    public function GetFreqDays() { return $this->FreqDays; }
    public function SetFreqDays($FreqDays) { $this->FreqDays = $FreqDays; }
    public function GetFreqMonth() { return $this->FreqMonth; }
    public function SetFreqMonth($FreqMonth) { $this->FreqMonth = $FreqMonth; }
    public function GetFreqWeek() { return $this->FreqWeek; }
    public function SetFreqWeek($FreqWeek) { $this->FreqWeek = $FreqWeek; }
    public function GetFreqYear() { return $this->FreqYear; }
    public function SetFreqYear($FreqYear) { $this->FreqYear = $FreqYear; }
    public function GetTaskDateStart() { return $this->taskDateStart; }
    public function SetTaskDateStart($taskDateStart) { $this->taskDateStart = $taskDateStart; }
    public function GetTaskDateEnd() { return $this->taskDateEnd; }
    public function SetTaskDateEnd($taskDateEnd) { $this->taskDateEnd = $taskDateEnd; }
    public function GetTaskDateRealStart() { return $this->taskDateRealStart; }
    public function SetTaskDateRealStart($v) { $this->taskDateRealStart = $v; }
    public function GetTaskDateRealEnd() { return $this->taskDateRealEnd; }
    public function SetTaskDateRealEnd($v) { $this->taskDateRealEnd = $v; }
    public function GetTaskDateCompleted() { return $this->taskDateCompleted; }
    public function SetTaskDateCompleted($v) { $this->taskDateCompleted = $v; }
    public function GetTaskDateCompletedH() { return $this->taskDateCompletedH; }
    public function SetTaskDateCompletedH($v) { $this->taskDateCompletedH = $v; }
    public function GetTaskDateCompletedM() { return $this->taskDateCompletedM; }
    public function SetTaskDateCompletedM($v) { $this->taskDateCompletedM = $v; }
    public function GetTaskID() { return $this->taskID; }
    public function SetTaskID($taskID) { $this->taskID = $taskID; }
    public function HasTask() { return $this->taskID && ($this->taskID != $this->unid); }
    public function SetDifficulty($v) { $this->Difficulty = $v; }
    public function GetDifficulty() { return $this->Difficulty; }
    public function GetCat1() { return $this->cat1; }
    public function SetCat1($cat1) { $this->cat1 = $cat1; }
    public function GetSEOTitle() { return $this->SEOTitle; }
    public function SetSEOTitle($v) { $this->SEOTitle = $v; }
    public function GetSEODescription() { return $this->SEODescription; }
    public function SetSEODescription($v) { $this->SEODescription = $v; }
    public function GetSEOKeywords() { return $this->SEOKeywords; }
    public function SetSEOKeywords($v) { $this->SEOKeywords = $v; }
    public function GetVacAnnotation() { return $this->VacAnnotation; }
    public function SetVacAnnotation($v) { $this->VacAnnotation = $v; }
    public function GetMailAccess() { return $this->mailAccess; }
    public function SetMailAccess($mailAccess) { $this->mailAccess = $mailAccess; }
    public function GetMailStatus() { return $this->mailStatus; }
    public function SetMailStatus($mailStatus) { $this->mailStatus = $mailStatus; }
    public function GetVacManager() { return $this->VacManager; }
    public function SetVacManager($v) { $this->VacManager = $v; }
    public function GetHeader() { return $this->Header; }
    public function SetHeader($Header) { $this->Header = $Header; }
    public function GetAdress() { return $this->Adress; }
    public function SetAdress($Adress) { $this->Adress = $Adress; }
//     public function GetNotif() {
//       if (!isset($this->Notif) || sizeof($this->Notif) == 0)
//         return [];
//       return $this->Notif;
//     }
//     public function GetNotifExpired() { //returns array
//       $res = array();
//       foreach ($this->Notif as $n) {
//         if (isset($n['expired']) && $n['expired'] == true) array_push($res, $n);
//       }
// 
//       return $res;
//     }
//     public function SetNotif($v) { $this->Notif = $v; }
    public function GetPASSWORD_1() { return $this->PASSWORD_1; }
    public function SetPASSWORD_1($PASSWORD_1) { $this->PASSWORD_1 = $PASSWORD_1; }
    public function GetShowUnreadedTree() { return $this->showUnreadedTree; }
    public function SetShowUnreadedTree($showUnreadedTree) { $this->showUnreadedTree = $showUnreadedTree; }
    public function GetFullName($asLdap = true) {
      return $asLdap ? $this->FullName : static::LdapToFullName($this->FullName);
    }
    public function GetFullNameRaw() { return $this->GetFullName(false); }
    public static function LdapToFullName($ldap) {
      $pos = strpos($ldap, '/');
      $cn = strpos($ldap, '=');
      if($cn !== false && $ldap[$cn-1] != 'O') { $cn++; } else { $cn = 0; }
      if($pos === false) {
        return substr($ldap, $cn);
      }
      return substr($ldap, $cn, $pos-$cn);
    }
    public static function FullNameToLdap($fullname) {
      return 'CN='.$fullname.'/O=skvirel';
    }
    public function SetFullName($FullName) { $this->FullName = $FullName; }
    public function GetLogin() { return $this->Login; }
    public function SetLogin($Login) { $this->Login = $Login; }
    public function GetOrganosation() { return $this->Organosation; }
    public function SetOrganosation($Organosation) { $this->Organosation = $Organosation; }
    public function GetWorkGroup($firstOnly = false) {
      if ($firstOnly) return reset($this->WorkGroup);
      return $this->WorkGroup;
    }
    public function SetWorkGroup($WorkGroup) { $this->WorkGroup = $WorkGroup; }
    public function GetLikeDate() { return $this->LikeDate; }
    public function SetLikeDate($LikeDate) { $this->LikeDate = $LikeDate; }
    public function GetLikeNotDate() { return $this->LikeNotDate; }
    public function SetLikeNotDate($LikeNotDate) { $this->LikeNotDate = $LikeNotDate; }
    public function GetLikes() { return $this->likes; }
    public function SetLikes($v) { $this->likes = $v; }
    public function GetSection() { return $this->section; }
    public function SetSection($section) {
      $this->section = !is_array($section)?[$section]:$section;
    }
    public function GetSection_1() { return $this->section_1; }
    public function SetSection_1($section) {
      if(!is_array($section)) $section = [$section];
      $this->section_1 = $section;
    }
    public function GetDtDismiss() { return $this->DtDismiss; }
    public function SetDtDismiss($DtDismiss) { $this->DtDismiss = $DtDismiss; }
    public function GetLevel() { return $this->level; }
    public function SetLevel($level) { $this->level = $level; }
    public function GetRole() { return is_array($this->role)?$this->role:[$this->role];}
    public function SetRole($role) { $this->role = $role; }
    public function GetContactWithMobileFhone() { return $this->ContactWithMobileFhone; }
    public function GetContactWithMobileFhone_1() { return $this->ContactWithMobileFhone_1; }
    public function GetContactWithMobileFhone_2() { return $this->ContactWithMobileFhone_2; }
    public function SetContactWithMobileFhone($ContactWithMobileFhone) { $this->ContactWithMobileFhone = $ContactWithMobileFhone; }
    public function SetContactWithMobileFhone_1($ContactWithMobileFhone_1) { $this->ContactWithMobileFhone_1 = $ContactWithMobileFhone_1; }
    public function SetContactWithMobileFhone_2($ContactWithMobileFhone_2) { $this->ContactWithMobileFhone_2 = $ContactWithMobileFhone_2; }
    public function GetEmail() { return $this->Email; }
    public function SetEmail($Email) { $this->Email = $Email; }
    public function GetPrivateEmail() { return $this->PrivateEmail; }
    public function SetPrivate($PrivateEmail) { $this->PrivateEmail = $PrivateEmail; }
    public function GetICQ() { return $this->ICQ; }
    public function SetICQ($ICQ) { $this->ICQ = $ICQ; }
    public function GetSkype() { return $this->Skype; }
    public function SetSkype($v) { $this->Skype = $v; }
    public function GetBirthday() { return $this->Birthday; }
    public function SetBirthday($Birthday) { $this->Birthday = $Birthday; }
    public function GetDocID() { return $this->DocID; }
    public function SetDocID($DocID) { $this->DocID = $DocID; }
    public function GetManager() { return $this->Manager; }
    public function SetManager($Manager) { $this->Manager = $Manager; }
    public function GetFullNameInRus() { return $this->FullNameInRus; }
    public function SetForeignPassport($ForeignPassport) { $this->ForeignPassport = $ForeignPassport; }
    public function GetForeignPassport() { return $this->ForeignPassport; }
    public function SetAddressToSite($AddressToSite) { $this->AddressToSite = $AddressToSite; }
    public function GetAddressToSite() { return $this->AddressToSite; }
    public function SetWorkPhoneToSite($WorkPhoneToSite) { $this->WorkPhoneToSite = $WorkPhoneToSite; }
    public function GetWorkPhoneToSite() { return $this->WorkPhoneToSite; }
    public function SetHomePhoneToSite($HomePhoneToSite) { $this->HomePhoneToSite = $HomePhoneToSite; }
    public function GetHomePhoneToSite() { return $this->HomePhoneToSite; }
    public function SetMobileFhoneToSite($MobileFhoneToSite) { $this->MobileFhoneToSite = $MobileFhoneToSite; }
    public function GetMobileFhoneToSite() { return $this->MobileFhoneToSite; }
    public function SetMobilePhoneToSite_1($MobilePhoneToSite_1) { $this->MobilePhoneToSite_1 = $MobilePhoneToSite_1; }
    public function GetMobilePhoneToSite_1() { return $this->MobilePhoneToSite_1; }
    public function SetMobilePhoneToSite_2($MobilePhoneToSite_2) { $this->MobilePhoneToSite_2 = $MobilePhoneToSite_2; }
    public function GetMobilePhoneToSite_2() { return $this->MobilePhoneToSite_2; }
    public function SetEmailToSite($EmailToSite) { $this->EmailToSite = $EmailToSite; }
    public function GetEmailToSite() { return $this->EmailToSite; }
    public function SetFullNameInRus($FullNameInRus) { $this->FullNameInRus = $FullNameInRus; }
    public function GetListOfWorkGroups() { return $this->ListOfWorkGroups; }
    public function SetListOfWorkGroups($ListOfWorkGroups) { $this->ListOfWorkGroups = $ListOfWorkGroups; }
    public function GetTownID() { return $this->TownID; }
    public function SetTownID($TownID) { $this->TownID = $TownID; }
    public function GetPersonal_Data() { return $this->Personal_Data; }
    public function SetPersonal_Data($Personal_Data) { $this->Personal_Data = $Personal_Data; }
    public function GetName() { return $this->name; }
    public function SetName($name) { $this->name = $name; }
    public function GetLastName() { return $this->LastName; }
    public function SetLastName($LastName) { $this->LastName = $LastName; }
    public function GetPushToken() { return $this->pushToken; }
    public function SetPushToken($pushToken) { $this->pushToken = $pushToken; }
    public function GetRole_1() { return $this->role_1; }
    public function SetRole_1($role_1) { $this->role_1 = $role_1; }
    public function GetReader() { return $this->Reader; }
    public function SetReader($Reader) { $this->Reader = $Reader; }
    public function GetPriority() { return $this->Priority; }
    public function SetPriority($Priority) { $this->Priority = $Priority; }
    public function GetDep() { return $this->Dep; }
    public function SetDep($Dep) { $this->Dep = $Dep; }
    public function GetSelectSubjectNotes() { return $this->SelectSubjectNotes; }
    public function SetSelectSubjectNotes($SelectSubjectNotes) { $this->SelectSubjectNotes = $SelectSubjectNotes; }
    public function GetSelectRegion() { return $this->SelectRegion; }
    public function SetSelectRegion($v) { $this->SelectRegion = $v; }
    public function GetCount() { return $this->Count; }
    public function SetCount($Count) { $this->Count = $Count; }
    public function GetQuery() { return $this->query; }
    public function SetQuery($query) { $this->query = $query; }
    public function GetScriptRTF() { return $this->ScriptRTF; }
    public function SetScriptRTF($ScriptRTF) { $this->ScriptRTF = $ScriptRTF; }
    public function GetFromSite() { return $this->fromSite; }
    public function SetFromSite($fromSite) { $this->fromSite = $fromSite; }
    public function GetViewBody() { return $this->viewBody; }
    public function SetViewBody($viewBody) { $this->viewBody = $viewBody; }
    public function GetHideFilesToHomePage() { return $this->HideFilesToHomePage; }
    public function SetHideFilesToHomePage($HideFilesToHomePage) { $this->HideFilesToHomePage = $HideFilesToHomePage; }
    public function GetC2() { return $this->C2; }
    public function SetC2($C2) { $this->C2 = $C2; }
    public function GetDtWork() { return $this->DtWork; }
    public function SetDtWork($DtWork) { $this->DtWork = $DtWork; }
    public function GetEmplUNID() { return $this->EmplUNID; }
    public function SetEmplUNID($EmplUNID) { $this->EmplUNID = $EmplUNID; }
    public function GetMiddleName() { return $this->MiddleName; }
    public function SetMiddleName($MiddleName) { $this->MiddleName = $MiddleName; }
    public function GetSubscribe() { return $this->Subscribe; }
    public function SetSubscribe($Subscribe) { $this->Subscribe = $Subscribe; }
    /* form: "MessageLite" */
    public function GetQuery_String_Decoded() { return $this->Query_String_Decoded; }
    public function SetQuery_String_Decoded($Query_String_Decoded) { $this->Query_String_Decoded = $Query_String_Decoded; }
    public function GetParentUnid() { return $this->parentUnid; }
    public function SetParentUnid($parentUnid) { $this->parentUnid = $parentUnid; }
    public function GetParentDbName() {
      return $this->ParentDbName;
    }
    public function SetParentDbName($dbName) {
        $this->ParentDbName = $dbName;
    }
    public function GetSubjectUnid() { return $this->subjectUnid; }
    public function SetSubjectUnid($subjectUnid) { $this->subjectUnid = $subjectUnid; }
    /* form: "messagebb" */
    /* form: "Empl" */
    public function GetSortType() { return $this->SortType; }
    public function SetSortType($SortType) { $this->SortType = $SortType; }
    public function GetLastConnect() { return $this->lastConnect; }
    public function SetLastConnect($lastConnect) { $this->lastConnect = $lastConnect; }
    public function GetBoss() { return $this->boss; }
    public function SetBoss($boss) { $this->boss = $boss; }
    public function GetSubDivision_2() { return $this->SubDivision_2; }
    public function SetSubDivision_2($SubDivision_2) { $this->SubDivision_2 = $SubDivision_2; }
    public function GetLastDiv() { return $this->lastDiv; }
    public function SetLastDiv($lastDiv) { $this->lastDiv = $lastDiv; }
    public function GetBossLat($single = false) { return ($single && $this->bossLat) ? reset($this->bossLat) : $this->bossLat; }
    public function SetBossLat($bossLat) { $this->bossLat = $bossLat; }
    public function GetAbout() { return $this->About; }
    public function SetAbout($About) { $this->About = $About; }
    public function GetResume() { return $this->Resume; }
    public function SetResume($Resume) { $this->Resume = $Resume; }
    public function GetFavorites() { return $this->favorites; }
    public function SetFavorites($Favorites) { $this->favorites = $Favorites; }
    public function GetGeoCity() { return $this->geoCity; }
    public function SetGeoCity($GeoCity) { $this->geoCity = $GeoCity; }
    public function GetGeoCoord() { return $this->geoCoord; }
    public function SetGeoCoord($GeoCoord) { $this->geoCoord = $GeoCoord; }
    /* form: "formTask" */
    public function GetRepeatLimitDate() { return $this->RepeatLimitDate; }
    public function SetRepeatLimitDate($RepeatLimitDate) { $this->RepeatLimitDate = $RepeatLimitDate; }
    public function GetCheckerLat($single = false) { return ($single && $this->CheckerLat && is_array($this->CheckerLat)) ? reset($this->CheckerLat) : $this->CheckerLat; }
    public function SetCheckerLat($CheckerLat) { $this->CheckerLat = $CheckerLat; }
    public function GetEscalationManagers() { return $this->EscalationManagers; }
    public function SetEscalationManagers($val) { $this->EscalationManagers = $val; }
    public function AddEscalationManagers($login, $type, $time = null) {
      if (!is_array($this->EscalationManagers)) { $this->EscalationManagers = []; }
      if ($this->GetEscalationManagersTime($login, $type)) { $this->DelEscalationManagers($login, $type); }

      if ($time == null) {
        $today = new \DateTime();
        $todayIso = static::dt2iso($today, true);
      } else {
        $todayIso = $time;
      }

      $item = ['login' => $login, 'time' => $todayIso, 'type' => $type]; //type = 'notify' || 'change' || 'remind'
      array_push($this->EscalationManagers, $item);
      return true;
    }
    public function DelEscalationManagers($login, $type) {
      if (!is_array($this->EscalationManagers) || empty($this->EscalationManagers)) return false;
      $tmp = [];
      foreach($this->EscalationManagers as $item) {
        if ($item['login'] != $login && $item['type'] != $type) {
          array_push($tmp, $item);
        }
      }
      $this->EscalationManagers = $tmp;
      return $this->EscalationManagers;
    }
    public function GetEscalationManagersTime($login, $type) {
      if (!is_array($this->EscalationManagers) || empty($this->EscalationManagers)) return false;
      foreach($this->EscalationManagers as $item) {
        if ($item['login'] == $login && $item['type'] == $type) {
          return $item['time'];
        }
      }
      return false;
    }
    public function GetChecker() { return $this->Checker; }
    public function SetChecker($Checker) { $this->Checker = $Checker; }
    public function GetTaskStateCurrent() {
      if (isset($this->TaskStateCurrent))
        return $this->TaskStateCurrent;
      else
        return -1;
    }
    public function SetTaskStateCurrent($v) { $this->TaskStateCurrent = $v; }
    public function GetTaskStatePrevious() { return $this->TaskStatePrevious; }
    public function SetTaskStatePrevious($v) { $this->TaskStatePrevious = $v; }
    public function GetIsArchive() { return $this->isArchive; }
    public function SetIsArchive($isArchive) { $this->isArchive = $isArchive; }
    public function GetTimeBeginH() { return $this->TimeBeginH; }
    public function SetTimeBeginH($TimeBeginH) { $this->TimeBeginH = $TimeBeginH; }
    public function GetTimeBeginM() { return $this->TimeBeginM; }
    public function SetTimeBeginM($TimeBeginM) { $this->TimeBeginM = $TimeBeginM; }
    public function GetDateEnd() { return $this->DateEnd; }
    public function SetDateEnd($DateEnd) { $this->DateEnd = $DateEnd; }
    public function GetTimeEndH() { return $this->TimeEndH; }
    public function SetTimeEndH($TimeEndH) { $this->TimeEndH = $TimeEndH; }
    public function GetTimeEndM() { return $this->TimeEndM; }
    public function SetTimeEndM($TimeEndM) { $this->TimeEndM = $TimeEndM; }
    /* form: "formProcess" */
    public function GetAuthorLogin() { return $this->authorLogin; }
    public function SetAuthorLogin($v) { $this->authorLogin = $v; }
    public function SetArchiveVacUnid($archiveVacUnid) { $this->archiveVacUnid = $archiveVacUnid; }
    public function GetArchiveVacUnid() { return $this->archiveVacUnid; }
    public function GetMessageBody() { return $this->messageBody; }
    public function SetMessageBody($messageBody) { $this->messageBody = $messageBody; }
    /* form: "decisionAfter" */
    public function GetFileDisplayDes() { return $this->fileDisplayDes; }
    public function SetFileDisplayDes($fileDisplayDes) { $this->fileDisplayDes = $fileDisplayDes; }
    /* form: "formVoting" */
    public function GetAnswers() { return $this->answers; }
    public function SetAnswers($Answers) { $this->answers = $Answers; }
    public function GetAnswersData() { return $this->AnswersData; }
    public function SetAnswersData($AnswersData) { $this->AnswersData = $AnswersData; }
    public function GetAnswersLim() { return $this->AnswersLim; }
    public function SetAnswersLim($AnswersLim) { $this->AnswersLim = $AnswersLim; }
    public function GetPeriodPoll() { return $this->PeriodPoll; }
    public function SetPeriodPoll($PeriodPoll) { $this->PeriodPoll = $PeriodPoll; }
    public function GetShowOnIndex() { return $this->ShowOnIndex; }
    public function SetShowOnIndex($v) { $this->ShowOnIndex = $v; }
    public function GetWatchedBy($login = false) {
      if (!is_array($this->watchedBy)) $this->watchedBy = [];
      if (!$login) return $this->watchedBy;
      
      $position = array_search($login, $this->watchedBy);
      if ($position !== false) {
        return true;
      }
      else return false;
    }
    public function SetWatchedBy($v) { $this->watchedBy = $v; }
    public function AddWatchedBy($login) {
      if (!is_array($this->watchedBy)) $this->watchedBy = [];
      
      if (array_search($login, $this->watchedBy) === false)
        array_push($this->watchedBy, $login);
        
      return true;
    }
    public function RemoveWatchedBy($login) {
      if (!is_array($this->watchedBy) || sizeof($this->watchedBy) == 0) return false;
      
      $position = array_search($login, $this->watchedBy);
      if ($position !== false) {
        array_splice($this->watchedBy, $position, 1);
        return true;
      }
      else return false;
    }
    public function GetAuthorCN() { return $this->AuthorCN; }
    public function SetAuthorCN($AuthorCN) { $this->AuthorCN = $AuthorCN; }
    public function GetSubjVoting() { return $this->subjVoting; }
    public function SetSubjVoting($subjVoting) { $this->subjVoting = $subjVoting; }
    public function GetPostFinishProcess() { return $this->PostFinishProcess; }
    public function SetPostFinishProcess($PostFinishProcess) { $this->PostFinishProcess = $PostFinishProcess; }
    public function GetShowRating() { return $this->ShowRating; }
    public function SetShowRating($ShowRating) { $this->ShowRating = $ShowRating; }
    public function GetRefuses() { return $this->Refuses; }
    public function SetRefuses($Refuses) { $this->Refuses = $Refuses; }
    /* form: "formTask" */
    public function GetTaskPerformerLat($forceSingle = false) {
      return (is_array($this->taskPerformerLat) && $forceSingle) ? (string)reset($this->taskPerformerLat) : $this->taskPerformerLat;
    }
    public function SetTaskPerformerLat($taskPerformerLat) { $this->taskPerformerLat = $taskPerformerLat; }
    public function GetTaskPerformer() { return $this->taskPerformer; }
    public function SetTaskPerformer($taskPerformer) { $this->taskPerformer = $taskPerformer; }
    public function GetResponsible() { return $this->responsible; }
    public function SetResponsible($v) { $this->responsible = $v; }
    public function GetAction() { return $this->action; }
    public function SetAction($v) { $this->action = $v; }
    /* form: "UnreadedStub" */
    public function GetSubjectDB() { return $this->subjectDB; }
    public function SetSubjectDB($subjectDB) { $this->subjectDB = $subjectDB; }
    public function GetSUBJECTFORM() { return $this->SUBJECTFORM; }
    public function SetSUBJECTFORM($SUBJECTFORM) { $this->SUBJECTFORM = $SUBJECTFORM; }
    /* form: "WorkPlan" */
    public function GetYear() { return $this->Year; }
    public function SetYear($Year) { $this->Year = $Year; }
    public function GetMonth() { return $this->Month; }
    public function SetMonth($Month) { $this->Month = $Month; }
    public function GetDaysData() { return $this->DaysData; }
    public function SetDaysData($DaysData) { $this->DaysData = $DaysData; }
    public function GetRegion() { return $this->Region; }
    public function SetRegion($Region) { $this->Region = $Region; }
    public function GetADDITIONAL() { return $this->ADDITIONAL; }
    public function SetADDITIONAL($ADDITIONAL) { $this->ADDITIONAL = $ADDITIONAL; }
    public function GetWeekEndsCount() { return $this->WeekEndsCount; }
    public function SetWeekEndsCount($WeekEndsCount) { $this->WeekEndsCount = $WeekEndsCount; }
    public function GetHistory() { return $this->History; }
    public function SetHistory($History) { $this->History = $History; }
    /* form: "formProcess" */
    public function GetCat2() { return $this->cat2; }
    public function SetCat2($cat2) { $this->cat2 = $cat2; }
    public function GetCat3() { return $this->cat3; }
    public function SetCat3($cat3) { $this->cat3 = $cat3; }
    /* form: "formAdapt" */
    public function GetSex() { return $this->Sex; }
    public function SetSex($Sex) { $this->Sex = $Sex; }
    public function GetWorkGroupEng() { return $this->WorkGroupEng; }
    public function SetWorkGroupEng($WorkGroupEng) { $this->WorkGroupEng = $WorkGroupEng; }
    public function GetTestPeriod() { return $this->TestPeriod; }
    public function SetTestPeriod($TestPeriod) { $this->TestPeriod = $TestPeriod; }
    public function GetPassword() { return $this->Password; }
    public function SetPassword($Password) { $this->Password = $Password; }
    public function GetAccessType() { return $this->AccessType; }
    public function SetAccessType($AccessType) { $this->AccessType = $AccessType; }
    public function GetRecruter() { return $this->Recruter; }
    public function SetRecruter($Recruter) { $this->Recruter = $Recruter; }
    public function GetHeadIT() { return $this->HeadIT; }
    public function SetHeadIT($HeadIT) { $this->HeadIT = $HeadIT; }
    public function GetManagerHR() { return $this->ManagerHR; }
    public function SetManagerHR($ManagerHR) { $this->ManagerHR = $ManagerHR; }
    public function GetHeadFin() { return $this->HeadFin; }
    public function SetHeadFin($HeadFin) { $this->HeadFin = $HeadFin; }
    public function GetHeadHR() { return $this->HeadHR; }
    public function SetHeadHR($HeadHR) { $this->HeadHR = $HeadHR; }
    public function GetWaitPerformer() { return $this->WaitPerformer; }
    public function SetWaitPerformer($val) { $this->WaitPerformer = $val; }
    public function GetSalary() { return $this->Salary; }
    public function SetSalary($val) { $this->Salary = $val; }
    public function GetCompanyName() { return $this->companyName; }
    public function SetCompanyName($val) { $this->companyName = $val; }
    public function GetAttachments() { return $this->attachments; }
    public function SetAttachments($v) { $this->attachments = $v; }
    
    public function getUserData() {
        return $this->userData;
    }

    public function setUserData($userData) {
        $this->userData = $userData;
    }

    public function getCountry(){
        return $this->Country;
    }

    public function setCountry($Country){
        $this->Country = $Country;
    }

    public function getContactData() {
        return $this->contactData;
    }

    public function setContactData($contactData) {
        $this->contactData = $contactData;
    }

    public function getSecurity() {
        return $this->security;
    }
    public function setSecurity($security) {
        $this->security = $security;
    }

    public function getShareSecurity() {
        return $this->shareSecurity;
    }
    public function setShareSecurity($security) {
        $this->shareSecurity = $security;
    }

    public function getQuestionary() {
        return $this->Questionary;
    }
    public function setQuestionary($Questionary) {
        $this->Questionary = $Questionary;
    }

    public function getQuestionaryID() {
        return $this->QuestionaryID;
    }
    public function setQuestionaryID($QuestionaryID) {
        $this->QuestionaryID = $QuestionaryID;
    }

    public function getDepSubmiss() {
        return $this->DepSubmiss;
    }
    public function setDepSubmiss($val) {
        $this->DepSubmiss = $val;
    }

    public function getLocale(){
        return $this->locale;
    }
    public function setLocale($locale){
        $this->locale = $locale;
    }

    public function getCommentMail(){
        return $this->commentMail;
    }
    public function setCommentMail($commentMail){
        $this->commentMail = $commentMail;
    }

    public function isSystemNameEquivTo($loginOrFullname) {
      if(!$loginOrFullname) { return false; }
      return $loginOrFullname == $this->GetLogin()
        || $loginOrFullname == $this->GetFullName(true)
        || $loginOrFullname == $this->GetFullName(false);
    }

    public function votingSetMember(User $user, array $answers) {
      if(! is_array($this->answers)) { $this->answers = []; }
      else if(isset($this->answers[0])) { // check if OLD voting style
        return false;
      }
      $this->answers[$user->getUsername()] = $answers;
      return true;
    }

    public function votingHasMember(User $user) {
      // check if OLD voting style
      if(! is_array($this->answers) || isset($this->answers[0])) { return false; }
      foreach($this->answers as $a) {
        if($a == $user->getUsername()) {
          return true;
        }
      }
      return false;
    }

    public function addTag($tagname, $username) {

      if (isset($this->Tags) && sizeof($this->Tags) > 0) {
        for ($i = 0; $i < sizeof($this->Tags); $i++) {
          if (isset($this->Tags[$i]['name']) && $this->Tags[$i]['name'] == $tagname) {
            $present = false;
            foreach ($this->Tags[$i]['users'] as $user) {
              if ($user == $username) $present = true;
            }
            if (!$present) $this->Tags[$i]['users'][] = $username;
            return true;
          }
        }
      }
      $newTag = array('name' => $tagname, 'users' => [$username]);
      $this->Tags[] = $newTag;
      return true;
    }

    public function deleteTag($tagname, $username) {
      if (isset($this->Tags) && sizeof($this->Tags) > 0) {
        for ($i = 0; $i < sizeof($this->Tags); $i++) {
          if ($this->Tags[$i]['name'] == $tagname) {
            $present = -1;
            for ($j = 0; $j < sizeof($this->Tags[$i]['users']); $j++) {
              if ($this->Tags[$i]['users'][$j] == $username) $present = $j;
            }
            if ($present >= 0) {
              array_splice($this->Tags[$i]['users'], $present, 1);

              if (sizeof($this->Tags[$i]['users']) == 0) {
                array_splice($this->Tags, $i, 1);
              }
              return true;
            }
          }
        }
      }
      return false;
    }

    /** @MongoDb\PreUpdate */
    public function preUpdate() {
        $this->SetModified();
    }

    public function isMustNotBeReadable($doc){ //unused
        if ($doc->GetForm() === 'formTask'){
            if (( !$doc->GetTaskDateRealEnd()
                  && in_array($doc->GetTaskPerformerLat(true), [$this->GetLogin(), $this->GetFullName()]) )
                || ( $doc->GetTaskDateCompleted()
                     && $doc->GetStatus() !== 'close'
                     && (($doc->GetAuthor() == $this->GetFullName(false) && !$doc->GetCheckerLat(true) )
                        || $doc->GetCheckerLat(true) == $this->GetLogin()))
                )
            {
                return true;
            }
        }
        return false;
    }

    /** @return 'expired', 'unaccepted' or false */
    public function isExpiredTask() {
      $today = new \DateTime();
      $todayIso = static::dt2iso($today, true);
      $todayYMD = static::dt2iso($today, false);
      if(($this->GetStatus() != 'open') || $this->GetTaskDateCompleted() || ($this->GetDocType() == 'event') || $this->GetTaskStateCurrent() == 10 || $this->GetTaskStateCurrent() >= 25) {
        return false; // completed or rejected or event
      }
      if($this->GetTaskDateRealEnd()) { // expired or not?
        if((strlen($this->GetTaskDateRealEnd()) == 8)
           && ($this->GetTaskDateRealEnd() < $todayYMD)) {
          return 'expired';
        } else if((strlen($this->GetTaskDateRealEnd()) > 8)
           && ($this->GetTaskDateRealEnd() < $todayIso)) {
          return 'expired';
        } else {
          return false; // not expired yet
        }
      }
      return 'unaccepted';
    }

    /**
    * Get document as array
    * @param bool $needUserData
    * @param bool $needContactData
    * @param string $roles, you can pass User::getRoles() result
    * @return array
    */
    public function getDocument($needUserData = false, $needContactData = false, $roles = [])
    {
      $document = (new \Treto\PortalBundle\Model\DocumentSerializer($this,$roles))->toArray(['contactData','userData']);
      $document['userData'] = null;
      if($needUserData && ($d = $this->getUserData())) {
          $document['userData'] = $d->getDocument(false,false, $roles);
      }
      $document['contactData'] = null;
      if($needContactData && ($d = $this->getContactData())) {
          $document['contactData'] = $d->getDocument(false,false, $roles);
      }
      $document['FullNameRaw'] = $this->GetFullName(false);
      return $document;
    }

    /** Set document from array
    * @param array $array representation of the document
    * @param \Treto\PortalBundle\Validator\Validator $validator
    * @param string $roles, you can pass User::getRoles() result
    * @return array of validation errors or empty array on success
    */
    public function setDocument(array $array, $validator = null, $updateContactData = false, $roles = []) {
        (new \Treto\PortalBundle\Model\DocumentSerializer($this,$roles))->fromArray($array,['_id', 'userData', 'contactData']);
        $contactDataErrors = [];
        if($updateContactData && !empty($document['contactData']) && ($d = $this->getContactData())) {
            $contactDataErrors = $d->setDocument($document['contactData'], $validator, $roles);
        }
        if($validator) {
            return $validator->validate($this) + $contactDataErrors;
        }
        return [];
    }
}
