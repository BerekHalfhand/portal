<?php

namespace Treto\UserBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Treto\PortalBundle\Document\Contacts;
use Treto\PortalBundle\Document\Portal;
use Treto\PortalBundle\Services\RoboService;
use Treto\UserBundle\Document\User;
use Treto\UserBundle\Document\Token;
use Treto\PortalBundle\Document\PreviousVersions;
use Treto\PortalBundle\Services\RoboJsonService;

class ProfileController extends Controller
{
    use \Treto\PortalBundle\Services\StaticLogger;

    private $monthWps = [];
    private $profiles = [];

    public function getAction()
    {
        $id = $this->getRequest()->get('id');
        $isFullname = false;
        $isLdap = false;
        $isLogin = true;
        if((strlen($id) > 20) || !preg_match('/^[a-zA-Z0-9\_]+$/', $id)) {
          $isLogin = false;
          if(!preg_match('/^[a-f0-9]+$/', $id)) {
              if(preg_match('/^[A-Za-z0-9 ]+$/', $id)) {
                $isFullname = true;
              } elseif(preg_match('/^CN\=[A-Za-z0-9 ]+\/O\=skvirel$/', $id)) {
                $isLdap = true;
              } else {
                return new JsonResponse(['success' => false, 'message' => 'wrong user id']);
              }
          }
        }
        
        $user = null;
        if($isFullname || $isLdap) {
          $fullname = $isLdap ? $id : 'CN='.$id.'/O=skvirel';
          $portal_rep = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
          $portal = $portal_rep->findOneBy(['FullName' => $fullname, 'form' => 'Empl']);
          if(! $portal) {
            return new JsonResponse(['success' => false, 'message' => 'user not found by fullname']);
          }
          $user = $this->get('doctrine_mongodb')->getRepository('TretoUserBundle:User')->getForPortal($portal);
        } else if($isLogin) {
          $userManager = $this->get('fos_user.user_manager');
          $user = $userManager->findUserBy(['username' => $id]);
        } else {
          $userManager = $this->get('fos_user.user_manager');
          $user = $userManager->findUserBy(['id' => $id]);
        }
        if(! $user) {
            return new JsonResponse(['success' => false, 'message' => 'user not found']);
        }
        
        $udoc = $user->getDocument( $this->getRequest()->get('needPortalData',true), 
          $this->getRequest()->get('needContactData',true), 
          $this->getUser()->getRoles());
        $udoc['portalData']['bosses'] = $this->getBosses($user->getPortalData());
        $udoc['involvement'] = $user->GetInvolvement();
        $udoc['involvementExpireDate'] = $user->GetInvolvementExpireDate();
        $re = $this->log(__CLASS__, __METHOD__, '#/profile/edit/'.$id, 'профиль '. $udoc['portalData']['FullNameInRus']);

        return new JsonResponse(['success' => true, 'user' => $udoc]);
    }
    
    public function dismount($object) {
      $reflectionClass = new \ReflectionClass(get_class($object));
      $array = array();
      foreach ($reflectionClass->getProperties() as $property) {
        $property->setAccessible(true);
        $array[$property->getName()] = $property->getValue($object);
        $property->setAccessible(false);
      }
      return $array;
    }

    public function setSettingsAction(){
      $dm = $this->getDM();
      $data = $this->fromJson();
      /** @var User $user */
      $user = $this->getUser();

      if($user){
        $userSettings = $user->getPortalData()->GetUserSettings();
        if(!$userSettings){
          $userSettings = [];
        }

        if($data && isset($data['data']) && $data['data']){
          $data = $data['data'];
          foreach ($data as $fieldName => $value) {
            $userSettings[$fieldName] = $value;
          }

          $user->getPortalData()->SetUserSettings($userSettings);

          $dm->persist($user->getPortalData());
          $dm->flush();
        }

        return $this->success();
      }
      else {
        return $this->fail('Not auth.');
      }
    }

    public function setAction(){
        $dm = $this->getDM();
        $contactRepo = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
        $versRepo = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:PreviousVersions');
        $data = json_decode($this->getRequest()->getContent(), true);
        if(empty($data['user'])) {
            return new JsonResponse(['success' => false, 'message' => 'wrong user data']);
        }
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->getUM();
        /** @var $user \Treto\UserBundle\Document\User */
        $user = $userManager->findUserBy(['id' => $data['user']['id']]);
        if(!$user) {
            return new JsonResponse(['success' => false, 'message' => 'user not found']);
        }
        $oldPortalData = clone $user->getPortalData();
        
        if(isset($data['user']['new_password'])) {
          $user->setPlainPassword($data['user']['new_password']);
          /** @var \Treto\PortalBundle\Services\RoboService $robo */
          $robo = $this->get('service.site_robojson');
          $params = '\''.$this->getUser()->getPortalData()->GetLogin().'@$#%synchPassword%&$@'.$data['user']['new_password'].'\'';
          $robo->runCommand('synchronize', ['password', $params], 'commands_'.date('d-m-Y'));
          // $hipchatService = $this->get('hipchat.service');
          // $hipchatService->updateUser($data['user']['portalData']['LastName']." ".$data['user']['portalData']['name'], $data['user']['email'], $data['user']['usernameCanonical'], $data['user']['new_password']);
        }
        $isChangeDismiss = $this->isChangeDismiss($user, $data);
        //notify subscribed
        
        $fieldsChanged = $user->getPortalData()->fromArray($data['user']['portalData'], []);
        if (sizeof($fieldsChanged) >= 1) {
          $portalData = $user->getPortalData();
          
          $docHist = new PreviousVersions($oldPortalData->GetUnid(), 'Portal', $this->getUser()->getPortalData()->GetLogin(), $oldPortalData->toArray());
          $dm->persist($docHist);
          $dm->flush();
          
          $ps = $portalData->getPermissionsByType('subscribed');
          
          $changedData = array();
          $portalDataArr = $this->dismount($portalData);
          
          $fieldsChangedKeys = array_keys($fieldsChanged);
//           file_put_contents('1.txt', print_r($portalDataArr, true));
          foreach($fieldsChangedKeys as $field) {
            $changedData[$field] = $portalDataArr[$field];
          }
        
          $this->get('notif.service')->notifMultipleAdding($portalData,
                                                           $portalData,
                                                           $ps['username'],
                                                           0,
                                                           __FUNCTION__.', '.__LINE__,
                                                           'Added notif to',
                                                           null,
                                                           $changedData);
        }

        $errors = $user->setDocument($data['user'], $this->get('treto.validator'), $this->getUser()->getRoles());
        $result = [];

        $pd = $data['user']['portalData'];
        $contactUnid = isset($pd['contactUnid'])?$pd['contactUnid']:'';
        $workGroup = isset($pd['WorkGroup'])?$pd['WorkGroup']:[];
        $section = isset($pd['section'])?$pd['section']:[];

        if($contactUnid){
            /** @var $contact Contacts */
            $contact = $contactRepo->findOneBy(['unid' => $contactUnid]);
        }

        if($isChangeDismiss) {
            if (isset($data['user']['portalData']['DtDismiss']) &&
                $data['user']['portalData']['DtDismiss'] !== '' &&
                strtotime($data['user']['portalData']['DtDismiss']) < time()) {
                $robo = new \Treto\PortalBundle\Services\RoboJsonService($this->container);
                $response = $robo->dismissUser($user, $result);
                $user = $response['user'];
                $result = $response['result'];
                if(isset($contact) && $contact){
                    $contact->SetDismissed('1');
                }
            } else {
                $user->setUnDismissUser();
                if(isset($contact) && $contact){
                    $contact->SetDismissed('0');
                }
            }
            $nodeService = $this->get('node.service');
            $result['chatrefresh'] = $nodeService->refreshUsers();
        }

        if(isset($contact) && $contact){
            $contact->SetSection(is_array($section)?$section:[$section]);
            $contact->SetRank(is_array($workGroup)?$workGroup:[$workGroup]);
            $contact->SetC1WaitSync(true);
            $dm->persist($contact);
            $dm->flush();
        }

        $userManager->updateUser($user);

        if (isset($data['user']['portalData']['attachments']) &&
        count($data['user']['portalData']['attachments']) > 0 &&
        isset($data['user']['portalData']['attachments'][0][0]['doc']['hash']) &&
        file_exists(KERNEL_DIR.'/upload/'.$data['user']['portalData']['attachments'][0][0]['doc']['hash'])){
            $environment = $this->container->getParameter("kernel.environment");
            $filePath = "public/img_site/$environment";
            if(!file_exists($filePath)){
                mkdir($filePath, 0777);
            }

            file_put_contents("$filePath/b_".$data['user']['username'].'.jpeg', file_get_contents(KERNEL_DIR.'/upload/'.$data['user']['portalData']['attachments'][0][0]['doc']['hash']));

            $file = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Files')->findOneBy(['hash'=>$data['user']['portalData']['attachments'][0][0]['doc']['hash']]);
            $output = $file->getThumbnailData(180,180);

            file_put_contents("$filePath/thumb_".$data['user']['username'].'.jpeg', $output);
        }
        if(!count($errors)){
            /** @var $nodeService \Treto\PortalBundle\Services\NodeService  */
            $nodeService = $this->get('node.service');
            $nodeService->refreshUsers();
        }

        return new JsonResponse([
            'success' => !((bool) count($errors)), 
            'message' => reset($errors), 
            'messages' => $errors,
            'result' => $result
        ]);
    }

    public function saveInvolvementAction() {
      $data = json_decode($this->getRequest()->getContent(), true);

      $um = $this->getDM();
      $user = $this->getUser();
      if (isset($data['involvement'])) $user->SetInvolvement($data['involvement']);
      if (isset($data['involvementExpireDate'])) {
        if ($data['involvementExpireDate'] === '') $user->SetInvolvementExpireDate('');
        else {
          $d = new \DateTime($data['involvementExpireDate']);
          $user->SetInvolvementExpireDate($d->format('Ymd'));
        }
      }
      $um->persist($user);
      $um->flush();

      return $this->success(['involvement' => $user->GetInvolvement(), 'involvementExpireDate' => $user->GetInvolvementExpireDate()]);
    }
    
    public function saveSettingsAction() {
      $data = json_decode($this->getRequest()->getContent(), true);
      $settings = isset($data['settings']) ? $data['settings'] : [];
      
      if(empty($settings)) {
        return new JsonResponse(['success' => false, 'message' => 'wrong settings']);
      }
      $dm = $this->getDM();
      $user = $this->getUser();
      $user->setSettings($settings);
      $dm->persist($user);
      $dm->flush();
      
      return $this->success(['user' => $user->getPortalData()->GetLogin()]);
    }
    
    public function saveSecurityAction() {
      $data = $this->fromJson();
      if(!$data['unid'] || !$data['security']) { return $this->fail('wrong input'); }
      $repo = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
      $unid = $data['unid'];
      $security = $data['security'];
      
      $doc = $repo->findOneBy(['unid' => $unid]);
      if(!$doc) { return $this->fail('document not found'); }
      
      if(empty($security) || !isset($security['privileges'])) {
        return $this->fail('document must contain security.privileges');
      }

      $doc->setSecurity($security);
      $this->getDM()->persist($doc);
      $this->getDM()->flush($doc);
      return $this->success();
    }

    public function listAction() {
      return $this->success(['users' => $this->getAllUsers()]);
    }
    
    public function getPortalEmplsAction(){
        $data = $this->fromJson();
        /** @var RoboService $robo */
        $robo = $robo = $this->get('service.site_robojson');
        if(!isset($data['hash']) || !$robo->checkHash($data['hash'])){
            return $this->fail('Invalid hash param.');
        }
        $users = $this->getAllUsers();
        $result = [];
        foreach ($users as $item) {
            $result[] = [
                'username' => $item['username'],
                'LastName' => $item['portalData']['LastName'],
                'MiddleName' => $item['portalData']['MiddleName'],
                'name' => $item['portalData']['name'],
                'WorkGroup' => $item['portalData']['WorkGroup'],
                'section' => $item['portalData']['section'],
                'Subscribe' => $item['portalData']['Subscribe']
            ];
        }

        return $this->success(['users' => $result, 'environment' => $this->container->getParameter("kernel.environment")]);
    }

    private function getAllUsers(){
        $profiles = $this->getProfiles();
        $result = [];
        $fullnames = $this->getWpByMonth(); //empty params is current month

        foreach($profiles as $p) {
            /** @var $p \Treto\PortalBundle\Document\Portal */
            if(!isset($fullnames[$p->GetFullName()])){
                if(in_array(date('w'), [0,6])){
                    $day = 'в';
                }else{
                    $day = 'р';
                }
            }else{
                $day = $fullnames[$p->GetFullName()]->getDaysData()[date('j')-1];
                if(is_array($day)){
                    $day['terms'] = $this->getStatusTerms($p->GetFullName());
                }
            }
            $userManager = $this->get('fos_user.user_manager');
            $user = $userManager->findUserBy(['username' => $p->getLogin()]);
            $involvement = ($user != null) ? $user->GetInvolvement() : null;
            $involvementExpireDate = ($user != null) ? $user->GetInvolvementExpireDate() : '';
            $result[] = ['id' => $p->getId(), 'username' => $p->getLogin(), 'portalData' => $p->getDocument(), 'wp'=> $day, 'involvement' => $involvement, 'involvementExpireDate' => $involvementExpireDate ];
        }

        return $result;
    }

    /**
     * Get terms of the end holiday by fullname
     * @param $fullName
     * @return bool|string
     */
    private function getStatusTerms($fullName){
        $currentStatus = $this->getStatusByDate($fullName, time());
        $nextStatus = $currentStatus;
        $i = 0;
        while($nextStatus == $currentStatus){
            $i++;
            $nextStatus = $this->getStatusByDate($fullName, strtotime("+".$i." day", time()));
        }

        return date('d.m.Y', strtotime("+".$i." day", time()));
    }

    /**
     * Get work-status by date and fullName
     * @param $fullName
     * @param $time
     * @return mixed
     */
    private function getStatusByDate($fullName, $time){
        $year = date('Y', $time);
        $month = date('m', $time);
        $day = date('j', $time);

        $wps = $this->getWpByMonth($year, $month);
        if($wps && isset($wps[$fullName])){
            $currentKeyDay = $day-1; // -1 == arr key
            $day = $wps[$fullName]->getDaysData()[$currentKeyDay];
        }
        else {
            $day = in_array(date('w', $time), [0,6])?'в':'р';
        }
        return is_array($day)?$day['type']:$day;
    }

    /**
     * Get work schedule by date (year and month)
     * @param bool $year
     * @param bool $month
     * @return mixed
     */
    private function getWpByMonth($year = false, $month = false){
        $fullnames = [];
        $profiles = $this->getProfiles();
        foreach($profiles as $p) {
            /** @var $p \Treto\PortalBundle\Document\Portal */
            $fullnames[$p->GetFullName()] = true;
        }
        $year = $year?$year:date('Y');
        $month = $month?$month:date('m');
        if(!isset($this->monthWps[$month.$year])){
            $wps = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal')
                ->findBy(['form'=>'WorkPlan','Year'=>$year, 'Month'=>$month, 'FullName'=>['$in' => array_keys($fullnames)]]);
            foreach($wps as $w) {
                /** @var $w \Treto\PortalBundle\Document\Portal */
                $this->monthWps[$month.$year][$w->GetFullName()] = $w;
            }
        }
        return isset($this->monthWps[$month.$year])?$this->monthWps[$month.$year]:[];
    }

    /**
     * Get users profiles
     * @return array
     */
    private function getProfiles(){
        if(!$this->profiles){
            $this->profiles = $this->get('doctrine_mongodb')
                ->getRepository('TretoPortalBundle:Portal')
                ->findBy([
                    'form'=>'Empl',
                    '$or' => [
                        ['DtDismiss'=>''],
                        ['DtDismiss'=> ['$exists'=>false]]
                    ]
                ]);
        }
        return $this->profiles;
    }

    public function listbysectionAction() {
      $section = $this->param('section', null);
      $name = $this->param('name', null);

      $searchArr = [ 'form'=>'Empl','$or' => [ ['DtDismiss'=>''],['DtDismiss'=>['$exists'=>false]] ] ];
      if ($section){
        $searchArr['section'] = $section;
      }
      if($name){
        $searchArr['$and'][] = ['FullNameInRus' => ['$regex' => new \MongoRegex("/$name/i")]];
      }
      $profiles = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal')->findBy($searchArr);
      $result = [];
      foreach($profiles as $p) {
        $result[] = $p->getDocument();
      }
      return $this->success(['users' => $result]);
    }
    
    public function listdismissedAction() {
      $name = $this->param('name', null);
      $searchArr = [ 'form'=>'Empl','$or' => [['DtDismiss'=>['$exists'=>true, '$ne' => ""]]] ];
      if($name){
        $searchArr['$and'][] = ['FullNameInRus' => ['$regex' => new \MongoRegex("/$name/i")]];
    }
      $profiles = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal')->findBy($searchArr);
      $result = [];
      foreach($profiles as $p) {
        $result[] = $p->getDocument();
      }
      return $this->success(['users' => $result]);
    }
    
    public function subjectToFullNameInRusAction() {
      $data = $this->fromJson();
      if(!isset($data['subjects'])) { return $this->fail('wrong input'); }
      $logins = [];
      $fullnames = [];
      $subjects = [];
      foreach($data['subjects'] as $id) {
        if(is_array($id)) { $id = reset($id); }
        if(!$id) { continue; }
        if((strlen($id) < 20) && preg_match('/^[A-Za-z\_]+$/', $id)) {
          $logins[] = $id;
        } else if(preg_match('/^CN\=[A-Za-z0-9 ]+/', $id)) {
          $fullnames[] = $id;
        } else {
          $id = 'CN='.$id.'/O=skvirel';
          $fullnames[] = $id;
        }
      }
      $portal_rep = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
      
      $portalDatasFullName = $portal_rep->findBy(['form' => 'Empl', 'FullName' => ['$in' => $fullnames]]);
      $portalDatasLogin = $portal_rep->findBy(['form' => 'Empl', 'Login' => ['$in' => $logins]]);
      
      foreach($portalDatasFullName as $doc) {
        $fullnameArr = explode(" ", $doc->getFullNameInRus());
        $subjects[$doc->getFullName()] = $fullnameArr[0]." ".$fullnameArr[1];
        $subjects[$doc->getFullName(false)] = $fullnameArr[0]." ".$fullnameArr[1];
      }
      foreach($portalDatasLogin as $doc) {
        $fullnameArr = explode(" ", $doc->getFullNameInRus());
        $subjects[$doc->getLogin()] = $fullnameArr[0]." ".$fullnameArr[1];
      }
      
      if(empty($subjects) && !empty($data['subjects'])) {
        return $this->fail('no documents with specified subjects found', ['fullnames' => $fullnames, 'logins' => $logins]);
      }
      return $this->success(['subjects' => $subjects]);
    }

    public function getCountNewEmailsAction(){
      if ($usr = $this->getUser()) {
        $host = $this->container->getParameter('imap_get_host');
        $port = $this->container->getParameter('imap_get_port');
        $ssl = $this->container->getParameter('imap_get_ssl') == 'ssl'?'/ssl':'';
        $server = "{".$host.":".$port.$ssl."}";
        $count = $usr->getNewMailCount([
          'server' => $server,
          'mdHost' => $this->container->getParameter('mongodb_host'),
          'mdPort' => $this->container->getParameter('mongodb_port'),
          'mdUsername' => $this->container->getParameter('mongodb_username'),
          'mdPass' => $this->container->getParameter('mongodb_password')
        ]);

        if ($count !== false) {
          return $this->success(['count' => $count]);
        }else{
          return $this->fail('Fail connect to imap server');
        }
      }else{
        return $this->fail('current user not found');
      }
    }

    public function getMailHeadersAction(){
      $subject = $this->param('SUBJECT');
      $since = $this->param('SINCE');
      $before = $this->param('BEFORE');
      $to = $this->param('TO');
      $from = $this->param('FROM');

      $query = '';
      if ($subject) $query = $query.'SUBJECT "'.mb_convert_encoding($subject, "UTF-7", "UTF-8").'"';
      if ($since) $query = $query.' SINCE "'.$since.'"';
      if ($before) {
        $d = \DateTime::createFromFormat("Ymd", $before);
        $d->modify('+1 day');
        $query = $query.' BEFORE "'.$d->format('Ymd').'"';
      }
      if ($to) $query = $query.' TO "'.mb_convert_encoding($to, "UTF-7", "UTF-8").'"';
      if ($from) $query = $query.' FROM "'.mb_convert_encoding($from, "UTF-7", "UTF-8").'"';

      if ($usr = $this->getUser()){
        return $this->success(['hdrs' => $usr->mailSearch($query)]);
      }else{
        return $this->fail('current user not found');
      }
    }

    public function getMailsByIdsAction(){
      $ids = split(',', $this->param('ids'));
      return $this->success(['mails'=> $this->getUser()->getMailByIds($ids)]);
    }

    public function isChangeDismiss($user, $data) {
        $issetDd = isset($data['user']['portalData']['DtDismiss']);
        return $issetDd && ($data['user']['portalData']['DtDismiss'] !== $user->getPortalData()->GetDtDismiss());
    }

    public function requestAction(){
      $data = $this->fromJson();
      $login = $data['login'];
      
      $userManager = $this->get('fos_user.user_manager');
      $user = $userManager->findUserBy(['$or' => [['usernameCanonical' => $login],['username' => $login]]]);
      if (!$user) {return $this->fail('Wrong username');}
      $login = $user->getUsername(); //getting rid of case-sensitivity
      
      $randomNum = rand(666, 666666);
      
      $dm = $this->getDM();
      
      $token = new Token();
      $token->setToken($randomNum);
      $token->setUser($login);
      
      $curl = curl_init();
      
      curl_setopt_array($curl, array(
        CURLOPT_URL => $this->container->getParameter('io_host') . '/send_sms',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
        CURLOPT_POSTFIELDS => json_encode(array('to' => $login, 'text' => 'Код сброса пароля: '.$randomNum))
      ));

      $resp = curl_exec($curl);

      $response = json_decode($resp, true);
      curl_close($curl);
      
      $dm->persist($token);
      $dm->flush();

      if ($response['success'] == true)
        return $this->success(['tokenGenerated' => $response]);
      else
        return $this->fail('Failed to generate token');
    }
    
    public function verifyAction(){
      $data = $this->fromJson();
      $login = $data['login'];
      
      $userManager = $this->get('fos_user.user_manager');
      $user = $userManager->findUserBy(['$or' => [['usernameCanonical' => $login],['username' => $login]]]);
      if (!$user) {return $this->fail('Wrong username');}
      $login = $user->getUsername();
      
      $token = $data['token'];
      $date = new \DateTime();
      $date->sub(new \DateInterval('PT1H'));
      $tokens_rep = $this->getRepo('Token');
      $dm = $this->getDM();
      
      $tokenInBase = $tokens_rep->findOneBy(['user' => $login, 'token' => $token]);
      
      if ($tokenInBase) {
        if ($tokenInBase->getCreated() < $date) {return $this->fail('Expired token');}
        $tokenInBase->setActivated(true);
        $dm->persist($tokenInBase);
        $dm->flush();
        return $this->success(['tokenGenerated' => true]);
      } else {
        return $this->fail('Wrong token');
      }
    }
    
    public function changeAction(){
      $data = $this->fromJson();
      $login = $data['login'];
      
      $userManager = $this->get('fos_user.user_manager');
      $user = $userManager->findUserBy(['$or' => [['usernameCanonical' => $login],['username' => $login]]]);
      if (!$user) {return $this->fail('Wrong username');}
      $login = $user->getUsername();
      
      $token = $data['token'];
      $password = $data['password'];
      $tokens_rep = $this->getRepo('Token');
      $dm = $this->getDM();
      
      $tokenInBase = $tokens_rep->findOneBy(['user' => $login, 'token' => $token]);
      
      if ($tokenInBase) {
        if ($tokenInBase->getActivated() == true){
          if(isset($password)) {$user->setPlainPassword($password);}
          else {return $this->fail('Error changing password');}
          
          $userManager->updateUser($user);
        }
        return $this->success(['passwordChanged' => true]);
      } else {
        return $this->fail('Wrong token');
      }
    }
    
    public function likesListAction(){
      $data = $this->fromJson();
      $username = $data['username'];
      $fullname = $data['fullname'];
      $type = $data['type'];
      
      $dd =($this->get('doctrine_mongodb.odm.default_configuration')->getDefaultDB());
      $db = $this->get('doctrine_mongodb.odm.default_connection')->selectDatabase($dd);
      $portal_rep = $db->{'Portal'};
      
      $docs = $portal_rep->find(
      ['$and' => 
        [
          [ '$or' => 
            [
              ['authorLogin' => $username],
              ['AuthorFullNotesName' => $fullname]
            ]
          ],
          ['likes' => ['$exists' => true]],
          ['likes' => ['$ne' => []]] 
        ] 
      ])->sort(['created' => -1])->limit(500);
      
      $likes = array();

      while($docs->hasNext() && sizeof($likes) < 10) {
        $docs->next();
        $cur = $docs->current();
        $docLikes = $cur['likes'];
        
        foreach($docLikes as $login => $like) {
          if ($like['isLike'] == $type && sizeof($likes) < 10) array_push($likes, $cur);
        }
      }
      return $this->success(['likes' => $likes]);

    }
    
}
