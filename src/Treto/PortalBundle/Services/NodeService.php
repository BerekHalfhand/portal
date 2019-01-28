<?php
namespace Treto\PortalBundle\Services;

class NodeService
{
  private $io_host;

  public function __construct($io_host){
    $this->io_host = $io_host;
  }

  public function ioNotifyUsers($users){
    return $this->sendPost('/notify', $users);
  }

  public function refreshUsers(){
    return $this->sendGet('/refresh_users');
  }

  public function addComent($unid, $doc){
    return $this->sendPost('/add_comment', ["unid"=>$unid, "doc"=>$doc]);
  }


//Private
  private function sendPost($addr, $doc){
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => $this->io_host . $addr,
      CURLOPT_HEADER => false,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => array("Content-type: application/json"),
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => json_encode($doc),
      CURLOPT_CONNECTTIMEOUT => 2,
      CURLOPT_TIMEOUT => 2
    ));
    $json_response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ( (int)$status !== 200 && (int)$status !== 201 ) {
      return ['success' => false, 'message' => curl_error($curl), 'curl_errno' => curl_errno($curl), 'status' => $status];
    }
    curl_close($curl);
    return json_decode($json_response, true);
  }

  private function sendGet($addr, $params = []){
    $request = [];
    foreach ($params as $key => $value) {
      $request[] = $key . '=' . $value;
    }

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array("Content-type: application/json"),
        CURLOPT_URL => $this->io_host . $addr . (!count($request) ? '?' . implode('&', $request) : ''),
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 2
    ));
    $json_response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ( (int)$status !== 200 && (int)$status !== 201 ) {
      return ['success' => false, 'message' => curl_error($curl), 'curl_errno' => curl_errno($curl), 'status' => $status];
    }
    curl_close($curl);
    return json_decode($json_response, true);
  }

}