<?php

namespace Treto\UserBundle\Document;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Treto\UserBundle\Annotation\ExtendedPrivileges as Escalated;
use Treto\PortalBundle\Document\SecureDocument;

/** 
 * @MongoDB\Document(repositoryClass="UserRepository")
 */
class User extends BaseUser
{
    /** @Escalated(set="deny") */
    protected $username;
    /** @Escalated(set="deny") */
    protected $usernameCanonical;
    /** @Escalated(set="PM") */
    protected $email;
    /** @Escalated(set="PM") */
    protected $emailCanonical;
    /** @Escalated(set="PM") */
    protected $enabled;
    /** @Escalated(set="deny", get="PM") */
    protected $salt;
    /** @Escalated(set="deny", get="PM") */
    protected $password;
    /** @Escalated(set="deny", get="PM") */
    protected $plainPassword;
    /** @MongoDB\Date
      * @Escalated(set="deny") */
    protected $lastLogin;
    /** @Escalated(set="PM") */
    protected $confirmationToken;
    /** @Escalated(set="PM") */
    protected $passwordRequestedAt;
    /** @Escalated(set="deny") */
    protected $groups;
    /** @Escalated(set="PM") */
    protected $locked;
    /** @Escalated(set="PM") */
    protected $expired;
    /** @Escalated(set="PM") */
    protected $expiresAt;
    /** @Escalated(set="deny") */
    protected $roles;
    /** @Escalated(set="PM") */
    protected $credentialsExpired;
    /** @Escalated(set="PM") */
    protected $credentialsExpireAt;
    /** @MongoDB\Hash */
    protected $mailProperties;
    /** @MongoDB\Hash */
    protected $settings;
    /** @mongoDB\Int */
    protected $involvement = 100;
    /** @mongoDB\String */
    protected $involvementExpireDate = '';

    /** 
     * @MongoDB\Id(strategy="auto") 
     */
    protected $id;

    /**
     * @MongoDB\ReferenceOne(
     *     targetDocument="Treto\PortalBundle\Document\Portal",
     *     mappedBy="userData",
     *     repositoryMethod="getForUser"
     * )
     */
    public $portalData;

    const ROBOT_PORTAL = 'portalrobot';
    const SITE_VISITOR = 'visitor';

    public function __construct() {
      parent::__construct();
    }

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }
    
    public function setSalt($salt) {
        $this->salt = $salt;
    }

    public function getEmail(){
        return $this->email;
    }

    public function getLastLogin()
    {
        return parent::getLastLogin();
    }

    public function setLastLogin(\DateTime $time = null)
    {
        return parent::setLastLogin($time);
    }

    /**
     * @return \Treto\PortalBundle\Document\Portal
     */
    public function getPortalData() {
        return $this->portalData;
    }
    
    public function setPortalData($portalData) {
        $this->portalData = $portalData;
    }
    
    public function getSecurity() {
        return ($pd = $this->getPortalData()) ? $pd->getSecurity() : ['privileges' => []];
    }
    
    public function getRoles() {
      $roles = [];
      if($this->getPortalData()) {
        $roles = $this->getPortalData()->getRole();
      }
      $roles[] = static::ROLE_DEFAULT;
      
      return $roles;
    }
    
    public function setRoles(array $roles) {
      if($this->getPortalData()) {
        $roles = $this->getPortalData()->setRole($roles);
      }
    }

    public function hasRole($r) {
      return in_array($r, $this->getRoles());
    }

    public function setMailProperties($properties){
      $this->mailProperties = $properties;
    }
    
    public function setSettings($v){
      $this->settings = $v;
    }
    public function getSettings(){
      return $this->settings;
    }

    /** Checks whether the User have permissions to do the specified $action to the specified $doc */
    public function can($action, SecureDocument $doc, $PMAccept = true) {
      if($PMAccept && $this->hasRole('PM')) {
        return true;
      }
      $rs = $this->getRoles();
      if($doc->hasPermission($action, $this->username, true, $rs)) {
        return true;
      }
      return false;
    }

    public function isEscalManager($task){
      $esMan = $task->GetEscalationManagers();
      if (!empty($esMan)){
        foreach ($esMan as $manager) {
          if ($manager['login'] == $this->username){
            return true;
          }
        }
      }
      return false;
    }
    
    public function mynameis($subject) {
      return $subject == $this->username
        || $subject == $this->getPortalData()->GetFullName(true)
        || $subject == $this->getPortalData()->GetFullName(false);
    }
    
    public function getNames($includeFullNameInRus = false) {
      $names = [$this->username,
        $this->getPortalData()->GetFullName(true),
        $this->getPortalData()->GetFullName(false)
      ];
      if($includeFullNameInRus) {
        $names[] = $this->getPortalData()->GetFullNameInRus();
      }
      return $names;
    }
    
    /**
    * Get document as array
    * @param bool $needPortalData
    * @param bool $needContactData
    * @param string $roles, you can pass User::getRoles() result
    * @return array
    */
    public function getDocument($needPortalData = true, $needContactData = false, $roles = []) {
        $document = (new \Treto\PortalBundle\Model\DocumentSerializer($this,$roles))->toArray(['portalData']);
        if($needPortalData && ($d = $this->getPortalData())) { 
            $document['portalData'] = $d->getDocument(false, $needContactData, $roles);
            $document['security'] = $d->getSecurity();
        } else {
            $document['portalData'] = null;
            $document['security'] = ['privileges' => []];
        }
        return $document;
    }
    
    /** Set document from array 
    * @param array $array representation of the document
    * @param \Treto\PortalBundle\Validator\Validator $validator
    * @param string $roles, you can pass User::getRoles() result
    * @return array of validation errors or empty array on success
    */
    public function setDocument(array $array, $validator = null, $roles = []) {
        (new \Treto\PortalBundle\Model\DocumentSerializer($this,$roles))->fromArray($array,['id','portalData']);
        $portalDataErrors = [];
        if(!empty($array['portalData']) && ($d = $this->getPortalData())) {
            $portalDataErrors = $d->setDocument($array['portalData'], $validator, false, $roles);
        }
        if($validator) {
            return $validator->validate($this) + $portalDataErrors;
        }
        return [];
    }

    public function getNewMailCount($params){
      $count = false;
      $access = $this->getAccess($params);
      if($access && count($access) == 1){
        foreach ($access as $acces) {
          $access = $acces;
        }

        $properties['login'] = $this->email;
        $properties['server'] = $params['server'];
        if(isset($access['mailAccess'])&&
          isset($access['mailAccess']['default'])&&
          isset($access['mailAccess']['default']['password'])){
          $properties['pass'] = $access['mailAccess']['default']['password'];
        }
      }

      if (isset($properties['server'])&&isset($properties['login'])&&isset($properties['pass'])){
          imap_timeout( IMAP_OPENTIMEOUT, 2 );
        try {
          $imap = @imap_open(
              $properties['server'],
              $properties['login'],
              $properties['pass'], 0, 10,
              array('DISABLE_AUTHENTICATOR' => 'CRAM-MD5')
          );
        } catch (Exception $e) {
          return $count;
        }
        if ($imap) {
          $info = imap_mailboxmsginfo($imap);
          $count = $info->Unread;
          imap_close($imap); 
        }
      }
      return $count;
    }

    /**
     * Get email access from bd
     * @param $params
     * @return bool|\MongoCursor
     */
    private function getAccess($params){
        $userMailAccess = false;
        if(
            isset($params['mdUsername'])&&$params['mdUsername']&&
            isset($params['mdPass'])&&$params['mdPass']&&
            isset($params['mdHost'])&&$params['mdHost']&&
            isset($params['mdPort'])&&$params['mdPort']
        ){
            $m = new \MongoClient("mongodb://".$params['mdUsername'].":".$params['mdPass']."@".$params['mdHost'].":".$params['mdPort']."/Treto");
            $tretodb = $m->selectDB('Treto');
            $collection = new \MongoCollection($tretodb, 'User');
            $userMailAccess = $collection->find(
                ['mailAccess' => ['$exists' => true],
                    'username' => $this->username],
                ['mailAccess' => 1, 'email' => 1]
            );
        }

        return $userMailAccess;
    }

    public function mailSearch($search_string){
      if (!$search_string) {
        $search_string = 'SINCE "'.date( "d-M-Y", strToTime ( "-2 days" ) ).'"';
      }
      $headers = [];
      $properties = $this->mailProperties;
      if ($properties['server']&&$properties['login']&&$properties['pass']){
        imap_timeout ( IMAP_OPENTIMEOUT, 2 );
        try {
          $imap = @imap_open($properties['server'], $properties['login'], $properties['pass'], 0, 10);
        } catch (Exception $e) {
          return $headers;
        }
        if ($imap) {
          $list = imap_list($imap, $properties['server'], "*");
          if (is_array($list)) {
            foreach ($list as $box) {
              if (imap_reopen($imap, $box)) {
                $msgNmbrs = imap_search($imap, $search_string, SE_UID, "UTF-7");
                if ($msgNmbrs) {
                  foreach ($msgNmbrs as $n) {
                    $info = imap_headerinfo($imap, $n);
                    array_push($headers, ['id' => $n.":".explode("}", $box)[1], 'date'=> strtotime($info->date), 'subject' => !empty($info->subject)?iconv_mime_decode($info->subject,0,'UTF-8'):"Без темы",
                      'to' => iconv_mime_decode($info->toaddress,0,'UTF-8'), 'from' => iconv_mime_decode($info->fromaddress,0,'UTF-8')]);
                  }
                }
              }
            }
          }
          imap_close($imap);
        }
      }
      return $headers;
    }

    public function getMailByIds($ids){
      global $charset,$htmlmsg,$plainmsg,$attachments,$headers;
      $mails = [];
      $properties = $this->mailProperties;
      if ($properties['server']&&$properties['login']&&$properties['pass']){
        imap_timeout( IMAP_OPENTIMEOUT, 2 );
        try {
          $imap = @imap_open($properties['server'], $properties['login'], $properties['pass'], 0, 10);
        } catch (Exception $e) {
          return $mails;
        }
        if ($imap) {
          foreach ($ids as $id) {
            if (imap_reopen($imap, split("}",$properties['server'])[0]."}".split(":", $id)[1])){
              $this->getmsg($imap, split(":", $id)[0]);
              if ($charset != 'UTF-8'){
                $htmlmsg = iconv($charset, 'UTF-8', $htmlmsg);
              }
              array_push($mails, ["charset"=>$charset, "htmlmsg"=>$htmlmsg, "plainmsg"=>$plainmsg, "headers"=>$headers, "date"=>$headers['date']]);
            }
          }
          imap_close($imap);
        }
      }
      return $mails;
    }

    protected function getmsg($mbox,$mid) {
      // input $mbox = IMAP stream, $mid = message id
      // output all the following:
      global $charset,$htmlmsg,$plainmsg,$attachments,$headers;
      $htmlmsg = $plainmsg = $charset = '';
      $attachments = $headers = array();

      // HEADER
      $h = imap_header($mbox,$mid);
      $headers = ['date'=> strtotime($h->date), 'subject' => !empty($h->subject)?iconv_mime_decode($h->subject,0,'UTF-8'):"Без темы",
                'to' => iconv_mime_decode($h->toaddress,0,'UTF-8'), 'from' => iconv_mime_decode($h->fromaddress,0,'UTF-8')];
      // add code here to get date, from, to, cc, subject...

      // BODY
      $s = imap_fetchstructure($mbox,$mid);
      if (empty($s->parts))  // simple
        $this->getpart($mbox,$mid,$s,0);  // pass 0 as part-number
      else {  // multipart: cycle through each part
        foreach ($s->parts as $partno0=>$p)
          $this->getpart($mbox,$mid,$p,$partno0+1);
      }
    }

    protected function getpart($mbox,$mid,$p,$partno) {
      // $partno = '1', '2', '2.1', '2.1.3', etc for multipart, 0 if simple
      global $htmlmsg,$plainmsg,$charset,$attachments;

      // DECODE DATA
      $data = (!empty($partno))?
        imap_fetchbody($mbox,$mid,$partno, FT_PEEK):  // multipart
        imap_body($mbox,$mid, FT_PEEK);  // simple
      // Any part may be encoded, even plain text messages, so check everything.
      if ($p->encoding==4)
        $data = quoted_printable_decode($data);
      elseif ($p->encoding==3)
        $data = base64_decode($data);

      // PARAMETERS
      // get all parameters, like charset, filenames of attachments, etc.
      $params = array();
      if (!empty($p->parameters))
        foreach ($p->parameters as $x)
          $params[strtolower($x->attribute)] = $x->value;
      if (!empty($p->dparameters))
        foreach ($p->dparameters as $x)
          $params[strtolower($x->attribute)] = $x->value;

      // ATTACHMENT
      // Any part with a filename is an attachment,
      // so an attached text file (type 0) is not mistaken as the message.
      if (!empty($params['filename']) || !empty($params['name'])) {
        // filename may be given as 'Filename' or 'Name' or both
        $filename = (!empty($params['filename']))? $params['filename'] : $params['name'];
        // filename may be encoded, so see imap_mime_header_decode()
        $attachments[$filename] = $data;  // this is a problem if two files have same name
      }

      // TEXT
      if ($p->type==0 && $data) {
        // Messages may be split in different parts because of inline attachments,
        // so append parts together with blank row.
        if (strtolower($p->subtype)=='plain')
          $plainmsg .= trim($data) ."\n\n";
        else
          $htmlmsg .= $data ."<br><br>";
        $charset = $params['charset'];  // assume all parts are same charset
      }

      // EMBEDDED MESSAGE
      // Many bounce notifications embed the original message as type 2,
      // but AOL uses type 1 (multipart), which is not handled here.
      // There are no PHP functions to parse embedded messages,
      // so this just appends the raw source to the main message.
      elseif ($p->type==2 && $data) {
        $plainmsg .= $data."\n\n";
      }

      // SUBPART RECURSION
      if (!empty($p->parts)) {
        foreach ($p->parts as $partno0=>$p2)
          $this->getpart($mbox,$mid,$p2,$partno.'.'.($partno0+1));  // 1.2, 1.2.1, etc.
      }
    }

    public function getEnabled(){
        return $this->enabled == 'true';
    }

    public function setDismissUser()
    {
        $this->setEnabled(false);
    }

    public function setUnDismissUser()
    {
        $this->getPortalData()->setDtDismiss('');
        $this->setEnabled(true);
    }
    
    public function fromArray(array $array, array $fieldsExclude = []) {
      $serializer = new \Treto\PortalBundle\Model\DocumentSerializer($this, $this ? $this->getRoles() : []);
      $serializer->fromArray($array, array_merge(['_id','id'], $fieldsExclude));
      return $serializer->getFieldsChanged();
    }

    public function GetInvolvement() {
      $today = new \DateTime();
      $todayIso = $today->format('Ymd');
      if ($todayIso > $this->involvementExpireDate) return 100;
      return $this->involvement;
    }

    public function SetInvolvement($involvement = 100) {
      if ($involvement < 0) $involvement = 0;
      if ($involvement > 100) $involvement = 100;
      $this->involvement = $involvement;
    }

    public function GetInvolvementExpireDate() {
      $today = new \DateTime();
      $todayIso = $today->format('Ymd');
      if ($todayIso > $this->involvementExpireDate) return '';
      return $this->involvementExpireDate;
    }

    public function SetInvolvementExpireDate($involvementExpireDate) {
      $today = new \DateTime();
      $todayIso = $today->format('Ymd');
      if ($todayIso > $this->involvementExpireDate) $this->involvementExpireDate = '';
      $this->involvementExpireDate = $involvementExpireDate;
    }
}
