<?php
namespace Treto\PortalBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Treto\PortalBundle\Document\Portal;
use Treto\PortalBundle\Document\PortalSettings;
use Treto\PortalBundle\Document\TaskHistory;
use Treto\PortalBundle\Services\RoboService;
use Treto\PortalBundle\Services\SynchService;
use Treto\PortalBundle\Services\TaskService;

class SynchronizeCommand extends ContainerAwareCommand
{
    private $logger;
    private $empls;
    private $dm;
    private $objSOAPClient;

    protected function configure() {
        $this->setName('synchronize')->setDescription('synchronize')
            ->addArgument('type', InputArgument::OPTIONAL)
            ->addArgument('params', InputArgument::OPTIONAL)
            ->addArgument('paramsSec', InputArgument::OPTIONAL)
            ->addArgument('paramsTh', InputArgument::OPTIONAL)
            ->addArgument('paramsFr', InputArgument::OPTIONAL)
            ->addArgument('paramsSx', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->prepare();
        $type = $input->getArgument('type');
        $params = $input->getArgument('params');
        $secParam = $input->getArgument('paramsSec');
        $thParam = $input->getArgument('paramsTh');
        $frParam = $input->getArgument('paramsFr');
        $sxParam = $input->getArgument('paramsSx');

        $this->logger->info('Run synchronize command. Type:'.$type.' Params: '.$params);

        if($type){
            switch($type){
                case 'wp':
                    $this->synchronizeWP($params);
                    break;
                case 'monthWp':
                    $this->synchMonthWp();
                    break;
                case 'password':
                    $this->synchPassword($params);
                    break;
                case 'shareCreate':
                    $this->shareDocument($params, $secParam, true, $thParam, $frParam);
                    break;
                case 'shareUpdate':
                    $this->shareDocument($params, $secParam, false, $thParam, $frParam, $sxParam);
                    break;
                case 'fullUpdate':
                    $this->fullShareUpdate($params, $secParam, $thParam, $frParam);
                    break;
                case 'taskHistory':
                    $this->syncTaskHistory($params, $secParam, $thParam, $frParam);
                    break;
                case 'empls':
                    $this->synchEmpls();
                    break;
            }
        }
    }

    /**
     * @param $domainKey
     * @param $unid
     * @param $repoName
     * @param $myHost
     */
    private function syncTaskHistory($domainKey, $unid, $repoName, $myHost){
        echo '('.__CLASS__.' '.__FUNCTION__.') Start command domainKey='.$domainKey.' unid='.$unid.' syncTaskHistory"\n\r';

        $repo = $this->dm->getRepository('TretoPortalBundle:'.$repoName);
        /** @var TaskHistory $history */
        $history = $repo->findOneBy(['unid' => $unid]);

        if($history){
            $portal = $this->dm->getRepository('TretoPortalBundle:Portal');
            /** @var Portal $task */
            $task = $portal->findOneBy(['$or' => [['_id' => $history->getTaskId()], ['unid' => $history->getTaskUnid()]]]);
            if($task){
                if($task->GetSubjectID() && $task->GetSubjectID() != $task->GetUnid()){
                    /** @var Portal $parent */
                    $parent = $portal->findOneBy(['unid' => $task->GetSubjectID()]);

                    if($parent){
                        $shareSecurity = $parent->getShareSecurity();
                    }
                }
                else {
                    $shareSecurity = $task->getShareSecurity();
                }
            }

            if(isset($shareSecurity) && isset($shareSecurity[$domainKey]) && isset($shareSecurity[$domainKey]['domain'])){
                $domain = $shareSecurity[$domainKey]['domain'];
                /** @var SynchService $synchService */
                $synchService = $this->getContainer()->get('synch.service');
                $history = $history->getDocument();
                $history['sendShareFrom'] = $myHost;
                $response = $synchService->sendShareDocument($history, $domain, 'taskHistory');

                print_r($response);
            }
        }
    }

    /**
     * @param $domainKey
     * @param $unid
     * @param $repoName
     * @param $myHost
     */
    private function fullShareUpdate($domainKey, $unid, $repoName, $myHost){
        echo '('.__CLASS__.' '.__FUNCTION__.') Start command domainKey='.$domainKey.' unid='.$unid.' fullShareUpdate"\n\r';

        $portalRep = $this->dm->getRepository('TretoPortalBundle:'.$repoName);
        /** @var Portal $doc */
        $doc = $portalRep->findOneBy(['unid' => $unid]);
        if($doc) {
            $docArr = $doc->getDocument();
            if (isset($docArr['subjectID']) && $docArr['subjectID'] != $docArr['unid']) {
                /** @var Portal $parentDoc */
                $parentDoc = $portalRep->findOneBy(['unid' => $docArr['subjectID']]);
                if ($parentDoc) {
                    $parentDocArr = $parentDoc->getDocument();
                }
                else {
                    $contactsRepo = $this->dm->getRepository('TretoPortalBundle:Contacts');
                    $parentDoc = $contactsRepo->findOneBy(['unid' => $docArr['subjectID']]);
                    if($parentDoc){
                        $parentDocArr = $parentDoc->getDocument();
                    }
                    else {
                        echo "Not found parentDocArr by subjectID=".$docArr['subjectID']."\n\r";
                    }
                }
            }
            else {
                $parentDocArr = $docArr;
            }

            if(isset($parentDocArr) && $parentDocArr){
                $toSend = ['main' => $this->preparationShareDoc($parentDocArr, $myHost), 'comments' => []];
                $domain = $parentDocArr['shareSecurity'][$domainKey]['domain'];
                $requestParam = [
                    'unid' => ['$ne' => $parentDocArr['unid']],
                    'subjectID' => $parentDocArr['unid'],
                    '$or' => []
                ];

                foreach (SynchService::$enableShareType as $type) {
                    $requestParam['$or'][] = ['form' => $type];
                }

                $childDocs = $portalRep->findBy($requestParam);
                foreach ($childDocs as $childDoc) {
                    $toSend['comments'][] = $this->preparationShareDoc($childDoc->getDocument(), $myHost);
                }

                /** @var SynchService $synchService */
                $synchService = $this->getContainer()->get('synch.service');
                $response = $synchService->sendShareDocument($toSend, $domain, 'fullUpdate');
                print_r($response);
            }
            else {
                echo "Not found parentDocArr\n\r";
            }
        }
    }

    /**
     * Share portal document
     * @param $domainKey
     * @param $unid
     * @param $isCreate
     * @param string $repoName
     * @param $myHost
     * @param bool $type
     */
    private function shareDocument($domainKey, $unid, $isCreate, $repoName = 'Portal', $myHost, $type = false){
        echo '('.__CLASS__.' '.__FUNCTION__.') Start command domainKey='.$domainKey.' unid='.$unid.' isCreate='.$isCreate."\n\r";

        $portalRep = $this->dm->getRepository('TretoPortalBundle:'.$repoName);
        /** @var Portal $doc */
        $doc = $portalRep->findOneBy(['unid' => $unid]);
        if($doc){
            $docArr = $doc->getDocument();
            if(isset($docArr['subjectID']) && $docArr['subjectID'] != $docArr['unid']){
                /** @var Portal $parentDoc */
                $parentDoc = $portalRep->findOneBy(['unid' => $docArr['subjectID']]);
                if($parentDoc){
                    $parentDocArr = $parentDoc->getDocument();
                    if(isset($parentDocArr['shareSecurity'])){
                        $docArr['shareSecurity'] = $parentDocArr['shareSecurity'];
                    }
                }
            }

            if(isset($docArr['shareSecurity']) && isset($docArr['shareSecurity'][$domainKey])){
                $domain = $docArr['shareSecurity'][$domainKey]['domain'];
                $docArr = $this->preparationShareDoc($docArr, $myHost, $isCreate);
                /** @var SynchService $synchService */
                $synchService = $this->getContainer()->get('synch.service');
                if($type == 'taskService' && $docArr['form'] == 'formTask'){
                    $docArr['shareType'] = 'taskService';
                }
                $response = $synchService->sendShareDocument($docArr, $domain, $isCreate);
                print_r($response);
            }
            else {
                echo 'Not found shareSecurity by domainKey='.$domainKey.". \n\r";
            }
        }
        else {
            echo 'Document by unid:'.$unid." not found.\n\r";
        }
    }

    /**
     * Preparation share document, before send
     * @param $doc
     * @param $myHost
     * @param $isCreate
     * @return mixed
     */
    private function preparationShareDoc($doc, $myHost, $isCreate = false){
        $doc['shareSecurity'][str_replace('.', '', $myHost)] = $doc['security'];
        $doc['shareSecurity'][str_replace('.', '', $myHost)]['domain'] = $myHost;
        $doc['sendShareFrom'] = $myHost;

        unset($doc['Author']);
        $doc['shareAuthorLogin'] = $doc['authorLogin'];
        unset($doc['authorLogin']);
        unset($doc['_id']);
        if(!$isCreate){
          unset($doc['readBy']);
        }
        else {
          $doc['readBy'] = [];
        }

        if(isset($doc['attachments']) && $doc['attachments']){
            foreach ($doc['attachments'] as $key => $attachment) {
                if(isset($attachment[0]) && !isset($doc['attachments'][$key][0]['domain'])){
                    $doc['attachments'][$key][0]['domain'] = $myHost;
                }
            }
        }

        if(isset($doc['form']) && $doc['form'] == 'formTask'){
            /** @var TaskService $taskService */
            $taskService = $this->getContainer()->get('task.service');
            $doc['taskHistory'] = $taskService->getHistories($doc);
        }

        return $doc;
    }

    private function synchEmpls(){
        $this->logger->info('('.__CLASS__.' '.__FUNCTION__.') Start command');
        $portalSettings = $this->getContainer()->get('doctrine_mongodb')->getRepository('TretoPortalBundle:PortalSettings');
        $settings = $portalSettings->findBy([
            "status" => "active",
            "type" => "sharePortal"
        ]);

        if($settings){
            foreach ($settings as $setting) {
                $this->getPortalEmplsBySettings($setting);
            }
        }
    }

    /**
     * @param $setting
     */
    private function getPortalEmplsBySettings($setting){
        /** @var RoboService $robo */
        /** @var $setting PortalSettings */
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $robo = $this->getContainer()->get('service.site_robojson');
        $domain = $setting->getDomain();
        $protocol =  $setting->getHttps()?'https://':'http://';
        $addr = rtrim($protocol.$domain, '/')."/api/user/getPortalEmpls";

        $response = $robo->sendRequest($addr, ['hash' => $robo->encodeAccessKey($setting->getSalt())]);
        $this->logger->info('('.__CLASS__.' '.__FUNCTION__.') Response from '.$addr.' '.json_encode($response, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        if($response && isset($response['status']) && $response['status'] == 200 && isset($response['result']['users'])){
            $setting->setUsers($response['result']['users']);
            $setting->setEnvironment($response['result']['environment']);
            $date = new \DateTime();
            $setting->setLastSynch($date->format('Ymd').'T'.$date->format('His'));
            $dm->persist($setting);
            $dm->flush();
        }
    }


    /**
     * $params = string 'login@password'
     * @param $params
     */
    private function synchPassword($params){
        echo "Run synchPassword ".date('Y-m-d H:i:s')." --------------\n\r";
        $params = explode('@$#%synchPassword%&$@', $params);
        if(isset($params[0]) && isset($params[1])){
            $host = $this->getContainer()->getParameter('c1_listeningportal_host');
            echo "connect to host: $host; params: user=".$params[0]." pass=".$params[1]."\n\r";

            if($host){
                $this->connectSOAP($host);
                $this->sendToSOAP([['User' => $params[0], 'Password' => $params[1]]], 'SetUserPassword');
            }
            else {
                echo "Missing second param - c1_listeningportal_host\n\r";
            }
        }
        else {
            echo "Missing second param - unid\n\r";
        }
    }

    /**
     * Prepare WP data days array for 1C
     * @param $dd
     * @return mixed
     */
    private function prepareWpTo1C($dd){
        $result = [];
        foreach ($dd as $item) {
            if(is_array($item) && isset($item['deputyLogin'])){
                $item['deputy'] = $this->getEmplArrByLogin($item['deputyLogin']);
                unset($item['deputyLogin']);
                $result[] = $item;
            }
            else {
                $result[] = ['type' => $item];
            }
        }
        return $result;
    }

    /**
     * Run every month
     */
    private function synchMonthWp(){
        $this->logger->info('('.__CLASS__.' '.__FUNCTION__.') Start command');
        $year = date('Y'); //, strtotime("+1 month", time())
        $month = date('m'); //, strtotime("+1 month", time())
        $portalRep = $this->dm->getRepository('TretoPortalBundle:Portal');

        $wps = $portalRep->findBy(['form' => 'WorkPlan', 'Year' => $year, 'Month' => $month]);
        $empls = $portalRep->findBy(['form' => 'Empl', '$or' => [['DtDismiss' => ''], ['DtDismiss' => ['$exists' => false]]]]);
        $arrEmpls = [];

        if($empls){
            foreach ($empls as $empl) {
                /** @var Portal $empl */
                $arrEmpls[$empl->GetUnid()] = [
                    'date' => $month.'.'.$year,
                    'empl' => $this->getEmplArrByLogin($empl->GetLogin(), $empl),
                    'daysData' => ''
                ];
            }

            if($wps){
                foreach ($wps as $wp) {
                    /** @var Portal $wp */
                    if(isset($arrEmpls[$wp->GetEmplUNID()])){
                        $arrEmpls[$wp->GetEmplUNID()]['daysData'] =  $this->prepareWpTo1C($wp->GetDaysData());
                    }
                }
            }
            else {
                $this->logger->info('('.__CLASS__.' '.__FUNCTION__.') Wps is empty');
            }

            foreach ($arrEmpls as $key => $arrEmpl) {
                if(!$arrEmpl['daysData']){
                    $arrEmpls[$key]['daysData'] = $this->defaultMonthModel([$month, $year]);
                }
            }
            $host = $this->getContainer()->getParameter('c1_wp_host');//$host = 'http://app4.treto.ru/vl1/workdays_my.1cws?wsdl';

            if($host){
                $toSynch = [];
                foreach ($arrEmpls as $item) {
                    $toSynch[] = $item;
                }
                $this->connectSOAP($host);
                $data = json_encode($toSynch, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
                $this->logger->info('('.__CLASS__.' '.__FUNCTION__.') send '.$data);
                $this->sendToSOAP([["Данные" => $data]]);
            }
            else {
                $this->logger->info('('.__CLASS__.' '.__FUNCTION__.') Not found host in params.yaml.');
            }
        }
        else {
            $this->logger->info('('.__CLASS__.' '.__FUNCTION__.') Not found empls in db');
        }
    }

    protected function defaultMonthModel($from, $for1C = false) {
        $t = sprintf("%s-%s-01",$from[1], $from[0]);
        $s = date_create_from_format("Y-m-d", $t);
        $next = $from;
        $next[0]++;
        if($next[0]==13) {
            $next[0]-=12;
            $next[1]++;
        }
        if(strlen($next[0].'') < 2)$next[0] = '0'.$next[0];
        $ss = date_create_from_format("Ymd", sprintf("%s%s01",$from[1], $from[0]));
        $e = date_create_from_format("Ymd", sprintf("%s%s01",$next[1], $next[0]));
        $i = date_diff($ss, $e);
        $n = 0+$i->format("%a");

        $ret = [];
        $di = new \DateInterval('P1D');
        foreach(range(0, $n-1) as $q) {
            $f = ((6 + $s->format("w"))%7);
            $R = ['р','р','р','р','р','в','в'];
            $ret []= !$for1C?['type'=>$R[$f]]:$R[$f];
            $s = date_add($s, $di);
        }
        return $ret;
    }

    private function prepare(){
        $this->logger = $this->getContainer()->get('monolog.logger.sync');
        $this->dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
    }

    private function synchronizeWP($params){
        $params = @unserialize($params);

        if($params && isset($params['date']) && isset($params['login']) && isset($params['dd'])){
            $toSynch = [
                'empl' => $this->getEmplArrByLogin($params['login']),
                'date' => implode('.', $params['date']),
                'daysData' => []
            ];

            foreach ($params['dd'] as $key => $day) {
                if(is_array($day)){
                    $params['dd'][$key]['deputy'] = $this->getEmplArrByLogin($day['deputyLogin']);
                    unset($params['dd'][$key]['deputyLogin']);
                    $toSynch['daysData'][$key] = $params['dd'][$key];
                }
                else {
                    $toSynch['daysData'][$key] = ['type' => $day];
                }
            }
            $host = $this->getContainer()->getParameter('c1_wp_host');//$host = 'http://app4.treto.ru/vl1/workdays_my.1cws?wsdl'; // test host

            if($host){
                $this->connectSOAP($host);
                $this->sendToSOAP([["Данные" => json_encode([$toSynch], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)]]);
            }
            else {
                $this->logger->info('Not found host in params.yaml.');
            }
        }
        else {
            $this->logger->info('Invalid params.');
        }
    }

    /**
     * Return empl array by login
     * @param $login
     * @param bool $portalEmpl
     * @return array
     */
    private function getEmplArrByLogin($login, $portalEmpl = false){
        if(!isset($this->empls[$login])){
            $portalRep = $this->dm->getRepository('TretoPortalBundle:Portal');
            $contactRepo = $this->dm->getRepository('TretoPortalBundle:Contacts');
            if(!$portalEmpl){
                /** @var $portalEmpl \Treto\PortalBundle\Document\Portal */
                $portalEmpl = $portalRep->findOneBy([
                    'Login' => $login,
                    'form' => 'Empl'
                ]);
            }

            if($portalEmpl){
                $emplWorkGroup = $portalEmpl->GetWorkGroup();
                $this->empls[$login] = [
                    'LastName' => $portalEmpl->GetLastName(),
                    'FirstName' => $portalEmpl->GetName(),
                    'MiddleName' => $portalEmpl->GetMiddleName(),
                    'ContactUnid' => $portalEmpl->GetContactUnid(),
                    'Login' => $login,
                    'WorkGroup' => is_array($emplWorkGroup)?$emplWorkGroup:[$emplWorkGroup]
                ];
            }
        }

        return isset($this->empls[$login])?$this->empls[$login]:[];
    }

    public function connectSOAP($host){
        try {
            $this->objSOAPClient = new \SoapClient($host, array("cache_wsdl" => 0));
        } catch (\SOAPFault $exception) {
            $this->logger->info($exception->getMessage());
        }
    }

    public function sendToSOAP($params, $functionName = 'ImportOperatingShedule'){
        $strLogText = "Отправка\n\n";
        try {
            $strResponse = $this->objSOAPClient->__soapCall($functionName, $params);
        } catch (\SOAPFault $exception) {
            $strLogText .= $this->objSOAPClient->__getLastRequest();
            $strLogText .= $this->objSOAPClient->__getLastRequestHeaders();
            $strLogText .= $exception;
        }
        $strLogText .= "Ответ:\n" . $strResponse->return . "\n\n";
        $this->logger->info($strLogText);
        echo $strLogText;
    }
}
