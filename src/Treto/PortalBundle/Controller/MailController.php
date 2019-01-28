<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MailController extends Controller
{
  public function sendQuestionaryAction(){
    $user = $this->getUser()->getEmail();
    $contactUnid = $this->param('contact');
    $questionaryUnid = $this->param('questionary');

    $repoContact = $this->getRepo("Contacts");
    $repoQuestionary = $this->getRepo("Question");

    $contact = $repoContact->findOneByUnid($contactUnid);
    $questionary = $repoQuestionary->findOneByUnid($questionaryUnid);
    $hitlist = false;
    if ($questionary->getName() != "ХитЛист") {
      $subject = 'Опрос для Tile.Expert';
      $templ = 'Emails/questionary.html.twig';  // app/Resources/views/Emails/questionary.html.twig
    }else{
      $subject = 'Анкета для Tile.Expert';
      $templ = 'Emails/hitlist.html.twig';
      $hitlist = true;
    }

    $from = [ "hr@tile.expert" => "HR" ];
    // $from[$this->getUser()->getEmail()] = $this->getUser()->getPortalData()->GetLastName() . " " . $this->getUser()->getPortalData()->GetName();

    $mailLogger = new \Swift_Plugins_Loggers_ArrayLogger();
    $message = \Swift_Message::newInstance()
                ->setFrom($from)
                ->setTo($contact->getEmailValues()[0])
                ->setSubject($subject)
                ->setBody($this->renderView($templ, array('user' => $this->getUser()->getPortalData()->getDocument(),'contact' => $contact->getDocument(), 'questionary' => $questionary->getDocument())), 'text/html');
    $headers = $message->getHeaders();
    $headers->addTextHeader('X-AUTOSENDER', "Portal");
    $msgId = $message->getHeaders()->get('Message-ID');
    $msgId->setId(time() . '.' . uniqid('megaSecretWord') . '@tile.expert');

    if ($this->get('mailer')->send($message)){
      if (!$hitlist) {
        $contact->SetHRQuestionsLinkGeneratedBy(array_values($from)[0]);
        $contact->SetHRQuestionsLinkGeneratedDate();
      }else{
        $contact->SetHitListLinkGeneratedBy(array_values($from)[0]);
        $contact->SetHitListLinkGeneratedDate();
      }
      $this->getDM()->persist($contact);
      $this->getDM()->flush();
      return $this->success(['hitlist' => $hitlist]);
    }else{
      return $this->fail($mailLogger->dump());
    }
  }
}
?>
