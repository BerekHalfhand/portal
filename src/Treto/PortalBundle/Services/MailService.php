<?php
namespace Treto\PortalBundle\Services;

use MongoDBODMProxies\__CG__\Treto\PortalBundle\Document\Portal;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Treto\PortalBundle\Document\Contacts;

class MailService {
    private $loggerAutotask;
    private $contactsRepo;
    private $portalRepo;
    private $dm;
    /** @var $robotService RoboJsonService  */
    private $robotService;
    private $contacts = [];

    /**
     * MailService constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container){
        $this->container = $container;
        $this->loggerAutotask = $this->container->get('monolog.logger.autotask');
        $this->dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $this->portalRepo = $this->dm->getRepository('TretoPortalBundle:Portal');
        $this->contactsRepo = $this->dm->getRepository('TretoPortalBundle:Contacts');
        $this->robotService = $this->container->get('service.site_robojson');
        $this->loggerAutotask->info(__FILE__.'(line:'.__LINE__.' function:'.__FUNCTION__.') Init class');
    }

    /**
     * Find contacts by email and create comment
     * @param $mails
     * @param $username
     * @return array
     */
    public function findContactsForMails($mails, $username){
        $result = [];
        $username = is_array($username)?$username:[$username];
        $this->loggerAutotask->info(__FILE__.'(line:'.__LINE__.' function:'.__FUNCTION__.') Get mails:'.json_encode($mails, JSON_UNESCAPED_UNICODE));

        foreach ($mails as $mail) {
            $search = $mail['box'] == 'inbox'?$mail['from']:$mail['to'];

            if($search){
                $emailKey = implode('~', $search);
                if(isset($this->contacts[$emailKey])){
                    $contacts = $this->contacts[$emailKey];
                }
                else {
                    $contacts = $this->contactsRepo->findBy([
                        'EmailValues' => $search,
                        'DocumentType' => "Person",
                        '$and' => [
                            ['ContactStatus' => ['$ne' => "14"]],
                            ['ContactStatus' => ['$ne' => 14]]
                        ]
                    ]);
                    $this->contacts[$emailKey] = $contacts;
                }

                if($contacts){
                    foreach ($contacts as $contact) {
                        /** @var $contact Contacts */
                        $contactUnid = $contact->GetUnid();
                        $existComment = $this->portalRepo->findBy([
                            '$or' => [['subjectID' => $contactUnid], ['parentID' => $contactUnid]],
                            'mailHash' => $mail['uniqueHash']
                        ]);

                        if(!$existComment){
                            $time = isset($mail['date'])&&$mail['date']?strtotime($mail['date']):time();
                            $openTags = ['<style', '<STYLE', '<script', '<SCRIPT'];
                            $closeTags = ['</style', '</STYLE', '</script', '</SCRIPT'];
                            $mail['body'] = str_replace($openTags, '<div style="visibility:hidden" ', $mail['body']);
                            $mail['body'] = str_replace(['<base', '<BASE'], '<div" ', $mail['body']);
                            $mail['body'] = str_replace($closeTags, '</div', $mail['body']);

                            $params = [
                                'subjectID' => $contactUnid,
                                'parentID' => $contactUnid,
                                'mailHash' => $mail['uniqueHash'],
                                'subject' => $mail['subject'],
                                'created' => date("Ymd\THis", $time),
                                'body' => $mail['body'],
                                'ParentDbName' => 'Contacts',
                                'mailAccess' => $username,
                                'mailStatus' => 'close'
                            ];

                            if(!in_array($contactUnid, $result)){
                                $result[] = $contactUnid;
                            }

                            $this->loggerAutotask->info(__FILE__.'(line:'.__LINE__.' function:'.__FUNCTION__.') Set params:'.json_encode($params, JSON_UNESCAPED_UNICODE));
                            /** @var Portal $comment */
                            $comment = $this->robotService->createComment($params, false, null, true);
                            //$this->sendNotifyToUserList($username, $comment);
                            //use notifMultipleAdding instead
                        }
                    }
                }
            }
        }

        return $result;
    }
    
    public function sendEmail($from, $to, $subject, $body) {
    
//       $from = [];
//       $from[$this->getUser()->getEmail()] = $this->getUser()->getPortalData()->GetLastName() . " " . $this->getUser()->getPortalData()->GetName();

      $mailLogger = new \Swift_Plugins_Loggers_ArrayLogger();
      $message = \Swift_Message::newInstance()
                  ->setFrom($from)
                  ->setTo($to)
                  ->setSubject($subject)
                  ->setBody($body, 'text/html');

                  //                   ->setBody($this->renderView($templ, array('user' => $this->getUser()->getPortalData()->getDocument(),'contact' => $contact->getDocument(), 'questionary' => $questionary->getDocument())), 'text/html');
                  
      $headers = $message->getHeaders();
      $headers->addTextHeader('X-mailer', 'noreply@tile.expert');
      $msgId = $message->getHeaders()->get('Message-ID');
      $msgId->setId(time() . '.' . uniqid('megaSecretWord') . '@treto.ru');

      $this->container->get('mailer')->send($message);
    }
}
