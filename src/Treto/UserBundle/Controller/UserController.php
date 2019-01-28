<?php

namespace Treto\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as Cont;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Cookie;
use Treto\PortalBundle\Services\RoboService;

class UserController extends \FOS\UserBundle\Controller\SecurityController
{
    protected $guard;

    /** @return \Doctrine\ODM\MongoDB\DocumentRepository */
    public function getRepo($shortDocumentName) {
        $repo = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:'.$shortDocumentName);
        if($repo instanceof \Treto\PortalBundle\Document\SecureRepository) {
            $repo->releaseUser();
        }
        return $repo;
    }

    /** @return \Doctrine\ODM\MongoDB\DocumentManager */
    public function getDM() {
        return $this->get('doctrine.odm.mongodb.document_manager');
    }
    
    protected function unsafe() {
      if(!in_array($this->getRequest()->getClientIp(), ['127.0.0.1','::1','fe80::1'])) {
        $this->guard = new JsonResponse('This action is for localhost only', 403);
        return true;
      }
      return false;
    }
  
    public function loginAction(Request $request) {
        /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
        $session = $request->getSession();
        $key = $this->getRequest()->get('key', false);

        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContextInterface::AUTHENTICATION_ERROR);
        } elseif (null !== $session && $session->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
            $error = $session->get(SecurityContextInterface::AUTHENTICATION_ERROR);
            $session->remove(SecurityContextInterface::AUTHENTICATION_ERROR);
        } else {
            $error = null;
        }

        if (!($error instanceof AuthenticationException)) {
            $error = null; // The value does not come from the security component.
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get(SecurityContextInterface::LAST_USERNAME);

        if ($key) {
          $guestRepository = $this->getDM()->getRepository('TretoPortalBundle:Guest');
          $guest = $guestRepository->findBy(['key'=>$key]);
          if ($guest)
            file_put_contents('1.txt', print_r($guest, true));
          
        }
        
        
        if ($session || $guest) {
            if (!$this->getUser()) {
                return new JsonResponse(['success'=>false, 'message'=>($error ? $error : 'not authorized'), 'user'=>null]);
            }

            $user = $this->getUser();
            $user = $user->getDocument();
            $userRepository = $this->getDM()->getRepository('TretoUserBundle:User');
            /** @var $userModel \Treto\UserBundle\Document\User */
            $userModel = $userRepository->findOneBy(['_id' => new \MongoId($user['id'])]);
            $userModel->setLastLogin(new \DateTime());
            $this->getDM()->persist($userModel);
            $this->getDM()->flush();

            $portalRepository = $this->getDM()->getRepository('TretoPortalBundle:Portal');
            $users = $portalRepository->findBy(['form'=>'Empl']);
            $resUsers = [];
            foreach ($users as $u) {
              $resUsers[$u->getLogin()] = [
                'name' => $u->getLastName().' '. $u->getName(),
                'Birthday' => $u->getBirthday(),
                'WorkGroup' => $u->getworkGroup(),
                'section' => $u->getSection(),
                'Sex' => $u->getsex(),
                'DtWork' => $u->getDtWork(),
                'DtDismiss' => $u->getDtDismiss(),
                'FullName' => $u->getFullName(),
                'FullNameRaw' => $u->getFullName(false)
              ];
            }

            /** @var RoboService $robo */
            $robo = $this->get('service.site_robojson');

            return new JsonResponse([
                'success'=> true,
                'user'=> $user,
                'users' => $resUsers,
                'shareUsers' => $robo->getAllActiveShareUsers(),
                'environment' => $this->container->getParameter("kernel.environment")
            ]);
        }
        
        
        
        return new JsonResponse([
            'success'=> false,
            'message'=> $error,
            'user'=> null,
            'users'=> null,
            'shareUsers' => null,
            'environment' => null
        ]);
    }
    
    public function generatePasswordsAction() {
      if($this->unsafe()) { return $this->guard; }
      
      $singlePassword = $this->getRequest()->get('single');
      $forEnabledUsers = $this->getRequest()->get('enabled'); // can be null, false or true
      $um = $this->get('fos_user.user_manager');
      $users = $um->findUsers();
      srand(time());
      $passwords = [];
      foreach($users as $u) {
        if($forEnabledUsers === null || ($u->isEnabled() == $forEnabledUsers)) {
          $password = $singlePassword ? $singlePassword : sprintf("%u", crc32((string)rand()));
          $u->setPlainPassword($password);
          $um->updateUser($u, false);
          $passwords[$u->getUsername()] = $password;
        }
      }
      $this->get('doctrine.odm.mongodb.document_manager')->flush();
      return new JsonResponse(['success' => true, 'passwords' => $passwords]);
    }

    /**
     * Write to DB access to mail
       $example = [
        'default' => ['password' => 'pass123'],
        'external' => [[
            'username' => 'email@testserver.ru',
            'password' => 'pass123',
            'server' => [
              'host' => 'imap.testserver.ru',
                'port' => 993,
               'ssl' => true
            ]
        ]]
      ];
     * @return JsonResponse
     */
    public function mailAccessSetAction(){
        $result = ['success' => false];
        if(isset($_POST['id']) && isset($_POST['password'])){
            $mdHost = $this->container->getParameter('mongodb_host');
            $mdPort = $this->container->getParameter('mongodb_port');
            $mdUsername = $this->container->getParameter('mongodb_username');
            $mdPass = $this->container->getParameter('mongodb_password');
            $dbName = $this->container->getParameter('mongodb_db');

            $m = new \MongoClient("mongodb://$mdUsername:$mdPass@$mdHost:$mdPort/$dbName");
            $collection = new \MongoCollection($m->selectDB($dbName), 'User');
            if($response = $collection->findOne(array('_id' => new \MongoId($_POST['id'])))){
                $result['success'] = true;
                $mailAccess = isset($response['mailAccess'])?$response['mailAccess']:[];
                /** @var \Treto\PortalBundle\Services\RoboService $robo */
                $robo = $this->get('service.site_robojson');
                $params = '\''.$this->getUser()->getPortalData()->GetLogin().'@$#%synchPassword%&$@'.$_POST['password'].'\'';
                $robo->runCommand('synchronize', ['password', $params], 'commands_'.date('d-m-Y'));
                if(!$mailAccess||!isset($mailAccess['default'])||$mailAccess['default']['password'] != $_POST['password']){
                    $mailAccess['default'] = ['password' => $_POST['password']];
                    $collection->update(array('_id' => new \MongoId($_POST['id'])), ['$set' => ['mailAccess' => $mailAccess]]);
                }
            }
        }
        return new JsonResponse([$result]);
    }

     /**
     * Set cookies for login to site
     * @return JsonResponse
     */
    public function getSiteAuthConfigAction(){
        $result = [];
        if($this->getUser()){
            $repo = $this->getRepo('Dictionaries');
            /** @var $record \Treto\PortalBundle\Document\Dictionaries */
            $records = $repo->findBy([
                'type' => 'Configurations'
            ]);
            if($records){
                foreach ($records as $record) {
                    if($record->getKey() == 'site_auth' && $val = $record->getValue()){
                        $val = json_decode($val, true);
                        if(is_array($val)){
                            foreach ($val as $key => $r) {
                                $result[$key] = $r;
                                $result[$key]['hash'] = md5($r['salt'].date('Y.m.d'));
                                unset($result[$key]['salt']);
                            }
                        }
                    }
                }
            }
        }

        return new JsonResponse($result);
    }
}
