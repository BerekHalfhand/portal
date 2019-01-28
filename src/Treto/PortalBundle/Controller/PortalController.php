<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PortalController extends Controller
{
  use \Treto\PortalBundle\Services\StaticLogger;
	
  
  public function indexAction()
  {
    $this->log(__CLASS__, __METHOD__, '#/');
		return $this->render('TretoPortalBundle:Portal:index.html.twig');
	}
	

  /**
   * candadate to moving to API family
   */
	public function get_taskAction(Request $request)
  {
		
		$id = $request->query->get('id');
		
		$portal_rep = $this->getRepo('Portal');
		$formProcess = $portal_rep->find($id);
		
		$comments = $portal_rep->findBy(array("parentID"=> $formProcess->getUnid()));
		
		$res['form_process'] = array(
			'id' => $formProcess->getId(),
			'subject' => $formProcess->getSubject(),
			'authorrus' => $formProcess->getAuthorRus(),
			'body' => $formProcess->getBody()
		);
		$res['comments'] = array();
		foreach( $comments as $comment){
			if ($comment->getUnid() != $comment->getParentId()){
				array_push($res['comments'], array(
					'id' => $comment->getId(),
					'authorrus' => $comment->getAuthorRus(),
					'body' => $comment->getBody(),
					'created' => $comment->getCreated(),
					'form' => $comment->getForm() )
				);
			}
		}
		
    $this->log(__CLASS__, __METHOD__, '');
		return new JsonResponse($res); 
	}

  public function getVotingAction(Request $request){
    $portal_rep = $this->getSecureRepo('Portal');
    $offset = $this->param('offset');
    $limit = $this->param('limit');
    $user = $this->getUser()->getPortalData();
    $portals = [];
    if($limit) {
      $portals = $portal_rep->findBy([
        'form' => 'formVoting',
        'status' => ['$ne' => 'deleted'],
        'Author' => ['$ne' => 'Portala Robot'],
        '$or' => [ ['ShowOnIndex' => 1], ['watchedBy' => $user->GetLogin()] ]
      ], ['created' => -1], $limit, $offset);
    } else {
      $portals = $portal_rep->findBy([
        'form' => 'formVoting',
        'status' => 'open',
        '$or' => [['ShowOnIndex' => 1], ['watchedBy' => $user->GetLogin()]] 
      ],
      ['created' => -1]);
    }

    $result = array();

    foreach ($portals as $doc) {
      array_push($result, $doc->getDocument());
    }

    return new JsonResponse($result);
  }

  public function getUsersAction(Request $request){
    $q = json_decode($request->query->get('query'), true);

    $query = ['$and' => [['form' => 'Empl']]];

    if (isset($q["name"])) {
      $nameRegex = "/.*".$q['name'].".*/i";
      if (isset($q['searchInWorkGroup']) && $q['searchInWorkGroup'] === '1') {
        $query['$and'][] = [
                            '$or' => [
                                      ['FullNameInRus' => new \MongoRegex($nameRegex)],
                                      ['WorkGroup' => new \MongoRegex($nameRegex)]
                                    ]
                          ];
      } else {
        $query['$and'][] = ['FullNameInRus' => new \MongoRegex($nameRegex)];
      }
    }

    $portalRepo = $this->getRepo('Portal');
    $users = $portalRepo->findBy($query);

    $result = [];
    foreach ($users as $user)
      $result[] = [
                    'name'        => $user->GetFullNameInRus(),
                    'shortName'   => $user->GetLastName().' '.$user->GetName(),
                    'login'       => $user->GetLogin(),
                    'FullName'    => $user->GetFullName(),
                    'FullNameRaw' => $user->GetFullName(false)
                  ];

    return count($result) > 0 ? $this->success(['result' => $result]) :
                                $this->fail('users not found', ['result' => $result]);
  }

  public function myFavoritesAction(){
    $limit = $this->param('limit', 100);
    $offset = $this->param('offset', 0);

    $favorites = $this->getUser()->getPortalData()->GetFavorites();
    if (!$favorites) {
      $favorites = [];
    }
    $favors = array_slice(array_reverse(array_unique($favorites)), $offset, $limit);

    $portal_favors = [];
    $contact_favors = [];
    $result = [];

    if (!empty($favors)) {
      foreach ($favors as $favor) {
        if (count(explode('|', $favor)) > 1){
          array_push($contact_favors, explode('|', $favor)[0]);
        }else{
          array_push($portal_favors, $favor);
        }
      }
    }else{
      return $this->success(['result' => $result]);
    }
    
    if (count($portal_favors) > 0){
      $qb = $this->getDM()->createQueryBuilder('TretoPortalBundle:Portal');
      $qb = $qb->field('unid')->in($portal_favors);
      $query = $qb->getQuery();
      $docs = $query->execute();
      $preresult = [];
      foreach ($docs as $doc) {
        if ($doc->getDocument() != null)
          array_push($preresult, $doc->getDocument());
      }
    }

    if (count($contact_favors) > 0){
      $qb = $this->getDM()->createQueryBuilder('TretoPortalBundle:Contacts');
      $qb = $qb->field('unid')->in($contact_favors);
      $query = $qb->getQuery();
      $docs = $query->execute();

      foreach ($docs as $doc) {
        if ($doc->getDocument() != null)
          array_push($preresult, $doc->getDocument());
      }
    }

    
    foreach ($favors as $favUnid) {
      foreach ($preresult as $key => $value) {
        if (explode('|', $favUnid)[0] == $value['unid']){
          array_push($result, $value);
          unset($preresult[$key]);
        }
      }
    }
    
    return $this->success(['result' => $result]);
  }

  public function addFavoritesAction(){
    if ($unid = $this->param('unid')){
      $repo = $this->getSecureRepo('Portal');
      $user = $this->getUser()->getPortalData();
      $user = $repo->findEmplByLogin($user->GetLogin());
      $favs = $user->GetFavorites();
      if (empty($favs)){
        $favs = [];
      }
      if (!$repo->findOneBy(array('unid' => $unid)))
        $token = '|crm_clients.angular';
      else
        $token = '';
      array_push($favs, $unid.$token);
      $user->SetFavorites($favs);
      $dm = $this->get('doctrine_mongodb')->getManager();
      $dm->persist($user);
      $dm->flush();
      return $this->success();
    }
    return $this->fail('parameter unid not found');
  }

  public function delFavoritesAction(Request $request){
    if ($unid = $this->param('unid')){
      $repo = $this->getSecureRepo('Portal');
      $user = $this->getUser()->getPortalData();
      $user = $repo->findEmplByLogin($user->GetLogin());
      $favs = $user->GetFavorites();

      if(($key = $this->search_favs($unid, $favs)) !== false) {
        unset($favs[$key]);
        $user->SetFavorites($favs);
        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($user);
        $dm->flush();
        return $this->success();
      } else {
        return $this->fail('nothing to remove');
      }
    }
    return $this->fail('parameter unid not found');
  }
  
  public function getUsernamesForSectionAction() {

    $start = time();

    $repo = $this->getSecureRepo('Portal');
    $data = $this->fromJson();
    $section = $data['section'];
    if(!$section) { return $this->fail('no section specified'); }
    $sections = explode(',',$section);
    $pds = $repo->findBy([
      'form' => 'Empl',
      'Login'=> ['$ne' => ''],
      '$or' => [
        [ 'DtDismiss' => ['$exists' => false] ],
        [ 'DtDismiss' => '' ]
      ],
      'section' => ['$in' => $sections]
    ]);

    if ($data['bySection'] == true) {
      $sections = [];
      foreach($pds as $p) {
          $section = $p->GetSection();
          if(!is_array($section)){
            $section = [$section];
          }
          foreach ($section as $sec) {
            $sections[$sec][$p->GetLogin()] = $p->getFullNameInRus();
          }
      }

      return $this->success(['sections' => $sections, 'time' => (time() - $start)]);
    } else { 
      $usernames = [];
      foreach($pds as $p) {
          $usernames[$p->GetLogin()] = $p->getFullNameInRus();
      }
      return $this->success(['usernames' => $usernames]);
    }
  }

  private function search_favs($unid, $favs){
    foreach ($favs as $key => $value) {
      if (strpos($value, $unid) !== false) {
        return $key;
      }
    }
    return false;
  }
  

}
