<?php
// AutoTaskCheckCommand.php

namespace Treto\PortalBundle\Command;

use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Treto\PortalBundle\Document\Contacts;
use Treto\PortalBundle\Document\Files;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Treto\PortalBundle\Document\Portal;
use Treto\PortalBundle\Services\RoboJsonService;

class TestCommand extends ContainerAwareCommand
{
    public $dm;

    protected function configure()
    {
        $this->setName('testCommand:run')->setDescription('testCommand')
            ->addArgument('type', InputArgument::OPTIONAL)
            ->addArgument('secondParam', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type');
        $secondParam = $input->getArgument('secondParam');

        if($type == 'checkContactFor1C'){
            $this->checkContactFor1C();
        }
        elseif($type == 'sendBlog'){
            $this->sendBlog();
        }
        elseif($type == 'removeTasks'){
            $this->removeTasks();
        }
        elseif($type == 'removeContact'){
            $this->removeContact($secondParam?$secondParam:'');
        }
        elseif($type == 'checkContact'){
            echo $this->checkContactService($secondParam?$secondParam:'');
        }
        elseif($type == 'removeDouble'){
            $this->removeDouble();
        }
        elseif($type == 'addCountries'){
            $this->addCountriesToContacts();
        }
        elseif($type == 'closeAccess'){
            $this->closeAccess();
        }
        elseif($type == 'findMissingComment'){
            $this->findMissingComment();
        }
        elseif($type == 'synchPass'){
            $this->synchPassword($secondParam?$secondParam:'');
        }

        elseif($type == 'long'){

            $contactrepo = $this->getContainer()->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
            $orgEmpls = $contactrepo->findBy(["OrganizationID"=>'0277C7B0B89A9F23C32577FF006F2CD8']);
            echo 'count:'.count($orgEmpls);
            $a = [];
            $b = [];
            foreach($orgEmpls as $empl){
                $a[] = $empl->getContactName();
                $b[] = $empl->getUnid();
            }
            print_r($a);
            print_r($b);
        }

        else {
            echo "Invalid type param.\n\r";
        }
    }

    private function removeContact($unid){
        /** @var \Treto\PortalBundle\Services\ExporterTo1C $exportService */
        $exportService = $this->getContainer()->get('exporterto1c');
        $response = $exportService->deleteContact($unid);
        print_r($response);
    }

    private function synchPassword($password){
        $loggerSynch = $this->getContainer()->get('monolog.logger.sync');
        $loggerSynch->info('('.__CLASS__.' '.__FUNCTION__.') Params: set new password '.$password);
        sleep(20);
        echo 543252345;
        exit;
        if($password){
            $host = $this->getContainer()->getParameter('c1_listeningportal_host');

            if($host){
                $objSOAPClient = new \SoapClient($host, array("cache_wsdl" => 0));
                $strLogText = '';
                try {
                    $strResponse = $objSOAPClient->__soapCall('SetUserPassword', array(['User' => 'ddudarev', 'Password' => $password]));
                    $strLogText .= "Ответ:\n".$strResponse->return."\n\n";
                    echo $strResponse->return;
                } catch (\SOAPFault $exception) {
                    $strLogText .= $objSOAPClient->__getLastRequest();
                    $strLogText .= $objSOAPClient->__getLastRequestHeaders();
                    $strLogText .= $exception->getMessage();
                }
            }
            else {
                echo "\n\rMissing second param - c1_wp_host\n\r";
            }
        }
        else {
            echo "\n\rMissing second param - unid\n\r";
        }
        return 'error';
    }

    private function findMissingComment(){
        $portal = $this->getContainer()->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $result = $portal->findBy([
                '$and' => [
                    ['$or' => [['ToSite' => '1'], ['ToSite' => 1]]],
                    ['$or' => [['parentID' => ['$exists' => false]], ['parentID' => '']]]
                ], 'C1' => "Коллекция"
        ]);

        echo "\n\r";
        if($result){
            $count = 0;
            foreach ($result as $item) {
                /** @var $item Portal */
                /** @var $rr Portal */

//                $docs = $dm->createQueryBuilder('TretoPortalBundle:Portal')
//                    ->field('parentID')
//                    ->equals($item->GetUnid())
//                    ->field('ToSite')
//                    ->equals('1')
//                    ->sort('created', 'asc')
//                    ->limit(1)
//                    ->getQuery()
//                    ->execute();

                $docs = $portal->findBy([
                    '$or' => [['parentID' => $item->GetUnid()], ['subjectID' => $item->GetUnid()]]
                ], ['created' => "DESC"]);

                if($docs){
                    foreach ($docs as $rr) {

                        if(!$rr->GetAuthorLogin()){
                            $count++;
                            echo "\n\r".$rr->GetUnid()."\n\r";
                        }

                        break;
                    }
                }
            }
            echo $count;
        }
        else {
            echo 'Nothing found.';
        }
        echo "\n\r";
    }

    private function closeAccess(){
        $contactrepo = $this->getContainer()->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        echo "find\n\r";
        $result = $contactrepo->findBy(['$or' => [
            ['ContactStatus' => 14],
            ['ContactStatus' => '14'],
           // ['ContactStatus' => '6'],
            //['ContactStatus' => 6]
        ]]);

        if($result){
            $count = count($result);
            echo "update $count \n\r";
            foreach ($result as $key => $item) {
                echo $item->GetUnid()."\n\r";
                $item->SetAccessOption('1');
                echo 1;
                $dm->persist($item);

                echo "key $key save\n\r";
            }
            $dm->flush();
            echo "done\n\r";
            $dm->clear();
        }
    }

    private function checkContactFor1C(){
        $objRepoContacts = $this->getContainer()->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
        $arrContacts = $objRepoContacts->findBy([
            '$and' => [
                ['$or' => [
                    ['Status' => 'open'],
                    ['Status' => ['$exists' => false]]
                ]
                ],
                ['Deleted' => ['$ne' => '1']],
                ['AccessOption' => ['$ne' => '2']],
                ['DocumentType' => ['$in' => ['Person', 'Organization']]],
                ['C1WaitSync' => '1']
            ]
        ]);

        foreach ($arrContacts as $arrContact) {
            /** @var Contacts $arrContact */
            echo $arrContact->GetUnid(); echo "\n\r";
            echo $arrContact->GetContactName(); echo "\n\r";
        }
    }

    private function removeDouble(){
        $mdHost = $this->getContainer()->getParameter('mongodb_host');
        $mdPort = $this->getContainer()->getParameter('mongodb_port');
        $mdUsername = $this->getContainer()->getParameter('mongodb_username');
        $mdPass = $this->getContainer()->getParameter('mongodb_password');
        $dbName = $this->getContainer()->getParameter('mongodb_db');

        $m = new \MongoClient("mongodb://$mdUsername:$mdPass@$mdHost:$mdPort/$dbName");
        $collection = new \MongoCollection($m->selectDB($dbName), 'Contacts');

        $response = $collection->aggregate([
            ['$group' => [
                "_id" => ['$toLower' => '$ContactName'],
                "Contacts"=>['$push'=>['low' => ['$toLower' => '$ContactName'],'name' => '$ContactName', 'unid' => '$unid', 'Deleted' => '$Deleted']],
                "count"=>['$sum'=> 1 ]
            ]],
            ['$match'=> [
                "count" => ['$gt'=> 1],
                'Status' => ['$ne' => 'deleted'],
                'doubleStatus' => ['$exists' => false],
                '$and' => [
                    ['Deleted' => ['$ne' => '1']],
                    ['Deleted' => ['$ne' => 1]]
                ]

            ]]
        ]);

        /**
         * doubleStatus: 1 = deleted, 2 = exists, 3 = error
         */
        $result = [];
        if(isset($response['result'])){
            foreach ($response['result'] as $key => $item) {
                if(isset($item['Contacts'])){
                    foreach ($item['Contacts'] as $contact) {
                        if(isset($contact['Deleted']) && $contact['Deleted'] == 1){
                            continue;
                        }
                        $result[$key][] = $contact;
                    }
                }
            }
        }

        if($result){
            foreach ($result as $key => $item) {
                if($item && count($item) > 1){
                    echo "--------------\n\r";
                    foreach ($item as $tt) {
                        echo $tt['unid']." ".$tt['name']."\n\r";
                        $res = $this->checkContactService($tt['unid']);
                        if($res === 'false'){
                            $collection->update(['unid' => $tt['unid']], ['$set' => [
                                'Status' => 'deleted',
                                'doubleStatus' => 1,
                                'Deleted' => "1"
                            ]]);
                        }
                        elseif($res === 'true'){
                            $collection->update(['unid' => $tt['unid']], ['$set' => ['doubleStatus' => 2]]);
                        }
                        else {
                            $collection->update(['unid' => $tt['unid']], ['$set' => ['doubleStatus' => 3]]);
                        }
                    }
                }

            }
        }
    }

    private function addCountriesToContacts(){
        $this->dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $coutries = '{"643":[{"fn":"Д","ln":"Абакумов"},{"fn":"Лилия","ln":"Бадрутдинова"},{"fn":"Дмитрий","ln":"Баранов"},{"fn":"Алина","ln":"Беликина"},{"fn":"Сергей","ln":"Бочарников"},{"fn":"Виктор","ln":"Быков"},{"fn":"Руслан","ln":"Гасанов"},{"fn":"Анастасия","ln":"Глущенко"},{"fn":"Илья","ln":"Госпадарев"},{"fn":"Андрей","ln":"Гриненко"},{"fn":"Ирина","ln":"Дремина"},{"fn":"Юрий","ln":"Дробков"},{"fn":"Роман","ln":"Загорудько"},{"fn":"Александр","ln":"Занин"},{"fn":"Дарья","ln":"Ивлева"},{"fn":"Ольга","ln":"Кабаева"},{"fn":"Вадим","ln":"Кавиев"},{"fn":"Игорь","ln":"Кедров"},{"fn":"Сирануш","ln":"Костандян"},{"fn":"Дмитрий","ln":"Кузьмич"},{"fn":"Вера","ln":"Хворостьянова"},{"fn":"Кирилл","ln":"Микрюков"},{"fn":"Станислав","ln":"Неустроев"},{"fn":"Игорь","ln":"Обухов"},{"fn":"Юлия","ln":"Петунова"},{"fn":"Виктор","ln":"Печеникин"},{"fn":"Александра","ln":"Пляскота"},{"fn":"Ирина","ln":"Посконина"},{"fn":"Елена","ln":"Ромашкина"},{"fn":"Ирина","ln":"Русова"},{"fn":"Алена","ln":"Рыбкина"},{"fn":"Дмитрий","ln":"Симонов"},{"fn":"Светлана","ln":"Солодова"},{"fn":"Илья","ln":"Сосновский"},{"fn":"Олег","ln":"Спешилов"},{"fn":"Мария","ln":"Сурьянинова"},{"fn":"Наталья","ln":"Терновая"},{"fn":"Олег","ln":"Трофимов"},{"fn":"Кирилл","ln":"Федоров"},{"fn":"Евгения","ln":"Филатова"},{"fn":"Дарья","ln":"Филинкова"},{"fn":"Эдуард","ln":"Хайбуллин"},{"fn":"Екатерина","ln":"Чернышева"},{"fn":"Александра","ln":"Швецова"},{"fn":"Михаил","ln":"Ширнин"},{"fn":"Ольга","ln":"Ширяева"},{"fn":"Елена","ln":"Ющенко"}],"417":[{"fn":"Айпери","ln":"Абдиева"}],"380":[{"fn":"Елена","ln":"Асоргина"},{"fn":"Сергей","ln":"Кукреш"}],"804":[{"fn":"Анна","ln":"Безбородова"},{"fn":"Максим","ln":"Головненко"},{"fn":"Роман","ln":"Горбунов"},{"fn":"Евгения","ln":"Доценко"},{"fn":"Дмитрий","ln":"Дударев"},{"fn":"Александр","ln":"Иващенко"},{"fn":"Полина","ln":"Игнатенко"},{"fn":"Сергей","ln":"Клименко"},{"fn":"Лилия","ln":"Коноплёва"},{"fn":"Василий","ln":"Костенюк"},{"fn":"Сергей","ln":"Курячий"},{"fn":"Виктория","ln":"Нагорная"},{"fn":"Максим","ln":"Одарич"},{"fn":"Павел","ln":"Павлов"},{"fn":"Максим","ln":"Пелуйко"},{"fn":"Ирина","ln":"Степченко"},{"fn":"Роман","ln":"Туривный"},{"fn":"Ирина","ln":"Ярош"}],"112":[{"fn":"Сергей","ln":"Бурковский"}],"756":[{"fn":"Алия","ln":"Кройшнер"}],"724":[{"fn":"Алена","ln":"Кузнецова"}]}';

        $coutries = json_decode($coutries, true);

        $contactrepo = $this->getContainer()->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
        foreach ($coutries as $code => $country) {
            foreach ($country as $empls) {
                $emplContacts = $contactrepo->findBy(['LastName' => $empls['ln'], 'FirstName' => $empls['fn'], '$or' => [
                    ['ContactStatus' => 14],
                    ['ContactStatus' => '14'],
                ]]);
                foreach ($emplContacts as $emplContact) {
                    /** @var $emplContact Contacts */
                    echo "\n\r".$empls['ln']."\n\r";
                    echo "\n\r".$emplContact->GetUnid()."\n\r";
                    $emplContact->SetCountry([$code]);
                    $emplContact->SetC1WaitSync(true);
                    $this->dm->persist($emplContact);
                }

            }
        }

        $this->dm->flush();
        $this->dm->clear();
    }

    private function checkContactService($unid){
        if($unid){
            $host = $this->getContainer()->getParameter('c1_listeningportal_host');

            if($host){
                $objSOAPClient = new \SoapClient($host, array("cache_wsdl" => 0));

                $jsonItems = json_encode(['unid' => $unid], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
                $strLogText = '';
                try {
                    $strResponse = $objSOAPClient->__soapCall('CompanyIsActual', array(['Request' => $jsonItems]));
                    $strLogText .= "Ответ:\n".$strResponse->return."\n\n";
                    return $strResponse->return;
                } catch (\SOAPFault $exception) {
                    $strLogText .= $objSOAPClient->__getLastRequest();
                    $strLogText .= $objSOAPClient->__getLastRequestHeaders();
                    $strLogText .= $exception->getMessage();
                }
            }
            else {
                echo "\n\rMissing second param - c1_wp_host\n\r";
            }
        }
        else {
            echo "\n\rMissing second param - unid\n\r";
        }
        return 'error';
    }


    private function removeTasks(){
        $host = 'https://te.remote.team';
       // $host = 'http://tretolocal.ru/app_dev.php';
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $portal = $this->getContainer()->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
        $tasks = $portal->findBy(['created' => ['$gte' => '20160915T073500'], 'subject' => 'Превышение срока ответа на письма клиентов']);

        foreach ($tasks as $task) {
            echo "\n\r In work: ".$task->GetUnid()."\n\r";
            /** @var $task Portal */
            $subscribed = $task->getPermissionsByType('subscribed');
            if(isset($subscribed['username'])){
                foreach ($subscribed['username'] as $login) {
                    $unid = $task->GetUnid();
                    $urlCleanNotif = $host."/api/notif/markAsReadForced/$login/$unid";
                    echo "\n\r";
                    echo "clean for $login";
                    echo "\n\r";
                    echo file_get_contents($urlCleanNotif);
                    echo "\n\r";
                }
            }
            $dm->remove($task);
        }
        $dm->flush();
    }

    private function sendBlog(){
        $loggerSynch = $this->getContainer()->get('monolog.logger.sync');
        $i = 0;
        $parent = 0;
        $portal = $this->getContainer()->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
        $blogs = $portal->findBy(['type' => 'Blog', 'ToSite' => '1']);

        /** @var \Treto\PortalBundle\Services\SiteService $siteService */
        $siteService = $this->getContainer()->get('site.service');
        $allDoc = [];
        foreach ($blogs as $blog) {
            ++$parent;
            /** @var $blog Portal */
            $comments = $portal->findBy([
               // 'created' => ['$gte' => '20160201T000000'],
                'NotForSite' => ['$ne' => '1'],
                '$or' => [
                    ['parentID' => $blog->GetUnid()],
                    ['subjectID' => $blog->GetUnid()]
                ]
            ]);

            foreach ($comments as $comment) {
                $allDoc[] = $comment->getDocument();
//                /** @var Portal $comment */
//                echo "\n\rКоммент: ".$comment->GetUnid()."\n\r";
//
//                $loggerSynch->info('('.__CLASS__.' '.__FUNCTION__.') Params: '.json_encode($d, JSON_UNESCAPED_UNICODE));
//                if(isset($d['authorLogin']) && $d['authorLogin']){
//                    $i++;
//                    $siteService->sendComment(['document'=> $d, 'author' => $d['authorLogin'], 'type' => 1]);
//                }
            }
        }
//        $parts = array_chunk($allDoc, 175);
//        foreach ($parts as $key => $item) {
//            file_put_contents('/var/www/test/'.$key.'.json', json_encode($item));
//        }

       // $loggerSynch->info('('.__CLASS__.' '.__FUNCTION__.') AllDoc: '.json_encode($allDoc, JSON_UNESCAPED_UNICODE));
    }
}
