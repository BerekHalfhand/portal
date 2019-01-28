<?php

namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Treto\UserBundle\Annotation\ExtendedPrivileges as Escalated;

/**
 * @MongoDB\Document(repositoryClass="MainStatRepository")
 */
class MainStat extends SecureDocument{

    /** @MongoDB\Id(strategy="auto") */
    protected $_id;

    /** @MongoDB\String */
    protected $type;

    /** @MongoDB\String */
    protected $created;

    /** @MongoDB\String */
    protected $modified;

    /** @MongoDB\String */
    protected $timestamp;

    /*
    * ==================== type: "LiveList" ====================
    */

    /** @MongoDB\String */
    protected $subType;

    /** @MongoDB\Boolean */
    protected $updateNeeded;

    /** @MongoDB\Int */
    protected $daysCount;

    /** @MongoDB\Int */
    protected $limit;

    /** @MongoDB\String */
    protected $forUser;

    /** @MongoDB\Hash */
    protected $LiveList;


    public function __construct() {
        $this->SetCreated();
        $this->SetModified();
    }
    
    public function GetId() {return $this->_id;}
    public function SetId($_id) {$this->_id = $_id;}
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
    public function GetType() {return $this->type;}
    public function SetType($type) {$this->type = $type;}
    public function GetTimestamp() {return $this->timestamp;}
    public function SetTimestamp($timestamp) {$this->timestamp = $timestamp;}


    public function GetSubType() {return $this->subType;}
    public function SetSubType($subType) {$this->subType = $subType;}
    public function GetUpdateNeeded() {return $this->updateNeeded;}
    public function SetUpdateNeeded($updateNeeded = true) {$this->updateNeeded = $updateNeeded;}
    public function GetDaysCount() {return $this->daysCount;}
    public function SetDaysCount($daysCount) {$this->daysCount = $daysCount;}
    public function GetLimit() {return $this->limit;}
    public function SetLimit($limit) {$this->limit = $limit;}
    public function GetForUser() {return $this->forUser;}
    public function SetForUser($forUser) {$this->forUser = $forUser;}
    public function GetLiveList() {return $this->LiveList;}
    public function SetLiveList($LiveList = []) {$this->LiveList = $LiveList;}

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

    

    /*
    * ==================== type: "LiveList", subType in ["readWriteLog", "clickLog"] ====================
    */

    // Function removes old entries that are out of date
    public function Sanitize() {
        if ($this->GetType() !== 'LiveList' || $this->GetSubType() !== 'readWriteLog') return $this;

        $expireDate = new \DateTime();
        $expireDate->sub(new \DateInterval("P{$this->GetDaysCount()}D"));
        $expireDate = $expireDate->format('Ymd').'T'.$expireDate->format('His');

        if ($this->GetTimestamp() > $expireDate) return $this;

        $newExpireDate = null;
        $list = $this->GetLiveList();
        foreach ($list as $user => $userVal) {
            foreach ($list[$user] as $unid => $unidVal) {
                foreach ($list[$user][$unid] as $action => $actionVal) {
                    foreach ($list[$user][$unid][$action] as $time => $timeVal) {
                        if ($timeVal < $expireDate) {
                            unset($list[$user][$unid][$action][$time]);
                        } elseif (!$newExpireDate || $newExpireDate > $timeVal) {
                            $newExpireDate = $timeVal;
                        }
                    }
                    $list[$user][$unid][$action] = array_values($list[$user][$unid][$action]);
                    if ( sizeof($list[$user][$unid][$action]) === 0 ) {
                        unset($list[$user][$unid][$action]);
                    }
                }
                if ( sizeof($list[$user][$unid]) === 0 ) {
                    unset($list[$user][$unid]);
                }
            }
            if ( sizeof($list[$user]) === 0 ) {
                unset($list[$user]);
            }
        }

        if (!$newExpireDate) {
            $newExpireDate = new \DateTime();
            $newExpireDate = $newExpireDate->format('Ymd').'T'.$newExpireDate->format('His');
        }

        $this->SetLiveList($list);
        $this->SetTimestamp($newExpireDate);

        return $this;
    }

    public function LogRead($user, $unid) {
        if ($this->GetType() !== 'LiveList' || $this->GetSubType() !== 'readWriteLog') return $this;

        $log = $this->GetLiveList();
        if ( !isset($log[$user]) ) {
            $log[$user] = [];
        }
        if ( !isset($log[$user][$unid]) ) {
            $log[$user][$unid] = ['read' => []];
        } elseif ( !isset($log[$user][$unid]['read']) ) {
            $log[$user][$unid]['read'] = [];
        }
        $log[$user][$unid]['read'][] = static::dt2iso(new \DateTime(), true);
        $this->SetLiveList($log);

        return $this;
    }

    public function LogWrite($user, $unid) {
        if ($this->GetType() !== 'LiveList' || $this->GetSubType() !== 'readWriteLog') return $this;

        $log = $this->GetLiveList();
        if ( !isset($log[$user]) ) {
            $log[$user] = [];
        }
        if ( !isset($log[$user][$unid]) ) {
            $log[$user][$unid] = ['write' => []];
        } elseif ( !isset($log[$user][$unid]['write']) ) {
            $log[$user][$unid]['write'] = [];
        }
        $log[$user][$unid]['write'][] = static::dt2iso(new \DateTime(), true);
        $this->SetLiveList($log);

        return $this;
    }

    public function LogClick($label) {
        if ($this->GetType() !== 'LiveList' || $this->GetSubType() !== 'clickLog') return $this;

        $log = $this->GetLiveList();
        if ( !isset($log[$label]) ) $log[$label] = [];
        $log[$label][] = static::dt2iso(new \DateTime(), true);
        $this->SetLiveList($log);

        return $this;
    }

    public function GetReadWriteLog() {
        if ($this->GetType() !== 'LiveList' || $this->GetSubType() !== 'readWriteLog') return false;
        return $this->GetLiveList();
    }

    public function GetReadWriteLogForUser($user) {
        if ($this->GetType() !== 'LiveList' || $this->GetSubType() !== 'readWriteLog') return false;

        $log = $this->GetLiveList();
        if (isset($log[$user])) return $log[$user];

        return [];
    }

    public function GetClickLog() {
        if ($this->GetType() !== 'LiveList' || $this->GetSubType() !== 'clickLog') return false;
        return $this->GetLiveList();
    }

}

?>
