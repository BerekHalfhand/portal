<?php
// CheckMailCommand.php

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

class CheckMailCommand extends ContainerAwareCommand
{
    /** @var $logger Logger */
    private $logger;
    /** @var $robotService \Treto\PortalBundle\Services\RoboService */
    private $robotService;
    private $robotUser = false;
    private $dm;
    private $contacts = [];

    /** IMAP SERVER CONFIGS */
    const CONSOLE_OUTPUT = true;
    const CHECK_PERIODICITY = 86400; //In seconds //must be 3600*24

    protected function configure()
    {
        $this->setName('tretoMail:check')->setDescription('TretoMailCheck');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->printToConsole("Initialization.");
        $this->dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $this->robotService = new RoboJsonService($this->getContainer());
        $this->logger = $this->getContainer()->get('logger');
        $this->runCheck();
    }

    /**
     * Control function
     */
    private function runCheck()
    {
        $this->printToConsole("Run.", true);
        $usersAccesses = $this->getAccess();
        foreach ($usersAccesses as $username => $userAccess) {
            $this->printToConsole("Get mails for \"$username\".", true);
            foreach ($userAccess as $access) {
                try {
                    $mails = $this->serverConnect($access);
                    if ($mails) {
                        $mails = $this->formingConversations($mails, $access['username']);
                        $this->writeMailToContacts($mails, $username, $access['username']);
                    }
                } catch (Exception $e) {
                    $this->writeLog($e->getMessage());
                }
            }
        }
        $this->printToConsole("Exit.", true);
    }

    /**
     * Connect to IMAP server, check and return mail
     * @param $userAccess
     * @return array
     */
    private function serverConnect($userAccess)
    {
        $configHost = $this->getContainer()->getParameter('imap_get_host');
        $configPort = $this->getContainer()->getParameter('imap_get_port');
        $configSsl = $this->getContainer()->getParameter('imap_get_ssl') == 'ssl';

        $host = isset($userAccess['server']) ? $userAccess['server']['host'] : $configHost;
        $port = isset($userAccess['server']) ? $userAccess['server']['port'] : $configPort;
        $ssl = isset($userAccess['server']) ? $userAccess['server']['ssl'] : $configSsl;

        $sslConfig = $ssl ? '/ssl' : '';
        $imapConfig = '{' . $host . ':' . $port . $sslConfig . '}';

        $mainConnect = @imap_open(
            $imapConfig,
            $userAccess['username'],
            $userAccess['password'],
            0, 0,
            array('DISABLE_AUTHENTICATOR' => 'CRAM-MD5')
        );

        $mails = [];
        if($mainConnect){
            $this->printToConsole("Successful first connect to \"".self::decodeUtf7($imapConfig)."\".");
            $mailBoxesList = imap_getsubscribed($mainConnect, $imapConfig, '*');

            foreach ($mailBoxesList as $mailbox) {
                if ($mailbox && preg_match('#inbox|sent|Отправленные#Ui', self::decodeUtf7($mailbox->name))) {
                    $this->printToConsole("Connect to \"".self::decodeUtf7($mailbox->name)."\".");
                    $mailResource = imap_open(
                        $mailbox->name,
                        $userAccess['username'],
                        $userAccess['password'],
                        0, 0,
                        array('DISABLE_AUTHENTICATOR' => 'CRAM-MD5')
                    );

                    if ($mailResource) {
                        $this->printToConsole("Success connect to \"".self::decodeUtf7($mailbox->name)."\".");
                        $mails = $this->getMails($mailResource, $mails, $userAccess['username']);
                        $this->printToConsole("Get mails done.");
                        imap_close($mailResource);
                    } else {
                        $this->writeLog('Failed imap_open to ' . $mailResource . '; username ' . $userAccess['username']);
                    }
                }
            }
            imap_close($mainConnect);
        }

        return $mails;
    }

    /**
     * Get mails from mailbox
     * @param $mailResource
     * @param $mails
     * @param $userMail
     * @return array
     */
    private function getMails($mailResource, $mails, $userMail)
    {
        $result = $mails ? $mails : [];
        $since = date("D, d M Y", (time() - self::CHECK_PERIODICITY));
        $this->printToConsole("Search start.");
        $search = imap_search($mailResource, 'SINCE "'.$since.' 00:00:00"');
        $this->printToConsole("Search end.");

        if(is_array($search)){
            foreach ($search as $key => $i) {
                $this->printToConsole("Mail #\"".($key+1)."\" in work.", true);
                $headers = imap_rfc822_parse_headers(imap_fetchheader($mailResource, $i));
                $unixTime = strtotime($headers->date);
                $subject = isset($headers->subject) ? $headers->subject : '';
                if ($subject && preg_match('#^\=\?utf\-8|\=\?KOI8\-R#i', trim($subject))) {
                    $subject = iconv_mime_decode($subject, 0, "UTF-8");
                }
                $subject = str_replace('&quot;', "\"", $subject);
                if (isset($headers->from) && is_array($headers->from) && isset($headers->to) && is_array($headers->to)) {
                    $from = [];
                    $to = [];
                    foreach ($headers->from as $fr) {
                        if(isset($fr->mailbox) && isset($fr->host)){
                            $from[] = imap_utf8($fr->mailbox . '@' . $fr->host);
                        }
                    }
                    foreach ($headers->to as $tt) {
                        if(isset($tt->mailbox) && isset($tt->host)){
                            $to[] = imap_utf8($tt->mailbox . '@' . $tt->host);
                        }

                    }

                    $contacts = $this->findContacts(array_merge($to, $from));
                    $contacts = $this->checkUnique($contacts, $subject, $unixTime);

                    if($contacts){
                        $body = $this->getBody($mailResource, $i);
                        foreach ($from as $f) {
                            $result[$f][] = [
                                'subject' => $subject,
                                'to' => $to,
                                'from' => $from,
                                'time' => $unixTime,
                                'num' => $i,
                                'body' => $body['text'],
                                'attachments' => $body['attachment'],
                                'messageId' => $headers->message_id,
                                'contactUnids' => $contacts
                            ];
                        }

                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get email access from bd
     * @return array
     */
    public function getAccess(){
        $result = [];
        $mdHost = $this->getContainer()->getParameter('mongodb_host');
        $mdPort = $this->getContainer()->getParameter('mongodb_port');
        $mdUsername = $this->getContainer()->getParameter('mongodb_username');
        $mdPass = $this->getContainer()->getParameter('mongodb_password');

        $m = new \MongoClient("mongodb://$mdUsername:$mdPass@$mdHost:$mdPort/Treto");
        $tretodb = $m->selectDB('Treto');
        $collection = new \MongoCollection($tretodb, 'User');
        $users = $collection->find(['mailAccess' => ['$exists' => true]], ['mailAccess' => 1, 'email' => 1, 'username' => 1]);
        if($users){
            foreach ($users as $user) {
                if(isset($user['username']) && isset($user['email']) && isset($user['mailAccess'])){
                    $mainEmail = $user['email'];
                    $access = $user['mailAccess'];
                    $login = $user['username'];

                    if(isset($access['default']) && isset($access['default']['password'])){
                        $result[$login][] = ['username' => $mainEmail, 'password' => $access['default']['password']];
                    }
                    if(isset($access['external']) && is_array($access['external'])){
                        foreach ($access['external'] as $mailAccess) {
                            $result[$login][] = $mailAccess;
                        }
                    }
                }
            }
        }

        return $result;
    }

//    /**
//     * Get email access from bd
//     * @return array
//     */
//    public function getAccess(){
//        $result = [];
//
//        $qb = $this->dm->createQueryBuilder('TretoUserBundle:User');
//        $userRepository = $qb->field('mailAccess')->exists(true)->getQuery();
//        foreach ($userRepository as $key => $item) {
//            /** @var $item \Treto\UserBundle\Document\User */
//            $mainEmail = $item->getEmail();
//            $access = $item->getMailAccess();
//            $login = $item->getUsername();
//            if(isset($access['default']) && isset($access['default']['password'])){
//                $result[$login][] = ['username' => $mainEmail, 'password' => $access['default']['password']];
//            }
//            if(isset($access['external']) && is_array($access['external'])){
//                foreach ($access['external'] as $mailAccess) {
//                    $result[$login][] = $mailAccess;
//                }
//            }
//        }
//        return $result;
//    }


    /**
     * Create comment in "contact"
     * @param $mails
     * @param $username
     * @param $currentCheckEmail
     */
    private function writeMailToContacts($mails, $username, $currentCheckEmail)
    {
        foreach ($mails as $address => $mail) {
            foreach ($mail as $kk => $m) {
                if (!$this->robotUser) {
                    $this->printToConsole("Robot find.");
                    $userRepository = $this->dm->getRepository('TretoUserBundle:User');
                    /** @var $user \Treto\UserBundle\Document\User */
                    $this->robotUser = $userRepository->findOneBy([
                        'username' => \Treto\UserBundle\Document\User::ROBOT_PORTAL
                    ]);
                }

                $body = "To: " . implode(', ', $m['to'])  . "<br/>\n\r";
                $body .= "From: " . implode(', ', $m['from']) . "<br/>\n\r";
                $body .= "Date: " . date('Y-m-d H:i:s', $m['time']) . "<br/>\n\r";
                $body .= "Check email: " . $currentCheckEmail . "<br/>\n\r";
                $body .= isset($m['body']) ? "<br/>\n\r" . nl2br($m['body']) . "\n\r" : '';
                $params = [
                    'subject' => $m['subject'],
                    'body' => $body,
                    'type' => 'mail',
                    'messageId' => $m['messageId']
                ];

                foreach ($m['contactUnids'] as $k => $contactUnid) {
                    $this->printToConsole("Save mail #$k in $contactUnid from $currentCheckEmail.");
                    $this->writeLog("Write message");
                    $params['messageId'] = md5($contactUnid.$m['subject'].$m['time']);
                    $params['subjectID'] = $contactUnid;
                    $params['parentID'] = $contactUnid;
                    $params['security'] = $this->getSecurity($username);

                    $commentUnid = substr(strtoupper(uniqid(time()).uniqid(time())),0,32);
                    if(isset($m['attachments']) && !empty($m['attachments'])){
                        $params['attachments'] = $this->saveAttachments($m['attachments'], $commentUnid);
                    }
                    $this->robotService->createComment($params, $this->robotUser, $commentUnid); //@todo WARNING ******************
                }
            }
        }
    }

    /**
     * Return Security for mail-comment
     * @param $username
     * @return array
     */
    private function getSecurity($username) {
        return [
            'privileges' => [
                'read' => [
                    ['role' => 'PM'],
                    ['username' => $username]
                ],
                'write' => [
                    ['role' => 'PM'],
                    ['username' => $username]
                ],
                'unread' => []
            ]
        ];
    }

    /**
     * @param $attachments
     * @param $unid
     * @return array
     */
    private function saveAttachments($attachments, $unid)
    {
        $files = [];
        $fileController = new \Treto\PortalBundle\Controller\FileSystemController();

        foreach ($attachments as $attachment) {
            $name = '';
            if(isset($attachment['filename']) && $attachment['filename']){
                $name = $attachment['filename'];
            }
            elseif(isset($attachment['name']) && $attachment['name']){
                $name = $attachment['name'];
            }
            if ($attachment['type'] == 'attachment' && $name) {
                $path = sys_get_temp_dir() . '/' . $name;
                $this->printToConsole("Work with file: $path");
                try {
                    file_put_contents($path, $attachment['body']);
                    $filesResult = $fileController->addRecordAction('Portal', $unid, [
                        'container' => $this->getContainer(),
                        'files' => [['tmp_name' => $path, 'name' => $name]]
                    ]);
                    unlink($path);
                    if(isset($filesResult['success']) && $filesResult['success']){
                        $files[] = $filesResult['data'];
                    }
                } catch (Exception $e) {
                    $this->writeLog("Failed write file to" . $path);
                }
            }
        }

        return $files;
    }

    /**
     * Check mail an unique
     * @param $contacts
     * @param $subject
     * @param $unixTime
     * @return mixed
     */
    private function checkUnique($contacts, $subject, $unixTime){
        $portalRepository = $this->dm->getRepository('TretoPortalBundle:Portal');
        $hashes = [];
        foreach ($contacts as $contact) {
            $hashes[] = md5($contact.$subject.$unixTime);
        }
        $results = $portalRepository->findBy(array('messageId' => ['$in' => $hashes]));

        foreach ($results as $result) {
            foreach ($contacts as $key => $contact) {
                if($result->GetMessageId() == md5($contact.$subject.$unixTime)){
                    unset($contacts[$key]);
                }
            }
        }
        return $contacts;
    }

    /**
     * Find contacts unid in bd
     * @param $emails
     * @return array
     */
    private function findContacts($emails){
        $this->printToConsole("Find contacts");
        $result = [];
        foreach ($emails as $email){
            $contactUnid = [];
            if(!isset($this->contacts[$email])){
                $this->printToConsole("FIND: ".$email);
                $contactRepository = $this->dm->getRepository('TretoPortalBundle:Contacts');
                $contacts = $contactRepository->findBy(array('EmailValues' => $email, 'ContactStatus' => ['$ne'=>"14"])); //14 Сотрудник
                if($contacts){
                    foreach ($contacts as $contact) {
                        $result[] = $contact->GetUnid();
                        $contactUnid[] = $contact->GetUnid();
                    }
                    $this->contacts[$email] = $contactUnid;
                }
                else {
                    $this->contacts[$email] = false;
                }
            }
            elseif($this->contacts[$email] !== false) {
                $result = array_merge($result, $this->contacts[$email]);
            }
        }
        return $result;
    }

    /**
     * Encoding mail body
     * @param $connection
     * @param $message_number
     * @param array $subParts
     * @return array
     */
    private function getBody($connection, $message_number, $subParts = [])
    {
        $this->printToConsole("Get body start");
        $attachments = array();

        if (empty($subParts)) {
            $structure = imap_fetchstructure($connection, $message_number);
            $parts = isset($structure->parts) && count($structure->parts) ? $structure->parts : [$structure];
        } else {
            $parts = $subParts;
        }

        foreach ($parts as $i => $part) {
            $attachments[$i] = array(
                'type' => false,
                'body' => ''
            );

            if ($part->type === 0 &&
                $part->ifsubtype &&
                $part->subtype == 'PLAIN') {
                $body = imap_fetchbody($connection, $message_number, $i + 1.1);
                if (!$body) {
                    $body = imap_fetchbody($connection, $message_number, $i + 1);
                }
                $body = $this->convertBody($parts[$i]->encoding, $body);
                $attachments[$i]['type'] = 'text';
                $attachments[$i]['body'] = $body;
            } elseif ($part->type === 1 && isset($part->parts)) {
                $attachments[$i]['type'] = 'subpart';
                $attachments[$i]['body'] = $this->getBody($connection, $message_number, $part->parts);
            } else {
                if ($part->ifdparameters) {
                    foreach ($part->dparameters as $object) {
                        if (strtolower($object->attribute) == 'filename') {
                            $attachments[$i]['type'] = 'attachment';
                            $attachments[$i]['filename'] = $object->value;
                        }
                    }
                }

                if ($part->ifparameters) {
                    foreach ($part->parameters as $object) {
                        if (strtolower($object->attribute) == 'name') {
                            $attachments[$i]['type'] = 'attachment';
                            $attachments[$i]['name'] = $object->value;
                        }
                    }
                }
            }

            if ($attachments[$i]['type'] && $attachments[$i]['type'] == 'attachment') {
                $attachments[$i]['body'] = imap_fetchbody($connection, $message_number, $i + 1);
                if ($part->encoding == 3) { // 3 = BASE64
                    $attachments[$i]['body'] = base64_decode($attachments[$i]['body']);
                } elseif ($part->encoding == 4) { // 4 = QUOTED-PRINTABLE
                    $attachments[$i]['body'] = quoted_printable_decode($attachments[$i]['body']);
                }
                //$attachments[$i]['body'] = ''; //@todo warning /*****************************
            }
        }

        if (empty($subParts)) {
            $attachments = $this->sortSubParts($attachments);
            $attachments = $this->separateAttachments($attachments);
        }
        $this->printToConsole("Get body done");
        return $attachments;
    }

    /**
     * Transformation nested array in one-dimensional array
     * @param $attachments
     * @return array
     */
    public function sortSubParts($attachments)
    {
        $result = [];
        foreach ($attachments as $key => $attachment) {
            if (isset($attachment['type']) && $attachment['type']) {
                if ($attachment['type'] == 'subpart') {
                    $result = array_merge($result, $this->sortSubParts($attachment['body']));
                    unset($attachments[$key]);
                } else {
                    $result[] = $attachment;
                }
            }
        }
        return $result;
    }

    /**
     * Separate attachments-content from Text-content
     * @param $attachments
     * @return array
     */
    public function separateAttachments($attachments)
    {
        $result = ['text' => '', 'attachment' => []];
        foreach ($attachments as $attachment) {
            switch ($attachment['type']) {
                case 'attachment':
                    $result['attachment'][] = $attachment;
                    break;
                case 'text':
                    $result['text'] .= $attachment['body'] . "\n\r";
                    break;
            }
        }
        return $result;
    }

    /**
     * Convert text encoding
     * @param $encoding
     * @param $body
     * @return string
     */
    private function convertBody($encoding, $body)
    {
        switch (trim($encoding)) {
            case 0:
                $body = imap_utf7_encode($body);
                break;
            case 1:
                $body = imap_8bit($body);
                break;
            case 2:
                $body = imap_binary($body);
                break;
            case 3:
                $body = imap_base64($body);
                break;
            case 4:
                $body = imap_qprint($body);
                break;
        }
        return mb_convert_encoding(str_replace('&DQo-', "\n\r", $body), "UTF-8", "auto");
    }

    /**
     * Forming mail conversations
     * @param $mails
     * @param $username
     * @return mixed
     */
    private function formingConversations($mails, $username)
    {
        $outbox = isset($mails[$username]) ? $mails[$username] : [];

        /** Link outbox to inbox */
        if ($outbox && $mails) {
            foreach ($outbox as $key => $outMail) {
                foreach ($outMail['to'] as $to) {
                    $mails[$to][] = $outMail;
                }
            }
            unset($mails[$username]);
        }

        /** Sort conversations */
        foreach ($mails as $key => $mail) {
            uasort($mails[$key], function ($a, $b) {
                return $a['time'] < $b['time'] ? -1 : 1;
            });
        }

        return $mails;
    }

    /**
     * Write error log
     * @param $message
     * @param bool $line
     */
    private function writeLog($message, $line = false)
    {
        $m = 'CHECK MAIL ' . __METHOD__;
        $m .= $line ? " line: " . $line : '';
        $m .= " MESSAGE: $message";
        $this->logger->error($m);
    }

    /**
     * Decode from utf-7
     * @param $s
     * @return string
     */
    static function decodeUtf7($s)
    {
        $res = '';
        $n = strlen($s);
        $h = 0;
        while ($h < $n) {
            $t = strpos($s, '&', $h);
            if ($t === false) $t = $n;
            $res .= substr($s, $h, $t - $h);
            $h = $t + 1;
            if ($h >= $n) break;
            $t = strpos($s, '-', $h);
            if ($t === false) $t = $n;
            $k = $t - $h;
            if ($k == 0) $res .= '&';
            else $res .= self::decodeB64imap(substr($s, $h, $k));
            $h = $t + 1;
        }
        return $res;
    }

    static private function decodeB64imap($s)
    {
        $imap_base64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+,';
        $a = 0;
        $al = 0;
        $res = '';
        $n = strlen($s);
        for ($i = 0; $i < $n; $i++) {
            $k = strpos($imap_base64, $s[$i]);
            if ($k === FALSE) continue;
            $a = ($a << 6) | $k;
            $al += 6;
            if ($al >= 8) {
                $res .= chr(($a >> ($al - 8)) & 255);
                $al -= 8;
            }
        }
        $r2 = '';
        $n = strlen($res);
        for ($i = 0; $i < $n; $i++) {
            $c = ord($res[$i]);
            $i++;
            if ($i < $n) $c = ($c << 8) | ord($res[$i]);
            $r2 .= self::encodeUtf8Char($c);
        }
        return $r2;
    }

    static private function encodeUtf8Char($w)
    {
        if ($w & 0x80000000) return '';
        if ($w & 0xFC000000) $n = 5; else
            if ($w & 0xFFE00000) $n = 4; else
                if ($w & 0xFFFF0000) $n = 3; else
                    if ($w & 0xFFFFF800) $n = 2; else
                        if ($w & 0xFFFFFF80) $n = 1; else return chr($w);
        $res = chr(((255 << (7 - $n)) | ($w >> ($n * 6))) & 255);
        while (--$n >= 0) $res .= chr((($w >> ($n * 6)) & 0x3F) | 0x80);
        return $res;
    }

    /**
     * Print to console with color
     * @param $text
     * @param bool $red
     * @param bool $back
     */
    private function printToConsole($text, $red = false, $back = false){
        if(self::CONSOLE_OUTPUT){
            $text = $red?"\033[1;41m$text\033[m":$text;
            $text = $back?"\033[1A$text":$text;
            echo $text."\n\r";
        }
    }
}






