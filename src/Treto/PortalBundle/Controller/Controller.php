<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Treto\PortalBundle\Document\HistoryLog;
use Treto\PortalBundle\Document\Portal;
use Treto\PortalBundle\Services\RoboService;

class Controller extends \Symfony\Bundle\FrameworkBundle\Controller\Controller
{
  protected $measurement = [];
  
  /** @return \Doctrine\ODM\MongoDB\DocumentRepository */
  public function getRepo($shortDocumentName) {
    $repo = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:'.$shortDocumentName);
    if($repo instanceof \Treto\PortalBundle\Document\SecureRepository) {
      $repo->releaseUser();
    }
    return $repo;
  }
  
  /** @return \Treto\PortalBundle\Document\SecureRepository */
  public function getSecureRepo($shortDocumentName) {
    $repo = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:'.$shortDocumentName);
    if($repo instanceof \Treto\PortalBundle\Document\SecureRepository) {
      $repo->setUser($this->getUser());
    }
    return $repo;
  }
  
  /** @return \Doctrine\ODM\MongoDB\Query\Builder */
  public function queryBuilderDM($shortDocumentName) {
    return $this->getDM()->createQueryBuilder('TretoPortalBundle:'.$shortDocumentName);
  }
  
  /** @return \Doctrine\ODM\MongoDB\DocumentManager */
  public function getDM() {
    return $this->get('doctrine.odm.mongodb.document_manager');
  }
  
  /** @return \FOS\UserBundle\Model\UserManagerInterface */
  public function getUM() {
    return $this->get('fos_user.user_manager');
  }
  
  /** @return \FOS\UserBundle\Util\UserManipulator */
  public function getUserManipulator() {
    return $this->get('fos_user.util.user_manipulator');
  }
  
  /** returns getUser()->getPortalData() */
  public function getUserPortalData() {
    return $this->getUser() ? $this->getUser()->getPortalData() : null;
  }
  
  public function newSecureDocument($shortDocumentName) {
    $name = 'Treto\\PortalBundle\\Document\\'.$shortDocumentName;;
    return new $name($this->getUser());
  }
  
  /** returns request GET or POST uri param */
  public function param($queryParam, $default = null) {
    return $this->getRequest()->get($queryParam,$default);
  }
  
  /** converts request content from json */
  public function fromJson() {
    return json_decode($this->getRequest()->getContent(),true);
  }
  
  public function success(array $additionalData = []) {
    return new JsonResponse(['success' => true] + $additionalData);
  }
  
  public function fail($errorOrErrors, array $additionalData = []) {
    if(is_array($errorOrErrors)) {
      return new JsonResponse(['success' => false, 'message' => implode("\n",$errorOrErrors), 'messages' => $errorOrErrors] + $additionalData);
    }
    return new JsonResponse(['success' => false, 'message' =>  $errorOrErrors] + $additionalData);
  }
  
  /** @deprecated USE SecureDocument::dt2iso instead ! */ 
  public function createIsoTimestamp() {
    return \Treto\PortalBundle\Document\SecureDocument::dt2iso(new \DateTime(), true);
  }
  
  public function isUnid($id) {
    return $id && (strlen($id) >= 32);
  }
  
  public function isGuid($id) {
    return $id && preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/', $id);
  }
  
  public function createUserFromAdaptation(\Treto\PortalBundle\Document\SecureDocument $adapt) {
    if(! ($adapt->GetLogin() && $adapt->GetFullName() && $adapt->GetFullNameInRus() && $adapt->GetPassword())) {
      return 'createUserFromAdaptation: not enough identification data';
    }
    $user = null;
    try {
      $manipulator = $this->getUserManipulator();
      $user = $manipulator->create($adapt->GetLogin(), $adapt->GetPassword(), $adapt->GetEmail(), true, false);
      if (file_exists ('public/img_site/Default.jpeg')){
        file_put_contents('public/img_site/thumb_'.$adapt->GetLogin().'.jpeg', file_get_contents('public/img_site/Default.jpeg'));
      }

      $host = $this->container->getParameter('imap_get_host');
      $ssl = $this->container->getParameter('imap_get_ssl');
      $port = $this->container->getParameter('imap_get_port');
      $ssl = $ssl?'ssl/':'';
      $server = '{'.$host.':'.$port.'/'.$ssl.'notls}';

      $user->setMailProperties(['server' => $server, 'login' => $adapt->GetLogin()]);
      $pu = new \Treto\PortalBundle\Document\Portal($user);
      $pu->fromArray($adapt->toArray(), ['security']);
      $pu->SetForm('Empl');
      $pu->SetModified();
      $pu->SetCreated();
      $pu->SetUnid();
      $pu->SetBoss($adapt->GetManager());
      $pu->SetBossLat($adapt->GetManager());//Иван Петрович Сидоров
      $pu->SetHeader('Employee');
      $this->getDM()->persist($pu);

    } catch(\Exception $e) {
      $es = (string)$e;
      if(strpos($es,'E11000') != false) {
        if((strpos($es,'username') != false) || (strpos($es,'Login') != false)) {
          return 'createUserFromAdaptation: username duplicate';
        } elseif(strpos($es,'FullName') != false) {
          return 'createUserFromAdaptation: fullname duplicate';
        } elseif(strpos($es,'mail') != false) {
          return 'createUserFromAdaptation: email duplicate';
        }
      }
      return 'createUserFromAdaptation: '.$e->getMessage();
    }
    return ['user' => $user, 'empl' => $pu];
  }
  
  public function measureStart($prefix = '_') {
    $this->measurement = ['prefix' => $prefix, ($prefix.'.initial') => microtime(true)];
  }
  
  public function measureMark($name) {
    $this->measurement[$this->measurement['prefix'].': '.$name]
      = microtime(true) - $this->measurement[$this->measurement['prefix'].'.initial'];
  }
  
  public function measureResult() {
    unset($this->measurement['prefix']);
    return $this->measurement;
  }

  public function getBosses($Empl){
    $robo = new \Treto\PortalBundle\Services\RoboJsonService($this->container);
    return $robo->getBosses($Empl);
  }

  public function sendProfile($username){
    $userToSend = $this->getUM()->findUserBy(['username' => $username]);
    if ($userToSend){
      $userToSend = $userToSend->getDocument(false, false, ['PM']);
      $repoPortal = $this->getRepo('Portal');

      $docToSend = $repoPortal->findOneBy(['form'=>'Empl', 'Login' => $username]);
      if ($docToSend) {
        $userToSend['portalData'] = [];
        $userToSend['portalData']['FirstName'] = $docToSend->GetName();
        $userToSend['portalData']['LastName'] = $docToSend->GetLastName();
        $userToSend['portalData']['MiddleName'] = $docToSend->GetMiddleName();
        $userToSend['portalData']['gender'] = $docToSend->GetSex();
        $userToSend['portalData']['position'] = $docToSend->GetWorkGroup();
        $userToSend['portalData']['status'] = $docToSend->GetToSite() == 1 ? 1 : 0;
        $userToSend['portalData']['fired'] = $docToSend->GetDtDismiss() ? 1 : 0;
        $userToSend['portalData']['lotus_name'] = $docToSend->GetFullName();
        $userToSend['portalData']['alias'] = $docToSend->GetFullNameInRus();
        $userToSend['portalData']['about'] = (count($docToSend->GetAbout()) > 0)?$docToSend->GetAbout()[0]:'';
      }else{
        $userToSend['portalData'] = false;
      }
      $userToSend['mailProperties'] = [];
      $cont = $this->getRepo('Contacts')->findOneBy(['FirstName' => $docToSend->GetName(), 
                                                     'LastName' => $docToSend->GetLastName(), 
                                                     'MiddleName' => $docToSend->GetMiddleName(),
                                                     'form' => 'Contact',
                                                     'DocumentType' => 'Person']);
      $userToSend['unid'] = $cont ? $cont->getUnid() : false;
      $siteService = $this->get('site.service');
      return $siteService->sendProfile([ 'document' => $userToSend ]);
    }
    return false;
  }
  
  public function getReadedTime($doc) {
    if (!isset($doc) || sizeof($doc->GetReadBy()) == 0) return false;
    $readBy = $doc->GetReadBy();
    $userLogin = $this->getUser()->getPortalData()->GetLogin();
    
    return isset($readBy[$userLogin]) ? $readBy[$userLogin] : false;
  }

  public function createHistoryLog($docid, $subject, $type) {
    $user = $this->getUser();
    $state = 'body.discus';

    if ($type === 'profile')
      $state = 'body.profileDisplay';
    else {
      if ($type == 'Contact') $subject = 'Контакт: '.$subject;
      $type = '';
    }

    $repHistory = $this->getRepo('HistoryLog');
    $log = $repHistory->findOneBy(['userId' => $user->getUserName(), 'label' => $subject]);
    if (!$log) $log = new HistoryLog();

    if (!$log->getId()) {
      $log->setDocument(['userId' => $user->getUserName(), 'label'=> $subject]);
    }

    $log->setDocument(['time' => new \DateTime(), 'stateParams' => ['id' => $docid, 'type' => $type], 'state' => $state]);

    $this->getDM()->persist($log);
    $this->getDM()->flush();
  }

  public function setReadByTime($t, $time = null, $isDocument = false, $isNotMain = false) {
    $robo = $this->get('service.site_robojson');
    return $robo->setReadByTime($t, $time, $isDocument, $isNotMain, $this->getUser()->getPortalData()->GetLogin());
  }
  
  // types "Блоги" and "Новости фабрик" to hardcode in AdminController getDictionaryAction
  public $arrSubscribeCategories = array(
    'ИТ отдел/Портал/News' => '64',
    'Биржа идей' => '38',
    'Блоги' => '2',
    'Новости' => '46',
    'Курилка' => '16',
    'Салон/Товароведение' => '72',
    'Вопросы и ответы' => '62',
    'Новости фабрик' => '1',
  );
  
  protected function getNormalCategory($doc) {
    $cat = null;
    if($doc instanceof \Treto\PortalBundle\Document\Contacts && $doc->GetGroup() === 'Фабрики' && $doc->GetDocumentType() === 'Organization')
    {
      $cat = 'Новости фабрик';
    } elseif ($doc instanceof \Treto\PortalBundle\Document\Portal && $doc->getType() === 'Blog')
    {
      $cat = 'Блоги';
    } elseif ($doc instanceof \Treto\PortalBundle\Document\Portal){
      $cat1 = $doc->getC1();
      $cat2 = $doc->getC2();
      $cat3 = $doc->getC3();
      $cat = $cat1;
      $cat .= $cat2 ? '/' . $cat2 : '';
      $cat .= $cat3 ? '/' . $cat3 : '';
    }
    return $cat;
  }

  private function addShareSubscribed($parent){
    /** @var Portal $parent */
    if($parent->getShareSecurity() && is_array($parent->getShareSecurity())){
      $domainList = [];
      $documentCategory = $this->getNormalCategory($parent);
      /** @var RoboService $robo */
      $robo = $this->get('service.site_robojson');
      foreach($parent->getShareSecurity() as $item){
        if(isset($item['domain'])){
          $domainList[] = $item['domain'];
        }
      }

      if($domainList){
        $shareUsers = $robo->getAllActiveShareUsers(false);
        foreach($shareUsers as $domain => $users){
          if(in_array($domain, $domainList)){
            foreach ($users as $user) {
              if(isset($user['Subscribe']) && in_array($this->arrSubscribeCategories[$documentCategory], $user['Subscribe'])){
                  $parent->addSharePrivileges($domain, 'read', 'username', $user['username']);
                  $parent->addSharePrivileges($domain, 'subscribed', 'username', $user['username']);
              }
            }
          }
        }
      }
    }
    return $parent;
  }

  public function addSubscribed($parent, $doc, $author = false) {
    $cat = $this->getNormalCategory($parent);
    $repo = $this->getRepo('Portal');
    $result = ['added' => 0];

    if($author){
      $docAuthor = $repo->findEmplByLogin($parent->GetAuthorLogin());
      if(!$parent->hasReadPrivilegeFor($parent->GetAuthorLogin(), true, $docAuthor->GetRole())){
        $parent->addReadPrivilege($parent->GetAuthorLogin(), $author);
      }
      if(!$parent->hasSubscribedPrivilegeFor($parent->GetAuthorLogin(), true, $docAuthor->GetRole())){
        $parent->addSubscribedPrivilege($parent->GetAuthorLogin(), $author);
      }
    }

    if(array_key_exists($cat,$this->arrSubscribeCategories)) {
      $parent = $this->addShareSubscribed($parent);
      $userPortalDatas = $repo->findBy([
          'form' => 'Empl',
          'Subscribe' => $this->arrSubscribeCategories[$cat],
          '$or' => [['DtDismiss'=>''], ['DtDismiss' => ['$exists'=>false]]]
      ]);

      $users = [];
      $notifLog = [];
      foreach ($userPortalDatas as $p) {
        if ($p->GetLogin() == $this->getUser()->getUsername() || !$parent->hasPermission('read', $p->GetLogin(), true, $p->GetRole())) {
          continue;
        } // dont add notif to myself or private discus
        
        $parent->addReadPrivilege($p->GetLogin(), $author);
        $parent->addSubscribedPrivilege($p->GetLogin(), $author);
        
        $users[] = $p->GetLogin();
      }
      
      $this->getDM()->persist($parent);
      
      $result['reallyAdded'] = $this->get('notif.service')->notifMultipleAdding(
          $parent,
          $doc,
          $users,
          0,
          __FUNCTION__.', '.__LINE__,
          'Added notif to'
      );
    }
    return $result;
  }
  
}
