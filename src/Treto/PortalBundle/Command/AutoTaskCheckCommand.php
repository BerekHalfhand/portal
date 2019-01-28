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
use Treto\PortalBundle\Document\Files;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Treto\PortalBundle\Services\RoboJsonService;

class AutoTaskCheckCommand extends ContainerAwareCommand
{
    private $dm;
    private $logger;

    protected function configure()
    {
        $this->setName('autoTask:check')->setDescription('AutoTaskCheck')->addArgument(
            'checkType',
            InputArgument::OPTIONAL
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('checkType');
        if($type){
            $this->preparation();
            $this->logger->info('Run autotask command; type = '.$type);
            switch($type){
                case 'birthday':
                    $this->checkBirthdayRun();
                    break;
                case 'dismiss':
                    $this->dismissCheckRun();
                    break;
            }
        }
    }

    private function dismissCheckRun(){
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->getContainer()->get('fos_user.user_manager');
        $portal = $this->getContainer()->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
        $users = $portal->findBy(['DtDismiss' => date('Ymd'), 'form' => 'Empl']);
        if($users){
            foreach($users as $empl){
                /** @var $empl \Treto\PortalBundle\Document\Portal */
                $user = $userManager->findUserByUsername($empl->GetLogin());
                /** @var \Treto\UserBundle\Document\User $user */
                if($user && $user->getEnabled()){
                    $robo = new \Treto\PortalBundle\Services\RoboJsonService($this->getContainer());
                    $robo->dismissUser($user);
                    $this->logger->info('Disable user '.$empl->GetLogin());
                }
            }
        }
    }

    /**
     * Preparation to run
     */
    private function preparation(){
        $this->logger = $this->getContainer()->get('monolog.logger.autotask');
        $this->dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
    }

    /**
     * Checking days of birth and employees to create a task reminder
     */
    private function checkBirthdayRun(){
        $portalRepository = $this->dm->getRepository('TretoPortalBundle:Portal');
        $tomorrow = date('md', time()+(60*60*24));

        $param = [
            'form' => 'Empl',
            'Birthday'=> new \MongoRegex("/....$tomorrow/"),
            '$or' => [
                ['DtDismiss'=>['$exists'=>false]],
                ['DtDismiss'=>'']
            ]
        ];

        $empls = $portalRepository->findBy($param);
        $taskBody = '';
        foreach ($empls as $empl) {
            $taskBody .= $empl->GetLastName().' '.$empl->GetName().' '.$empl->GetMiddleName()."<br/>\n\r";
        }
        if($taskBody){
            $date = date('d-m', time()+(60*60*24));
            $pd = count($empls)>1?'празднуют':'празднует';
            $taskBody .= 'завтра '.$date.' '.$pd.' День рождения.';

            $robo = $this->getContainer()->get('service.site_robojson');
            /** @var \Treto\PortalBundle\Services\RoboService $robo */
            $hr = $robo->getAutoTaskPersonByKey('Рекрутер');

            if($hr){
                $robo->setTask(['document' => [
                    'body' => $taskBody,
                    'form' => 'formTask',
                    'readSecurity' => [$hr],
                    'status' => 'open',
                    'subject' => 'Напоминание',
                    'taskPerformerLat' => $hr,
                    'taskPerformerLatType' => 'logins'
                ]]);

                $this->logger->info("Create birthday task. Body = ".$taskBody);
            }
        }
    }
}
