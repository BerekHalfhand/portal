<?php
namespace Treto\PortalBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Treto\PortalBundle\Document\Portal;

class SearchService
{
  private $searchQuery;

  public function __construct( ContainerInterface $container){
    $this->container = $container;
    $this->context = $container->get('security.context');
    $elastic_host = $this->container->getParameter("elastic_host");
    $elastic_port = $this->container->getParameter("elastic_port");
    $this->elastic_index = $this->container->getParameter("elastic_index");
    $this->host = "$elastic_host:$elastic_port";

    $this->searchQuery =  [ "query" =>
                            [ "bool" =>
                              [ "must" =>
                                  [ "match_all" => [] ],
                                "filter" =>
                                  [ "bool" => [ ] ] ] ] ];
    $this->exceptForms(["empl", "formadapt"]);
  }

  public function getByUnid($type, $unid){
    $result = $this->sendGet("/$this->elastic_index$type/$unid/_source");
    return [ "result" => $result ];
  }

  public function addFields($fields = []){
    $this->searchQuery["fields"] = $fields;
  }
  public function addSourceFields($value = []){
    $this->searchQuery["_source"] = $value;
  }

  public function search($type, $myPart='false', $size=1000){
    
    $res = [];
    $parntDocs = [];
    $start_search = 0;
    $max_output = $size;
    do {
      $this->searchQuery["from"] = strval($start_search);
      $this->searchQuery["size"] = strval($size);
      $results = $this->sendPost("/$this->elastic_index$type/_search", $this->searchQuery);
      
      if (count($results['hits']['hits']) == 0 ) {
        break;
      }
      $start_search = $start_search + $size;

      $parnts = [];
      foreach ($results['hits']['hits'] as $doc) {
        if (!empty($doc['_source']['subjectID'])) $parnts[] = $doc['_source']['subjectID'];
      }

      $portalParents = $this->getRepo('Portal')->findBy(["unid" => ['$in' => $parnts]]);
      $contactParents = $this->getRepo('Contacts')->findBy(["unid" => ['$in' => $parnts]]);
      $parents = array_merge($portalParents, $contactParents);

      foreach ($parents as $par) {
        $parntDocs[$par->getUnid()] = ['form' => $par->getForm(), 'subject' => $par->getSubject(), 'doc' => $par];
      }
      $removed = [];
      foreach ($results['hits']['hits'] as $key => $doc) {
        if (empty($doc['_source']['subjectID']) || empty($parntDocs[$doc['_source']['subjectID']])){
          $main = new Portal();
          $main->setDocument($doc);
          $main->setSecurity($doc['_source']['security']);
        }else{
          $main = $parntDocs[$doc['_source']['subjectID']]['doc'];
        }

        if (!$this->getUser()->can('read', $main, true) || 
            ($myPart == "true" && !$main->hasReadPrivilegeFor($this->getUser()->GetUserName(), true)) ||
            ( !empty($doc['_source']['mailStatus']) && $doc['_source']['mailStatus'] === 'close' &&
              !empty($doc['_source']['mailAccess']) && $doc['_source']['mailAccess'] !== $this->getUser()->getUserName())
        ){
          array_splice($results['hits']['hits'], $key);
        }
      }
      $need = $max_output - count($res);
      $res = array_merge($res, array_slice($results['hits']['hits'], 0, $need));
    }while( count($res) < $max_output );


    return ["result" => $res, "query" => $this->searchQuery, "host"=>$this->host."/$this->elastic_index$type/_search", "parntDocs" => $parntDocs];
  }

  public function setQuery($value=''){
    if (empty($value)){
      $this->searchQuery["query"]["bool"]["must"] = [ "match_all" => [] ];
    }else{
      $this->searchQuery["query"]["bool"]["must"] = $value;
    }
  }

  public function addAllPriveleges(){
    if (empty($this->searchQuery["query"]["bool"]["filter"]["bool"]["should"])) {
      $this->searchQuery["query"]["bool"]["filter"]["bool"]["should"] = [];
    }
    array_push($this->searchQuery["query"]["bool"]["filter"]["bool"]["should"], ["term"=>["security.privileges.read.role"=>"all"]]);
  }

  public function mustSubject(){
    if (empty($this->searchQuery["query"]["bool"]["filter"]["bool"]["filter"]))
      $this->searchQuery["query"]["bool"]["filter"]["bool"]["filter"]["bool"]["must"] = [];
    array_push($this->searchQuery["query"]["bool"]["filter"]["bool"]["filter"]["bool"]["must"], [
      "bool"=>[
        "should"=>[
          ["wildcard"=>["subject"=>"*"]],
          ["wildcard"=>["ContactName"=>"*"]]],
        "minimum_should_match" => "1"]]);
  }

  public function addAuthor($author){
    if (empty($this->searchQuery["query"]["bool"]["filter"]["bool"]["filter"]))
      $this->searchQuery["query"]["bool"]["filter"]["bool"]["filter"]["bool"]["must"] = [];
    array_push($this->searchQuery["query"]["bool"]["filter"]["bool"]["filter"]["bool"]["must"], [
      "bool" => [
        "should" => [
          ["term"=>["authorLogin"=>strtolower($author['login'])]],
          ["term"=>["AuthorFullNotesName"=>strtolower($author['name'])]]],
        "minimum_should_match" => "1"]]);
  }

  public function setSize($value=''){
    $this->searchQuery["query"]["size"] = $value;
  }

  public function sortBy($value, $ord){
    $this->searchQuery['sort'] = [[$value => ['order' => $ord]]];
  }

  public function addMyPart(){
    if (empty($this->searchQuery["query"]["bool"]["filter"]["bool"]["should"])){
      $this->searchQuery["query"]["bool"]["filter"]["bool"]["should"] = [];
    }
    array_push($this->searchQuery["query"]["bool"]["filter"]["bool"]["should"],
              ["term"=>["security.privileges.read.username"=>strtolower($this->getUser()->GetUserName())]]);
  }

  public function setHighlight($value = ''){
    if (empty($value)){
      $this->searchQuery['highlight'] = [
        "pre_tags" => ["<b>"], "post_tags" => ["</b>"], "fields"=>
            ["*"=>["number_of_fragments"=>5, "type" => "fvh"],
             "EmailValues" => ["number_of_fragments"=>0] ]
      ];
    }else{
      $this->searchQuery['highlight'] = $value;
    }
  }

//Private
  private function getUser(){
    return $this->context->getToken()->getUser();
  }


  private function exceptForms($forms=[]){
    if (empty($this->searchQuery["query"]["bool"]["filter"]["bool"]["must_not"])){
      $this->searchQuery["query"]["bool"]["filter"]["bool"]["must_not"] = [];
    }
    array_push($this->searchQuery["query"]["bool"]["filter"]["bool"]["must_not"], ["terms"=>["form"=>$forms]]);
    array_push($this->searchQuery["query"]["bool"]["filter"]["bool"]["must_not"], ["term"=>["status"=>"deleted"]]);
  }

  private function sendPost($addr, $doc){
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => $this->host . $addr,
      CURLOPT_HEADER => false,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => array("Content-type: application/json"),
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => json_encode($doc)
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
        CURLOPT_URL => $this->host . $addr . (!count($request) ? '?' . implode('&', $request) : ''),
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

  /** @return \Doctrine\ODM\MongoDB\DocumentRepository */
  private function getRepo($shortDocumentName) {
    $repo = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:'.$shortDocumentName);
    if($repo instanceof \Treto\PortalBundle\Document\SecureRepository) {
      $repo->releaseUser();
    }
    return $repo;
  }

}
