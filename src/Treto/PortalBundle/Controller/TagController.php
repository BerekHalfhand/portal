<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \Treto\PortalBundle\Services\StaticLogger;
use Treto\PortalBundle\Document\Tag;

class TagController extends AbstractDiscussionController
{
  public function addTagsAction() {
    $unid = $this->param('unid');
    $data = $this->fromJson();
    $tags = $data['tags'];
    
    $dm = $this->getDM();
    
    $portal_rep = $this->getRepo('Portal');
    $tags_rep = $this->getRepo('Tag');
    $docInBase = $portal_rep->findOneBy(['unid' => $unid]);
    $user = $this->getUserPortalData();
    $user = $portal_rep->findEmplByLogin($user->GetLogin());
    
    foreach ($tags as $tag) {
      $tagsInBase = $tags_rep->findBy(['name' => $tag]);
      if (count($tagsInBase) <= 1) {
        if (count($tagsInBase) == 0) {
          $newTag = new Tag();
          $params = ['name' => $tag, 'count' => 1];
          $newTag->setDocument($params, false);
          $newTag->AddUsedBy($user->GetLogin());
          $dm->persist($newTag);
          $dm->flush();
        } else {
          $tagsInBase[0]->IncrementCount();
          $tagsInBase[0]->AddUsedBy($user->GetLogin());
          $dm->persist($tagsInBase[0]);
          $dm->flush();
        }
      } else {
        $maxCount = 0;
        $mainTag = 0;
        for ($i = 0; $i < count($tagsInBase); $i++) {
          if ($tagsInBase[$i]->GetCount() > $maxCount) {
            $maxCount = $tagsInBase[$i]->GetCount();
            $mainTag = $tagsInBase[$i];
          }
        }
        for ($i = 0; $i < count($tagsInBase); $i++) {
          if ($tagsInBase[$i] != $mainTag) {
            $mainTag->SetCount($mainTag->GetCount()+$tagsInBase[$i]->GetCount());
            $mainTag->AddUsedBy(array_keys($tagsInBase[$i]->GetUsedBy()));
            $dm->remove($tagsInBase[$i]);
          }
        }
        $mainTag->IncrementCount();
        $mainTag->AddUsedBy($user->GetLogin());
        $dm->persist($mainTag);
      }

      if(!$docInBase) {
        return $this->fail('document not found');
      }
        
        $result = $docInBase->addTag($tag, $user->GetLogin());
        if(!$result) $this->fail('Failed to add tag');

        $dm->persist($docInBase);
        $dm->flush();
    }

    return $this->success();
  }
  
  public function getTagsAction(Request $request) {
    $user = $this->getUserPortalData();

    $tagToGet = $request->query->get('tag');
    $myOnly = $request->query->get('myonly');
    
    $dd =($this->get('doctrine_mongodb.odm.default_configuration')->getDefaultDB());
    $db = $this->get('doctrine_mongodb.odm.default_connection')->selectDatabase($dd);

    $tags_rep = $db->{'Tag'};
    if ($myOnly == 'false')
      $tags = $tags_rep->find(['name' => ['$regex' => '.*'.$tagToGet.'.*', '$options' => 'i']])->sort(['count' => -1])->limit(25);
    else
      $tags = $tags_rep->find(['name' => ['$regex' => '.*'.$tagToGet.'.*', '$options' => 'i'],
                               'usedBy' => ['$exists' => true],
                               'usedBy.'.$user->GetLogin() => ['$exists' => true],
                               'usedBy.'.$user->GetLogin() => ['$gt' => 0]])
                       ->sort(['count' => -1])->limit(25);


    $result = ['success' => false, 'message' => 'tags not found', 'tags' => []];

    while($tags->hasNext()) {
      $tags->next();
      $cur = $tags->current();
      array_push($result['tags'], array('name'  => $cur['name'],
                                        'count' => $cur['count']));
    }

    if (count($result['tags']) > 0) {
      $result['success'] = true;
      $result['message'] = "ok";
    }

    return new JsonResponse($result);
  }
  
  public function deleteTagAction() {
    $unid = $this->param('unid');
    $data = $this->fromJson();
    $tag = $data['tag'];
    $dm = $this->getDM();
    $portal_rep = $this->getRepo('Portal');
    $user = $this->getUserPortalData();
    $user = $portal_rep->findEmplByLogin($user->GetLogin());
    $docInBase = $portal_rep->findOneBy(['unid' => $unid]);
    
    $tags_rep = $this->getRepo('Tag');
    $tagInBase = $tags_rep->findOneBy(['name' => $tag]);
    
    $result = $docInBase->deleteTag($tag, $user->GetLogin());
    
    if ($tagInBase && $result) {
      $count = $tagInBase->GetCount() - 1;
      if ($count == 0) {
        $dm->remove($tagInBase);
        $dm->flush();
      } else {
        $tagInBase->SetCount($count);
        $dm->persist($tagInBase);
        $dm->flush();
      }
      $tagInBase->RemoveUsedBy($user->GetLogin());
    }
    
    if(! $docInBase) {
      return $this->fail('document not found');
    }

    $dm->persist($docInBase);
    $dm->flush();
    return $this->success();    
  }
  
  public function tagsListAction() {
    $result = array();
    
    $dd =($this->get('doctrine_mongodb.odm.default_configuration')->getDefaultDB());
    $db = $this->get('doctrine_mongodb.odm.default_connection')->selectDatabase($dd);
    
    $tags_rep = $db->{'Tag'};
    $tags = $tags_rep->find([]);
    
    while($tags->hasNext()) {
      $tags->next();
      $cur = $tags->current();
      array_push($result, $cur);
    }
    
    return $this->success(['tags' => $result]);
  }

  public function myTagsListAction() {
    $result = array();
    $user = $this->getUserPortalData();
    $data = $this->fromJson();
    $limit = $this->param('limit', 0);
    
    $dd =($this->get('doctrine_mongodb.odm.default_configuration')->getDefaultDB());
    $db = $this->get('doctrine_mongodb.odm.default_connection')->selectDatabase($dd);
    
    $tags_rep = $db->{'Tag'};
    $tags = $tags_rep->find(['usedBy' => ['$exists' => true], 'usedBy.'.$user->GetLogin() => ['$exists' => true], 'usedBy.'.$user->GetLogin() => ['$gt' => 0]])->sort(['usedBy.'.$user->GetLogin() => -1])->limit($limit);
    
    while($tags->hasNext()) {
      $tags->next();
      $cur = $tags->current();
      array_push($result, $cur);
    }
    
    return $this->success(['tags' => $result]);
  }

  public function popularTagsListAction() {
    $result = array();
    
    $dd =($this->get('doctrine_mongodb.odm.default_configuration')->getDefaultDB());
    $db = $this->get('doctrine_mongodb.odm.default_connection')->selectDatabase($dd);
    
    $tags_rep = $db->{'Tag'};
    $tags = $tags_rep->find([])->sort(['count'=> -1])->limit(100);
    
    while($tags->hasNext()) {
      $tags->next();
      $cur = $tags->current();
      array_push($result, $cur);
    }
    
    return $this->success(['tags' => $result]);
  }
  
  public function findByTagAction($tagname = false, $myOnly = false) {
    if (!$myOnly)
      $myOnly = $this->param('myOnly') == 'true';
    $username = $this->getUserPortalData()->GetLogin();
    if (!$tagname)
      $tagname = $this->param('tag');
      
    $result = array();
    
    $dd =($this->get('doctrine_mongodb.odm.default_configuration')->getDefaultDB());
    $db = $this->get('doctrine_mongodb.odm.default_connection')->selectDatabase($dd);
    
    $portal_rep = $db->{'Portal'};
    $docs = $portal_rep->find(['Tags.name' => $tagname])->sort(['created' => -1]);
    
    while($docs->hasNext()) {
      $docs->next();
      $cur = $docs->current();
      $allowDoc = true;
      if ($myOnly) {
        $allowDoc = false;
        foreach($cur['Tags'] as $tag) {
          if ($tagname == $tag['name'] && isset($tag['users'])) {
            foreach($tag['users'] as $user) {
              if ($user == $username) {
                $allowDoc = true;
                break;
              }
            }
          }
        }
      }
      if ($allowDoc) array_push($result, $cur);
    }
        
    if(!$docs || sizeof($result) == 0) {
      return $this->fail('Documents not found');
    }
    
    $parentIDs = array();
    foreach($result as $doc) {
      if (!empty($doc['parentID'])) $parentIDs[] = $doc['parentID'];
    }
    
    $parents = array();
    $parentsDocs = $portal_rep->find(['unid' => ['$in' => $parentIDs]]);
//     file_put_contents('1.txt', print_r($parentIDs, true));
    while($parentsDocs->hasNext()) {
      $parentsDocs->next();
      $cur = $parentsDocs->current();
      $parents[$cur['unid']] = [];
      $parents[$cur['unid']]['subject'] = $cur['subject'];
      $parents[$cur['unid']]['AuthorRus'] = $cur['AuthorRus'];
      $parents[$cur['unid']]['created'] = $cur['created'];
    }
    
    return $this->success(['docs' => $result, 'parents' => $parents]);
  }
  
  public function deleteTagCompletelyAction() {
    $data = $this->fromJson();
    $tag = $data['tag'];
    $searchRes = $this->findByTagAction($tag, 'true');
    $docs = json_decode($searchRes->getContent(), true);

    if (!$docs || !$docs['success'])
      return $this->fail('Documents not found');

    if (isset($docs['docs']))
      $docs = $docs['docs'];
      
    $username = $this->getUserPortalData()->GetLogin();
    
    $dm = $this->getDM();   
    $portal_rep = $this->getRepo('Portal');
    $tags_rep = $this->getRepo('Tag');
    $tagInBase = $tags_rep->findOneBy(['name' => $tag]);
    $count = $tagInBase->GetCount();

    if (isset($docs)) {
      foreach($docs as $doc) {
        $docInBase = $portal_rep->findOneBy(['unid' => $doc['unid']]);

        $result = $docInBase->deleteTag($tag, $username);
        if(!$result) continue;
        else $count--;
        $dm->persist($docInBase);
        $dm->flush();
      }
    }
    if ($tagInBase) {
      $tagInBase->RemoveUsedBy($username);
      if ($count < 1) {
        $dm->remove($tagInBase);
      } else {
        $tagInBase->SetCount($count);
        $dm->persist($tagInBase);
      }
      $dm->flush();
    }
    
    return $this->success();
  }
  
}
