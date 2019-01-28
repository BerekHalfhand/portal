<?php
// ConvertStuffCommand.php

namespace Treto\PortalBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Treto\PortalBundle\Document\Notif;

class ConvertStuffCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this ->setName('convert:stuff')
    ->setDescription('Convert various areas of data')
    ->addArgument('type', InputArgument::REQUIRED, 'The type of convertion.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output->writeln([
        'Started execution',
        '============',
    ]);
    $this->dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
    
    if ($input->getArgument('type') == 'discus') 
      $output->writeln($this->reassignDiscussReadPerms($output));
    elseif ($input->getArgument('type') == 'permissions')
      $output->writeln($this->extendPermissions($output));
    elseif ($input->getArgument('type') == 'authorLogins')
      $output->writeln($this->assignAuthorLogins($output));
    elseif ($input->getArgument('type') == 'performers')
      $output->writeln($this->convertPerformers($output));
    elseif ($input->getArgument('type') == 'resend')
      $output->writeln($this->resendNotifications($output));
    elseif ($input->getArgument('type') == 'mentions')
      $output->writeln($this->convertMentions($output));
    elseif ($input->getArgument('type') == 'notif')
      $output->writeln($this->convertNotif($output));
    elseif ($input->getArgument('type') == 'readedby')
      $output->writeln($this->convertReadedby($output));
    elseif ($input->getArgument('type') == 'roles')
      $output->writeln($this->cleanseUserRoles($output));
      
      //CONVERTED count = 12
  }

  protected function reassignDiscussReadPerms(OutputInterface $output) {
    $output->writeln([
        'Started discus subscribed permissions reassignment',
        '============',
    ]);
    $iterator = 0;

    $portal_repo = $this->dm->getRepository('TretoPortalBundle:Portal');
    $contacts_repo = $this->dm->getRepository('TretoPortalBundle:Contacts');
    
    do {
      $docs = $portal_repo->findBy([
        'countMess' => ['$gt' => 0],
        'status' => ['$ne' => 'deleted'],
        'created' => ['$lt' => '20160201T000000'],
        'CONVERTED' => ['$ne' => 6],
      ], null, 100, 0);
      $itemsLeft = sizeof($docs) > 0;
      $output->writeln('Selected '.sizeof($docs).' targets.');
      
      foreach($docs as $doc) {
        $output->writeln('-> Processing '.$doc->GetUnid().' doc.');
        
        $comments = $portal_repo->findBy([
          'form' => ['$in' => ['message','messagebb','formTask','subTotal','formVoting']],
          '$or' => [
            ['parentID' => $doc->GetUnid()],
            ['subjectID' => $doc->GetUnid()]
          ]
        ]);
        $output->writeln('Found '.sizeof($comments).' comments.');
        
        $authors = [];
        foreach($comments as $comment) {
//           $output->writeln($comment->GetUnid().'  ');
          $author = $portal_repo->findEmplByNames([$comment->GetAuthorLogin()], [$comment->GetAuthor()], [$comment->GetAuthorFullNotesName()]);
          if ($author && $author[0] && $author[0]->GetLogin() && !in_array($author[0]->GetLogin(), $authors)) $authors[] = $author[0]->GetLogin();
        }
        
        if ($authors) {
          foreach($authors as $author) {
            if (strpos($author, '.') !== false) continue;
            $output->writeln($author);
            $doc->addReadPrivilege($author, '_convertion');
            $doc->addSubscribedPrivilege($author, '_convertion');
          }
          $this->dm->persist($doc);
        }
        $iterator++;
        $doc->SetCONVERTED(6);
        $output->writeln('Processed '.$iterator.' docs...');
      }
      
      $output->writeln('Flush...');
      $this->dm->flush();
      $this->dm->clear();
    } while($itemsLeft);
    
    return true;
  }
  
  protected function extendPermissions(OutputInterface $output) {
    $output->writeln([
        'Started permissions extension',
        '============',
    ]);
    $iterator = 0;

    $portal_repo = $this->dm->getRepository('TretoPortalBundle:Portal');
    $contacts_repo = $this->dm->getRepository('TretoPortalBundle:Contacts');
    
    $itemsLeft = false;//true;
    do {
      $contacts = $contacts_repo->findBy([
        'Status' => ['$ne' => 'deleted'],
        'form' => 'Contact',
        'CONVERTED' => ['$ne' => 4],
      ], null, 250, 0);
      $output->writeln('Selected '.sizeof($contacts).' targets.');
      $itemsLeft = sizeof($contacts) > 0;

      foreach($contacts as $contact) {
        $output->writeln('-> Processing '.$contact->GetUnid().' contact.');
        
          $security = $contact->getSecurity();
//           $output->writeln(print_r($security, true));
          if ($security && sizeof($security)>0) {
            foreach($security['privileges'] as $action => $privs) {
              $name = false;
              for($i=0; $i<sizeof($privs); $i++) {
                if(is_array($privs[$i])) {
                  if(isset($privs[$i]['ldap'])) {
                    $name = $privs[$i]['ldap'];
                  } elseif(isset($privs[$i]['fullname'])) {
                    $name = $privs[$i]['fullname'];
                  } elseif(isset($privs[$i]['username']) && strpos($privs[$i]['username'], ' ')) {
                    $name = $privs[$i]['username'];
                  }
                  
                  if ($name) {
//                     echo $action.'=>'.$name.'|  ';
                    $login = false;
                    if (isset($usersArray[$name])) {
                      $login = $usersArray[$name];
//                       echo 'login found: '.$login.'. ';
                    } else {
                      $userDoc = $portal_repo->findEmplByNames([$name], [$name], [$name]);
                      if ($userDoc){
                        $userDoc = $userDoc[0];
                        $login = $userDoc->GetLogin();
                        $usersArray[$name] = $login;
                      } else {
//                         echo 'USER NOT FOUND: '.$name.'!!! ';
                      }
                    }
                    if ($login) {
//                       echo ' ('.$login.') ';
                      $privs[$i]['username'] = $login;
                    }
                  }
                  
                }
              }
              $security['privileges'][$action] = $privs;
//               echo $action.' assigned';
            }
            $contact->setSecurity($security);
          }
        
        $currentReadPer = $contact->getPermissionsByType('read');
        if (isset($currentReadPer['username'])) $readPer = $currentReadPer['username'];
        $contact->addActionPrivileges('subscribed', $readPer, '_convertion');
        $currentUnreadPer = $contact->getPermissionsByType('unread');
        if (isset($currentUnreadPer['username'])) $unreadPer = $currentUnreadPer['username'];
        $contact->addActionPrivileges('unsubscribed', $unreadPer, '_convertion');
        if (sizeof($contact->GetAuthorLogin()) == 0 && is_array($contact->GetAuthor())) {
          $authorInDb = $portal_repo->findEmplByNames($contact->GetAuthor(), $contact->GetAuthor(), $contact->GetAuthor());
          if ($authorInDb) {
            $authorDoc = reset($authorInDb);
            $contact->SetAuthorLogin($authorDoc->GetLogin());
          }
        }
        $contact->SetCONVERTED(4);
        $this->dm->persist($contact);

        $iterator++;
        $output->writeln('Processed '.$iterator.' contacts...');
      }
      
      $output->writeln('Flush...');
      $this->dm->flush();
      $this->dm->clear();
    } while ($itemsLeft);
    $output->writeln('Finished. Contacts processed = '.$iterator);
    $iterator = 0;
    
    $itemsLeft = false;
    do {
      $docs = $portal_repo->findBy([
        'Status' => ['$ne' => 'deleted'],
        'parentID' => ['$exists' => false],
        'CONVERTED' => ['$ne' => 4],
        'form' => ['$in' => ['formProcess', 'formTask', 'formVoting', 'formAdapt']],
      ], null, 250, 0);
      $output->writeln('Selected '.sizeof($docs).' targets.');
      $itemsLeft = sizeof($docs) > 0;

      foreach($docs as $doc) {
        $output->writeln('-> Processing '.$doc->GetUnid().' document. Form: '.$doc->GetForm());
        
        $currentReadPer = $doc->getPermissionsByType('read');
        if (isset($currentReadPer['username'])) $readPer = $currentReadPer['username'];
        $doc->addActionPrivileges('subscribed', $readPer, '_convertion');
        $currentUnreadPer = $doc->getPermissionsByType('unread');
        if (isset($currentUnreadPer['username'])) $unreadPer = $currentUnreadPer['username'];
        $doc->addActionPrivileges('unsubscribed', $unreadPer, '_convertion');
        $doc->SetCONVERTED(4);
        $this->dm->persist($doc);

        $iterator++;
        $output->writeln('Processed '.$iterator.' documents...');
      }
      
      $output->writeln('Flush...');
      $this->dm->flush();
      $this->dm->clear();
    } while ($itemsLeft);
    $output->writeln('Finished. Documents processed = '.$iterator);
    $iterator = 0;
    
    return true;
  }
  
  protected function resendNotifications(OutputInterface $output) {
    $output->writeln([
        'Started resending lost notifications',
        '============',
    ]);
    $iterator = 0;

    $portal_repo = $this->dm->getRepository('TretoPortalBundle:Portal');
    
    $itemsLeft = false;
    do {
      $docs = $portal_repo->findBy([
        'Status' => ['$ne' => 'deleted'],
        'form' => 'formTask',
        'TaskStateCurrent' => 10,
        'status' => 'open',
        'authorLogin' => ['$exists' => true, '$ne' => 'portalrobot'],
        'CONVERTED' => ['$ne' => 9],
      ], null, 250, 0);

      $itemsLeft = sizeof($docs) > 0;

      foreach($docs as $doc) {
        $output->writeln('-> Processing '.$doc->GetUnid().' document.');
        
        $main = $portal_repo->findOneBy(['unid'=>$doc->GetSubjectID()]);
        if(!$main) $main = $doc;
        
        $authorLogin = $doc->GetAuthorLogin();
        $output->writeln('authorLogin is '.$authorLogin);
        
        if ($authorLogin) {
          if (!$this->getContainer()->get('notif.service')->hasNotif($doc->GetUnid(), $authorLogin, true)) {
            $this->getContainer()->get('notif.service')->notifAdding($main,
                                                                    $doc,
                                                                    $authorLogin,
                                                                    1,
                                                                    __FUNCTION__.', '.__LINE__,
                                                                    'Added urgent-1 notif to');
          }
          else {$output->writeln('Notif is present '.$authorLogin);}
          
          $doc->SetCONVERTED(9);
          $this->dm->persist($doc);
        }
        else {$output->writeln('Author not found! '.$authorLogin);}
        
        $iterator++;
        $output->writeln('Processed '.$iterator.' documents...');
      }
      
      $output->writeln('Flush...');
      $this->dm->flush();
      $this->dm->clear();
    } while ($itemsLeft);
    $output->writeln('Finished. Documents processed = '.$iterator);
    $iterator = 0;
    
    return 'success';
  }
  
  protected function convertPerformers(OutputInterface $output) {
    $output->writeln([
        'Started converting performers\' LDAP to logins',
        '============',
    ]);
    $iterator = 0;

    $portal_repo = $this->dm->getRepository('TretoPortalBundle:Portal');
    
    $itemsLeft = false;
    do { //CN=Yuriy Vitalevich Drobkov/O=skvirel
      $docs = $portal_repo->findBy([
        'Status' => ['$ne' => 'deleted'],
        'form' => 'formTask',
        'taskPerformerLat' => ['$regex' => '^CN='],
//         'unid' => '0D0E38239C6D8092C325785B00635844',
        'CONVERTED' => ['$ne' => 8],
      ], null, 250, 0);

      $itemsLeft = sizeof($docs) > 0;

      foreach($docs as $doc) {
        $output->writeln('-> Processing '.$doc->GetUnid().' document. Form: '.$doc->GetForm());
        
        $perfLDAP = $doc->GetTaskPerformerLat(true);
        $output->writeln($perfLDAP);
        $perfInDb = null;
        if (!empty($perfLDAP)) {
          if ($perfLDAP == 'CN=Alena Viktorovna Rybkina/O=skvirel')
            $perfLDAP = 'CN=Ruyibkina Alena Viktorovna/O=skvirel';
          else if ($perfLDAP == 'CN=Danil Aleksandrovich Avakumov/O=skvirel')
            $perfLDAP = 'CN=Abakumov Danil Aleksandrovich/O=skvirel';
          else if ($perfLDAP == 'CN=Roman Sergeevich Turivnyy/O=skvirel')
            $perfLDAP = 'CN=Turivnuyiy Roman Sergeevich/O=skvirel';
          else if ($perfLDAP == 'CN=Elena Sergeevna Yushenko/O=skvirel')
            $perfLDAP = 'CN=Yushchenko Elena Sergeevna/O=skvirel';
            
          $perfInDb = $portal_repo->findOneBy(['form' => 'Empl', 'FullName' => $perfLDAP]);
          if (!$perfInDb) {
            $pieces = explode(" ", $perfLDAP);
            if (isset($pieces[2])) {
              $firstPt = explode("=", $pieces[0])[1];
              $secondPt = $pieces[1];
              $thirdPt = explode("/", $pieces[2])[0];
              
              $perfLDAPOld = 'CN='.$thirdPt.' '.$firstPt.' '.$secondPt.'/O=skvirel';
              $output->writeln($perfLDAPOld);
              $perfInDb = $portal_repo->findOneBy(['form' => 'Empl', 'FullName' => $perfLDAPOld]);
            } else {  //beyond saving
              $doc->SetCONVERTED(8);
              $this->dm->persist($doc);
            }
          }
          if ($perfInDb) {
//             $perfDoc = reset($perfInDb);
            $doc->SetTaskPerformerLat([$perfInDb->GetLogin()]);
            $output->writeln($perfLDAP.' => '.$perfInDb->GetLogin());
            $doc->SetCONVERTED(8);
            $this->dm->persist($doc);
          } else $output->writeln($perfLDAP.' not found!');
        }

        $iterator++;
        $output->writeln('Processed '.$iterator.' documents...');
      }
      
      $output->writeln('Flush...');
      $this->dm->flush();
      $this->dm->clear();
    } while ($itemsLeft);
    $output->writeln('Finished. Documents processed = '.$iterator);
    $iterator = 0;
    
    return 'success';
  }
  
  protected function assignAuthorLogins(OutputInterface $output) {
    $output->writeln([
        'Started assigning authorLogins',
        '============',
    ]);
    $iterator = 0;

    $portal_repo = $this->dm->getRepository('TretoPortalBundle:Portal');
    $contacts_repo = $this->dm->getRepository('TretoPortalBundle:Contacts');
    
    $itemsLeft = false;//true;
    do {
      $contacts = $contacts_repo->findBy([
        'Status' => ['$ne' => 'deleted'],
        'form' => 'Contact',
        'authorLogin' => ['$exists' => false],
        'CONVERTED' => ['$ne' => 5],
      ], null, 250, 0);
      
      $itemsLeft = sizeof($contacts) > 0;

      foreach($contacts as $contact) {
        $output->writeln('-> Processing '.$contact->GetUnid().' contact.');
        
        if ($contact->GetAuthor()) {
          $authorInDb = $portal_repo->findEmplByNames($contact->GetAuthor(), $contact->GetAuthor(), $contact->GetAuthor());
          if ($authorInDb) {
            $authorDoc = reset($authorInDb);
            $contact->SetAuthorLogin($authorDoc->GetLogin());
          }
        }
        $contact->SetCONVERTED(5);
        $this->dm->persist($contact);

        $iterator++;
        $output->writeln('Processed '.$iterator.' contacts...');
      }
      
      $output->writeln('Flush...');
      $this->dm->flush();
      $this->dm->clear();
    } while ($itemsLeft);
    $output->writeln('Finished. Contacts processed = '.$iterator);
    $iterator = 0;
    
    $itemsLeft = false;
    do {
      $docs = $portal_repo->findBy([
        'Status' => ['$ne' => 'deleted'],
        'authorLogin' => ['$exists' => false],
        'CONVERTED' => ['$ne' => 5],
      ], null, 250, 0);

      $itemsLeft = sizeof($docs) > 0;

      foreach($docs as $doc) {
        $output->writeln('-> Processing '.$doc->GetUnid().' document. Form: '.$doc->GetForm());
        
        if ($doc->GetAuthor() || $doc->GetAuthorFullNotesName()) {
          $authorInDb = $portal_repo->findEmplByNames($doc->GetAuthor(), $doc->GetAuthor(), $doc->GetAuthorFullNotesName());
          if ($authorInDb) {
            $authorDoc = reset($authorInDb);
            $doc->SetAuthorLogin($authorDoc->GetLogin());
          }
        }
        $doc->SetCONVERTED(5);
        $this->dm->persist($doc);

        $iterator++;
        $output->writeln('Processed '.$iterator.' documents...');
      }
      
      $output->writeln('Flush...');
      $this->dm->flush();
      $this->dm->clear();
    } while ($itemsLeft);
    $output->writeln('Finished. Documents processed = '.$iterator);
    $iterator = 0;
    
    return 'success';
  }
  
  protected function convertMentions(OutputInterface $output) {
    $output->writeln([
        'Started converting mentions',
        '============',
    ]);
    $iterator = 0;

    $portal_repo = $this->dm->getRepository('TretoPortalBundle:Portal');
    $contacts_repo = $this->dm->getRepository('TretoPortalBundle:Contacts');
    
    $itemsLeft = false;
    do {
      $contacts = $contacts_repo->findBy([
        '$and'=> [
          ['parentID' => ['$exists' => false]],
          ['mentions' => ['$exists' => true]],
          ['mentions' => ['$ne' => []]],
          ['CONVERTED' => ['$ne' => 6]]
        ]
      ], null, 250, 0);
      
      $itemsLeft = sizeof($contacts) > 0;

      foreach($contacts as $contact) {
        $output->writeln('-> Processing '.$contact->GetUnid().' contact.');
        
        $mentions = $contact->GetMentions();
        if (is_array($mentions)) {
          foreach($mentions as $mention) {
            $comments = $portal_repo->findBy([
                                                '$and'=> [
                                                  ['$or' => [
                                                    ["subjectID"=> $contact->GetUnid()],
                                                    ["parentUnid"=> $contact->GetUnid()]
                                                  ]],
                                                  ['mentions' => ['$exists' => true]],
                                                  ['mentions' => $mention],
                                                ]
                                             ]);
            $comments = array_reverse($comments);
            $mentionDoc = isset($comments[0]) ? $comments[0] : $doc;
          
            $mentionObj = new \Treto\PortalBundle\Document\Mention($contact->GetUnid(), $mentionDoc->GetUnid(), '_convertation', $mention);
            $this->dm->persist($mentionObj);
          }
        }
        
        $contact->SetCONVERTED(6);
        $contact->SetMentions([]);
        $this->dm->persist($contact);

        $iterator++;
        $output->writeln('Processed '.$iterator.' contacts...');
      }
      
      $output->writeln('Flush...');
      $this->dm->flush();
      $this->dm->clear();
    } while ($itemsLeft);
    $output->writeln('Finished. Contacts processed = '.$iterator);
    $iterator = 0;
    
    $itemsLeft = false;
    do {
      $docs = $portal_repo->findBy([
        '$and'=> [
          ['parentID' => ['$exists' => false]],
          ['mentions' => ['$exists' => true]],
          ['mentions' => ['$ne' => []]],
          ['CONVERTED' => ['$ne' => 6]]
        ]
      ], null, 250, 0);

      $itemsLeft = sizeof($docs) > 0;

      foreach($docs as $doc) {
        $output->writeln('-> Processing '.$doc->GetUnid().' document. Form: '.$doc->GetForm());
        
        $mentions = $doc->GetMentions();
        if (is_array($mentions)) {
          foreach($mentions as $mention) {
            $comments = $portal_repo->findBy([
                                                '$and'=> [
                                                  ['$or' => [
                                                    ["subjectID"=> $doc->GetUnid()],
                                                    ["parentUnid"=> $doc->GetUnid()]
                                                  ]],
                                                  ['mentions' => ['$exists' => true]],
                                                  ['mentions' => $mention],
                                                ]
                                             ]);
            $comments = array_reverse($comments);
            $mentionDoc = isset($comments[0]) ? $comments[0] : $doc;
          
            $mentionObj = new \Treto\PortalBundle\Document\Mention($doc->GetUnid(), $mentionDoc->GetUnid(), '_convertation', $mention);
            $this->dm->persist($mentionObj);
          }
        }
        
        $doc->SetCONVERTED(6);
        $doc->SetMentions([]);
        $this->dm->persist($doc);

        $iterator++;
        $output->writeln('Processed '.$iterator.' documents...');
      }
      
      $output->writeln('Flush...');
      $this->dm->flush();
      $this->dm->clear();
    } while ($itemsLeft);
    $output->writeln('Finished. Documents processed = '.$iterator);
    $iterator = 0;
    
    return 'success';
  }
    
  public function convertNotif(OutputInterface $output) {
//     $result = [];
//     $result['users processed'] = 0;
//     $result['instances converted'] = 0;
//     $repo = $this->dm->getRepository('TretoPortalBundle:Portal');
//     
//     $users = $repo->findBy([ 'form'=>'Empl', '$or' => [ ['DtDismiss'=>''],['DtDismiss'=>['$exists'=>false]] ] ]);
//     foreach($users as $user) {
//       $output->writeln($user->GetLogin());
//       $result['users processed']++;
//       
//       $U1 = $user->GetNotif();
//       if ($U1 && is_array($U1)) {
//         foreach($U1 as $U1_i) {
//           
//           $notifNew = new Notif($U1_i['parentUnid'], $U1_i['unid'], $user->GetLogin(), (isset($U1_i['expired']) && $U1_i['expired']?1:0));
// 
//           $notifNew->SetAuthor($U1_i['Author']);
//           $notifNew->SetAuthorLogin($U1_i['AuthorLogin']);
//           $notifNew->SetSubject($U1_i['subject']);
//           $notifNew->SetModified($U1_i['modified']);
//           $notifNew->SetCreated($U1_i['created']);
//           $notifNew->SetEntryOrder($U1_i['entryOrder']);
//           if (isset($U1_i['flag'])) $notifNew->SetFlag($U1_i['flag']);
//           $notifNew->SetAddedWhen();
//           $notifNew->SetAddedFrom('_conv');
//           $notifNew->SetForm($U1_i['form']);
//           $notifNew->SetParentForm($U1_i['parentForm']);
//           $notifNew->SetIsPublic(1);
//           if (isset($U1_i['docs'])){ 
//             $docs = $U1_i['docs'];
//             $newDocs = [];
//             foreach($docs as $j => $doc){
//               $newDocs[$j] = ['urgency' => (isset($doc['expired']) && $doc['expired'] == true)?1:0,
//                             'subject' => isset($doc['expired'])?$doc['subject']:null,
//                             'timestamp' => isset($doc['timestamp'])?$doc['timestamp']:null];
//             }
//             $notifNew->SetDocs($newDocs);
//           }
//           
//           if ($U1_i['parentForm'] == 'Contact') {
//             $notifNew->SetDocumentType($U1_i['documentType']);
//           } else if ($U1_i['parentForm'] == 'Empl') {
//             $notifNew->SetFields($U1_i['fields']);
//           }
//           
//           $this->dm->persist($notifNew);
//           
//           $result['instances converted']++;
//           
//         }
//       }
//       
//       $this->dm->flush();
//       $this->dm->clear();
// 
//     }
// 
//     $output->writeln('Processed '.$result['users processed'].' users.');
//     $output->writeln('Converted '.$result['instances converted'].' instances.');
    
    return 'success';
  }
  
  public function convertReadedby(OutputInterface $output) {
    $output->writeln([
        'Started converting READEDBY',
        '============',
    ]);
    $iterator = 0;

    $portal_repo = $this->dm->getRepository('TretoPortalBundle:Portal');
    $contacts_repo = $this->dm->getRepository('TretoPortalBundle:Contacts');
    
    $itemsLeft = false;//true;
    do {
      $contacts = $contacts_repo->findBy([
        'Status' => ['$ne' => 'deleted'],
        'form' => 'Contact',
        'READEDBY' => ['$exists' => true],
        'CONVERTED' => ['$ne' => 12],
      ], null, 200, 0);
      
      $itemsLeft = sizeof($contacts) > 0;

      foreach($contacts as $contact) {
        $output->writeln('-> Processing '.$contact->GetUnid().' contact.');
        
        $READEDBY = $contact->GetREADEDBY();
        $readBy = $contact->GetReadBy();
        
        if (!empty($READEDBY)) {
          foreach($READEDBY as $instance) {
            $parts = explode("|", $instance);
            if (!empty($parts[0])) {
                if ($parts[0] == 'Sergey Kukresh') {
                
                  $userInDb = $portal_repo->findEmplByLogin('SKukresh');
                  
                  if ($userInDb) {
                    $userDoc = reset($userInDb);
                    $login = 'SKukresh';
                    $output->writeln($login);
                    
                    if(!empty($login) && !strstr($login, '.') && !isset($readBy[$login])) {
                      $readBy[$login] = $parts[1];
                      $contact->SetReadBy($readBy);
                    }
                  } else $output->writeln('WTF? Kukresh not found!');
                }
              
            }
          }
        }
        
        $contact->SetCONVERTED(12);
        $this->dm->persist($contact);

        $iterator++;
        $output->writeln('Processed '.$iterator.' contacts...');
      }
      
      $output->writeln('Flush...');
      $this->dm->flush();
      $this->dm->clear();
    } while ($itemsLeft);
    $output->writeln('Finished. Contacts processed = '.$iterator);
    $iterator = 0;
    
    $itemsLeft = false;
    do {
      $docs = $portal_repo->findBy([
        'Status' => ['$ne' => 'deleted'],
        'READEDBY' => ['$exists' => true],
        'CONVERTED' => ['$ne' => 12],
      ], null, 200, 0);

      $itemsLeft = sizeof($docs) > 0;

      foreach($docs as $doc) {
        $output->writeln('-> Processing '.$doc->GetUnid().' document. Form: '.$doc->GetForm());
        
        $READEDBY = $doc->GetREADEDBY();
        $readBy = $doc->GetReadBy();
        
        if (!empty($READEDBY)) {
          foreach($READEDBY as $instance) {
            $parts = explode("|", $instance);
            if (!empty($parts[0])) {
                if ($parts[0] == 'Sergey Kukresh') {
                
                  $userInDb = $portal_repo->findEmplByLogin('SKukresh');
                  
                  if ($userInDb) {
                    $userDoc = reset($userInDb);
                    $login = 'SKukresh';
                    $output->writeln($login);
                    
                    if(!empty($login) && !strstr($login, '.') && !isset($readBy[$login])) {
                      $readBy[$login] = $parts[1];
                      $doc->SetReadBy($readBy);
                    }
                  } else $output->writeln('WTF? Kukresh not found!');
                }
              
            }
          }
        }
        
        $doc->SetCONVERTED(12);
        $this->dm->persist($doc);

        $iterator++;
        $output->writeln('Processed '.$iterator.' documents...');
      }
      
      $output->writeln('Flush...');
      $this->dm->flush();
      $this->dm->clear();
    } while ($itemsLeft);
    $output->writeln('Finished. Documents processed = '.$iterator);
    $iterator = 0;
    
    return 'success';
  }
  
    public function cleanseUserRoles(OutputInterface $output) {
    $result = [];
    $result['users processed'] = 0;
    $repo = $this->dm->getRepository('TretoPortalBundle:Portal');
    
    $users = $repo->findBy([ 'form'=>'Empl', '$or' => [ ['DtDismiss'=>''],['DtDismiss'=>['$exists'=>false]] ] ]);
    foreach($users as $user) {
      $output->writeln($user->GetLogin());
      $result['users processed']++;
      
      $roles = $user->GetRole();
      $newRoles = [];
      foreach($roles as $role) {
        if (is_string($role) && $role != '') $newRoles[] = $role;
      }
      if (!in_array('all', $newRoles)) $newRoles[] = 'all';
      
      $user->SetRole($newRoles);
      
      $this->dm->persist($user);
      
      $this->dm->flush();
      $this->dm->clear();

    }

    $output->writeln('Processed '.$result['users processed'].' users.');
    
    return 'success';
  }
  
}
