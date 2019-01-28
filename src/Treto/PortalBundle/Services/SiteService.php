<?php
namespace Treto\PortalBundle\Services;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Treto\PortalBundle\Document\Portal;

class SiteService
{
  private $site_host;
  private $salt;
  private $container;
  private $logger;

  public function __construct($site_host, $salt, ContainerInterface $container){
    $this->container = $container;
    $this->site_host = $site_host;
    $this->salt = $salt;
    $this->logger = $this->container->get('monolog.logger.sync');
  }

  /**
   * Set type and send to site
   * @param $main
   * @param $doc
   * @param $user
   */
  public function sendCommentToSite($main, $doc, $user){
    $document = $doc->getDocument(false,false, $user->getRoles());
    $username = isset($document['authorLogin'])?$document['authorLogin']:$user->getPortalData()->GetLogin();
    $document['processed'] = 1;

    /** @var $main Portal */
    if (!in_array($document['form'], ['messagebb', 'formVoting'])) {
      if($main->GetForm() == 'Contact'){
        $type = 7;
      }elseif ($main->getType() == 'Blog'){
        $type = 1;
      }elseif ($document['form'] == 'formTask'){
        $type = 6;
      }elseif ($main->getC2() == 'Вакансии'){
        $type = 2;
      }elseif ($main->getC1() == 'Коллекция'){
        $type = 3;
      }elseif ($main->getC1() == 'Для сайта'){
        $type = 4;
      }elseif ($main->getC1() == 'Заказы'){
        $type = 5;
      }

      if(isset($type) && $type){
        $params = ['document'=> $document, 'author' => $username, 'type' => $type];
        $document['status'] == 'deleted'?$this->delComment($params):$this->sendComment($params);
      }
    }
  }

  public function checkSum($sum){
    return $sum === md5($this->salt . date('Y.m.d'));
  }

  public function sendBlog($publication){
    $response = $this->sendPost('/json/publication/add', $publication);
    return $response;
  }

  public function sendVacancy($vacancy){
    $vacancyHost = $this->container->getParameter('vacancy_host');
    $response = $this->sendPost('/json/career/add', $vacancy, $vacancyHost?$vacancyHost:false);
    return $response;
  }

  public function sendSpecPage($page){
  }

  public function getCollectionsByFactory($unid){
    $response = $this->sendGet('/api/get-article-by-factory/'.$unid, []);
    return json_decode($response, true);
  }

  public function sendProfile($profile){
    $response = $this->sendPost('/json/user/add', $profile);
    return $response;
  }

  public function sendComment($comment){
    $response = $this->sendPost('/portal/comment/add', $comment);
    return $response;
  }

  public function delComment($comment){
    $response = $this->sendPost('/portal/comment/del', $comment);
    return $response;
  }

//Protected
  protected function sendPost($addr, $doc, $url = false){
    $doc = $this->addHash($doc);
    $curl = curl_init();
    $url = $url?$url:$this->site_host;
    $this->logger->info("Запрос на " . $url . $addr);
    $this->logger->info(json_encode($doc, JSON_UNESCAPED_UNICODE));

    curl_setopt_array($curl, array(
      CURLOPT_URL => $url . $addr,
      CURLOPT_USERPWD => "treto:treto$$$$",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => json_encode($doc)
    ));

    $resp = curl_exec($curl);

    $this->logger->info("Ответ"); 
    $this->logger->info($resp);

    $response = json_decode($resp, true);
    curl_close($curl);
    return $response;
  }

  protected function sendGet($addr, $params){
    $params = $this->addHash($params);

    $request = [];
    foreach ($params as $key => $value) {
      $request[] = $key . '=' . $value;
    }

    $this->logger->info("Запрос");
    $this->logger->info($this->site_host . $addr . (!count($request)==0 ? '?' . implode('&', $request) : ''));

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_USERPWD => "treto:treto$$$$",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => $this->site_host . $addr . (!count($request)==0 ? '?' . implode('&', $request) : ''),
    ));
    $response = curl_exec($curl);

    $this->logger->info("Ответ");
    $this->logger->info($response);

    curl_close($curl);

    return $response;
  }

  protected function addHash($req){
    $req['hash'] = md5($this->salt.date('Y.m.d'));
    return $req;
  }
}