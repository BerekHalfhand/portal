<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Treto\PortalBundle\Document\Question;

class QuestionController extends Controller
{
  public function getCriterionsAction(){
    $repQuest = $this->getRepo('Question');
    $docs = $repQuest->findBy(['$or'=>[['form'=>'Criterion'],['form'=>'Questionary']]]);
    $crits = [];
    $quests = [];
    foreach ($docs as $doc) {
      if ($doc->getForm() === 'Criterion'){
        $crits[$doc->getUnid()] = $doc->getDocument();
        $crits[$doc->getUnid()]['questions'] = [];
      }else{
        $quests[$doc->getUnid()] = $doc->getDocument();
      }
    }

    $qsts = $repQuest->findBy(['form' => 'Question']);
    foreach ($qsts as $quest) {
      if (is_array($quest->getCriterions())){
        foreach ($quest->getCriterions() as $value) {
          if (isset($crits[$value])){
            $crits[$value]['questions'][] = $quest->getDocument();
          }
        }
      }else{
        if (isset($crits[$quest->getCriterions()]))
        $crits[$quest->getCriterions()]['questions'][] = $quest->getDocument();
      }
    }

    return $this->success(['criterions' => $crits, 'questionaries' => $quests]);
  }

  public function getQuestionariesAction(){
    $repQuest = $this->getRepo('Question');
    $docs = $repQuest->findBy(['form'=>'Questionary']);
    $res = [];
    foreach ($docs as $doc) {
      $res[] = ['unid' => $doc->getUnid(), 'name' => $doc->getName()];
    }
    return $this->success(['documents' => $res]);
  }

  public function setAction(Request $request){
    $q = json_decode($request->getContent(), true);
    $doc = $q['query'];

    $repQuest = $this->getRepo('Question');
    if (isset($doc['unid'])){
      $quest = $repQuest->findOneBy(['unid' => $doc['unid']]);
      $quest->setModified();
    }else{
      $quest = new Question($this->getUser());
    }
    $quest->setDocument($doc);
    $this->getDM()->persist($quest);
    $this->getDM()->flush();

    return $this->success(['document' => $quest->getDocument()]);
  }

  public function delAction(){
    $unid = $this->param('unid');
    if (!$unid) return $this->fail('Require unid');

    $repQuest = $this->getRepo('Question');
    $quests = $repQuest->findBy(['Criterions' => $unid]);
    foreach ($quests as $quest) {
      if (is_array($quest->getCriterions())){
        if ( count($quest->getCriterions()) > 1 ) {
          $quest->setCriterions(array_diff($quest->getCriterions(), [$unid]));
        }else{
          $this->getDM()->remove($quest);
        }
      }else{
        $this->getDM()->remove($quest);
      }
    }
    $quest = $repQuest->findOneBy(['unid' => $unid]);
    $this->getDM()->remove($quest);
    $this->getDM()->flush();

    return $this->success();
  }

  public function getQuestionaryAction(){
    $unid = $this->param('unid');
    $contactUnid = $this->param('contactUnid')?$this->param('contactUnid'):false;
    $res = [];

    if ($contactUnid){
      $repContact = $this->getRepo('Contacts');
      $contact = $repContact->findOneBy(['unid' => $contactUnid]);
      $quests = $contact->getHRQuestionsREF();
      if ($quests[$unid]) {
        $repPortal = $this->getRepo('Portal');
        $answer = $repPortal->findOneBy(['unid' => $quests[$unid]]);
        $res = $answer->getQuestionary();
        return $this->success(['questionary' => $res]);
      }
    }

    $repQuest = $this->getRepo('Question');
    $doc = $repQuest->findOneBy(['unid' => $unid]);
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
}