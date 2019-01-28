<?php
namespace Treto\PortalBundle\Services;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

class HipChatService
{
  private $hipchat_team;
  private $hipchat_token;
  private $salt;

  public function __construct($hipchat_team, $hipchat_token, LoggerInterface $logger){
    $this->hipchat_team = $hipchat_team;
    $this->hipchat_token = $hipchat_token;
    $this->logger = $logger;
  }

  public function createUser($fullName, $mail, $login, $password=false){
    if (empty($mail)||empty($fullName)) return false;

    $params = ['name' => $fullName, 'email' => $mail];
    if (!empty($password)) $params['password'] = $password;
    if (!empty($login)) $params['mention_name'] = $login;

    if ($this->sendPost("/v2/user", $params)==201) return true;
    return false;
  }

  public function updateUser($fullName, $mail, $login, $password=false){
    if (empty($mail)||empty($fullName)||empty($login)) return false;
    $params = ['email' => $mail, 'name' => $fullName, 'mention_name' => $login];
    if (!empty($password)) $params['password'] = $password;

    if ($this->sendPut("/v2/user/$mail", $params)==204) return true;
    return false;
  }

  public function deleteUser($mail){
    if (empty($emal)) return false;
    $status = $this->sendDelete("/v2/user/$mail");
    if ($status===204){
      return true;
    }else{
      return $status;
    }
  }

  private function sendPost($action, $fields){
    $url = $this->hipchat_team . $action."?auth_token=$this->hipchat_token";

    $fields = (is_array($fields)) ? json_encode($fields, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) : $fields; 

    if($ch = curl_init($url)) 
    {
      curl_setopt($ch, CURLOPT_POST, true); 
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($fields), 'content-type:application/json')); 
      curl_setopt($ch, CURLOPT_POSTFIELDS, $fields); 
      curl_exec($ch); 

      $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 

      curl_close($ch); 

      return (int) $status;
    }else{
      return false;
    }
  }

  private function sendPut($action, $fields){
    $url = $this->hipchat_team . $action."?auth_token=$this->hipchat_token";

    $fields = (is_array($fields)) ? json_encode($fields, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) : $fields; 

    if($ch = curl_init($url)) 
    {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); 
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($fields), 'content-type:application/json')); 
      curl_setopt($ch, CURLOPT_POSTFIELDS, $fields); 
      curl_exec($ch); 

      $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 

      curl_close($ch); 

      return (int) $status;
    }else{
      return false;
    }
  }

  private function sendDelete($action){
    $url = $this->hipchat_team . $action."?auth_token=$this->hipchat_token";

    if($ch = curl_init($url)) 
    {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE'); 
      curl_exec($ch); 

      $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 

      curl_close($ch); 

      return (int) $status;
    }else{
      return false;
    }
  }
}
?>