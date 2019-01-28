<?php

namespace Treto\PortalBundle\Controller\v1;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class QuestionController extends ApiController
{
  private function writeLog(){
    $logger = $this->get('monolog.logger.sync');
    $request = $this->getRequest();
    $logger->info(
        '('.__CLASS__.' '.__FUNCTION__.') Path info: "'.$this->getRequest()->server->get('PATH_INFO').
        "\n\rRoute: ".json_encode($request->attributes->get('_route')).
        "\n\rController: ".json_encode($request->attributes->get('_controller')).
        "\n\rParams: ".json_encode($this->params, JSON_UNESCAPED_UNICODE)
    );
  }
  
  public function getAction(){
    $this->writeLog();
    $unid = $this->param('unid');
    $contactUnid = $this->param('contactUnid')?$this->param('contactUnid'):false;
    $res = [];

    if ($contactUnid){
      $repContact = $this->getRepo('Contacts');
      $contact = $repContact->findOneBy(['unid' => $contactUnid]);
      $quests = $contact->getHRQuestionsREF();
      if (isset($quests[$unid])) {
        $repPortal = $this->getRepo('Portal');
        $answer = $repPortal->findOneBy(['unid' => $quests[$unid]]);
        $res = $answer->getQuestionary();
        return $this->success(['questionary' => $res]);
      }
    }

    $repQuest = $this->getRepo('Question');
    $doc = $repQuest->findOneBy(['unid' => $unid, 'form' => 'Questionary']);
    if ($doc) {
      $content = $doc->getContent();
      foreach (explode(';', $content) as $crit) {
         foreach (explode('~#', $crit) as $key => $quest) {
           if ($key > 0){
            $res[] = ['question' => $quest, 'answer' => ''];
           }
         }
       };
    }else{
      return $this->fail('questionary not found');
    }
    return $this->success(['questionary' => $res]);
  }

  public function setAction(){
    $this->writeLog();

    if(isset($this->params['unid']) && isset($this->params['contact']) && isset($this->params['questionary'])){
      $unid = $this->params['unid'];
      $contact = $this->params['contact'];
      $questionary = $this->params['questionary'];

      $repContact = $this->getRepo('Contacts');
      $repPortal = $this->getRepo('Portal');
      $repQuest = $this->getRepo('Question');
      $repoDict = $this->getRepo('Dictionaries');

      $recruter = $repoDict->findOneBy(['type' => "AutoTaskPersons", 'key' => "Рекрутер"]);

      if (isset($contact['unid'])) {
        $contact = $repContact->findOneBy(['unid' => $contact['unid']]);
      }else{
        $contact['DocumentType'] = "Person";
        $contact = $this->robo->createContact($contact);
      }

      if($contact){
        $contact->addReadPrivilege($recruter->getValue(), '_questionctrl');
        $contact->addSubscribedPrivilege($recruter->getValue(), '_questionctrl');

        $questry = $repQuest->findOneBy(['unid' => $unid]);

        if($questry){
          $HRQuestionsREF = $contact->getHRQuestionsREF();

          if (isset($HRQuestionsREF[$unid])){
            $message = $repPortal->findOneBy(['unid' => $HRQuestionsREF[$unid]]);
            if($message){
              $fields = $this->getQuestionaryArrToMessage($questionary, $questry->getName());
              $message->setDocument($fields);
              $this->getDM()->persist($message);
              $this->processNotifications($contact, $message, true);
            }
            else {
              return $this->fail('Not found message by HRQuestionsREF[$unid].');
            }
          } else {
            $message = $this->getQuestionaryArrToMessage($questionary, $questry->getName());
            $message['subjectID'] = $contact->getUnid();
            $message['ParentDbName'] = "Contacts";
            $message = $this->robo->createComment($message);

            $HRQuestionsREF[$unid] = $message->GetUnid();
            $contact->setHRQuestionsREF($HRQuestionsREF);
            $this->processNotifications($contact,$contact,true);
          }

          $this->getDM()->persist($contact);
          $this->getDM()->flush();

          return $this->success(['contactUnid' => $contact->getUnid(), $questry->getName()]);
        }
        else {
          return $this->fail('Not found by unid.');
        }
      }
      else {
        return $this->fail('Not found or create contact.');
      }
    }
    else {
      return $this->fail('Missing require params.');
    }
  }

  protected function getQuestionaryArrToMessage($questionary, $name){
    $result = [];
    $body = '';
    foreach ($questionary as $quest) {
      $question = isset($quest['question'])?$quest['question'].'<br/>':'';
      $answer = isset($quest['answer'])?$quest['answer'].'<br/>':'';
      $body .= $question.$answer.'<br>';
    }
    $result['body'] = $body;
    $result['Questionary'] = $questionary;
    $result['subject'] = 'Опросник "'.$name.'"';
    return $result;
  }

}
