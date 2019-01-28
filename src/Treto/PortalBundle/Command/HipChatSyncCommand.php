<?php
// FileRepTestCommand.php

namespace Treto\PortalBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HipChatSyncCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this ->setName('HipChat:UserSync') ->setDescription('Sync work Empls with HipChat') ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    echo "start\n";
    $this->dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
    $portalRepo = $this->dm->getRepository('TretoPortalBundle:Portal');

    $users = $portalRepo->findBy([
      'form' => 'Empl',
      'Login'=> 'jdrobkov',//['$ne' => ''],
      '$or' => [
        [ 'DtDismiss' => ['$exists' => false] ],
        [ 'DtDismiss' => '' ]
      ]
    ]);

    $hipchatService = $this->getContainer()->get('hipchat.service');
    foreach ($users as $user) {
      $email = strtolower($user->getLogin()."@treto.ru");
      echo $user->getLogin()." $email - ";
      $res = $hipchatService->deleteUser($email);
      echo ($res === true) ? 'true' : $res;
      // echo " - ";
      // echo $hipchatService->createUser($user->getLastName()." ".$user->getName(), $user->getEmail(), $user->getLogin());
      // echo $hipchatService->updateUser($user->getName()." ".$user->getLastName(), $user->getEmail(), $user->getLogin());
      echo "\n";
    }
    echo "done";
  }
}
?>