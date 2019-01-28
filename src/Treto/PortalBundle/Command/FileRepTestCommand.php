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
use Treto\PortalBundle\Document\Files;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileRepTestCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this ->setName('filesRepository:development') ->setDescription('Test ') ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    //$output->writeln(join(':',[__METHOD__.'()', 'begin']));
    //$logger = $this->getContainer()->get('logger'); 
    //$logger->info(join(':',[__METHOD__.'()' , 'begin']));

    $this->test();

    //$logger->info(join(':',[__METHOD__.'()' , 'end']));
    //$output->writeln(join(':',[__METHOD__.'()', 'end']));
  }

  protected function test() {
    $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
    $rep = $this->getContainer()->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Files');
    $filename = '/tmp/xxx';
    $item2 = $rep->findOrSave($filename, 'Portal' , '002E5BAA0D1A34E7C3257073002DCA3C');
    $dm->persist($item2);
    $dm->flush(null, array('safe' => true, 'fsync' => true));
    $item2 = $rep->findOrSave($filename, 'Portal' , '002E5BAA0D1A34E7C3257073002DCA3C');
    $dm->persist($item2);
    $dm->flush(null, array('safe' => true, 'fsync' => true));
    print_r(($item2->getDocument())); print "\n";
    print_r(($rep->findAsRefs('Portal', '002E5BAA0D1A34E7C3257073002DCA3C')));
  }
}
