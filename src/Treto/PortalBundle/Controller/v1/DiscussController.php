<?php

namespace Treto\PortalBundle\Controller\v1;

use Symfony\Component\HttpFoundation\JsonResponse;
use Treto\PortalBundle\Document\Portal;
use Treto\PortalBundle\Services\SynchService;
use Treto\PortalBundle\Services\TaskService;

class DiscussController extends ApiController implements CheckHashInterface
{
    /**
     * Get comments by parent unid
     * only one required param  "unid"
     * @return JsonResponse
     */
    public function getCommentAction(){
        $result = $this->robo->getCommentsByUnid($this->params);
        return $this->success(['result' => $result]);
    }

    /**
     * @param bool $data
     * @return JsonResponse
     * Create theme from API
     * URL: /api/v1/discuss/set/theme
     * Example: {'body':'body', 'subject':'subject', 'Participants':'ddudarev|kfedorov|ssolodova',
     * 'category':'Общекорпоративные', 'type':"Blog", 'ToSite':1}
     * use param 'dontNotify' => 1 for dont nonify participants
     */
    public function setThemeAction($data = false){
        $data = $data?$data:$this->params;
        $params = $this->robo->prepareThemeParams($data, $this->getRequest()->getHost());
        $unid = $this->robo->setTheme(['document' => $params], true, $this->getRequest()->getHost());
        return $this->success(['unid' => $unid]);
    }

    public function setHistoryAction(){
        if(isset($this->params['sendShareFrom'])){
            $this->robo->setTaskHistory([$this->params], $this->getRequest()->getHost(), $this->params['sendShareFrom']);
            return $this->success([]);
        }
        else {
            return $this->fail('Missing sendShareFrom param.');
        }
    }

    public function fullUpdateAction(){
        if(isset($this->params['main']) && isset($this->params['comments'])){
            $response = ['main' => [], 'comments' => []];
            $response['main'] = $this->setThemeAction($this->params['main']);
            foreach ($this->params['comments'] as $comment) {
                $response['comments'][] = $this->setThemeAction($comment);
            }
            return $this->success(['response' => $response]);
        }
        else {
            return $this->fail('Missing "main" or "comments" param.');
        }
    }

    /**
     * Update theme from API
     * URL: /api/v1/discuss/update/theme
     * Example see as setThemeAction + unid param
     * 'Participants' => 'ikedrov|ddudarev' add participants
     * 'removeParticipants' => 'kfedorov|ssolodova' remove participants
     * @return JsonResponse
     */
    public function updateThemeAction(){
        $params = $this->robo->prepareThemeParams($this->params, $this->getRequest()->getHost());
        $result = $this->robo->updatePortalDoc($params, SynchService::$enableShareType, $this->getRequest()->getHost());
        if(isset($result['error']) && $result['error']) {
            return $this->fail($result['error'] === true?'Unknown error':$result['error']);
        }
        else {
            return $this->success();
        }
    }

    /**
     * Create task from API
     * URL: /api/v1/discuss/set/task
     * Example: {'document':{'subjectID':'0A6BD3A0-6C07-1A68-FD9A-1EA6EC4BED12', 'subject':'create tasl',
     * 'body':'body fafafa', 'taskPerformerLat':'145761923356E181216DDA3145761923'}}
     * or taskPerformerLatType == 'logins' and login in taskPerformerLat
     * @return JsonResponse
     */
    public function setTaskAction(){
      $unid = $this->robo->setTask($this->params);
      return $this->success(['unid' => $unid]);
    }

    /**
     * Create comment from API
     * URL: /api/v1/discuss/createComment
     * Example: {'document':{'AuthorRus':'Николай Иванович', 'unid':'28F7F46A-60F4-FB38-C8CB-F6FB7FF4D411',
     * 'subjectID':'F0982B45-B7D7-D449-5FF1-59F5BFAB3639', 'subject':'', 'body':'Тест с сайта'}}
     * @return JsonResponse
     */
    public function createCommentAction(){
        $doc = $this->params['document'];
        $doc['ToSite'] = '1';
        /** @var \Treto\PortalBundle\Services\ConsultService $consultService */
        $consultService = $this->get('consult.service');
        if(isset($doc['email']) && $doc['email']){
            $doc['commentMail'] = $doc['email'];
            unset($doc['email']);
        }
        if(isset($doc['citation']) && isset($doc['citation']['text'])){
            $templateName = 'TretoPortalBundle:Portal:Partials/quoteFromSite.html.twig';
            $quote = $this->get('templating')->render($templateName, ['quote' => $doc['citation']]);
            $doc['body'] = $quote?$quote.$doc['body']:$doc['body'];
        }

        if(isset($doc['subjectID'])){
            $dm = $this->get('doctrine_mongodb');
            /** @var Portal $parentDoc */
            $parentDoc = $dm->getRepository('TretoPortalBundle:Portal')->findOneBy(['unid' => $doc['subjectID']]);
            if(!$parentDoc){
                $parentDoc = $dm->getRepository('TretoPortalBundle:Contacts')->findOneBy(['unid' => $doc['subjectID']]);
            }
        }

        if(isset($parentDoc) && $parentDoc){
            $employeer = isset($doc['employeer']) && trim($doc['employeer'])?$doc['employeer']:\Treto\UserBundle\Document\User::SITE_VISITOR;

            $dm = $this->get('doctrine_mongodb');
            $userRepository = $dm->getRepository('TretoUserBundle:User');
            $authorDoc = $userRepository->findOneBy([
                'username' => $employeer
            ]);

            $message = $this->robo->createComment($doc, $authorDoc?$authorDoc:false);
            $langField = isset($doc['locale']) && (strtolower($doc['locale']) == 'ru' || in_array(strtolower($doc['country']), ['ru', 'ch']))?$doc['locale']:(isset($doc['country'])?$doc['country']:'');

            if(!isset($doc['employeer']) || !trim($doc['employeer'])){
                $consultService->createCommentLocaleTask($message, $langField);
            }

            return $this->success(['unid' => $message->GetUnid()]);
        }
        else {
            $error = 'Not found document by subjectID.';
            $consultService->createDebugTask($doc, $error.'<br/>Line: '.__LINE__.' Function: '.__FUNCTION__.' Class: '.__CLASS__);
            return $this->fail($error);
        }
    }

    /**
     * Check document exist by UNID
     * URL: /api/v1/discuss/check/document
     * Example: {'unid':'28F7F46A-60F4-FB38-C8CB-F6FB7FF4D411'}
     * Success response {success:true, exists:true}
     * Fail response {success:false, message:Missing required param unid.}
     * @return JsonResponse
     */
    public function checkDocumentAction(){
        $params = $this->params;
        if(isset($params['unid'])){
            $result = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal')->findOneBy(['unid' => $params['unid']]);
            return $this->success(['exists' => $result?true:false]);
        }
        else {
            return $this->fail('Missing required param unid.');
        }
    }

    /**
     * Get participants list by unid from API
     * URL: /api/v1/discuss/getParticipants
     * Example request: {'unid' => '5DD85255-BD38-17CE-421C-5EB43585F714'}
     * Example success response: {"success":true,"result":["vpechenikin","kfedorov","ddudarev"]}
     * Example fail response: {"success":false,"message":"Not found document"}
     * @return JsonResponse
     */
    public function getParticipantsAction(){
        $params = $this->params;
        $error = false;
        $result = [];
        if(isset($params['unid']) && $params['unid']){
            $response = $this->robo->getParticipantsByUnid($params['unid']);
            if(!$response['error']){
                $result = $response['result'];
            }
            else {
                $error = $response['error'];
            }
        }
        else {
            $error = 'Missing required param UNID';
        }
        return !$error?$this->success(['result' => $result]):$this->fail($error);
    }

    /**
     * Write mail-comment to contact by email
     * @return JsonResponse
     */
    public function mailToContactAction(){
        $data = json_decode($this->params['data'], true) ;
        $result = [];
        $response = [];

        if(isset($data['mails'])){
            foreach ($data['mails'] as $box => $mails) {
                foreach ($mails as $mail) {
                    $html = isset($mail['html'])?$mail['html']:'';
                    $text = isset($mail['text'])?$mail['text']:'';

                    $to = [];
                    $from = [];

                    if(isset($mail['to']) && is_array($mail['to'])){
                        foreach ($mail['to'] as $mailTo) {
                            if(isset($mailTo['address'])){
                                $to[] = $mailTo['address'];
                            }
                        }
                    }

                    if(isset($mail['from']) && is_array($mail['from'])){
                        foreach ($mail['from'] as $mailFrom) {
                            if(isset($mailFrom['address'])){
                                $from[] = $mailFrom['address'];
                            }
                        }
                    }

                    $body = '';
                    $body .= $from?"<b>From:</b> ".implode(',', $from).'<br/>':'';
                    $body .= $to?"<b>To:</b> ".implode(',', $to).'<br/>':'';
                    $body .= $html?$html:$text;

                    $messageId = isset($mail['messageId'])?$mail['messageId']:'';
                    $subject = isset($mail['subject'])?$mail['subject']:'';
                    $date = isset($mail['date'])?$mail['date']:'';
                    $seqno = isset($mail['date'])?$mail['seqno']:'';

                    $result[] = [
                        'to' => $to,
                        'from' => $from,
                        'subject' => $subject,
                        'date' => $date,
                        'uniqueHash' => md5($messageId.$date.$seqno),
                        'box' => $box,
                        'seqno' => $seqno,
                        'body' => $body,
                        'attachments' => isset($mail['attachments'])?$mail['attachments']:false
                    ];
                }
            }

            if($result && isset($data['username'])){
                /** @var \Treto\PortalBundle\Services\MailService $mailService */
                $mailService = $this->get('mail.service');
                $response = $mailService->findContactsForMails($result, $data['username']);
            }
        }

        return new JsonResponse($response);
    }

    public function checkHashAction(){
        return $this->success();
    }
}
