<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Treto\PortalBundle\Document\Contacts;

class SerpController extends Controller
{

  public function elaSearchAction(){
    $query = $this->param('query')?$this->param('query'):'';
    $collections = $this->param('collections')?explode(",", $this->param('collections')):[];
    $sort = $this->param('sort');
    $insub = $this->param('insub');
    $params = $this->param('params')?json_decode(base64_decode($this->param('params')), true):[];
    $myPart = $this->param('mypart');
    $strict = $this->param('strict')?$this->param('strict'):'false';

    $morf_fields = function($value) {
        return $value.".morf";
    };
    $origin_fields = function($value) {
        return $value.".origin";
    };

    if ($insub === "true") {
      $fields = ["subject", "ContactName", "FullName", "SiteName"];
      $not_analized_fields = [];
    }else{
      $fields = ["subject", "body", "ContactName", "FullName", "SiteName"];
      $not_analized_fields = [ "EmailValues^2" ];
    }

    $searchService = $this->get('search.service');

    // $searchService->addMyPart();
    // $searchService->addAllPriveleges();

    $fuzzy_query = ["multi_match" =>
                      ["type" => $strict==='true'? "phrase": "best_fields",
                       "fields" => array_merge($not_analized_fields, $fields),
                       "operator" => "and",
                       "query" => $query,
                       "slop" => 1,
                       "fuzziness" => 1
                    ]];
    $strict_query = ["multi_match" =>
                      ["type" => $strict==='true'? "phrase": "best_fields",
                       "fields" => array_merge($not_analized_fields, array_map($origin_fields, $fields)),
                       "operator" => "and",
                       "query" => $query,
                       "boost" => 10
                    ]];

    if (empty($query)){
      $searchService->setQuery();
    }else{
      $searchService->setQuery(["bool" => ["must" => $strict==='true'? $strict_query:$fuzzy_query, "should" => $strict_query]]);
    }

    if ($insub === "true"){
      $searchService->mustSubject();
    }
    
    if (isset($params) && isset($params['Author'])) $author = $params['Author'];
    if (isset($author)) {
      $searchService->addAuthor([ "login" => $author['$in'][0], "name" => $author['$in'][1]]);
    }
    if ($sort === "-created"){
      $searchService->sortBy("created", "desc");
    }

    if (isset($collections[0]) && $collections[0] == "Contacts") {
      $type = "/contact";
    }else{
      $type = "";
    }
    $searchService->setHighlight();

    $results = $searchService->search($type, $myPart, 1000);

    return $this->success(['docs' => $results['result'], 'query'=> $results['query'], 'parents' => $results['parntDocs'], 'host' => $results['host']]);
  }

  public function contactAutoAction(){
    $query = $this->param('text');
    $fields = $this->param('fields');

    $autocomplete_fields = function($value){
      return $value.".autocomplete";
    };

    $fields = array_map($autocomplete_fields, $fields);
    $searchService = $this->get('search.service');
    $searchService->addSourceFields(['security']);
    $searchService->addFields(array_merge($fields));
    $searchService->setQuery(["multi_match" => [
                                           "fields" => $fields,
                                           "operator" => "and",
                                           "slop" => 1,
                                           "query" => $query,
                                           "analyzer" => "keyword"
                                          ]]);

    $results = $searchService->search("/contact", 0, 10);
    $variants = [];
    foreach ($results["result"] as $result) {
      foreach ($fields as $field) {
        if ( !empty($result["fields"][$field]) && !in_array($result["fields"][$field][0], $variants) ){
          array_push($variants, $result["fields"][$field][0]);
        }
      }
    }

    return $this->success(['variants' => $variants]);
  }

  public function autocompleteAction(){
    $q = $this->param('query');
    $collections = isset($q['collections'])?explode(",", $q['collections']):[];
    $sort = $this->param('sort');
    $insub = $this->param('insub');
    $myPart = isset($q['mypart'])?$q['mypart']:false;
    $strict = $this->param('strict')?$this->param('strict'):'false';

    $query = strtolower($q['query']);
    $fields = $this->param('fields');
    $type = $this->param('type')? $this->param('type') : '';

    $autocomplete_fields = function($value){
      return $value.".autocomplete";
    };

    $fields = array_map($autocomplete_fields, $fields);
    $searchService = $this->get('search.service');

    if (isset($q['params']) && isset($q['params']['Author'])) $author = $q['params']['Author'];
    if (isset($author)) {
      $searchService->addAuthor([ "login" => $author['$in'][0], "name" => $author['$in'][1]]);
    }

    if ($q['sort'] === "-created"){
      $searchService->sortBy("created", "desc");
    }

    if (isset($collections[0]) && $collections[0]=="Contacts") {
      $type = "/contact";
    }else{
      $type = "";
    }

    $searchService->addSourceFields(['security']);
    $searchService->setQuery(["multi_match" => [
                                          "type" => "phrase",
                                          "fields" => $fields,
                                          "operator" => "and",
                                          "query" => $query,
                                          "analyzer" => "keyword"
                                          ]]);
    $highlight = [
        "pre_tags" => [''], "post_tags" => [''], "fields"=>
            ["*"=>["number_of_fragments"=>1, "fragment_size"=>40, "type" => "fvh"],
             "EmailValues" => ["number_of_fragments"=>0] ]
      ];
    $searchService->setHighlight($highlight);

    $results = $searchService->search($type, $myPart, 10);
    $variants = [];
    foreach ($results["result"] as $result) {
      foreach ($fields as $field) {
        if ( !empty($result["highlight"][$field]) && !in_array($result["highlight"][$field][0], $variants) ){
          array_push($variants, ["unid" => $result["_id"], "val" => $result["highlight"][$field][0]]);
        }
      }
    }

    return $this->success(['variants' => $variants, 'res' => $results["result"], 'req' => $q ]);
  }
}
