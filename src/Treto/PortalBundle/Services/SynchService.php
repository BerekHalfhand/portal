<?php
namespace Treto\PortalBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use \Treto\PortalBundle\Document\Portal;
use \Treto\PortalBundle\Document\Contacts;
use Treto\PortalBundle\Document\PortalSettings;

class SynchService {
    private $container;
    private $objSOAPClient;
    public static $enableShareType = [
        'message',
        'formProcess',
        'formTask',
        'messagebb'
    ];

    public function __construct(ContainerInterface $container){
        $this->container = $container;
    }

    /**
     * @param $docObj
     * @param $mainDocObj
     * @param $host
     * @param $isCreating
     * @param $oldShare
     * @param bool $type
     * @return array
     */
    public function checkShare($docObj, $mainDocObj, $host, $isCreating, $oldShare, $type = false){
        $result = [];
        /** @var $docObj Portal */
        /** @var $mainDocObj Portal */
        $docArr = $docObj->getDocument();
        $mainDocArr = $mainDocObj->getDocument();
        /** @var RoboService $robo */
        $robo = $this->container->get('service.site_robojson');

        if(in_array($docArr['form'], self::$enableShareType)){
            $shareSecurity = isset($mainDocArr['shareSecurity'])?$mainDocArr['shareSecurity']:[];
            $result['takeOut'] = false;
            foreach($shareSecurity as $keyDomain => $secur){
                if(isset($secur['domain'])){
                    $commandName = $isCreating?'shareCreate':'shareUpdate';
                    if((!$isCreating && $oldShare && !isset($oldShare[$keyDomain])) ||
                      (!$isCreating && !$oldShare) || (!$oldShare && $isCreating &&
                      isset($docArr['subjectID']) && $docArr['subjectID'] != $docArr['unid'])){
                        $commandName = 'fullUpdate';
                    }

                    if($docObj->GetForm() == 'formTask' && !in_array($mainDocObj->GetForm(), self::$enableShareType) && !$result['takeOut']){
                        $result['takeOut'] = true;
                        $robo->takeOutTask($docObj);
                    }

                    $params = [
                        $commandName,
                        $keyDomain,
                        $docArr['unid'],
                        'Portal',
                        $host,
                        $type
                    ];

                    $robo->runCommand('synchronize', $params, 'commands_'.date('d-m-Y'));
                }
            }
        }

        return $result;
    }

    /**
     * Share task history
     * @param $taskArr
     * @param $historyArr
     * @param $host
     */
    public function shareTaskHistory($taskArr, $historyArr, $host){
        if(isset($taskArr['subjectID']) && $taskArr['subjectID'] != $taskArr['unid']){
            $portalRepo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
            /** @var Portal $taskParent */
            $taskParent = $portalRepo->findOneBy(['unid' => $taskArr['subjectID']]);
            if($taskParent){
                $main = $taskParent->getDocument();
            }
            else {
                $contactRepo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
                $taskParent = $contactRepo->findOneBy(['unid' => $taskArr['subjectID']]);

                if($taskParent){
                    $main = $taskParent->getDocument();
                }
            }
        }
        else {
            $main = $taskArr;
        }

        if(isset($main)){
            $shareSecurity = isset($main['shareSecurity'])?$main['shareSecurity']:[];

            foreach ($shareSecurity as $keyDomain => $secur) {
                $params = [
                  'taskHistory',
                  $keyDomain,
                  $historyArr['unid'],
                  'TaskHistory',
                  $host
                ];

                /** @var RoboService $robo */
                $robo = $this->container->get('service.site_robojson');
                $robo->runCommand('synchronize', $params, 'commands_'.date('d-m-Y'));
            }
        }
    }

    /**
     * Init send url
     * @param $doc
     * @param $domain
     * @param $action
     * @return array
     */
    public function sendShareDocument($doc, $domain, $action){
        $result = [];
        $protocol = 'http://';

        $portalSettings = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:PortalSettings');
        /** @var $setting PortalSettings */
        $setting = $portalSettings->findOneBy(["domain" => $domain]);

        if($setting && $setting->getHttps()){
            $protocol = 'https://';
        }

        if($action === 'fullUpdate'){
            $domain = $domain.'/api/v1/discuss/update/full';
        }
        elseif($action === 'taskHistory'){
            $domain = $domain.'/api/v1/discuss/setHistory';
        }
        elseif($action){
            $domain = $domain.'/api/v1/discuss/set/theme';
        }
        else {
            $domain = $domain.'/api/v1/discuss/update/theme';
        }

        $result['request'] = date('d-m-y H:i:s').' Send to='.$protocol.$domain.' params='.json_encode($doc, JSON_UNESCAPED_UNICODE);
        $result['response'] = $this->sendPost($protocol.$domain, $doc, $setting->getSalt());

        return $result;
    }

    /**
     * Send http request
     * @param $addr
     * @param $params
     * @param bool $salt
     * @return array
     */
    public function sendPost($addr, $params, $salt = false){
        $salt = $salt?$salt:'test';
        $result = [];
        $params["hash"] = md5($salt.date('Y.m.d'));

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $addr,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => array("Content-type: application/json"),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 15
        ));
        $json_response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ((int)$status !== 200 && (int)$status !== 201 ) {
            $result['fail'] = ['success' => false, 'message' => curl_error($curl), 'curl_errno' => curl_errno($curl), 'status' => $status];
        }
        curl_close($curl);
        $result['success'] = json_decode($json_response, true);

        return $result;
    }

    /**
     * Test request
     * @param $settings
     * @return array
     */
    public function checkShareSettings($settings){
        $result = [];

        if($settings){
           $protocol =  $settings['https'] == 1?'https://':'http://';
           $url = $protocol.$settings['domain'].'/api/v1/discuss/checkHash';
           $result = $this->sendPost($url, [], $settings['salt']);
        }

        return $result;
    }
}