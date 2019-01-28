<?php
namespace Treto\PortalBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Treto\PortalBundle\Document\JivoSite;

class ConsultService {
    private $loggerSynch;
    private $loggerAutotask;
    /** @var \Treto\PortalBundle\Services\RoboService $roboService */
    private $roboService;
    private $dicRepo;
    private $consultants;
    private $widgetsArray;

    public function __construct(ContainerInterface $container){
        $this->container = $container;
        $this->loggerAutotask = $this->container->get('monolog.logger.autotask');
        $this->loggerSynch = $this->container->get('monolog.logger.sync');
        $this->roboService = $this->container->get('service.site_robojson');
        $this->dicRepo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Dictionaries');
        $this->consultants = $this->getConsultantsInfo();
        $this->widgetsArray = $this->getWidgetsArray();
    }

    private function getWidgetsArray(){
        $widgetArray = [];
        $widgetsDocuments = $this->dicRepo->findBy(['type' => 'Widgets']);
        if($widgetsDocuments){
            foreach($widgetsDocuments as $widget){
                /** @var $widget Dictionaries */
                $widgetArray[$widget->getKey()] = strtolower($widget->getValue());
            }
        }
        return $widgetArray;
    }

    /**
     * Check missing message from client and create task
     * @param $doc
     */
    public function checkMissingMessage($doc){
        if($this->isMissing($doc)){
            $logins = $this->getLoginsByIds($doc);

            /** @var $dic \Treto\PortalBundle\Document\Dictionaries */
            $dic = $this->dicRepo->findOneBy(['type' => 'AutoTaskPersons', 'key' => 'Руководитель колл-центра']);
            if($dic && !in_array($dic->getValue(), $logins)){
                $logins[] = $dic->getValue();
            }

            if($logins){
                $templateName = 'TretoPortalBundle:Portal:AutoTasks/jivoChatTask.html.twig';
                $deputy = $this->roboService->getDeputy($logins[0]);

                if($deputy && !in_array($deputy, $logins)){
                    $logins[] = $deputy;
                }

                $atLeastOne = false;
                if(isset($doc['chat']['messages'])){
                    foreach ($doc['chat']['messages'] as $message) {
                        if(isset($message['message']) && trim($message['message'])){
                            $atLeastOne = true;
                            break;
                        }
                    }
                }

                $doc['visitor']['name'] = isset($doc['visitor']['name'])?$doc['visitor']['name']:false;
                $doc['visitor']['email'] = isset($doc['visitor']['email'])?$doc['visitor']['email']:false;
                $doc['visitor']['phone'] = isset($doc['visitor']['phone'])?$doc['visitor']['phone']:false;
                $doc['visitor']['description'] = isset($doc['visitor']['description'])?$doc['visitor']['description']:false;

                if($doc['visitor']['name'] || $doc['visitor']['email'] || $doc['visitor']['phone'] || $doc['visitor']['description'] || $atLeastOne){
                    $taskParams = ['document' => [
                        'body' => $this->container->get('templating')->render($templateName, ['doc' => $doc]),
                        'form' => 'formTask',
                        'readSecurity' => $logins,
                        'status' => 'open',
                        'subject' => 'Пропущен диалог в Jivosite',
                        'taskPerformerLat' => $deputy?$deputy:$logins[0],
                        'taskPerformerLatType' => 'logins'
                    ]];
                    $this->loggerAutotask->info('('.__CLASS__.' '.__FUNCTION__.') set task params: '.json_encode($taskParams, JSON_UNESCAPED_UNICODE));
                    $this->roboService->setTask($taskParams);
                }
                else {
                    $this->loggerAutotask->info('('.__CLASS__.' '.__FUNCTION__.') Empty required fields');
                }
            }
            else {
                $this->loggerAutotask->info('('.__CLASS__.' '.__FUNCTION__.') Not found more than one performer');
            }
        }
    }

    /**
     * Create task-notification for consultants
     * @param $doc
     * @param bool $country
     */
    public function createCommentLocaleTask($doc, $country = false){
        $error = false;
        /** @var \Treto\PortalBundle\Services\RoboService $robo */
        $robo = $this->container->get('service.site_robojson');
        $this->loggerAutotask->info('('.__CLASS__.' '.__FUNCTION__.') Run create task for comment from site');
        /** @var $doc \Treto\PortalBundle\Document\Portal */

        if($country = strtolower(trim($country))){
            $consults = $this->getArrayFromCons('countryCode');

            if(isset($consults[$country]) && $consults[$country]){
                $consLogin = [];
                foreach ($consults[$country] as $consult) {
                    $consLogin[] = ['login' => $consult['login'], 'wt' => $consult['workTime']];
                }
                $selectedCons = $this->defineSuitableConsultant($consLogin);
            }
        }
        else {
            $error = 'Not found consultants for country: '.$country;
            $this->loggerAutotask->info('('.__CLASS__.' '.__FUNCTION__.') '.$error);
        }

        if(!isset($selectedCons)){
            $selectedCons = $robo->getAutoTaskPersonByKey('Руководитель колл-центра');
        }

        if(isset($selectedCons) && $selectedCons){
            $homeUrl = $this->container->get('router')->getContext()->getBaseUrl();
            $link = '<a href="'.$homeUrl.'/#/discus/'.$doc->GetUnid().'/">Новый комментарий</a>';
            $this->roboService->setTask(['document' => [
                'body' => "$link требует ответа.",
                'form' => 'formTask',
                'readSecurity' => [$selectedCons],
                'status' => 'open',
                'subject' => 'Новый комментарий с сайта',
                'taskPerformerLat' => $selectedCons,
                'taskPerformerLatType' => 'logins'
            ]]);
        }
        else {
            $error = 'Error created comment';
            $this->loggerAutotask->info('('.__CLASS__.' '.__FUNCTION__.') '.$error.':'.json_encode($doc));
        }

        if($error){
            $this->createDebugTask($doc->getDocument(), $error.'<br/>Line: '.__LINE__.' Function: '.__FUNCTION__.' Class: '.__CLASS__);
        }
    }

    public function createDebugTask($doc, $lineFunction, $for1C = false){
        /** @var \Treto\PortalBundle\Services\RoboService $robo */
        $robo = $this->container->get('service.site_robojson');
        $taskPerformerLat = $robo->getAutoTaskPersonByKey('Debug');
        if($taskPerformerLat){
            $taskParams = ['document' => [
                'Author' => \Treto\UserBundle\Document\User::ROBOT_PORTAL,
                'C1' => 'Общекорпоративные',
                'body' => $lineFunction.'<br/>'.json_encode($doc),
                'form' => 'formTask',
                'status' => 'open',
                'subject' => 'Auto-task debug',
                'taskPerformerLat' => [$taskPerformerLat],
                'taskPerformerLatType' => 'logins'
            ]];

            if($for1C){
                $taskParams['document']['type'] = '1c';
            }
            $robo->setTask($taskParams);
        }
        else {
            $this->loggerAutotask->info('('.$lineFunction.') Error find debug login:'.json_encode($doc));
        }
    }

    /**
     * Define suitable consultant
     * @param $consLogin
     * @return mixed
     */
    private function defineSuitableConsultant($consLogin){
        $result = [];
        $allLogin = [];
        $wt = [];
        foreach ($consLogin as $cons) {
            $allLogin[] =  $cons['login'];
            $wt[$cons['login']] = time() > $cons['wt']['from'] && time() < $cons['wt']['to'];
            $cons = $cons['login'];
            $deputy = $this->roboService->getDeputy($cons, false, false, true);
            if($deputy){
                if(iconv_strlen($deputy) > 1 && !in_array($deputy, $result)){
                    $result[] = $deputy;
                }
                elseif($deputy == 'р' && !in_array($cons, $result)) {
                    $result[] = $cons;
                }
            }
            elseif(!in_array(date('w'), [0,6])) {
                $result[] = $cons;
            }
        }

        $result = !$result?$allLogin:$result;

        if(count($result) > 1 && $wt) {
            $wtResult = [];
            foreach ($wt as $login => $wtStatus) {
                if(in_array($login, $result) && $wtStatus){
                    $wtResult[] = $login;
                }
            }

            $result = $wtResult?$wtResult:$result;
        }

        return $result[rand(0, count($result)-1)];
    }

    /**
     * Get and sort consultant info-array from dictionaries (chat Id as key)
     * @return array
     */
    private function getConsultantsInfo(){
        $consDocuments = $this->dicRepo->findBy(['type' => 'Consultants']);
        $cons = [];
        foreach ($consDocuments as $consDocument) {
            $params = explode(';', $consDocument->getValue());
            if(is_array($params) && count($params) == 4){
                $time = explode('-', $params[2]);
                if(!isset($cons[$consDocument->getKey()])){
                    $cons[$consDocument->getKey()] = [];
                }

                $cons[$consDocument->getKey()][] = [
                    'login' => trim($params[0]),
                    'widgetId' => trim($params[1]),
                    'workTime' => [
                        'from' => strtotime(trim($time[0])),
                        'to' => strtotime(trim($time[1]))
                    ],
                    'countryCode' => trim(strtolower($params[3]))
                ];
            }
        }

        return $cons;
    }

    /**
     * Get and sort consultant info-array from dictionaries ($newKey as key)
     * @param string $newKey
     * @return array
     */
    private function getArrayFromCons($newKey = 'widgetId'){
        $result = [];
        if($this->consultants){
            foreach ($this->consultants as $key => $consultants) {
                foreach ($consultants as $consultant) {
                    if(isset($consultant[$newKey])){
                        if(!isset($result[$consultant[$newKey]])){
                            $result[$consultant[$newKey]] = [];
                        }
                        $consultant['chatId'] = $key;
                        $result[$consultant[$newKey]][] = $consultant;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get login array by JivoSite ID
     * @param $doc
     * @return array
     */
    private function getLoginsByIds($doc){
        $logins = [];

        if($this->consultants){
            if(isset($doc['agents'])){
                foreach ($doc['agents'] as $agent) {
                    if(isset($doc['agents']['id']) &&
                        isset($this->consultants[$doc['agents']['id']]) &&
                        isset($this->consultants[$doc['agents']['id']][0])){
                        $logins[] = $this->consultants[$doc['agents']['id']][0]['login'];
                    }
                    else {
                        $this->loggerAutotask->info('('.__CLASS__.' '.__FUNCTION__.') Not found login by id:'.$agent['id']);
                    }
                }
            }
            elseif(isset($doc['widget_id']) && $lw = $this->getLoginByWidget($doc['widget_id'])) {
                $logins[] = $lw;
            }
            else {
                $this->loggerAutotask->info('Required fields missing in request');
            }
        }
        else {
            $this->loggerAutotask->info('Not found consultants in dictionaries');
        }

        return $logins;
    }

    /**
     * Get consultant login by widgetId
     * @param $widgetId
     * @return bool
     */
    private function getLoginByWidget($widgetId){
        $result = false;
        $widgets = $this->getArrayFromCons();

        if(isset($widgets[$widgetId]) && $widgets[$widgetId]){
            if(count($widgets[$widgetId]) > 1){
                $loginInTime = [];
                foreach ($widgets[$widgetId] as $cons) {
                    if(time() > $cons['workTime']['from'] && time() < $cons['workTime']['to']){
                        $loginInTime[] = $cons['login'];
                    }
                }
                if($loginInTime){
                    $result = count($loginInTime)>1?$loginInTime[rand(0, count($loginInTime)-1)]:$loginInTime[0];
                }
                else {
                    $result = $widgets[$widgetId][rand(0, count($widgets[$widgetId])-1)]['login'];
                }
            }
            elseif(isset($widgets[$widgetId][0])) {
                $result = $widgets[$widgetId][0]['login'];
            }

            if(!$result){
                $this->loggerAutotask->info('Not found login by current widget. Time:'.time().' Widget:'.$widgetId);
            }
        }
        else {
            $this->loggerAutotask->info('Not found current widgets '.$widgetId.' in dictionaries');
        }

        return $result;
    }

    /**
     * Check messages on response
     * @param $doc
     * @return bool
     */
    private function isMissing($doc){
        $result = true;
        if(isset($doc['chat']) && isset($doc['chat']['messages']) && is_array($doc['chat']['messages'])){
            foreach ($doc['chat']['messages'] as $message) {
                if($message['type'] == 'agent'){
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Get JivoSite statistic main func
     * @param $params
     * @return string
     */
    public function getStatistics($params){
        $result = ['error' => true];

        if(isset($params['StatFrom']) && isset($params['DateRangeFrom']) && isset($params['DateRangeTo'])){
            $rangeChats = $this->getChatsByRange($params['DateRangeFrom'], $params['DateRangeTo']);
            if($rangeChats){
                switch ($params['StatFrom']){
                    case 'widgets':
                        $stat = $this->getWidgetsStat($rangeChats, $params);
                        break;
                    case 'consults':
                        $stat = $this->getConsultsStat($rangeChats, $params);
                        break;
                }

                if(isset($stat) && $stat){
                    $result = ['error' => false, 'result' => $stat];
                }
            }
            else {
                $result['error'] = 'Not found chats by range (Maybe invalid date format)';
            }
        }
        else {
            $result['error'] = 'Missing required params.';
        }

        return json_encode($result);
    }

    /**
     * Get all chat by date range
     * @param $from
     * @param $to
     * @return array
     */
    private function getChatsByRange($from, $to){
        $result = [];
        $from = date('Ymd\T000000', strtotime($from));
        $to = date('Ymd\T000000', strtotime($to));

        if($from && $to){
            $jivoRepo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:JivoSite');
            $result = $jivoRepo->findBy(['$and' => [
                 ['created' => ['$gte' => $from]],
                 ['created' => ['$lte' => $to]],
            ]]);
        }
        return $result?$result:[];
    }

    /**
     * Get widget-stat
     * @param $chats
     * @param $params
     * @return array
     */
    private function getWidgetsStat($chats, $params){
        $result = [];
        $widgetArr = [];

        foreach ($chats as $chat) {
            /** @var $chat JivoSite */
            $doc = $chat->getDocument();
            $widgetArr[$chat->getWidgetId()][$chat->getEventName()][] = $doc;
        }

        foreach ($widgetArr as $widgetId => $item) {
            $offlineMessages = 0;
            $answered = 0;
            $unanswered = 0;

            if(isset($item['offline_message'])){
               $offlineMessages = count($item['offline_message']);
            }

            if(isset($item['chat_finished'])){
                foreach ($item['chat_finished'] as $finished) {
                    if(isset($finished['chat']) && isset($finished['chat']['messages'])){
                        $hasAgentId = false;
                        foreach ($finished['chat']['messages'] as $message) {
                            if(isset($message['agent_id'])){
                                $hasAgentId = true;
                                break;
                            }
                        }
                        if($hasAgentId){
                            $answered++;
                        }
                        else {
                            $unanswered++;
                        }
                    }
                }
            }

            $all = $answered+$unanswered+$offlineMessages;
            $widgetCode = isset($this->widgetsArray)&&isset($this->widgetsArray[$widgetId])?$this->widgetsArray[$widgetId]:false;

            if($widgetCode){
                $result[$widgetCode] = [
                    'offline' => $offlineMessages,
                    'answered' => $answered,
                    'unanswered' => $unanswered,
                    'all' => $all,
                    'average' => $this->getAverageForDataRange($params, $all)
                ];
            }
        }

        return $result;
    }

    /**
     * Get average stat for date range
     * @param $params
     * @param $all
     * @return float
     */
    private function getAverageForDataRange($params, $all){
        $diff = date_diff(
            date_create('@'. strtotime($params['DateRangeFrom'])),
            date_create('@'.strtotime($params['DateRangeTo']))
        )->format('%a');
        return round($all/$diff);
    }

    /**
     * Get consults statistics
     * @param $chats
     * @param $params
     * @return array
     */
    private function getConsultsStat($chats, $params){
        $result = [];

        foreach ($chats as $chat) {
            if($chat->getEventName() == 'chat_finished'){
                /** @var $chat JivoSite */
                $doc = $chat->getDocument();
                if(isset($doc['agents']) && $doc['agents']){
                    foreach ($doc['agents'] as $agentNum => $agent) {
                        $existAgent = false;

                        if(!isset($result[$agent['id']])){
                            $result[$agent['id']] = [
                                'answered' => 0,
                                'unanswered' => 0,
                                'transmitted' => 0,
                                'email' => $agent['email'],
                                'name' => $agent['name']
                            ];
                        }

                        if(isset($doc['chat']) && isset($doc['chat']['messages'])){
                            foreach ($doc['chat']['messages'] as $message) {
                                if(isset($message['agent_id']) && $agent['id'] == $message['agent_id']){
                                    $existAgent = true;
                                    $result[$agent['id']]['answered']++;
                                    if($agentNum != 0){
                                        $result[$agent['id']]['transmitted']++;
                                    }
                                    break;
                                }
                            }

                            if(!$existAgent){
                                $result[$agent['id']]['unanswered']++;
                            }
                        }
                    }
                }
            }
        }

        foreach ($result as $k => $item) {
            $result[$k]['all'] = $item['transmitted']+$item['answered'];
            $result[$k]['average'] = $this->getAverageForDataRange($params, $item['transmitted']+$item['answered']);
        }

        return $result;
    }
}