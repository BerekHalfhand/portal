<?php
namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Validator\Constraints as Assert;
use Treto\UserBundle\Annotation\ExtendedPrivileges as Escalated;

/** 
 * Публичный класс для ОДМ монго клиент/монго, 
 * хранящий данные контакта, то есть фирмы, человека или группы,
 * для общения по каналам связи; адреса, банк. реквизиты, итд.
 * 
 * @MongoDB\Document(repositoryClass="ContactsRepository") @MongoDB\HasLifecycleCallbacks
 */
class Contacts extends SecureDocument {
  /*
  * ==================== B/DOCTRINE MAPPINGS ====================
  */
    /*
    * ==================== DocumentType: "Common" ====================
    */
    const C1_SYNCH_URL = 'https://te.remote.team/exportcontactsto1c';
    public static $c1FieldList = [
        'ID' => '',
        'SearchLine' => '',
        'ReadingChange' => '',
        'LastName' => '',
        'FirstName' => '',
        'MiddleName' => '',
        'Rank' => '',
        'MainEmail' => '',
        'Salary' => '',
        'EmailValues' => '',
        'isHomeOrganization' => '',
        'Organization' => '',
        'currency' => '',
        'payMethod' => '',
        'individuallySamples' => '',
        'OrganizationID' => '',
        'ContactBirthday' => '',
        'ContactBirthmonth' => '',
        'ContactBirthyear' => '',
        'Notify' => '',
        'RegionID' => '',
        'Section' => '',
        'Departament' => '',
        'Level' => '',
        'Role' => '',
        'MainName' => '',
        'OtherName' => '',
        'NameCompany' => '',
        'FormOwnership' => '',
        'SiteValues' => '',
        'Person' => '',
        'PersonID' => '',
        'Group' => '',
        'GroupID' => '',
        'PhoneValues' => '',
        'ContactStatus' => '',
        'Other' => '',
        'Comment' => '',
        'AccessType' => '',
        'DocumentType' => '',
        'Author' => '',
        'ResponsibleManager' => '',
        'Dismissed' => '',
        'InformationSource' => '',
        'ResidenceRegistrationAddress' => '',
        'Passport' => '',
        'ActualAddress' => '',
        'DeliveryAddress' => '',
        'LegalAddress' => '',
        'OGRN' => '',
        'INN' => '',
        'KPP' => '',
        'OKPO' => '',
        'Bank' => '',
        'CurrentAccount' => '',
        'Bick' => '',
        'CorrespondentAccount' => '',
        'BossFIO' => '',
        'BossRank' => '',
        'BookerFIO' => '',
        'C1_Result' => '',
        'C1_Description' => '',
        'C1_InProgress' => '',
        'ModifiedBy' => '',
        'FirstReferrer' => '',
        'ContactWorkFrom' => '',
        'ContactWorkFromID' => '',
        'Swift' => '',
        'CompanyGroup' => '',
        'Country' => '',
        'MinDeliveryTerm' => '',
        'MinOrderSum' => '0',
        'IsPackageOnly' => '',
        'IsPackageWithConditions' => '',
        'PackageMeasureUnit' => '',
        'PackageSize' => '',
        'PackageFactor' => '',
        'PayType' => '',
        'PayCreditSum' => '',
        'PayCreditTerm' => '',
        'PayIsDelayPresent' => '',
        'PayPrepayment' => '',
        'PayAfterInvoiceTerm' => '',
        'DiscountAccepted' => '',
        'DiscountAcceptorID' => '',
        'DiscountAcceptedDate' => '',
        'CreationType' => '',
        'IsIrregularFabric' => '',
        'CallCost' => '',
        'ExportDeclarationCost' => '',
        'PrepaymentDiscount' => '',
        'DiscountAcceptedComment' => '',
        'SiteName' => '',
        'RegNo' => '',
        'VATNo' => '',
        'TimePreparationShippingTreto' => '',
        'TimePreparationShippingTe' => '',
        'Sex' => '',
        'DaysObtainOrders' => '',
        'SumOrdersMax' => '',
        'Language' => '',
        'companyName' => ''
    ];

    /** @MongoDB\Id(strategy="auto") */
    protected $_id;

    /** @MongoDB\String */
    protected $unid;

    /** @MongoDB\String */
    protected $linkedUNID;

    /** @MongoDB\Int */
    protected $isLinked;
    
    /** @MongoDB\String */
    protected $SubID;
    
    /** @MongoDB\String */
    protected $parentUNID;
    
    /** @MongoDB\Int */
    protected $CONVERTED;
    
    /** @MongoDB\String */
    protected $noteid;

    /** @MongoDB\Boolean */
    protected $banApi;

    /** @MongoDB\String */
    protected $sequence;

    /** @MongoDB\String */
    protected $created;

    /** @MongoDB\String */
    protected $lastFailSynch;

    /** @MongoDB\String */
    protected $modified;

    /** @MongoDB\String */
    protected $revised;

    /** @MongoDB\String */
    protected $lastaccessed;

    /** @MongoDB\String */
    protected $addedtofile;

    /** @MongoDB\String */
    protected $DocumentTypeGroup;

    /** @MongoDB\String */
    protected $DocumentType;

    /** @MongoDB\String */
    protected $Name;

    /** @MongoDB\String */
    protected $ModifiedBy;

    /** @MongoDB\Collection */
    protected $Language;

    /** @MongoDB\String */
    protected $statusForAccess;

    /** @MongoDB\String */
    protected $AccessOption;

    /** @MongoDB\String */
    protected $Status;

    /** @MongoDB\String */
    protected $createdDate;

    /** @MongoDB\String */
    protected $ContactId;

    /** @MongoDB\String */
    protected $SubjectID;

    /** @MongoDB\String */
    protected $PARENTID;
    
    /** @MongoDB\String */
    protected $ParentDbName;

    /** @MongoDB\String */
    protected $AuthorCommon;

    /** @MongoDB\Collection */
    protected $Author;

    /** @MongoDB\Collection */
    protected $payMethod;

    /** @MongoDB\String */
    protected $AuthorRus;
    
    /** @MongoDB\String */
    protected $authorLogin;

    /** 
     *    Внимание здесь находится unid
     */
    /** @MongoDB\String */
    protected $ID;

    /** @MongoDB\String */
    protected $subject;

    /** @MongoDB\String */
    protected $ContactName;

    /** @MongoDB\String */
    protected $xml1CResponse;

    /** @MongoDB\String */
    protected $Comment;

    /** @MongoDB\String */
    protected $V2AttachmentOptions;

    /** @MongoDB\String */
    protected $tmpComment;

    /** @MongoDB\Collection */
    protected $READEDBY; /*DEPRECATED*/
    
    /** @MongoDB\Hash */
    protected $readBy;

    /** @MongoDB\String */
    protected $DATEMODIFIED;

    /** @MongoDB\String */
    protected $ConflictAction;

    /** @MongoDB\Collection */
    protected $GroupPN;

    /** @MongoDB\Collection */
    protected $GroupID;

    /** @MongoDB\Collection */
    protected $Group;

    /** @MongoDB\Collection */
    protected $individuallySamples;

    /** @MongoDB\String */
    protected $modify1C;

    /** @MongoDB\Collection */
    protected $StatusList;

    /** @MongoDB\String */
    protected $form; 

    /** @MongoDB\String */
    protected $xml;

    /** @MongoDB\Collection */
    protected $ContactStatus;
    
    /** @MongoDB\Collection */
    protected $mentions = [];
    /*
    * ==================== DocumentType: "Organization" ====================
    */

    /** @MongoDB\String */
    protected $Employee;

    /** @MongoDB\String */
    protected $ResponsibleManager;

    /** @MongoDB\String */
    protected $ResponsibleManager_ID;

    /** @MongoDB\String */
    protected $ResponsibleManager_LN;

    /** @MongoDB\String */
    protected $ResponsibleManager_PortalID;

    /** @MongoDB\String */
    protected $TimePreparationShipping_Treto;

    /** @MongoDB\String */
    protected $TimePreparationShipping_Te;

    /** @MongoDB\String */
    protected $GlobalHistory;

    /** @MongoDB\String */
    protected $FormOwnership;

    /** @MongoDB\String */
    protected $MainName;

    /** @MongoDB\String */
    protected $Other;

    /** @MongoDB\Collection */
    protected $OldPersonID;

    /** @MongoDB\Collection */
    protected $PersonID;

    /** @MongoDB\Collection */
    protected $Person;

    /** @MongoDB\Collection */
    protected $wqovalue_;
    
    /** @MongoDB\Collection */
    protected $wqovalue_Person;
    
    /** @MongoDB\String */
    protected $wqovalue_StatusForAccess;
    
    /** @MongoDB\String */
    protected $wqovalue_Status;
    
    /** @MongoDB\String */
    protected $wqovalue_MainName;
    
    /** @MongoDB\String */
    protected $wqovalue_OtherName;

    /** @MongoDB\String */
    protected $LastHistoryChanged;

    /** @MongoDB\String */
    protected $PayType;

    /** @MongoDB\Collection */
    protected $ContactWorkFrom;

    /** @MongoDB\Collection */
    protected $ContactWorkFromID;

    /** @MongoDB\Collection */
    protected $PackageMeasureUnit;

    /** @MongoDB\Collection */
    protected $PackageSize;

    /** @MongoDB\Collection */
    protected $PackageFactor;

    /** @MongoDB\String */
    protected $MinDeliveryTerm;

    /** @MongoDB\String */
    protected $currency;

     /** @MongoDB\String */
    protected $MinOrderSum;

     /** @MongoDB\String */
    protected $IsIrregularFabric;

     /** @MongoDB\String */
    protected $CallCost;

     /** @MongoDB\String */
    protected $ExportDeclarationCost;

     /** @MongoDB\String */
    protected $PayCreditSum;

     /** @MongoDB\String */
    protected $PayCreditTerm;

     /** @MongoDB\String */
    protected $PayIsDelayPresent;

     /** @MongoDB\String */
    protected $PayPrepayment;

     /** @MongoDB\String */
    protected $PrepaymentDiscount;

     /** @MongoDB\Hash */
    protected $inHoliday;

     /** @MongoDB\Hash */
    protected $outHoliday;

     /** @MongoDB\String */
    protected $PayAfterInvoiceTerm;

     /** @MongoDB\String */
    protected $IsPackageOnly;

      /** @MongoDB\String */
    protected $IsPackageWithConditions;
      /** @MongoDB\String */
    protected $DeliveryAddress;
      /** @MongoDB\String */
    protected $LegalAddress;
      /** @MongoDB\String */
    protected $ActualAddress;

    /*
    * ==================== DocumentType: "Person" ====================
    */

    /** @MongoDB\String */
    protected $LastName;

    /** @MongoDB\String */
    protected $FirstName;

    /** @MongoDB\Collection */
    protected $Rank;

    /** @MongoDB\String */
    protected $isHomeOrganization;

    /** @MongoDB\Collection */
    protected $OrganizationID;

    /** @MongoDB\Collection */
    protected $Organization;

    /** @MongoDB\Collection */
    protected $OrganizationPN;

    /** @MongoDB\String */
    protected $ContactBirthday;

    /** @MongoDB\String */
    protected $ContactBirthmonth;

    /** @MongoDB\String */
    protected $ContactBirthyear;

    /** @MongoDB\String */
    protected $BirthDay;

    /** @MongoDB\Collection */
    protected $role;

    /** @MongoDB\String */
    protected $Dismissed;

    /** @MongoDB\String */
    protected $ERegion;

    /** @MongoDB\String */
    protected $PortalUser;

    /** @MongoDB\String */
    protected $PortalUser_LN;

    /** @MongoDB\String */
    protected $PortalUser_ID;

    /** @MongoDB\String */
    protected $GroupFullID;

    /** @MongoDB\String */
    protected $ChangeLog;

    /** @MongoDB\String */
    protected $EDepartment;

    /** @MongoDB\String */
    protected $ESection;

    /** @MongoDB\String */
    protected $AutoText;

    /** @MongoDB\String */
    protected $setAutoSave;

    /** @MongoDB\String */
    protected $Email;

    /** @MongoDB\String */
    protected $MiddleName;

    /** @MongoDB\String */
    protected $OtherContInf;

    /** @MongoDB\String */
    protected $MobilTel;

    /** @MongoDB\String */
    protected $MainEmail;
    
    /** @MongoDB\Collection */
    protected $EmailNames;

    /** @MongoDB\Collection */
    protected $EmailValues;

    /** @MongoDB\Collection */
    protected $PhoneNames;

    /** @MongoDB\Collection */
    protected $PhoneValues;

    /** @MongoDB\Collection */
    protected $PhoneCellNames;

    /** @MongoDB\Collection */
    protected $PhoneCellValues;

    /** @MongoDB\Collection */
    protected $Phone;

    /** @MongoDB\Collection */
    protected $SiteNames;

    /** @MongoDB\Collection */
    protected $SiteValues;

    /** @MongoDB\String */
    protected $Site;

    /** @MongoDB\String */
    protected $SiteName;

    /** @MongoDB\Collection */
    protected $OldGroupID;

    /** @MongoDB\String */
    protected $COUNTMESS = 0;

    /** @MongoDB\String */
    protected $AUTHORLASTMESS;

    /** @MongoDB\Collection */
    protected $PARTLAT;
    
    /** @MongoDB\String */
    protected $FullName;

    /** @MongoDB\String */
    protected $OtherName;

    /** @MongoDB\String */
    protected $NameCompany;

    /** @MongoDB\String */
    protected $ToSite;
    
    /** @MongoDB\String */
    protected $RegPlace;

    /** @MongoDB\String */
    protected $Sex;

    /** @MongoDB\Collection */
    protected $section;
    /*
    * ==================== DocumentType: "Group" ====================
    */

    /** @MongoDB\String */
    protected $C1WasImported;

    /** @MongoDB\String */
    protected $C1WaitSync;

    /** @MongoDB\String */
    protected $C1InternalCode;

    /** @MongoDB\String */
    protected $CreationType;

    /** @MongoDB\String */
    protected $Deleted;

    /** @MongoDB\String */
    protected $HRQuestionsLinkGeneratedBy;

    /** @MongoDB\String */
    protected $HRQuestionsLinkGeneratedDate;

    /** @MongoDB\String */
    protected $HitListLinkGeneratedBy;

    /** @MongoDB\String */
    protected $HitListLinkGeneratedDate;

    /** @MongoDB\String */
    protected $companyName;

    /* DISABLED
    * @MongoDB\ReferenceOne(
    *     targetDocument="Treto\PortalBundle\Document\Portal", 
    *     mappedBy="contactData",
    *     repositoryMethod="getForContact"
    * )
    */
    public $portalData = [];
    
    /** @MongoDB\Hash
    *   @Escalated(set="PM") */
    protected $security;

  /*
  * ==================== E/DOCTRINE MAPPINGS ====================
  */

    /*-----------B/CONTACT-EDITS---------------*/
    /** @MongoDB\String */
    protected $AddressBlockNumber_Actual;

    /** @MongoDB\String */
    protected $AddressBlockNumber_ForDelivery;

    /** @MongoDB\String */
    protected $AddressBlockNumber_ForLegal;

    /** @MongoDB\String */
    protected $AddressCityName_Actual;

    /** @MongoDB\String */
    protected $AddressCityName_ForDelivery;

    /** @MongoDB\String */
    protected $AddressCityName_ForLegal;

    /** @MongoDB\String */
    protected $AddressHouseNumber_Actual;

    /** @MongoDB\String */
    protected $AddressHouseNumber_ForDelivery;

    /** @MongoDB\String */
    protected $AddressHouseNumber_ForLegal;

    /** @MongoDB\String */
    protected $AddressOfficeSuiteNumber_Actual;

    /** @MongoDB\String */
    protected $AddressOfficeSuiteNumber_ForDelivery;

    /** @MongoDB\String */
    protected $AddressOfficeSuiteNumber_ForLegal;

    /** @MongoDB\String */
    protected $AddressStreetName_Actual;

    /** @MongoDB\String */
    protected $AddressStreetName_ForDelivery;

    /** @MongoDB\String */
    protected $AddressStreetName_ForLegal;

    /** @MongoDB\String */
    protected $AddressZipCode_Actual;

    /** @MongoDB\String */
    protected $AddressZipCode_ForDelivery;

    /** @MongoDB\String */
    protected $AddressZipCode_ForLegal;

    /** @MongoDB\String */
    protected $Auth_Active;

    /** @MongoDB\String */
    protected $Auth_Email;

    /** @MongoDB\String */
    protected $Bank;

    /** @MongoDB\String */
    protected $Bick;

    /** @MongoDB\Collection */
    protected $Company;

    /** @MongoDB\Collection */
    protected $InformationSource;

    /** @MongoDB\String */
    protected $CorrespondentAccount;

    /** @MongoDB\String */
    protected $CurrentAccount;

    /** @MongoDB\String */
    protected $DeliveryAddressIsDiff;

    /** @MongoDB\String */
    protected $KPP;

    /** @MongoDB\String */
    protected $INN;

    /** @MongoDB\String */
    protected $LegalAddressIsDiff;

    /** @MongoDB\String */
    protected $OGRN;

    /** @MongoDB\String */
    protected $OKPO;
    
    /** @MongoDB\String */
    protected $RegNo;
    
    /** @MongoDB\String */
    protected $VATNo;

    /** @MongoDB\String */
    protected $PassportDateIssued;

    /** @MongoDB\String */
    protected $PassportIssuedByOrg;

    /** @MongoDB\String */
    protected $PassportNubmer;

    /** @MongoDB\String */
    protected $PassportSeries;

    /** @MongoDB\String */
    protected $Passport;

    /** @MongoDB\String */
    protected $Swift;

    /** @MongoDB\String */
    protected $BossFIO;

    /** @MongoDB\String */
    protected $BossRank;

    /** @MongoDB\String */
    protected $BookerFIO;

    /** @MongoDB\String */
    protected $UserNotesName;

    /** @MongoDB\String */
    protected $WorkTel;

    /** @MongoDB\Collection */
    protected $Country;

    /** @MongoDB\String */
    protected $DiscountAccepted;

    /** @MongoDB\String */
    protected $DiscountAcceptedDate;

    /** @MongoDB\String */
    protected $DiscountAcceptor;

    /** @MongoDB\String */
    protected $DiscountAcceptorID;

    /** @MongoDB\String */
    protected $DiscountTermTaskUNID;

    /** @MongoDB\String */
    protected $DiscountAcceptTaskUNID;

    /** @MongoDB\String */
    protected $DiscountAcceptedComment;

    /** @MongoDB\String */
    protected $IsSupposed;

    /** @MongoDB\String */
    protected $DaysObtainOrders;

    /** @MongoDB\String */
    protected $SumOrdersMax;

    /*-----------E/CONTACT-EDITS---------------*/

  /*-----------B/form: "formDiscount"---------------*/
  /** @MongoDB\String */
  protected $ObjectDiscount;

  /** @MongoDB\String */
  protected $SampleDiscount;

  /** @MongoDB\String */
  protected $UseDiscount;

  /** @MongoDB\String */
  protected $BasicDiscount;

  /** @MongoDB\String */
  protected $ConditionDiscount_1;

  /** @MongoDB\String */
  protected $ConditionDiscount_2;

  /** @MongoDB\String */
  protected $ConditionDiscount_3;

  /** @MongoDB\String */
  protected $ConditionDiscount_4;

  /** @MongoDB\String */
  protected $ConditionDiscount_5;

  /** @MongoDB\String */
  protected $ConditionDiscount_6;

  /** @MongoDB\String */
  protected $Salary;
  
  /** @MongoDB\String */
  protected $FromDate;

  /** @MongoDB\String */
  protected $ConditionDuration;

  /** @MongoDB\String */
  protected $conditionunlimited;

  /** @MongoDB\Collection */
  protected $SeriesDiscount;

  /** @MongoDB\Collection */
  protected $SeriesDiscountId;

  /** @MongoDB\Collection */
  protected $ArticleDiscount;

  /** @MongoDB\Collection */
  protected $articlediscountid;

  /** @MongoDB\Collection */
  protected $SizeDiscount;

  /** @MongoDB\Collection */
  protected $sizediscountid;

  /** @MongoDB\String */
  protected $wase;

  /** @MongoDB\String */
  protected $Editor;

  /** @MongoDB\String */
  protected $OldDiscount;

  /** @MongoDB\Hash */
  protected $HRQuestionsREF;

  /** @MongoDB\String */
  protected $HitListREF;

  /** @MongoDB\String */
  protected $notSynch;
  /*-----------E/form: "formDiscount"---------------*/


  /*                                                            
   *================== B/Getters and Setters ====================
   */
    public function __construct($user = null, $accessOption = false) {
      if($accessOption){
        $this->SetAccessOption($accessOption);
      }
      $this->SetCreated();
      $this->SetModified();
      $this->setDefaultSecurity($user);
      $this->SetForm('Contact');
    }

    public function setDefaultSecurity($user = null) {
      $this->user = $user;
      $this->setupAccess($user);
      $this->setDefaultWriteSecurity($user);
    }

    public function setupAccess($user = null) {
      if($user) {
        $security['privileges']['read'][] = ['username' => $user->getUsername()];
      } else
      {
        $security['privileges']['read'] = [];
      }

      if($this->GetAccessOption() == '3')
      {
        $security['privileges']['read'][] = ['role' => 'all'];
      }

      $this->addSecurity($security);
    }

    public function getPayMethod() {
        return $this->payMethod;
    }
    public function setPayMethod($payMethod) {
        $this->payMethod = $payMethod;
    }

    public function GetSection() {
        return $this->section;
    }
    public function SetSection($section) {
        $this->section = !is_array($section)?[$section]:$section;
    }

    public function GetСurrency(){
        return $this->currency;
    }
    public function SetСurrency($currency){
        $this->currency = $currency;
    }

    public function GetLastFailSynch(){
        return $this->lastFailSynch;
    }

    public function SetLastFailSynch($lastFailSynch){
        return $this->lastFailSynch = $lastFailSynch;
    }

    /* DocumentType: "Common" */
    public function GetId() { 
      return $this->_id; 
    }
    public function SetId($_id) { 
      $this->_id = $_id; 
    }
    public function Get_id() { 
      return $this->_id; 
    }
    public function Set_id($_id){
      $this->_id = $_id; 
    }
    public function GetParentUNID(){
      return $this->parentUNID;
    }
    public function GetUnid(){
      return $this->unid;  
    }
    public function SetParentUNID($parentUNID) {
      $this->parentUNID = $parentUNID;
    }
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
    public function GetSubID() { return $this->SubID; }
    public function SetSubID($SubID) { $this->SubID = $SubID; }
    public function GetIsLinked() { return $this->isLinked; }
    public function SetIsLinked($v) { $this->isLinked = $v; }
    public function GetForm(){
      return $this->form; 
    }
    public function SetForm($form){
      $this->form = $form; 
    }
    public function GetActualAddress(){
      return $this->ActualAddress; 
    }
    public function SetActualAddress($ActualAddress){
      $this->ActualAddress = $ActualAddress; 
    }
    public function GetModifiedBy(){
        return $this->ModifiedBy;
    }
    public function SetModifiedBy($value){
        $this->ModifiedBy = $value;
    }
    public function GetDeliveryAddress(){
      return $this->DeliveryAddress; 
    }
    public function SetDeliveryAddress($DeliveryAddress){
      $this->DeliveryAddress = $DeliveryAddress; 
    }
    public function GetLegalAddress(){
      return $this->LegalAddress; 
    }
    public function SetLegalAddress($LegalAddress){
      $this->LegalAddress = $LegalAddress; 
    }
    public function GetNoteid(){
      return $this->noteid; 
    }
    public function SetNoteid($noteid){
      $this->noteid = $noteid; 
    }
    public function GetLanguage(){
      return $this->Language;
    }
    public function SetLanguage($language){
      $this->Language = $language;
    }
    public function GetSequence(){
      return $this->sequence; 
    }
    public function SetSequence($sequence){
      $this->sequence = $sequence; 
    }
    public function GetCreated(){
      return $this->created; 
    }
    public function SetCreated($created = null){
      if(! $created) {
        $d = (new \DateTime());
        $this->created = $d->format('Ymd').'T'.$d->format('His');
      } else {
        $this->created = $created;
      }
    }
    public function GetModified(){
      return $this->modified; 
    }
    public function SetModified($modified = null){
      if(! $modified) {
        $d = (new \DateTime());
        $this->modified = $d->format('Ymd').'T'.$d->format('His');
      } else {
        $this->modified = $modified;
      }
    }
    public function GetRevised(){
      return $this->revised; 
    }
    public function SetRevised($revised = false){
      if(! $revised) {
        $d = (new \DateTime());
        $this->revised = $d->format('Ymd').'T'.$d->format('His');
      } else {
        $this->revised = $revised;
      }
    }
    public function GetLastaccessed(){
      return $this->lastaccessed; 
    }
    public function SetLastaccessed($lastaccessed = false){
      if(! $lastaccessed) {
        $d = (new \DateTime());
        $this->lastaccessed = $d->format('Ymd').'T'.$d->format('His');
      } else {
        $this->lastaccessed = $lastaccessed;
      }
    }
    public function GetAddedtofile(){
      return $this->addedtofile; 
    }
    public function SetAddedtofile($addedtofile = false){
      if(! $addedtofile) {
        $d = (new \DateTime());
        $this->addedtofile = $d->format('Ymd').'T'.$d->format('His');
      } else {
        $this->addedtofile = $addedtofile;
      }
    }
    public function GetDocumentTypeGroup(){
      return $this->DocumentTypeGroup; 
    }
    public function SetDocumentTypeGroup($DocumentTypeGroup){
      $this->DocumentTypeGroup = $DocumentTypeGroup; 
    }
    public function GetDocumentType(){
      return $this->DocumentType; 
    }
    public function SetDocumentType($DocumentType){
      $this->DocumentType = $DocumentType; 
    }
    public function GetName(){
      return $this->Name; 
    }
    public function SetName($Name){
      $this->Name = $Name; 
    }
    public function GetBanApi(){
        return $this->banApi;
    }
    public function SetBanApi($value){
        $this->banApi = $value;
    }
    public function GetStatusForAccess(){
      return $this->statusForAccess; 
    }
    public function SetStatusForAccess($statusForAccess){
      $this->statusForAccess = $statusForAccess; 
    }
    public function GetAccessOption(){
      return $this->AccessOption; 
    }
    public function SetAccessOption($AccessOption){
        if($AccessOption == 1 || $AccessOption == 2){
            $this->removeActionPrivilege('read', 'role', 'all', null, true);
        }

        $this->AccessOption = $AccessOption;
    }
    public function GetStatus(){
      return $this->Status; 
    }
    public function SetStatus($Status){
      $this->Status = $Status; 
    }
    public function GetCreatedDate(){
      return $this->createdDate; 
    }
    public function SetCreatedDate($createdDate){
      $this->createdDate = $createdDate; 
    }
    public function GetContactId(){
      return $this->ContactId; 
    }
    public function SetContactId($ContactId){
      $this->ContactId = $ContactId; 
    }
    public function GetSubjectID(){
      return $this->SubjectID; 
    }
    public function SetSubjectID($SubjectID){
      $this->SubjectID = $SubjectID; 
    }
    public function GetPARENTID(){
      return $this->PARENTID; 
    }
    public function SetPARENTID($PARENTID){
      $this->PARENTID = $PARENTID; 
    }
    public function GetParentDbName() {
      return $this->ParentDbName;
    }
    public function GetAuthorCommon(){
      return $this->AuthorCommon; 
    }
    public function SetAuthorCommon($AuthorCommon){
      $this->AuthorCommon = $AuthorCommon; 
    }
    public function GetAuthor(){
      return $this->Author; 
    }
    public function SetAuthor($Author){
      $this->Author = $Author; 
    }
    public function GetAuthorLogin() { return $this->authorLogin; }
    public function SetAuthorLogin($v) { $this->authorLogin = $v; }
    public function GetAuthorRus(){
      return $this->AuthorRus; 
    }
    public function SetAuthorRus($AuthorRus){
      $this->AuthorRus = $AuthorRus; 
    }
    public function GetID_(){
      return $this->ID; 
    }
    public function SetID_($ID){
      $this->ID = $ID; 
    }
    public function GetSubject(){
      return $this->subject ? $this->subject : $this->GetSaveMainName(); 
    }
    public function SetSubject($subject){
      $this->subject = $subject; 
    }
    public function GetContactName(){
      return $this->ContactName; 
    }
    public function SetContactName($ContactName){
      $this->ContactName = $ContactName; 
    }
    public function GetXml1CResponse(){
      return $this->xml1CResponse; 
    }
    public function SetXml1CResponse($xml1CResponse){
      $this->xml1CResponse = $xml1CResponse; 
    }
    public function GetComment(){
      return $this->Comment; 
    }
    public function SetComment($Comment){
      $this->Comment = $Comment; 
    }
    public function GetV2AttachmentOptions(){
      return $this->V2AttachmentOptions; 
    }
    public function SetV2AttachmentOptions($V2AttachmentOptions){
      $this->V2AttachmentOptions = $V2AttachmentOptions; 
    }
    public function GetTmpComment(){
      return $this->tmpComment; 
    }
    public function SetTmpComment($tmpComment){
      $this->tmpComment = $tmpComment; 
    }
    public function GetREADEDBY(){
      return $this->READEDBY; 
    }
    public function SetREADEDBY($READEDBY){
      $this->READEDBY = $READEDBY; 
    }
    
    public function GetReadBy() { return $this->readBy ? $this->readBy : []; }
    public function SetReadBy($v) { $this->readBy = $v; }
    
    public function GetDATEMODIFIED(){
      return $this->DATEMODIFIED; 
    }
    public function SetDATEMODIFIED($DATEMODIFIED){
      $this->DATEMODIFIED = $DATEMODIFIED; 
    }
    public function GetConflictAction(){
      return $this->ConflictAction; 
    }
    public function SetConflictAction($ConflictAction){
      $this->ConflictAction = $ConflictAction; 
    }
    public function GetGroupPN(){
      return $this->GroupPN; 
    }
    public function SetGroupPN($GroupPN){
      $this->GroupPN = $GroupPN; 
    }
    public function GetGroupID(){
      return $this->GroupID; 
    }
    public function SetGroupID($GroupID){
      $this->GroupID = $GroupID; 
    }
    public function GetGroup(){
      return $this->Group; 
    }
    public function SetGroup($Group){
      $this->Group = $Group; 
    }
    public function GetModify1C(){
      return $this->modify1C; 
    }
    public function SetModify1C($modify1C){
      $this->modify1C = $modify1C; 
    }
    
    /* DocumentType: "Organization" */
    public function GetEmployee(){
      return $this->Employee; 
    }
    public function SetEmployee($Employee){
      $this->Employee = $Employee; 
    }
    public function GetResponsibleManager(){
      return $this->ResponsibleManager; 
    }
    public function SetResponsibleManager($ResponsibleManager){
      $this->ResponsibleManager = $ResponsibleManager; 
    }

    public function GetResponsibleManager_ID(){
      return $this->ResponsibleManager_ID; 
    }
    public function SetResponsibleManager_ID($ResponsibleManager){
      $this->ResponsibleManager_ID = $ResponsibleManager; 
    }

    public function GetResponsibleManager_LN(){
      return $this->ResponsibleManager_LN;
    }

    public function SetResponsibleManager_LN($ResponsibleManager_LN){
      $this->ResponsibleManager_LN = $ResponsibleManager_LN;
    }

    public function GetResponsibleManager_PortalID(){
      return $this->ResponsibleManager_PortalID;
    }

    public function SetResponsibleManager_PortalID($ResponsibleManager_PortalID){
      $this->ResponsibleManager_PortalID = $ResponsibleManager_PortalID;
    }

    public function GetTimePreparationShipping_Treto(){
      return $this->TimePreparationShipping_Treto;
    }

    public function SetTimePreparationShipping_Treto($TimePreparationShipping_Treto){
      $this->TimePreparationShipping_Treto = $TimePreparationShipping_Treto;
    }

    public function GetTimePreparationShipping_Te(){
      return $this->TimePreparationShipping_Te;
    }

    public function SetTimePreparationShipping_Te($TimePreparationShipping_Te){
      $this->TimePreparationShipping_Te = $TimePreparationShipping_Te;
    }


  public function GetGlobalHistory(){
      return $this->GlobalHistory; 
    }
    public function SetGlobalHistory($GlobalHistory){
      $this->GlobalHistory = $GlobalHistory; 
    }
    public function GetFormOwnership(){
      return $this->FormOwnership; 
    }
    public function SetFormOwnership($FormOwnership){
      $this->FormOwnership = $FormOwnership; 
    }
    public function GetMainName(){
      return $this->MainName; 
    }
    public function SetMainName($MainName){
      $this->MainName = $MainName; 
    }
    public function GetOther(){
      return $this->Other; 
    }
    public function SetOther($Other){
      $this->Other = $Other; 
    }
    public function GetOldPersonID(){
      return $this->OldPersonID; 
    }
    public function SetOldPersonID($OldPersonID){
      $this->OldPersonID = $OldPersonID; 
    }
    public function GetPersonID(){
      return $this->PersonID; 
    }
    public function SetPersonID($PersonID){
      $this->PersonID = $PersonID; 
    }
    public function GetPerson(){
      return $this->Person; 
    }
    public function SetPerson($Person){
      $this->Person = $Person; 
    }
    
    public function GetWqovalue_Person(){
      return $this->wqovalue_Person; 
    }
    public function SetWqovalue_Person($wqovalue_Person){
      $this->wqovalue_Person = $wqovalue_Person; 
    }
    public function GetWqovalue_(){
      return $this->wqovalue_; 
    }
    public function SetWqovalue_($wqovalue_){
      $this->wqovalue_ = $wqovalue_; 
    }
    public function GetWqovalue_StatusForAccess(){
      return $this->wqovalue_StatusForAccess; 
    }
    public function SetWqovalue_StatusForAccess($wqovalue_StatusForAccess){
      $this->wqovalue_StatusForAccess = $wqovalue_StatusForAccess; 
    }
    public function GetWqovalue_Status(){
      return $this->wqovalue_Status; 
    }
    public function SetWqovalue_Status($wqovalue_Status){
      $this->wqovalue_Status = $wqovalue_Status; 
    }
    public function GetWqovalue_MainName(){
      return $this->wqovalue_MainName; 
    }
    public function SetWqovalue_MainName($wqovalue_MainName){
      $this->wqovalue_MainName = $wqovalue_MainName; 
    }
    public function GetWqovalue_OtherName(){
      return $this->wqovalue_OtherName; 
    }
    public function SetWqovalue_OtherName($wqovalue_OtherName){
      $this->wqovalue_OtherName = $wqovalue_OtherName; 
    }
    
    public function GetLastHistoryChanged(){
      return $this->LastHistoryChanged; 
    }
    public function SetLastHistoryChanged($LastHistoryChanged){
      $this->LastHistoryChanged = $LastHistoryChanged;
      return $this; 
    }
    public function GetXml(){
      return $this->xml; 
    }
    public function SetXml($xml){
      $this->xml = $xml;
      return $this; 
    }
    public function GetContactStatus(){
      return $this->ContactStatus; 
    }
    public function SetContactStatus($ContactStatus){
      $this->ContactStatus = $ContactStatus;
      return $this; 
    }
    
    public function GetMentions() { return $this->mentions; }
    public function SetMentions($v) { $this->mentions = $v; }
    
    public function AddMentions($logins) {
      if(!is_array($this->mentions)) { $this->mentions = []; }
      foreach($logins as $login) {
        if (array_search($login, $this->mentions) === false) array_push($this->mentions, $login);
      }
    }
    
    public function RemoveMention($login) {
      if(!is_array($this->mentions) || sizeof($this->mentions) == 0) { return false; }
      $position = array_search($login, $this->mentions);
      if ($position !== false) {
        array_splice($this->mentions, $position, 1);
        return true;
      }
      else return false;
    }
    
    public function GetPayType(){
      return $this->PayType;
    }
    public function SetPayType($PayType){
      $this->PayType = $PayType;
      return $this;
    }

    public function GetContactWorkFrom(){
      return $this->ContactWorkFrom;
    }
    public function SetContactWorkFrom($ContactWorkFrom){
      $this->ContactWorkFrom = $ContactWorkFrom;
      return $this;
    }

    public function GetContactWorkFromID(){
      return $this->ContactWorkFromID;
    }

    public function SetContactWorkFromID($ContactWorkFromID){
      $this->ContactWorkFromID = $ContactWorkFromID;
      return $this;
    }

    public function GetPackageMeasureUnit(){
      return $this->ContactWorkFromID;
    }

    public function SetPackageMeasureUnit($PackageMeasureUnit){
      $this->PackageMeasureUnit = $PackageMeasureUnit;
      return $this;
    }

    public function GetPackageSize(){
      return $this->PackageSize;
    }

    public function SetPackageSize($PackageSize){
      $this->PackageSize = $PackageSize;
      return $this;
    }

    public function GetPackageFactor(){
      return $this->PackageFactor;
    }

    public function SetPackageFactor($PackageFactor){
      $this->PackageFactor = $PackageFactor;
      return $this;
    }

    public function GetMinDeliveryTerm(){
      return $this->MinDeliveryTerm;
    }

    public function SetMinDeliveryTerm($MinDeliveryTerm){
      $this->MinDeliveryTerm = $MinDeliveryTerm;
      return $this;
    }

    public function GetMinOrderSum(){
      return $this->MinOrderSum;
    }

    public function SetMinOrderSum($MinOrderSum){
      $this->MinOrderSum = $MinOrderSum;
      return $this;
    }

    public function GetIsIrregularFabric(){
      return $this->IsIrregularFabric;
    }

    public function SetIsIrregularFabric($IsIrregularFabric){
      $this->IsIrregularFabric = $IsIrregularFabric;
      return $this;
    }

    public function GetCallCost(){
      return $this->CallCost;
    }

    public function SetCallCost($CallCost){
      $this->CallCost = $CallCost;
      return $this;
    }

    public function GetExportDeclarationCost(){
      return $this->ExportDeclarationCost;
    }

    public function SetExportDeclarationCost($ExportDeclarationCost){
      $this->ExportDeclarationCost = $ExportDeclarationCost;
      return $this;
    }

    public function GetPayCreditSum(){
      return $this->PayCreditSum;
    }

    public function SetPayCreditSum($PayCreditSum){
      $this->PayCreditSum = $PayCreditSum;
      return $this;
    }

    public function GetPayCreditTerm(){
      return $this->PayCreditTerm;
    }

    public function SetPayCreditTerm($PayCreditTerm){
      $this->PayCreditTerm = $PayCreditTerm;
      return $this;
    }

    public function GetPayIsDelayPresent(){
      return $this->PayIsDelayPresent;
    }

    public function SetPayIsDelayPresent($PayIsDelayPresent){
      $this->PayIsDelayPresent = $PayIsDelayPresent;
      return $this;
    }

    public function GetPayPrepayment(){
      return $this->PayPrepayment;
    }

    public function SetPayPrepayment($PayPrepayment){
      $this->PayPrepayment = $PayPrepayment;
      return $this;
    }

    public function GetPrepaymentDiscount(){
      return $this->PrepaymentDiscount;
    }

    public function SetPrepaymentDiscount($PrepaymentDiscount){
      $this->PrepaymentDiscount = $PrepaymentDiscount;
      return $this;
    }

    public function GetinHoliday(){
      return $this->inHoliday;
    }

    public function SetinHoliday($inHoliday){
      $this->inHoliday = $inHoliday;
      return $this;
    }

    public function GetoutHoliday(){
      return $this->outHoliday;
    }

    public function SetoutHoliday($outHoliday){
      $this->outHoliday = $outHoliday;
      return $this;
    }

    public function GetPayAfterInvoiceTerm(){
      return $this->PayAfterInvoiceTerm;
    }

    public function SetPayAfterInvoiceTerm($PayAfterInvoiceTerm){
      $this->PayAfterInvoiceTerm = $PayAfterInvoiceTerm;
      return $this;
    }

    public function GetIsPackageOnly(){
      return $this->IsPackageOnly;
    }

    public function SetIsPackageOnly($IsPackageOnly){
      $this->IsPackageOnly = $IsPackageOnly;
      return $this;
    }

    public function GetIsPackageWithConditions(){
      return $this->inHoliday;
    }

    public function SetIsPackageWithConditions($IsPackageWithConditions){
      $this->IsPackageWithConditions = $IsPackageWithConditions;
      return $this;
    }

    /** DocumentType: "Person" */

    public function GetLastName(){
      return $this->LastName; 
    }
    public function SetLastName($LastName) { $this->LastName = $LastName;
      return $this;}
    public function GetFirstName(){
      return $this->FirstName; 
    }
    public function SetFirstName($FirstName){
      $this->FirstName = $FirstName;
      return $this; 
    }
    public function GetRank(){
      return $this->Rank; 
    }
    public function SetRank($Rank){
      $this->Rank = $Rank;
      return $this; 
    }
    public function GetIsHomeOrganization(){
      return $this->isHomeOrganization; 
    }
    public function SetIsHomeOrganization($isHomeOrganization){
      $this->isHomeOrganization = $isHomeOrganization;
      return $this; 
    }
    public function GetOrganizationID(){
      return $this->OrganizationID; 
    }
    public function SetOrganizationID($OrganizationID){
      $this->OrganizationID = $OrganizationID;
      return $this; 
    }
    public function GetOrganization(){
      return $this->Organization; 
    }
    public function SetOrganization($Organization){
      $this->Organization = $Organization;
      return $this; 
    }
    public function GetOrganizationPN(){
      return $this->OrganizationPN; 
    }
    public function SetOrganizationPN($OrganizationPN){
      $this->OrganizationPN = $OrganizationPN;
      return $this; 
    }
    public function GetContactBirthday(){
      return $this->ContactBirthday; 
    }
    public function SetContactBirthday($ContactBirthday){
      $this->ContactBirthday = $ContactBirthday;
      return $this; 
    }
    public function GetContactBirthmonth(){
      return $this->ContactBirthmonth; 
    }
    public function SetContactBirthmonth($ContactBirthmonth){
      $this->ContactBirthmonth = $ContactBirthmonth;
      return $this; 
    }
    public function GetContactBirthyear(){
      return $this->ContactBirthyear; 
    }
    public function SetContactBirthyear($ContactBirthyear){
      $this->ContactBirthyear = $ContactBirthyear;
      return $this; 
    }
    public function GetBirthDay(){
      return $this->BirthDay; 
    }
    public function SetBirthDay($BirthDay){
      $this->BirthDay = $BirthDay;
      return $this; 
    }
    public function GetRole(){
      return $this->role; 
    }
    public function SetRole($role){
      $this->role = $role;
      return $this; 
    }
    public function GetDismissed(){
      return $this->Dismissed; 
    }
    public function SetDismissed($Dismissed){
      $this->Dismissed = $Dismissed;
      return $this; 
    }
    public function GetERegion(){
      return $this->ERegion; 
    }
    public function SetERegion($ERegion){
      $this->ERegion = $ERegion;
      return $this; 
    }
    public function GetPortalUser(){
      return $this->PortalUser; 
    }
    public function SetPortalUser($PortalUser){
      $this->PortalUser = $PortalUser;
      return $this; 
    }
    public function GetPortalUser_LN(){
      return $this->PortalUser_LN; 
    }
    public function SetPortalUser_LN($PortalUser_LN){
      $this->PortalUser_LN = $PortalUser_LN; 
    }
    public function GetPortalUser_ID(){
      return $this->PortalUser_ID; 
    }
    public function SetPortalUser_ID($PortalUser_ID){
      $this->PortalUser_ID = $PortalUser_ID; 
    }
    public function GetGroupFullID(){
      return $this->GroupFullID; 
    }
    public function SetGroupFullID($GroupFullID){
      $this->GroupFullID = $GroupFullID;
      return $this; 
    }
    public function GetChangeLog(){
      return $this->ChangeLog; 
    }
    public function SetChangeLog($ChangeLog){
      $this->ChangeLog = $ChangeLog;
      return $this; 
    }
    public function GetEDepartment(){
      return $this->EDepartment; 
    }
    public function SetEDepartment($EDepartment){
      $this->EDepartment = $EDepartment;
      return $this; 
    }
    public function GetESection(){
      return $this->ESection; 
    }
    public function SetESection($ESection){
      $this->ESection = $ESection;
      return $this; 
    }
    public function GetAutoText(){
      return $this->AutoText; 
    }
    public function SetAutoText($AutoText){
      $this->AutoText = $AutoText;
      return $this; 
    }
    public function GetSetAutoSave(){
      return $this->setAutoSave; 
    }
    public function SetSetAutoSave($setAutoSave){
      $this->setAutoSave = $setAutoSave;
      return $this; 
    }
    public function GetSalary(){
      return $this->Salary; 
    }
    public function SetSalary($Salary){
      $this->Salary = $Salary;
      return $this; 
    }
    public function GetEmail(){
      return $this->Email; 
    }
    public function SetEmail($Email){
      $this->Email = $Email;
      return $this; 
    }
    public function GetMiddleName(){
      return $this->MiddleName; 
    }
    public function SetMiddleName($MiddleName){
      $this->MiddleName = $MiddleName;
      return $this; 
    }
    public function GetOtherContInf(){
      return $this->OtherContInf; 
    }
    public function SetOtherContInf($OtherContInf){
      $this->OtherContInf = $OtherContInf;
      return $this; 
    }
    public function GetMobilTel(){
      return $this->MobilTel; 
    }
    public function SetMobilTel($MobilTel){
      $this->MobilTel = $MobilTel;
      return $this; 
    }
    public function GetEmailNames(){
      return $this->EmailNames; 
    }
    public function SetEmailNames($EmailNames){
      $this->EmailNames = $EmailNames;
      return $this; 
    }
    public function GetEmailValues(){
      return $this->EmailValues; 
    }
    public function SetEmailValues($EmailValues){
      $this->EmailValues = $EmailValues;
      return $this; 
    }
    public function GetMainEmail(){
      return $this->MainEmail; 
    }
    public function SetMainEmail($MainEmail){
      $this->MainEmail = $MainEmail;
      return $this; 
    }
    public function GetSiteNames(){
      return $this->SiteNames; 
    }
    public function SetSiteNames($SiteNames){
      $this->SiteNames = $SiteNames;
      return $this; 
    }
    public function GetSiteValues(){
      return $this->SiteValues;
    }
    public function SetSiteValues($SiteValues){
      $this->SiteValues = $SiteValues;
      return $this;
    }
    public function GetSite(){
      return $this->Site;
    }
    public function SetSite($Site){
      $this->Site = $Site;
      return $this;
    }
    public function GetSiteName(){
      return $this->SiteName;
    }
    public function SetSiteName($SiteName){
      $this->SiteName = $SiteName;
      return $this;
    }
    public function GetPhoneNames(){
      return $this->PhoneNames; 
    }
    public function SetPhoneNames($PhoneNames){
      $this->PhoneNames = $PhoneNames;
      return $this; 
    }
    public function GetPhoneValues(){
      return $this->PhoneValues; 
    }
    public function SetPhoneValues($PhoneValues){
      $this->PhoneValues = $PhoneValues;
      return $this; 
    }
    public function GetPhoneCellValues(){
      return $this->PhoneCellValues;
    }
    public function SetPhoneCellValues($PhoneCellValues){
      $this->PhoneCellValues = $PhoneCellValues;
      return $this;
    }
    public function GetOldGroupID(){
      return $this->OldGroupID; 
    }
    public function SetOldGroupID($OldGroupID){
      $this->OldGroupID = $OldGroupID;
      return $this; 
    }
    public function GetCOUNTMESS(){
      return $this->COUNTMESS; 
    }
    public function SetCOUNTMESS($COUNTMESS){
      $this->COUNTMESS = $COUNTMESS;
      return $this; 
    }
    public function IncrementCountMess($fromUser) {
      if(!$fromUser) { return; }
      $this->COUNTMESS++;
      $this->authorLastMess = $fromUser->getPortalData()->GetFullNameInRus();
    }
    public function GetAUTHORLASTMESS(){
      return $this->AUTHORLASTMESS; 
    }
    public function SetAUTHORLASTMESS($AUTHORLASTMESS){
      $this->AUTHORLASTMESS = $AUTHORLASTMESS;
      return $this; 
    }
    public function GetPARTLAT(){
      return $this->PARTLAT; 
    }
    public function SetPARTLAT($PARTLAT){
      $this->PARTLAT = $PARTLAT;
      return $this; 
    }
    public function GetFullName(){
      return $this->FullName; 
    }
    public function SetFullName($FullName){
      $this->FullName = $FullName;
      return $this; 
    }
    public function GetOtherName(){
      return $this->OtherName; 
    }
    public function SetOtherName($OtherName){
      $this->OtherName = $OtherName;
      return $this; 
    }
    public function GetNameCompany(){
      return $this->NameCompany; 
    }
    public function SetNameCompany($NameCompany){
      $this->NameCompany = $NameCompany;
      return $this; 
    }
    public function GetToSite(){
      return $this->ToSite; 
    }
    public function SetToSite($ToSite){
      $this->ToSite = $ToSite;
      return $this; 
    }
    public function GetRegPlace(){
      return $this->RegPlace; 
    }
    public function SetRegPlace($RegPlace){
      $this->RegPlace = $RegPlace;
      return $this; 
    }
    public function GetSex(){
      return $this->Sex; 
    }
    public function SetSex($sex){
      $this->Sex = $sex;
      return $this; 
    }
    
    /* DocumentType: "Group" */
    public function GetC1WasImported(){
      return $this->C1WasImported; 
    }
    public function SetC1WasImported($C1WasImported){
      $this->C1WasImported = $C1WasImported; 
    }
    public function GetC1WaitSync(){
      return $this->C1WaitSync; 
    }
    public function SetC1WaitSync($C1WaitSync){
      $this->C1WaitSync = $C1WaitSync; 
    }
    public function GetC1InternalCode(){
      return $this->C1InternalCode; 
    }
    public function SetC1InternalCode($C1InternalCode){
      $this->C1InternalCode = $C1InternalCode; 
    }
    public function GetCreationType(){
      return $this->CreationType; 
    }
    public function SetCreationType($CreationType){
      $this->CreationType = $CreationType;
      return $this; 
    }
    public function GetDeleted(){
      return $this->Deleted; 
    }
    public function SetDeleted($Deleted){
      $this->Deleted = $Deleted;
      return $this; 
    }

    /*-----------B/CONTACT-EDITS---------------*/
    /** AddressBlockNumber_Actual */
    public function setAddressBlockNumber_Actual($AddressBlockNumber_Actual) { 
      $this->AddressBlockNumber_Actual = $AddressBlockNumber_Actual;
      return $this; 
    }
    /** AddressBlockNumber_Actual */
    public function getAddressBlockNumber_Actual (){
      return $this->AddressBlockNumber_Actual; 
    }

    /** AddressBlockNumber_ForDelivery */
    public function setAddressBlockNumber_ForDelivery($AddressBlockNumber_ForDelivery) { 
      $this->AddressBlockNumber_ForDelivery = $AddressBlockNumber_ForDelivery;
      return $this; 
    }
    /** AddressBlockNumber_ForDelivery */
    public function getAddressBlockNumber_ForDelivery (){
      return $this->AddressBlockNumber_ForDelivery; 
    }

    /** AddressBlockNumber_ForLegal */
    public function setAddressBlockNumber_ForLegal($AddressBlockNumber_ForLegal) { 
      $this->AddressBlockNumber_ForLegal = $AddressBlockNumber_ForLegal;
      return $this; 
    }
    /** AddressBlockNumber_ForLegal */
    public function getAddressBlockNumber_ForLegal (){
      return $this->AddressBlockNumber_ForLegal; 
    }

    /** AddressCityName_Actual */
    public function setAddressCityName_Actual($AddressCityName_Actual) { 
      $this->AddressCityName_Actual = $AddressCityName_Actual;
      return $this; 
    }
    /** AddressCityName_Actual */
    public function getAddressCityName_Actual (){
      return $this->AddressCityName_Actual; 
    }

    /** AddressCityName_ForDelivery */
    public function setAddressCityName_ForDelivery($AddressCityName_ForDelivery) { 
      $this->AddressCityName_ForDelivery = $AddressCityName_ForDelivery;
      return $this; 
    }
    /** AddressCityName_ForDelivery */
    public function getAddressCityName_ForDelivery (){
      return $this->AddressCityName_ForDelivery; 
    }

    /** AddressCityName_ForLegal */
    public function setAddressCityName_ForLegal($AddressCityName_ForLegal) { 
      $this->AddressCityName_ForLegal = $AddressCityName_ForLegal;
      return $this; 
    }
    /** AddressCityName_ForLegal */
    public function getAddressCityName_ForLegal (){
      return $this->AddressCityName_ForLegal; 
    }

    /** AddressHouseNumber_Actual */
    public function setAddressHouseNumber_Actual($AddressHouseNumber_Actual) { 
      $this->AddressHouseNumber_Actual = $AddressHouseNumber_Actual;
      return $this; 
    }
    /** AddressHouseNumber_Actual */
    public function getAddressHouseNumber_Actual (){
      return $this->AddressHouseNumber_Actual; 
    }

    /** AddressHouseNumber_ForDelivery */
    public function setAddressHouseNumber_ForDelivery($AddressHouseNumber_ForDelivery) { 
      $this->AddressHouseNumber_ForDelivery = $AddressHouseNumber_ForDelivery;
      return $this; 
    }
    /** AddressHouseNumber_ForDelivery */
    public function getAddressHouseNumber_ForDelivery (){
      return $this->AddressHouseNumber_ForDelivery; 
    }

    /** AddressHouseNumber_ForLegal */
    public function setAddressHouseNumber_ForLegal($AddressHouseNumber_ForLegal) { 
      $this->AddressHouseNumber_ForLegal = $AddressHouseNumber_ForLegal;
      return $this; 
    }
    /** AddressHouseNumber_ForLegal */
    public function getAddressHouseNumber_ForLegal (){
      return $this->AddressHouseNumber_ForLegal; 
    }

    /** AddressOfficeSuiteNumber_Actual */
    public function setAddressOfficeSuiteNumber_Actual($AddressOfficeSuiteNumber_Actual) { 
      $this->AddressOfficeSuiteNumber_Actual = $AddressOfficeSuiteNumber_Actual;
      return $this; 
    }
    /** AddressOfficeSuiteNumber_Actual */
    public function getAddressOfficeSuiteNumber_Actual (){
      return $this->AddressOfficeSuiteNumber_Actual; 
    }

    /** AddressOfficeSuiteNumber_ForDelivery */
    public function setAddressOfficeSuiteNumber_ForDelivery($AddressOfficeSuiteNumber_ForDelivery) { 
      $this->AddressOfficeSuiteNumber_ForDelivery = $AddressOfficeSuiteNumber_ForDelivery;
      return $this; 
    }
    /** AddressOfficeSuiteNumber_ForDelivery */
    public function getAddressOfficeSuiteNumber_ForDelivery (){
      return $this->AddressOfficeSuiteNumber_ForDelivery; 
    }

    /** AddressOfficeSuiteNumber_ForLegal */
    public function setAddressOfficeSuiteNumber_ForLegal($AddressOfficeSuiteNumber_ForLegal) { 
      $this->AddressOfficeSuiteNumber_ForLegal = $AddressOfficeSuiteNumber_ForLegal;
      return $this; 
    }
    /** AddressOfficeSuiteNumber_ForLegal */
    public function getAddressOfficeSuiteNumber_ForLegal (){
      return $this->AddressOfficeSuiteNumber_ForLegal; 
    }

    /** AddressStreetName_Actual */
    public function setAddressStreetName_Actual($AddressStreetName_Actual) { 
      $this->AddressStreetName_Actual = $AddressStreetName_Actual;
      return $this; 
    }
    /** AddressStreetName_Actual */
    public function getAddressStreetName_Actual (){
      return $this->AddressStreetName_Actual; 
    }

    /** AddressStreetName_ForDelivery */
    public function setAddressStreetName_ForDelivery($AddressStreetName_ForDelivery) { 
      $this->AddressStreetName_ForDelivery = $AddressStreetName_ForDelivery;
      return $this; 
    }
    /** AddressStreetName_ForDelivery */
    public function getAddressStreetName_ForDelivery (){
      return $this->AddressStreetName_ForDelivery; 
    }

    /** AddressStreetName_ForLegal */
    public function setAddressStreetName_ForLegal($AddressStreetName_ForLegal) { 
      $this->AddressStreetName_ForLegal = $AddressStreetName_ForLegal;
      return $this; 
    }
    /** AddressStreetName_ForLegal */
    public function getAddressStreetName_ForLegal (){
      return $this->AddressStreetName_ForLegal; 
    }

    /** AddressZipCode_Actual */
    public function setAddressZipCode_Actual($AddressZipCode_Actual) { 
      $this->AddressZipCode_Actual = $AddressZipCode_Actual;
      return $this; 
    }
    /** AddressZipCode_Actual */
    public function getAddressZipCode_Actual (){
      return $this->AddressZipCode_Actual; 
    }

    /** AddressZipCode_ForDelivery */
    public function setAddressZipCode_ForDelivery($AddressZipCode_ForDelivery) { 
      $this->AddressZipCode_ForDelivery = $AddressZipCode_ForDelivery;
      return $this; 
    }
    /** AddressZipCode_ForDelivery */
    public function getAddressZipCode_ForDelivery (){
      return $this->AddressZipCode_ForDelivery; 
    }

    /** AddressZipCode_ForLegal */
    public function setAddressZipCode_ForLegal($AddressZipCode_ForLegal) { 
      $this->AddressZipCode_ForLegal = $AddressZipCode_ForLegal;
      return $this; 
    }
    /** AddressZipCode_ForLegal */
    public function getAddressZipCode_ForLegal (){
      return $this->AddressZipCode_ForLegal; 
    }

    /** Auth_Active */
    public function setAuth_Active($Auth_Active) { 
      $this->Auth_Active = $Auth_Active;
      return $this; 
    }
    /** Auth_Active */
    public function getAuth_Active (){
      return $this->Auth_Active; 
    }

    /** Auth_Email */
    public function setAuth_Email($Auth_Email) { 
      $this->Auth_Email = $Auth_Email;
      return $this; 
    }
    /** Auth_Email */
    public function getAuth_Email (){
      return $this->Auth_Email; 
    }

    /** Bank */
    public function setBank($Bank) { 
      $this->Bank = $Bank;
      return $this; 
    }
    /** Bank */
    public function getBank (){
      return $this->Bank; 
    }

    /** BossFIO */
    public function setBossFIO($BossFIO) {
      $this->BossFIO = $BossFIO;
      return $this;
    }
    /** BossFIO */
    public function getBossFIO (){
      return $this->BossFIO;
    }

    /** BossRank */
    public function setCeoPosition($BossRank) {
      $this->BossRank = $BossRank;
      return $this;
    }
    /** BossRank */
    public function getBossRank (){
      return $this->BossRank;
    }

    /** BookerFIO */
    public function setBookerFIO($BookerFIO) {
      $this->BookerFIO = $BookerFIO;
      return $this;
    }
    /** BookerFIO */
    public function getBookerFIO (){
      return $this->BookerFIO;
    }

    /** Bick */
    public function setBick($Bick) { 
      $this->Bick = $Bick;
      return $this; 
    }
    /** Bick */
    public function getBick (){
      return $this->Bick; 
    }

    /** Company */
    public function setCompany($Company) { 
      $this->Company = $Company;
      return $this; 
    }
    /** Company */
    public function getCompany (){
      return $this->Company; 
    }

    /** InformationSource */
    public function setInformationSource($InformationSource) { 
      $this->InformationSource = $InformationSource;
      return $this; 
    }
    /** InformationSource */
    public function getInformationSource (){
      return $this->InformationSource; 
    }

    /** CorrespondentAccount */
    public function setCorrespondentAccount($CorrespondentAccount) { 
      $this->CorrespondentAccount = $CorrespondentAccount;
      return $this; 
    }
    /** CorrespondentAccount */
    public function getCorrespondentAccount (){
      return $this->CorrespondentAccount; 
    }

    /** CurrentAccount */
    public function setCurrentAccount($CurrentAccount) { 
      $this->CurrentAccount = $CurrentAccount;
      return $this; 
    }
    /** CurrentAccount */
    public function getCurrentAccount (){
      return $this->CurrentAccount; 
    }

    /** DeliveryAddressIsDiff */
    public function setDeliveryAddressIsDiff($DeliveryAddressIsDiff) { 
      $this->DeliveryAddressIsDiff = $DeliveryAddressIsDiff;
      return $this; 
    }
    /** DeliveryAddressIsDiff */
    public function getDeliveryAddressIsDiff (){
      return $this->DeliveryAddressIsDiff; 
    }

    /** KPP */
    public function setKPP($KPP) { 
      $this->KPP = $KPP;
      return $this; 
    }
    /** KPP */
    public function getKPP (){
      return $this->KPP; 
    }

    /** INN */
    public function setINN ($INN) {
      $this->INN = $INN;
      return $this;
    }
    /** INN */
    public function getINN (){
      return $this->INN;
    }

    /** LegalAddressIsDiff */
    public function setLegalAddressIsDiff($LegalAddressIsDiff) { 
      $this->LegalAddressIsDiff = $LegalAddressIsDiff;
      return $this; 
    }
    /** LegalAddressIsDiff */
    public function getLegalAddressIsDiff (){
      return $this->LegalAddressIsDiff; 
    }

    /** OGRN */
    public function setOGRN($OGRN) { 
      $this->OGRN = $OGRN;
      return $this; 
    }
    /** OGRN */
    public function getOGRN (){
      return $this->OGRN; 
    }

    /** OKPO */
    public function setOKPO($OKPO) { 
      $this->OKPO = $OKPO;
      return $this; 
    }
    /** OKPO */
    public function getOKPO (){
      return $this->OKPO; 
    }
    
    public function setRegNo($RegNo) { $this->RegNo = $RegNo; }

    public function getRegNo(){ return $this->RegNo; }
    
    public function setVATNo($VATNo) { $this->VATNo = $VATNo; }

    public function getVATNo(){ return $this->VATNo; }

    /** PassportDateIssued */
    public function setPassportDateIssued($PassportDateIssued) { 
      $this->PassportDateIssued = $PassportDateIssued;
      return $this; 
    }
    /** PassportDateIssued */
    public function getPassportDateIssued (){
      return $this->PassportDateIssued; 
    }

    /** PassportIssuedByOrg */
    public function setPassportIssuedByOrg($PassportIssuedByOrg) { 
      $this->PassportIssuedByOrg = $PassportIssuedByOrg;
      return $this; 
    }
    /** PassportIssuedByOrg */
    public function getPassportIssuedByOrg (){
      return $this->PassportIssuedByOrg; 
    }

    /** PassportNubmer */
    public function setPassportNubmer($PassportNubmer) { 
      $this->PassportNubmer = $PassportNubmer;
      return $this; 
    }
    /** PassportNubmer */
    public function getPassportNubmer (){
      return $this->PassportNubmer; 
    }

    /** PassportSeries */
    public function setPassportSeries($PassportSeries) { 
      $this->PassportSeries = $PassportSeries;
      return $this; 
    }
    /** PassportSeries */
    public function getPassportSeries (){
      return $this->PassportSeries; 
    }

     /** Passport*/
    public function setPassport($Passport) { 
      $this->Passport = $Passport;
      return $this; 
    }
    /** Passport */
    public function getPassport (){
      return $this->Passpor; 
    }

    /** Phone */
    public function SetPhone ($Phone) { 
      $this->Phone = $Phone;
      return $this;
    }
    /** Phone */
    public function GetPhone () { 
      return $this->Phone; 
    }

    /** Swift */
    public function setSwift($Swift) { 
      $this->Swift = $Swift;
      return $this; 
    }
    /** Swift */
    public function getSwift (){
      return $this->Swift; 
    } 

    /** UserNotesName */
    public function setUserNotesName($UserNotesName) { 
      $this->UserNotesName = $UserNotesName;
      return $this; 
    }
    /** UserNotesName */
    public function getUserNotesName (){
      return $this->UserNotesName; 
    }

    /** WorkTel */
    public function setWorkTel($WorkTel) { 
      $this->WorkTel = $WorkTel;
      return $this; 
    }
    /** WorkTel */
    public function getWorkTel (){
      return $this->WorkTel; 
    }

    /** Country */
    public function SetCountry ($Country) { 
      $this->Country = $Country;
      return $this; 
    }
    /** Country */
    public function GetCountry (){
      return $this->Country; 
    }

    /** ObjectDiscount */
    public function SetObjectDiscount ($ObjectDiscount) {
      $this->ObjectDiscount = $ObjectDiscount;
      return $this;
    }

    /** ObjectDiscount */
    public function GetObjectDiscount (){
      return $this->ObjectDiscount;
    }

    /** DiscountAccepted */
    public function SetDiscountAccepted ($DiscountAccepted) {
      $this->DiscountAccepted = $DiscountAccepted;
      return $this;
    }

    /** DiscountAccepted */
    public function GetDiscountAccepted (){
      return $this->DiscountAccepted;
    }

    /** DiscountAcceptedDate */
    public function SetDiscountAcceptedDate ($DiscountAcceptedDate = null) {
      if(! $DiscountAcceptedDate) {
        $d = (new \DateTime());
        $this->DiscountAcceptedDate = $d->format('Ymd').'T'.$d->format('His');
      } else {
        $this->DiscountAcceptedDate = $DiscountAcceptedDate;
      }
      return $this;
    }

    /** DiscountAcceptedDate */
    public function GetDiscountAcceptedDate (){
      return $this->DiscountAcceptedDate;
    }

    /** DiscountAcceptor */
    public function SetDiscountAcceptor ($DiscountAcceptor) {
      $this->DiscountAcceptor = $DiscountAcceptor;
      return $this;
    }

    /** DiscountAcceptor */
    public function GetDiscountAcceptor (){
      return $this->DiscountAcceptor;
    }

    /** DiscountAcceptorID */
    public function SetDiscountAcceptorID ($DiscountAcceptorID) {
      $this->DiscountAcceptorID = $DiscountAcceptorID;
      return $this;
    }

    /** DiscountAcceptorID */
    public function GetDiscountAcceptorID (){
      return $this->DiscountAcceptorID;
    }

    /** DiscountTermTaskUNID */
    public function SetDiscountTermTaskUNID ($DiscountTermTaskUNID) {
      $this->DiscountTermTaskUNID = $DiscountTermTaskUNID;
      return $this;
    }

    /** DiscountTermTaskUNID */
    public function GetDiscountTermTaskUNID (){
      return $this->DiscountTermTaskUNID;
    }

    /** DiscountAcceptTaskUNID */
    public function SetDiscountAcceptTaskUNID ($DiscountAcceptTaskUNID) {
      $this->DiscountAcceptTaskUNID = $DiscountAcceptTaskUNID;
      return $this;
    }

    /** DiscountAcceptTaskUNID */
    public function GetDiscountAcceptTaskUNID (){
      return $this->DiscountAcceptTaskUNID;
    }

    /** DiscountAcceptedComment */
    public function SetDiscountAcceptedComment ($DiscountAcceptedComment) {
      $this->DiscountAcceptedComment = $DiscountAcceptedComment;
      return $this;
    }

    /** DiscountAcceptedComment */
    public function GetDiscountAcceptedComment (){
      return $this->DiscountAcceptedComment;
    }

    /** SampleDiscount */
    public function SetSampleDiscount ($SampleDiscount) {
      $this->SampleDiscount = $SampleDiscount;
      return $this;
    }

    /** SampleDiscount */
    public function GetSampleDiscount (){
      return $this->SampleDiscount;
    }

    /** ConditionDiscount_4 */
    public function SetConditionDiscount_4 ($ConditionDiscount_4) {
      $this->ConditionDiscount_4 = $ConditionDiscount_4;
      return $this;
    }

    /** ConditionDiscount_4 */
    public function GetConditionDiscount_4 (){
      return $this->ConditionDiscount_4;
    }

    /** ConditionDiscount_3 */
    public function SetConditionDiscount_3 ($ConditionDiscount_3) {
      $this->ConditionDiscount_3 = $ConditionDiscount_3;
      return $this;
    }

    /** ConditionDiscount_3 */
    public function GetConditionDiscount_3 (){
      return $this->ConditionDiscount_3;
    }

    /** ConditionDiscount_2 */
    public function SetConditionDiscount_2 ($ConditionDiscount_2) {
      $this->ConditionDiscount_2 = $ConditionDiscount_2;
      return $this;
    }

    /** ConditionDiscount_2 */
    public function GetConditionDiscount_2 (){
      return $this->ConditionDiscount_2;
    }

    /** ConditionDiscount_1 */
    public function SetConditionDiscount_1 ($ConditionDiscount_1) {
      $this->ConditionDiscount_1 = $ConditionDiscount_1;
      return $this;
    }

    /** ConditionDiscount_1 */
    public function GetConditionDiscount_1 (){
      return $this->ConditionDiscount_1;
    }

    /** BasicDiscount */
    public function SetBasicDiscount ($BasicDiscount) {
      $this->BasicDiscount = $BasicDiscount;
      return $this;
    }

    /** BasicDiscount */
    public function GetBasicDiscount (){
      return $this->BasicDiscount;
    }

    /** UseDiscount */
    public function SetUseDiscount ($UseDiscount) {
      $this->UseDiscount = $UseDiscount;
      return $this;
    }

    /** UseDiscount */
    public function GetUseDiscount (){
      return $this->UseDiscount;
    }

    /** ArticleDiscount */
    public function SetArticleDiscount ($ArticleDiscount) {
      $this->ArticleDiscount = $ArticleDiscount;
      return $this;
    }

    /** ArticleDiscount */
    public function GetArticleDiscount (){
      return $this->ArticleDiscount;
    }

    /** SeriesDiscountId */
    public function SetSeriesDiscountId ($SeriesDiscountId) {
      $this->SeriesDiscountId = $SeriesDiscountId;
      return $this;
    }

    /** SeriesDiscountId */
    public function GetSeriesDiscountId (){
      return $this->SeriesDiscountId;
    }

    /** SeriesDiscount */
    public function SetSeriesDiscount ($SeriesDiscount) {
      $this->SeriesDiscount = $SeriesDiscount;
      return $this;
    }

    /** SeriesDiscount */
    public function GetSeriesDiscount (){
      return $this->SeriesDiscount;
    }

    /** IsSupposed */
    public function SetIsSupposed ($IsSupposed) {
      $this->IsSupposed = $IsSupposed;
      return $this;
    }

    /** IsSupposed */
    public function GetIsSupposed (){
      return $this->IsSupposed;
    }

    /** conditionunlimited */
    public function SetConditionunlimited ($conditionunlimited) {
      $this->conditionunlimited = $conditionunlimited;
      return $this;
    }

    /** conditionunlimited */
    public function GetConditionunlimited (){
      return $this->conditionunlimited;
    }

    /** ConditionDuration */
    public function SetConditionDuration ($ConditionDuration) {
      $this->ConditionDuration = $ConditionDuration;
      return $this;
    }

    /** ConditionDuration */
    public function GetConditionDuration (){
      return $this->ConditionDuration;
    }

    /** FromDate */
    public function SetFromDate ($FromDate) {
      $this->FromDate = $FromDate;
      return $this;
    }

    /** FromDate */
    public function GetFromDate (){
      return $this->FromDate;
    }

    /** ConditionDiscount_6 */
    public function SetConditionDiscount_6 ($ConditionDiscount_6) {
      $this->ConditionDiscount_6 = $ConditionDiscount_6;
      return $this;
    }

    /** ConditionDiscount_6 */
    public function GetConditionDiscount_6 (){
      return $this->ConditionDiscount_6;
    }

    /** ConditionDiscount_5 */
    public function SetConditionDiscount_5 ($ConditionDiscount_5) {
      $this->ConditionDiscount_5 = $ConditionDiscount_5;
      return $this;
    }

    /** ConditionDiscount_5 */
    public function GetConditionDiscount_5 (){
      return $this->ConditionDiscount_5;
    }

    /** Editor */
    public function SetEditor ($Editor) {
      $this->Editor = $Editor;
      return $this;
    }

    /** Editor */
    public function GetEditor (){
      return $this->Editor;
    }

    /** wase */
    public function SetWase ($wase) {
      $this->wase = $wase;
      return $this;
    }

    /** wase */
    public function GetWase (){
      return $this->wase;
    }

    /** sizediscountid */
    public function SetSizediscountid ($sizediscountid) {
      $this->sizediscountid = $sizediscountid;
      return $this;
    }

    /** sizediscountid */
    public function GetSizediscountid (){
      return $this->sizediscountid;
    }

    /** SizeDiscount */
    public function SetSizeDiscount ($SizeDiscount) {
      $this->SizeDiscount = $SizeDiscount;
      return $this;
    }

    /** SizeDiscount */
    public function GetSizeDiscount (){
      return $this->SizeDiscount;
    }

    /** articlediscountid */
    public function SetArticlediscountid ($articlediscountid) {
      $this->articlediscountid = $articlediscountid;
      return $this;
    }

    /** articlediscountid */
    public function GetArticlediscountid (){
      return $this->articlediscountid;
    }

    /** OldDiscount */
    public function SetOldDiscount ($OldDiscount) {
      $this->OldDiscount = $OldDiscount;
      return $this;
    }

    /** OldDiscount */
    public function GetOldDiscount (){
      return $this->OldDiscount;
    }

    /** HRQuestionsLinkGeneratedBy */
    public function SetHRQuestionsLinkGeneratedBy ($HRQuestionsLinkGeneratedBy) {
      $this->HRQuestionsLinkGeneratedBy = $HRQuestionsLinkGeneratedBy;
    }

    /** HRQuestionsLinkGeneratedBy */
    public function GetHRQuestionsLinkGeneratedBy (){
      return $this->SetHRQuestionsLinkGeneratedBy;
    }

    /** HRQuestionsLinkGeneratedDate */
    public function SetHRQuestionsLinkGeneratedDate ($HRQuestionsLinkGeneratedDate = '') {
      if(! $HRQuestionsLinkGeneratedDate) {
        $d = (new \DateTime());
        $this->HRQuestionsLinkGeneratedDate = $d->format('Ymd').'T'.$d->format('His');
      } else {
        $this->HRQuestionsLinkGeneratedDate = $HRQuestionsLinkGeneratedDate;
      }
    }

    /** HRQuestionsLinkGeneratedDate */
    public function GetHRQuestionsLinkGeneratedDate (){
      return $this->HRQuestionsLinkGeneratedDate;
    }

    /** HitListLinkGeneratedBy */
    public function SetHitListLinkGeneratedBy ($HitListLinkGeneratedBy) {
      $this->HitListLinkGeneratedBy = $HitListLinkGeneratedBy;
      return $this;
    }

    /** HitListLinkGeneratedBy */
    public function GetHitListLinkGeneratedBy (){
      return $this->HitListLinkGeneratedBy;
    }

    public function GetCompanyName() {
        $this->companyName;
    }

    public function SetCompanyName($val) {
        $this->companyName = $val;
    }

    /** HitListLinkGeneratedDate */
    public function SetHitListLinkGeneratedDate ($HitListLinkGeneratedDate = '') {
      if(! $HitListLinkGeneratedDate) {
        $d = (new \DateTime());
        $this->HitListLinkGeneratedDate = $d->format('Ymd').'T'.$d->format('His');
      } else {
        $this->HitListLinkGeneratedDate = $HitListLinkGeneratedDate;
      }
    }

    /** HitListLinkGeneratedDate */
    public function GetHitListLinkGeneratedDate (){
      return $this->HitListLinkGeneratedDate;
    }

    /*-----------E/CONTACT-EDITS---------------*/


    /**
     * Set statusList
     *
     * @param collection $statusList
     * @return self
     */
    public function setStatusList($statusList)
    {
        $this->StatusList = $statusList;
        return $this;
    }

    /**
     * Get statusList
     *
     * @return collection $statusList
     */
    public function getStatusList() { 
      return ($this->StatusList !== null) ? $this->StatusList : []; 
    }
    
    public function getSecurity() {
        return $this->security;
    }
    public function setSecurity($security) {
        $this->security = $security;
    }

    public function getPortalData() {
      return $this->portalData; 
    }
    
    public function setPortalData($portalData) {
      $this->portalData = $portalData;
      return $this;
    }

    public function HasParent() {
      return false;
    }

    public function getHRQuestionsREF(){
      return $this->HRQuestionsREF;
    }

    public function setHRQuestionsREF($HRQuestionsREF){
      $this->HRQuestionsREF = $HRQuestionsREF;
    }

    public function getHitListREF(){
      return $this->HitListREF;
    }

    public function setHitListREF($HitListREF){
      $this->HitListREF = $HitListREF;
    }

    public function getDaysObtainOrders(){
      return $this->DaysObtainOrders;
    }

    public function setDaysObtainOrders($DaysObtainOrders){
      $this->DaysObtainOrders = $DaysObtainOrders;
    }

    public function getSumOrdersMax(){
      return $this->SumOrdersMax;
    }

    public function setSumOrdersMax($SumOrdersMax){
      $this->SumOrdersMax = $SumOrdersMax;
    }

    public function setNotSynch($param){
        $this->notSynch = $param;
    }

    public function getNotSynch(){
        return $this->notSynch;
    }
    
    /**
    * get document as array
    * @param bool $needPortalData
    * @param bool $needUserData
    * @param string $roles, you can pass User::getRoles() result
    * @return array
    */
    public function getDocument($needPortalData = false, $needUserData = false, $roles = [])
    {
        $document = (new \Treto\PortalBundle\Model\DocumentSerializer($this,$roles))->toArray(['portalData']);
        $document['portalData'] = null;
        if($needPortalData && ($d = $this->getPortalData())) { 
          $document['portalData'] = $d->getDocument($needUserData, false, $roles);
        }
        $document['id'] = $this->getId(); // TODO: IMPORTANT! remove 'id' and work with '_id'
        if(! $this->GetForm()) { $document['form'] = 'Contact'; }
        return $document;
    }

    /**
      * @return timestamp as 20141231T181104,12+04
     */ 
    protected function createTimestamp() {
      $d = new \DateTime();
      return $d->format('d:m:Y H:i:s');
    }
    
    /** Set document from array 
    * @param array $array representation of the document
    * @param \Treto\PortalBundle\Validator\Validator $validator
    * @param string $roles, you can pass User::getRoles() result
    * @return array of validation errors or empty array on success
    * TODO: слить 3й и 4й аргумент воедино, поскольку они избыточны. $roles можно брать из $user
    */
    public function setDocument(array $array, $validator = null, $roles = [], $user = null) {
        if($user) {
          $array['ChangeLog'] = $this->getUpdatedChangeLog($user);
          $array['ModifiedBy'] = $user->getPortalData()->GetContactUnid();
        }
        (new \Treto\PortalBundle\Model\DocumentSerializer($this,$roles))->fromArray($array,['_id', 'portalData']);
        if($validator) {
            return $validator->validate($this);
        }
        return [];
    }

    public function getUpdatedChangeLog($user) {
      $History = simplexml_load_string('<History/>');
      if(strstr($this->GetChangeLog(),'<')) {
          libxml_use_internal_errors(true);

          $parsResult = simplexml_load_string(str_replace(['<par>', '</par>', "\n", "\r"], '', $this->GetChangeLog()));
          if($parsResult !== false){
              $History = $parsResult;
          }

          libxml_use_internal_errors(false);
      }


      $Entry = $History->addChild('Entry');
      $newEntry = $this->getChangeLogEntry($user);
      foreach($newEntry as $key=>$value) {
        $value = htmlentities($newEntry[$key],ENT_QUOTES);
        $Entry->addAttribute($key, $value);
      }
      $newXml = preg_replace('/^<.xml version="1\.0".>/', '', $History->asXML());
      $newXml = html_entity_decode($newXml);
      return $newXml;
      //$this->SetChangeLog($newXml);

    }

    protected function getChangeLogEntry($user) {
      $contactData = $user->getDocument(1,1)['portalData'];
      $UserNotesName = isset($contactData['FullName']) ? $contactData['FullName'] : '';
      $UserShortName = isset($contactData['LastName']) ? $contactData['LastName'] . ' ' : '' . ' ';
      $UserShortName .= preg_replace('/(\S)\S+/u', '\1. ', isset($contactData['name']) ? $contactData['name'] : '' . ' ');
      $UserShortName .= preg_replace('/(\S)\S+/u', '\1.' , isset($contactData['MiddleName']) ? $contactData['MiddleName'] : '' . ' ');
      return [
        'Date' => $this->createTimestamp(),
        'UserName' => $contactData['FullNameInRus'],
        'UserNotesName' => $UserNotesName,
        'UserShortName' => $UserShortName,
      ];
    }

    /** @MongoDb\PreUpdate */
    public function preUpdate() {
     // $this->SetModified();
    }
    
    /** @MongoDb\PostLoad */
    public function postLoad() {
     // $this->SetLastaccessed();
    }

    private function GetSaveMainName() {
      $Name = $this->GetMainName();
      if($this->GetDocumentType()=='Person') {
        $Name = '';
        if($this->FirstName) $Name = $this->FirstName;
        if($this->MiddleName) $Name .= ' ' . $this->MiddleName;
        if($this->LastName) $Name .= ' ' . $this->LastName;
      }
      if($this->DocumentType == 'Organization') {
        $Name = $this->GetOtherName();
      }
      return $Name;
    }

    /** @MongoDb\PostUpdate */
    public function postUpdate(){
      @file_get_contents(self::C1_SYNCH_URL);
    }

    /** @MongoDb\PostPersist */
    public function postPersist(){
        $this->postUpdate();
    }

    public function prepareAutoDicomposition($text) {
      $text = $this::prepareStringToClear($text);

      $arrFioAndGeo = $this::parseFIOAndGeo($text);
      $this->setupFIO($arrFioAndGeo);
      $this->setupAddress($arrFioAndGeo);

      $text = explode(' ', $text);

      $this->setupEmails($text);
      $this->setupPhone($text);
    }
    
    /** @MongoDb\PrePersist */
    public function prePersist() {
      if(!$this->GetDocumentTypeGroup()) $this->SetDocumentTypeGroup('Contact');
      $Name = $this->GetSaveMainName();
      if(!$this->GetMainName())$this->SetMainName( $Name );
      if($this->DocumentType != 'Organization') {
        if($Name) $this->FullName = $Name;
        if($Name) $this->ContactName = $Name;
      }
      if(!$this->OtherName)$this->OtherName = $Name;
      if(!$this->subject)$this->subject = $Name;
      if(!$this->unid) { $this->SetUnid(); }
      $unid = $this->GetUnid();
      if(!$this->ContactId) $this->SetContactId($unid);
      $this->SetSubjectID($unid);
      $this->SetID_($unid);
      $this->setupAccess();
      if($this->getNotSynch()){
        $this->setNotSynch(false);
      }
      else {
        $this->SetC1WaitSync(true);
      }
    }

  public static function prepareStringToClear($text)
  {
    $text = strip_tags($text);
    $text = str_replace(chr(10), ' ', $text);
    $text = str_replace(chr(13), ' ', $text);
    $text = str_replace(chr(9), ' ', $text);
    $text = str_replace(';', ' ', $text);
    $text = str_replace(',', ' ', $text);

    return $text;
  }

  public static function parseEmails($arrStrings)
  {
    $arrEmails = [];
    foreach ($arrStrings as $string)
    {
      if(filter_var(trim($string), FILTER_VALIDATE_EMAIL))
      {
        array_push($arrEmails, $string);
      }
    }
    return $arrEmails;
  }

  public function setupEmails($text)
  {
    $this->EmailValues = $this::parseEmails($text);
  }

  public static function parseFIOAndGeo($text)
  {
    $marker = time();
    $ext = '.txt';
    $fileName = __DIR__ . '/../../../../app/resume/resume' . $marker . $ext;
    $fileJsonName = __DIR__ . '/../../../../app/resume/resume' . $marker . '_json' . $ext;
    $fp = fopen($fileName, "w+");
    $test = fwrite($fp, $text);
    fclose($fp);

    shell_exec('mystem -nig --weight --format json ' . $fileName . ' ' . $fileJsonName);

    $arr = [];
    $handle = @fopen($fileJsonName, "r");
    if ($handle) {
      while (($buffer = fgets($handle, 4096)) !== false) {
        $arr[] = json_decode($buffer, true);
      }
      if (!feof($handle)) {
        return false;
      }
      fclose($handle);
    }
    @unlink($fileName);
    @unlink($fileJsonName);
    $result = ['FirstName' => [],'MiddleName' => [],'LastName' => [],'geo' => []];
    foreach ($arr as $key => $val)
    {
      if(!empty($val['analysis']))
      {
        foreach ($val['analysis'] as $item)
        {
          if (stripos($item['gr'], 'од') === false || !round($item['wt'], 2))
          {
            continue;
          }
          if(stripos($item['gr'], 'фам') !== false)
          {
            $result['LastName'][] = ['key' => $key, 'val' => $val['text'], 'gr' => $item['gr']];
          }
          if(stripos($item['gr'], 'отч') !== false)
          {
            $result['MiddleName'][] = ['key' => $key, 'val' => $val['text'], 'gr' => $item['gr']];
          }
          if(stripos($item['gr'], 'имя') !== false)
          {
            $result['FirstName'][] = ['key' => $key, 'val' => $val['text'], 'gr' => $item['gr']];
          }
          if(stripos($item['gr'], 'гео') !== false)
          {
            $result['geo'][] = ['key' => $key, 'val' => $val['text'], 'gr' => $item['gr']];
          }
        }
      }
    }

    return $result;
  }

  public function setupFIO($arrFioAndGeo)
  {
    $this->setupLastName($arrFioAndGeo);
    $this->setupFirstName($arrFioAndGeo);
    $this->setupMiddleName($arrFioAndGeo);
  }

    /**
     * Check the register of the first character
     * @param $oldWord
     * @param $newWord
     * @return mixed
     */
  private function checkFlRegister ($oldWord, $newWord){
      $oldFirstLetter = mb_substr($oldWord, 0, 1,"UTF-8");
      $newFirstLetter = mb_substr($newWord, 0, 1,"UTF-8");
      return !$oldWord || !(strtoupper($oldFirstLetter) == $oldFirstLetter
          && strtolower($newFirstLetter) == $newFirstLetter)?$newWord:$oldWord;
  }

  public function setupFirstName($arrFioAndGeo)
  {
    if(!$this->FirstName)
    {
      switch (count($arrFioAndGeo['FirstName'])) {
        case 1:
          $this->FirstName = $arrFioAndGeo['FirstName'][0]['val'];
          break;
        default:
          foreach ($arrFioAndGeo['FirstName'] as $firstName) {
            foreach ($arrFioAndGeo['LastName'] as $lastName) {
              if ($firstName['key'] >= ($lastName['key'] - 3) && $firstName['key'] <= ($lastName['key'] + 3)) {
                  $this->FirstName = $this->checkFlRegister($this->FirstName, $firstName['val']);
              }
            }
          }
      }
    }
    if(!$this->FirstName)
    {
      $this->FirstName = 'Имя';
    }
  }

  public function setupLastName($arrFioAndGeo)
  {
      switch (count($arrFioAndGeo['LastName'])) {
        case 1:
          $this->LastName = $arrFioAndGeo['LastName'][0]['val'];
          break;
        default:
          foreach ($arrFioAndGeo['LastName'] as $lastName) {
            foreach ($arrFioAndGeo['FirstName'] as $firstName) {
              if($lastName['key'] >= ($firstName['key'] - 3) && $lastName['key'] <= ($firstName['key'] + 3))
              {
                  $this->LastName =  $this->checkFlRegister($this->LastName, $lastName['val']);
                  $this->FirstName = $this->checkFlRegister($this->FirstName, $firstName['val']);
              }
            }
          }
      }
      if(!$this->LastName)
      {
        $this->LastName = 'Фамилия';
      }
  }

  public function setupMiddleName($arrFioAndGeo)
  {
    switch (count($arrFioAndGeo['MiddleName'])) {
      case 1:
        $this->MiddleName = $arrFioAndGeo['MiddleName'][0]['val'];
        break;
      default:
        foreach ($arrFioAndGeo['MiddleName'] as $middleName) {
          foreach ($arrFioAndGeo['FirstName'] as $firstName) {
            if($middleName['key'] >= ($firstName['key'] - 3) && $middleName['key'] <= ($firstName['key'] + 3))
            {
                $this->FirstName = $this->checkFlRegister($this->MiddleName, $middleName['val']);
            }
          }
        }
    }
  }

  /* just get first geo object, because it's nobody wants
   * */
  public function setupAddress($arrFioAndGeo)
  {
    if (count($arrFioAndGeo['geo'])) {
      $this->AddressCityName_Actual = $arrFioAndGeo['geo'][0]['val'];
    }
  }

  public function parsePhone($arrStrings)
  {
    $arrPhones = [];
    foreach ($arrStrings as $string)
    {
      if(preg_match('/((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}/',$string))
      {
        array_push($arrPhones, $string);
      }
    }
    return $arrPhones;
  }

  public function setupPhone($text)
  {
    $this->PhoneValues = $this::parsePhone($text);
  }

    /**
     * Edit document name
     * @param $document
     * @param $contactType
     * @return mixed
     */
    static function editContactName($document, $contactType){
        if(isset($contactType)){
            $contactName = '';
            switch($contactType){
                case 'Organization':
                    if(isset($document['OtherName']) && $document['OtherName']){
                        $contactName = $document['OtherName'];
                    }
                    if(isset($document['MainName']) && $document['MainName']){
                        $contactName = $document['MainName'];
                    }
                    $document['ContactName'] = $contactName;
                    $document['FullName'] = $contactName;
                    $document['subject'] = $contactName;
                    $document['Employee'] = $contactName;
                    break;
                case 'Person':
                    $contactName .= isset($document['LastName'])?trim($document['LastName']).' ':'';
                    $contactName .= isset($document['FirstName'])?trim($document['FirstName']).' ':'';
                    $contactName .= isset($document['MiddleName'])?trim($document['MiddleName']):'';

                    $contactName = trim($contactName);
                    if($contactName){
                        $document['Employee'] = $contactName;
                        $document['FullName'] = $contactName;
                        $document['MainName'] = $contactName;
                        $document['OtherName'] = $contactName;
                        $document['subject'] = $contactName;
                        $document['ContactName'] = $contactName;
                    }
                    break;
            }
        }
        return $document;
    }
}
