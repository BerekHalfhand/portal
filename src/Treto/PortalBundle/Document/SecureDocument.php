<?php
namespace Treto\PortalBundle\Document;

abstract class SecureDocument {
  
  protected $security;
  protected $shareSecurity;
  protected $user;
    
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
  
  public function addSecurity($security) {
    $this->_repairSecurity();
    foreach($security['privileges'] as $action => $privs) {
      foreach($privs as $priv) {
        $subject = reset($priv);
        $this->addActionPrivilege($action, key($priv), $subject);
      }
    }
  }
    
  public function setUser($user) { $this->user = $user; }
  public function getUser() { return $this->user; }
  
  public function setDefaultSecurity($user = null) {
    $this->user = $user;
    $this->security = [
      'privileges' => [
        'read' => [
          ['role' => 'all']
        ]
      ],
      'log' => [
        'read' => [
          'role' => [],
          'username' => [],
        ]
      ],
    ];
    $this->setDefaultWriteSecurity($user);
  }
  
  public function setDefaultWriteSecurity($user = null) {
    $this->security['privileges']['write'][] = ['role' => 'PM'];
    if($user) {
      $this->security['privileges']['write'][] = ['username' => $user->getUsername()];
    }
  }
  
  protected function _repairSecurity() {
    if($this->security === null) {
      $this->security = [];
    }
    if(!isset($this->security['privileges'])) {
      $this->security['privileges'] = [];
    }
    if(!isset($this->security['log'])) {
      $this->security['log'] = [];
    }
  }
  
  public function addLog($action, $type, $subject, $author = null) {
    if (!isset($this->security['log'])){
      $this->security['log'] = [];
    }

    $this->security['log'][$action] = isset($this->security['log'][$action]) ? $this->security['log'][$action]:[];
    $this->security['log'][$action][$type] = isset($this->security['log'][$action][$type]) ? $this->security['log'][$action][$type] : [];
    $this->security['log'][$action][$type][$subject] = isset($this->security['log'][$action][$type][$subject]) ? $this->security['log'][$action][$type][$subject] : [];
    $this->security['log'][$action][$type][$subject][] = ['added' => $this->dt2iso(new \DateTime(), true), 'by' => $author];
  }
  
  public function addActionPrivilege($action, $type, $subject, $author = null) {
    $ap = $this->getActionPrivileges($action);
    if(! $ap) {
      $this->_repairSecurity();
      $this->security['privileges'][$action] = [];
    }
    if(!$this->hasPermission($action, $subject)) {
      $this->security['privileges'][$action] = array_values($this->security['privileges'][$action]);
      $this->security['privileges'][$action][] = [$type => $subject];
      $this->addLog($action, $type, $subject, $author);
      if (substr($action, 0, 2) != 'un')
        $this->removeActionPrivilege('un'.$action, $type, $subject, $author);
    }
  }
  
  public function removeActionPrivilege($action, $type, $subject, $author = null, $noUnread = false){
    if (isset($this->security['privileges'][$action])) {
      foreach ($this->security['privileges'][$action] as $key => $object){
        if(isset($object[$type]) && $object[$type] == $subject){
          array_splice($this->security['privileges'][$action], $key, 1);
          if (substr($action, 0, 2) != 'un' && !$noUnread){
            $this->addActionPrivilege('un'.$action, $type, $subject, $author);
          }
        }
      }
    }
  }

  public function addReadPrivilege($username, $author = null) {
    $this->addActionPrivilege('read', 'username', $username, $author);
  }
  
  public function addSubscribedPrivilege($username, $author = null) {
    $this->addActionPrivilege('subscribed', 'username', $username, $author);
  }
  
  public function addWritePrivilege($username, $author = null) {
    $this->addActionPrivilege('write', 'username', $username, $author);
  }
  
  public function removeReadPrivilege($username, $author = null) {
    $this->removeActionPrivilege('read', 'username', $username, $author);
  }
  
  public function removeSubscribedPrivilege($username, $author = null) {
    $this->removeActionPrivilege('subscribed', 'username', $username, $author);
  }
  
  public function removeWritePrivilege($username, $author = null) {
    $this->removeActionPrivilege('write', 'username', $username, $author);
  }
  
  public function addActionPrivileges($action, $usernames, $author = null) {
    if (is_array($usernames) && sizeof($usernames)>0) {
      foreach($usernames as $username) {
        if (strpos($username, '.') === false) //safeguard against ancient curses
          $this->addActionPrivilege($action, 'username', $username, $author);
      }
    }
  }
  
  public function getPrivileges() {
    if(empty($this->security['privileges'])) {
      return [];
    }
    return $this->security['privileges'];    
  }
  
  public function getReadPrivileges() {
    return $this->getActionPrivileges('read');
  }
  
  public function getVotePrivileges() {
    return $this->getActionPrivileges('vote');
  }
  
  public function getSubscribedPrivileges() {
    return $this->getActionPrivileges('subscribed');
  }
  
  public function getWritePrivileges() {
    return $this->getActionPrivileges('write');
  }
  
  public function getActionPrivileges($action) {
    $r = $this->getPrivileges();
    if(!isset($r[$action]) || !$r[$action]) {
      return [];
    }
    return $r[$action];
  }
  
  public function getPermissionsByType($action) { //just array of logins
    $privs = $this->getActionPrivileges($action);
    $result = ['role'=>[], 'username'=>[]];
    foreach($privs as $p) {
      if(is_array($p)) {
        if(isset($p['role'])) {
          $result['role'][] = $p['role'];
        }
        if(isset($p['username'])) {
          $result['username'][] = $p['username'];
        }
      }
    }
    return $result;
  }
  
  /** if $subject has no read privs it checks for write privileges. In other words, $subject cannot write without reading */
  public function hasReadPrivilegeFor($subject, $checkRole = false, $userRoles = false) {
    return $this->hasPermission('read', $subject, $checkRole, $userRoles);
  }
  
  public function hasSubscribedPrivilegeFor($subject, $checkRole = false, $userRoles = false) {
    return $this->hasPermission('subscribed', $subject, $checkRole, $userRoles);
  }
  
  public function hasWritePrivilegeFor($subject, $checkRole = false, $userRoles = false) {
    return $this->hasPermission('write', $subject, $checkRole, $userRoles);
  }
  
  public function hasPermission($action, $param, $checkRole = false, $userRoles = false) {
    $privs = $this->getActionPrivileges($action);

    if($privs && is_array($privs)) {
      foreach($privs as $p) {
        if(is_array($p)) {
          if(isset($p['username']) && $p['username'] == $param) { return true; }
          if($checkRole && isset($p['role'])) {
            if($p['role'] == $param) { return true; }
            if($userRoles && is_array($userRoles)) {
              foreach ($userRoles as $userRole) if ($userRole == $p['role']) return true;
            }
          }
        }
      }
    }
    return false;
  }

  public function hasSharePrivileges($domain, $action, $type, $subject){
    $domain = strpos($domain, '.') !== false?str_replace('.', '', $domain):$domain;

    $result = false;
    if($this->shareSecurity && isset($this->shareSecurity[$domain]) && isset($this->shareSecurity[$domain]['privileges'])){
      $privileges = $this->shareSecurity[$domain]['privileges'];
      if(isset($privileges[$action])){
        foreach($privileges[$action] as $prv){
          if(isset($prv[$type]) && $prv[$type] == $subject){
            $result = true;
          }
        }
      }
    }

    return $result;
  }

  public function addSharePrivileges($domain, $action, $type, $subject){
    $fullDomain = $domain;
    $domain = strpos($domain, '.') !== false?str_replace('.', '', $domain):$domain;

    if(!$this->hasSharePrivileges($domain, $action, $type, $subject)){
      if(!isset($this->shareSecurity[$domain])){
        $this->shareSecurity[$domain] = ['privileges' => [], 'domain' => $fullDomain];
      }
      if(!isset($this->shareSecurity[$domain]['privileges'][$action])){
        $this->shareSecurity[$domain]['privileges'][$action] = [];
      }

      if($action == 'read'){
          if($this->hasReadPrivilegeFor('all', true) && !$this->hasSharePrivileges($domain, 'read', 'role', 'all')){
              $this->shareSecurity[$domain]['privileges'][$action][] = ['role' => 'all'];
          }
          elseif(!$this->hasReadPrivilegeFor('all', true) && $this->hasSharePrivileges($domain, 'read', 'role', 'all')){
              $this->removeSharePrivileges($domain, 'read', 'role', 'all');
          }

      }

      $this->shareSecurity[$domain]['privileges'][$action][] = [$type => $subject];
    }
  }

  public function removeSharePrivileges($domain, $action, $type, $subject){
      if($this->hasSharePrivileges($domain, $action, $type, $subject)){
          foreach ($this->shareSecurity[$domain]['privileges'][$action] as $key => $privilege) {
              if(isset($this->shareSecurity[$domain]['privileges'][$action][$key][$type]) &&
              $this->shareSecurity[$domain]['privileges'][$action][$key][$type] == $subject){
                  array_splice($this->shareSecurity[$domain]['privileges'][$action], $key, 1);
              }
          }
      }
  }
  
  public function toArray() {
    $serializer = new \Treto\PortalBundle\Model\DocumentSerializer($this, $this->user ? $this->user->getRoles() : []);
    return $serializer->toArray();
  }
  
  /** returns array of changed fields (false if the src and dst values are identical) */
  public function fromArray(array $array, array $fieldsExclude = []) {
    $serializer = new \Treto\PortalBundle\Model\DocumentSerializer($this, $this->user ? $this->user->getRoles() : []);
    $serializer->fromArray($array, array_merge(['_id','id'], $fieldsExclude));
    return $serializer->getFieldsChanged();
  }
  
  /** php datetime to iso8601 string
    @param $dt source php DateTime
    @param $includeTime tells to add time values to destination iso string */
  public static function dt2iso(\DateTime $dt, $includeTime = false) {
    if($includeTime) {
      return $dt->format('Ymd').'T'.$dt->format('His');
    }
    return $dt->format('Ymd');
  }
  
  /** @param $iso source iso string, for ex. '20110311T192215' or '20110311'
   *  @param $copyTime - copies time if exists,
   *  @param $forceTime - adds time even if it doesnt exist in source */
  public static function iso2iso($iso, $copyTime = true, $forceTime = false) {
    $len = strlen($iso);
    $dstLen = ($copyTime && ($len >= 15 || $forceTime)) ? 15 : 8;
    $iso = substr($iso, 0, $dstLen);
    if($len != $dstLen) { // normalize dst string requested length
      for($i = $len; $i < $dstLen; ++$i) {
        $iso .= (($i == 8) ? 'T' : '0'); 
      }
    }
    return $iso;
  }
}
