<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Treto\PortalBundle\Document\Contacts;
use Treto\PortalBundle\Document\Portal;
use Treto\PortalBundle\Document\SecureDocument;
use Treto\PortalBundle\Document\C1Log;
use Treto\PortalBundle\Document\PreviousVersions;

class ContactController extends AbstractDiscussionController
{
    const CONTACTS_LIMIT = 20;
    const CONTACTS_OFFSET = 0;

    use \Treto\PortalBundle\Services\StaticLogger;
    public function groupsAction(){
        $contact_rep = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');

        $settings = $contact_rep->findOneBy(array("unid" => "99999999999999999999999999999999"));
        $groups = $contact_rep->findBy(array("DocumentType" => "Group"));

        $res = array();
        foreach ($settings->getStatusList() as $value) {
            array_push($res, array("unid" => explode("|", $value)[1], "name" => explode("|", $value)[0]));
        }

        foreach ($groups as $group) {
            array_push($res, array("unid" => $group->getUnid(), "name" => $group->getName(), "groupId" => $group->getGroupId()));
        }

        return new JsonResponse($res);
    }

    /**
     * Add holiday to factory
     * @return JsonResponse
     */
    public function addHolidayAction(){
        $param = $this->fromJson();
        if(isset($param['unid']) && isset($param['from']) && isset($param['to'])){
            $from = strtotime($param['from']);
            $to = strtotime($param['to']);
            if(!$from || !$to || $from >= $to){
                return $this->fail('Invalid date.');
            }
            $contactRepo = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
            $contact = $contactRepo->findOneBy(['unid' => $param['unid']]);
            if($contact){
                /** @var $contact Contacts */
                $oldIn = $contact->GetinHoliday()?$contact->GetinHoliday():[];
                $oldOut = $contact->GetoutHoliday()?$contact->GetoutHoliday():[];
                if(count($oldIn) != count($oldOut)){
                    $oldIn = [];
                    $oldOut = [];
                }
                $oldIn[] = $param['from'];
                $oldOut[] = $param['to'];

                $contact->SetinHoliday($oldIn);
                $contact->SetoutHoliday($oldOut);
                $contact->SetC1WaitSync('1');
                $this->getDM()->persist($contact);
                $this->getDM()->flush();

                $holidayResponse = [];
                foreach ($oldIn as $key => $in) {
                    $holidayResponse[] = date('d.m.Y', strtotime($in))  .' - '.date('d.m.Y', strtotime($oldOut[$key]));
                }

                return $this->success(['holiday' => $holidayResponse]);
            }
            else {
                return $this->fail('Not found contact by unid.');
            }
        }
        else {
            return $this->fail('Missing some required params.');
        }
    }

    /**
     * Get factories holiday by BM
     * @return JsonResponse
     */
    public function getBmFactoriesAction(){
        $param = $this->fromJson();

        if(isset($param['bmLogin'])){
            $portalRepo = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');

            $bmEmpl = $portalRepo->findOneBy(['form' => 'Empl', 'Login' => $param['bmLogin']]);
            if($bmEmpl){
                /** @var $bmEmpl Portal */
                $bmContactUnid = $bmEmpl->GetContactUnid();
                $contactRepo = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');

                $bmFactories = $contactRepo->findBy([
                  'form' => 'Contact',
                  'DocumentType' => 'Organization',
                  '$or' => [['ContactStatus' => 11], ['ContactStatus' => '11']],
                  'ResponsibleManager_ID' => $bmContactUnid
                ]);

                if($bmFactories){
                    $result = [];
                    foreach ($bmFactories as $bmFactory) {
                        /** @var $bmFactory Contacts */
                        $inHoliday = $bmFactory->GetinHoliday()?$bmFactory->GetinHoliday():[];
                        $outHoliday = $bmFactory->GetoutHoliday()?$bmFactory->GetoutHoliday():[];
                        $error = '';
                        $holiday = [];
                        if(is_array($inHoliday) && is_array($outHoliday) && count($inHoliday) == count($outHoliday)){
                            foreach ($inHoliday as $key => $in) {
                                $holiday[] = date('d.m.Y', strtotime($in))  .' - '.date('d.m.Y', strtotime($outHoliday[$key]));
                            }
                        }
                        else {
                            $error = 'Invalid holiday format';
                        }

                        $result[] = [
                            'unid' => $bmFactory->GetUnid(),
                            'contactName' => $bmFactory->GetContactName(),
                            'mainName' => $bmFactory->GetMainName(),
                            'otherName' => $bmFactory->GetOtherName(),
                            'holiday' => $holiday,
                            'error' => $error
                        ];
                    }

                    return $this->success(['factories' => $result]);
                }
                else {
                    return $this->fail('Not found factories by ResponsibleManager '.$param['bmLogin']);
                }
            }
            else {
                return $this->fail('Not found empl obj by "bmLogin"');
            }
        }
        else {
            return $this->fail('Missing require param "bmLogin"');
        }
    }

    public function listAction(Request $request){
        $limit = $this->param('limit', self::CONTACTS_LIMIT);
        $offset = $this->param('offset', self::CONTACTS_OFFSET);

        $query = $this->param('query');
        $q = array();
        if (is_array($query)) {
            foreach ($query as $key => $value) {
                $q[$key] = $value;
            }
        }
        $search_array = [];
        $search_array['$and'] = [];
        if (!isset($q["Status"])) {
            $search_array['$and'][] = [
                '$or' => [
                    ['Status' => ['$ne' => 'deleted']],
                    ['Status' => ['$exists' => false]]
                ]
            ];
        }

        $usr = $this->getUser();
        $portalData = $usr->getPortalData();
        $roles = $portalData->GetRole();
        $form = isset($q['form'])?$q['form']:'';
        if (!$this->getUser()->hasRole('PM') && $form != 'formDiscount') {
            if (!empty($usr) && !empty($portalData)) {
                $userName = $usr->getUserName();

                $search_array['$and'][]['$or'] = [
                  ["security.privileges.read.role" => ['$in' => $roles]],
                  ["security.privileges.read.username" => $userName],
                ];
            }
        }

        $contact_rep = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');

        if (isset($q["group"])) {
          $search_array['$and'][] = ['$or' => [
              ['ContactStatus' => $q['group']['search']],
              ['Group' => $q['group']['name']]
          ]];
        };
        
        //file_put_contents('q.txt', print_r($q ,true));
        $sString = '';
        if (is_array($q) && array_key_exists("text", $q)) {
            $sString = $q['text'];
            if ($q['type']['search'] == 'manager') {
                $q['type']['search'] = 'ContactName';
                $search_array['$and'][]['isHomeOrganization'] = ['$in' => ['1']];
            }
            
            if (!is_array($q['type']['search'])) {
                $search_array['$and'][][$q['type']['search']] = ['$regex' => new \MongoRegex("/$sString/i")];
            }
            else {
              $l = sizeof($search_array['$and']);
              $searchArray = $this->prepareSearchString(explode(' ', trim($sString)));
              foreach ($q['type']['search'] as $v) {
                  $searchArray = in_array($v, ["ContactName","MainName","OtherName","Full Name"])?$searchArray:[trim($sString)];
                  foreach ($searchArray as $s) {
                      $search_array['$and'][$l]['$or'][][$v] = ['$regex' => new \MongoRegex("/".trim($s)."/i")];
                  }
              }
            }
        }

        if (isset($q["contact"])) {
            $search_array['$and'][]['DocumentType'] = $q['contact']['search'];
        } elseif (!isset($q["form"]) || $q["form"] !== 'formDiscount') {
            $search_array['$and'][]['DocumentType'] = ['$in' => ['Organization', 'Person']];
        }

        if (isset($q["form"])) {
            $search_array['$and'][]['form'] = $q['form'];
        }

        if (isset($q["ContactId"])) {
            $search_array['$and'][]['ContactId'] = $q['ContactId'];
        }

        if (isset($q["Status"])) {
            $search_array['$and'][]['Status'] = $q['Status'];
        }
        // file_put_contents('1.txt', print_r($search_array ,true));
        $contacts = $contact_rep->findBy($search_array, array('created' => "DESC"), $limit, $offset);

        $res = array();

        foreach ($contacts as $key => $value) { {
                $debug = $value->getDocument();
                $debug['debug_offset'] = $offset;
                array_push($res, $debug);
            }
        };

        $this->log(__CLASS__, __METHOD__, '#/contact/list', 'contact list');
        return new JsonResponse($res);
    }

    /**
     * Link person contact ro organization
     * @return JsonResponse
     */
    public function linkEmplToOrgAction(){

        $personUnid = $this->param('personUnid');
        $orgUnid = $this->param('orgUnid');

        if($personUnid && $orgUnid && $this->changeOrganizationLink($personUnid, $orgUnid)){
            return $this->success();
        }

        return $this->fail('Error');
    }

    /**
     * Link person contact ro organization
     * @return JsonResponse
     */
    public function removeEmplFromOrgAction(){

        $personUnid = $this->param('personUnid');
        $orgUnid = $this->param('orgUnid');

        if($personUnid && $orgUnid && $this->changeOrganizationLink($personUnid, $orgUnid, true)){
            return $this->success();
        }

        return $this->fail('Error');
    }

    /**
     * change organization link for person
     * @param $personUnid
     * @param $orgUnid
     * @param bool $remove
     * @return bool
     */
    private function changeOrganizationLink($personUnid, $orgUnid, $remove = false){
        $result = false;
        $contactRepository = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
        $dm = $this->getDM();
        /** @var Contacts  $organizationContact */
        /** @var Contacts $personContact */
        $personContact = $contactRepository->findOneBy(['unid' => $personUnid]);
        $organizationContact = $contactRepository->findOneBy(['unid' => $orgUnid]);

        if($personContact && $personContact->GetDocumentType() == 'Person'
            && $organizationContact && $organizationContact->GetDocumentType() == 'Organization'){
            $personContact->SetOrganization(!$remove?[$organizationContact->GetContactName()]:[]);
            $personContact->SetOrganizationID(!$remove?[$orgUnid]:[]);
            $personContact->SetC1WaitSync('1');
            $dm->persist($personContact);
            $dm->flush();
            $result = true;
        }

        return $result;
    }

    /**
     * Returns all possible combinations
     * @param $searchArray
     * @param int $depth
     * @return array
     */
    private function  prepareSearchString($searchArray, $depth = 0)
    {
        $result = [];
        $countSearchArray = count($searchArray);

        if ($depth == $countSearchArray) {
            $finishStr = '';
            for ($i = 0; $i < $countSearchArray; $i++) {
                $finishStr .= trim($searchArray[$i]) . ' ';
            }
            $result[] = $finishStr;
        } else {
            for ($i = $depth; $i < $countSearchArray; $i++) {
                $v = $searchArray[$depth];
                $searchArray[$depth] = $searchArray[$i];
                $searchArray[$i] = $v;
                $result = array_merge($result, $this->prepareSearchString($searchArray, $depth + 1)) ;
                $v = $searchArray[$depth];
                $searchArray[$depth] = $searchArray[$i];
                $searchArray[$i] = $v;
            }
        }
        return $result;
    }

    public function itemAction(Request $request)
    {
        $id = $request->query->get('id');
        if (!$id)
            $id = basename($_SERVER['REQUEST_URI']);
        if (!$id) {
            print('null id ');
        }

        $item = $this->findItem($id);
        if ($item) {
            $this->log(__CLASS__, __METHOD__, '#/discus/' . $id . '/contact', 'Просмотр контакта ' . $id . ': ' . $item->getContactName());
            $ret = $item->getDocument(true, true);
            $this->setReadByTime($item->GetUnid());
            return new JsonResponse($ret);
        } else {
            header("HTTP/1.1 404 Not Found");
            return new JsonResponse(array('errors' => array('Missing id ' . $id)));
        }
    }

    private function updateProfile($contactFields){
        if(isset($contactFields['unid']) && $contactFields['unid']){
            $dm = $this->getDM();
            $versRepo = $this->getRepo('PreviousVersions');
            /** @var Portal $empl */
            $empl = $this->getRepo('Portal')->findOneBy(['form' => 'Empl', 'contactUnid' => $contactFields['unid']]);
            if($empl){
                $isChanged = false;
                
                $docHist = new PreviousVersions($empl->GetUnid(), 'Portal', $this->getUserPortalData()->GetLogin(), $empl->toArray());

                if(isset($contactFields['section']) && is_array($contactFields['section'])){
                    $isChanged = true;
                    $empl->SetSection($contactFields['section']);
                }
                if(isset($contactFields['Rank']) && is_array($contactFields['Rank'])){
                    $isChanged = true;
                    $empl->SetWorkGroup($contactFields['Rank']);
                }

                if($isChanged){
                    $dm->persist($docHist);
                    $dm->persist($empl);
                    $dm->flush();
                }
            }
        }
    }

    public function saveAction(Request $request){
        try {
            $q = json_decode($request->getContent(), true);
            $contact = $q['contact'];
            $result = [];
            $res = [];
            if (!is_array($contact)){
                throw new \Exception('data is in wrong format');
            }

            $this->updateProfile($contact);
            /** @var $item Contacts */
            if(isset($contact['id'])){
                $id = $contact['id'];
                $item = $this->updateItem($id, $request);
            } else {
                $item = $this->insertItem($request);
                if (!($item) || !($item->getId())) {
                    throw new \Exception("Contact has not been saved");
                }
            }

            if($item->GetResponsibleManager_ID()){
                $repo = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
                /** @var Portal $responsibleManager */
                $responsibleManager = $repo->findOneBy([
                    'form' => 'Empl',
                    'contactUnid' => $item->GetResponsibleManager_ID()
                ]);

                if($responsibleManager){
                    $item->addReadPrivilege($responsibleManager->GetLogin(), $this->getUserPortalData()->GetLogin());
                    $item->addSubscribedPrivilege($responsibleManager->GetLogin(), $this->getUserPortalData()->GetLogin());
                }
            }

            $objDM = $this->getDM();
            if($item->getForm() === 'formDiscount') {
                $objDM->persist($item);
                $objDM->flush();
                $this->sendInfoTo1C($item);
            } else {
                if(!isset($id)){
                    $result = $this->processNotifications($item, $item, true, false, false, false, true);
                }

                $objDM->flush();
            }

            if($item->getContactStatus() && in_array(14, $item->getContactStatus()) && isset($contact['Login'])) {  //если сотрудник
                $result['sendProfileResponse'] = $this->sendProfile($contact['Login']);
            }

            return $this->success(['response' => $item->getDocument(1, 1), 'result' => $result, 'res' => $res]);
        } catch (\Exception $x) {
            header("HTTP/1.1 500 Internal Server Error");
            return new JsonResponse($x->getMessage());
        }
    }
    
    public function findAction(Request $request) {
        if($name = $this->param('name')){
          $param = ['FullName' => $name];
        }
        elseif($email = $this->param('Email')){
          $param = ['EmailValues' => trim($email)];
        }
      $contact = $this->getRepo('Contacts')->findOneBy($param);
      if ($contact) {
        return $this->success(['response' => $contact->getDocument(true, true)]);
      } else {
        return $this->fail('No such user');
      }
    }

    public function deleteAction(Request $request)
    {
        try {
            $id = $request->query->get('id');
            $item = $this->findItem($id);
            if (!$item) {
                throw new \Exception("contact with id $id is missing");
            }
            $cmgr = $this->get('doctrine.odm.mongodb.document_manager');
            $item->SetStatus('deleted');
            $item->SetDeleted('1');
            $cmgr->flush();
            $strLogText ='';
            if ($item->getForm() === 'formDiscount') {
              $strLogText = $this->delDiscountFrom1C($item);
            }
            
            $this->log(__CLASS__, __METHOD__, '#/discus/' . $id . '/contact/', 'Удаление конакта ' . $id);
            $res = $item->getDocument(0, 0);
            return new JsonResponse(['Status'=>$res['Status'], '1cResp'=>$strLogText]);
        } catch (\Exception $x) {

            header("HTTP/1.1 500 Internal Server Error");
            return new JsonResponse($x);
        }
    }

    public function permanentDeleteAction(Request $request)
    {

        try {
            $id = $request->query->get('id');
            $item = $this->findItem($id);
            if (!$item) {
                throw new \Exception("contact with id $id is missing");
            }
            $cmgr = $this->get('doctrine.odm.mongodb.document_manager');
            $cmgr->remove($item);
            $cmgr->flush();
            $this->log(__CLASS__, __METHOD__, '#/discus/' . $id . '/contact/', 'Удаление конакта ' . $id);
            return new JsonResponse(['result' => true]);
        } catch (\Exception $x) {

            header("HTTP/1.1 500 Internal Server Error");
            return new JsonResponse($x);
        }
    }

    public function undeleteAction(Request $request){
        try {
            $id = $request->query->get('id');
            $item = $this->findItem($id);
            if (!$item) {
                throw new \Exception("contact with id $id is missing");
            }
            $cmgr = $this->get('doctrine.odm.mongodb.document_manager');
            $item->SetStatus('open');
            $item->SetDeleted('');
            $cmgr->flush();
            
            $this->log(__CLASS__, __METHOD__, '#/discus/' . $id . '/contact/', 'Возврат конакта ' . $id);
            return new JsonResponse($item->getDocument(0, 0));
        } catch (\Exception $x) {

            header("HTTP/1.1 500 Internal Server Error");
            return new JsonResponse($x);
        }
    }

    public function setOldAction(Request $request)
    {

        try {
            $id = $request->query->get('id');
            $item = $this->findItem($id);
            if (!$item) {
                throw new \Exception("contact with id $id is missing");
            }
            $cmgr = $this->get('doctrine.odm.mongodb.document_manager');
            $item->setOldDiscount('1');
            $cmgr->flush();
            
            $this->log(__CLASS__, __METHOD__, '#/discus/' . $id . '/contact/', 'Скидка не действительна ' . $id);
            return new JsonResponse($item->getDocument(true, true));
        } catch (\Exception $x) {

            header("HTTP/1.1 500 Internal Server Error");
            return new JsonResponse($x);
        }
    }

    public function acceptedAction(Request $request){
        try {
            $id = $request->query->get('id');
            $repo = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
            $item = $repo->findOneBy(array('unid' => $id));

            if (!$item) {
                throw new \Exception("contact with id $id is missing");
            }
            $cmgr = $this->get('doctrine.odm.mongodb.document_manager');
            $objUser = $this->getUser()->getPortalData();
            $item->setDiscountAccepted('1');
            $item->setDiscountAcceptedDate();
            $item->setDiscountAcceptor($objUser->GetFullNameInRus());
            $item->setDiscountAcceptorID($objUser->GetUnid());

            $cmgr->flush();
            
            $this->log(__CLASS__, __METHOD__, '#/discus/' . $id . '/contact/', 'Скидка акцептирована ' . $id);
            return new JsonResponse($item->getDocument(true, true));
        } catch (\Exception $x) {

            header("HTTP/1.1 500 Internal Server Error");
            return new JsonResponse($x);
        }
    }

    /**
     * @param $id
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    protected function updateItem($id, $request){
        $versRepo = $this->getRepo('PreviousVersions');
        $item = $this->findItem($id);
        if (!$item) {
            throw new \Exception("contact with id $id is missing");
        }
        /** @var $item Contacts */
        $oldContactName = $item->GetContactName();
        
        $docHist = new PreviousVersions($item->GetUnid(), 'Contacts', $this->getUserPortalData()->GetLogin(), $item->toArray());
        $this->getDM()->persist($docHist);
        $this->getDM()->flush();
        
        $this->setFieldsFromRequest($item, $request);
        $item->SetModified();
        $cmgr = $this->get('doctrine.odm.mongodb.document_manager');
        $cmgr->persist($item);
        if($item->GetDocumentType() == 'Organization' && $oldContactName != $item->GetContactName()){
            /** @var \Treto\PortalBundle\Services\RoboService $robo */
            $robo = $this->get('service.site_robojson');
            $robo->changePersonOrganizationName($item);
        }
        $cmgr->flush(null, array('safe' => true, 'fsync' => true));
        $this->log(__CLASS__, __METHOD__, '#/discus/' . $id . '/contact/', 'Контакт изменен:' . $item->getContactName());
        return $item;
    }

    protected function insertItem($request)
    {
        $item = new Contacts($this->getUser());
        $this->setFieldsFromRequest($item, $request);

        $q = json_decode($request->getContent(), true);
        if (array_key_exists('AutoDicomposition', $q['contact']) && array_key_exists('textAuto', $q['contact'])) {
            $item->prepareAutoDicomposition($q['contact']['textAuto']);
        }

        $repo = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');

        if ($item->getDocumentType() === 'Person') {
            $portal = false;
            if ($item->GetPortalUser_ID()) {
                $portal = $repo->findOneBy(['$or' => [ ['FullName' => $item->getUserNotesName()], ['PortalUser_ID' => $item->GetPortalUser_ID()]]]);
            } elseif ($item->getUserNotesName()) {
                $portal = $repo->findOneBy(['FullName' => $item->getUserNotesName()]);
            }
            if ($portal) {
                return false;
            }
        }

        if ($item->getDocumentType() === 'Organization' && in_array('11', $item->getGroup())) {
            $item->setDiscountAccepted('0');
        }

        if ($item->getForm() === 'formDiscount') {
            $item->setEditor('[all]');
        }

        $cmgr = $this->get('doctrine.odm.mongodb.document_manager');
        $item->SetModified();
        $cmgr->persist($item);
        $cmgr->flush(null, array('safe' => true, 'fsync' => true));
        
        $id = $item->getId();
        $this->log(__CLASS__, __METHOD__, '#/discus/' . $id . '/contact/', 'Контакт создан:' . $item->getContactName());
        return $item;
    }

    protected function setFieldsFromRequest(&$item, $request){
        $q = json_decode($request->getContent(), true);
        $q = $q['contact'];
        if(isset($q['form']) && $q['form'] != 'formDiscount'){
            $q = \Treto\PortalBundle\Document\Contacts::editContactName($q, $q['DocumentType']);
        }
        if($q['DocumentType'] == 'Person' && (!isset($q['Sex']) || !$q['Sex'])){
            $q['Sex'] = '0';
        }
        if (isset($q['security']) && !empty($q['security'])) {
            $item->setSecurity($q['security']);
        }
        if(isset($q['ContactStatus'])){
            $needle = false;
            foreach ($q['ContactStatus'] as $status) {
                if($status == 6 || $status == 14){ //empl or candidate
                    $needle = true;
                    break;
                }
            }

            if($needle){
                $item->SetAccessOption('1');
                unset($q['security']);
                unset($q['AccessOption']);
            }
        }

        $item->setDocument($q, null, [], $this->getUser());
        $item->SetModified();
    }

    protected function findItem($id){
        $repo = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
        $item = $repo->findOneBy(array('_id' => $id));
        return $item;
    }

    /**
     * autocomplete method
     *
     */
    public function getCompaniesAction(Request $request)
    {
        $q = json_decode($request->query->get('query'), true);

        $qb = $this->getDM()->createQueryBuilder('TretoPortalBundle:Contacts');
        $qb = $qb->field('DocumentType')->equals('Organization');
        if (isset($q["name"])) {
            $sString = "/.*" . $q['name'] . ".*/i";
            $qb = $qb->field('ContactName')->equals(new \MongoRegex($sString));
        }
        $query = $qb->getQuery(); #limit(10)->getQuery();
        $items = $query->execute();
        $result = ['success' => false, 'message' => 'not found with name', 'result' => []];
        foreach ($items as $doc) {
            array_push($result['result'], ['name' => $doc->getContactName(), 'unid' => $doc->getUnid()]);
        }
        if (count($result['result']) > 0) {
            $result['success'] = true;
            $result['message'] = "ok";
        }

        return new JsonResponse($result);
    }

    /**
     * autocomplete method
     *
     */
    public function getPersonsAction(Request $request)
    {
        $q = json_decode($request->query->get('query'), true);

        $qb = $this->getDM()->createQueryBuilder('TretoPortalBundle:Contacts');
        $qb = $qb->field('DocumentType')->equals('Person');
        if (!empty($q['rank'])){
          $qb = $qb->field('Rank')->equals($q['rank']);
        }
        if (isset($q["name"])) {
            $sString = "/.*" . $q['name'] . ".*/i";
            $qb = $qb->field('ContactName')->equals(new \MongoRegex($sString));
        }
        $query = $qb->getQuery(); #limit(10)->getQuery();
        $items = $query->execute();
        $result = ['success' => false, 'message' => 'not found with name', 'result' => []];
        $repoPortal = $this->getRepo('Portal');
        foreach ($items as $doc) {
            /** @var $doc \Treto\PortalBundle\Document\Contacts */
            $push = ['name' => $doc->getContactName(), 'unid' => $doc->getUnid()];
            /** @var $objPortal \Treto\PortalBundle\Document\Portal */
            $objPortal = $repoPortal->findOneBy([
                '$or' => [
                    ['unid' => $doc->GetPortalUser_ID()],
                    ['FullName' => $doc->getUserNotesName()],
                    [
                        'name' => $doc->GetFirstName(),
                        'LastName' => $doc->GetLastName(),
                        'MiddleName' => $doc->GetMiddleName(),
                    ]
                ],
                'form' => 'Empl'
            ]);

            if($objPortal){
                $push['login'] = $objPortal->getLogin();
            }
            array_push($result['result'], $push);
        }
        if (count($result['result']) > 0) {
            $result['success'] = true;
            $result['message'] = "ok";
        }

        return new JsonResponse($result);
    }

    public function getCollectionsAction(Request $request)
    {
        $result = [];
        if ($id = $this->param('id')) {
            $result = $this->get('site.service')->getCollectionsByFactory($id);
        }
        return new JsonResponse($result);
    }

    public function sendInfoTo1C($item){
        $item = $this->findItem($item->getId());
        $strLogSubject = 'Экспорт скидок';
        $strLogText = '';
        $objDM = $this->getDM();
        $params = [];
        if($item->getContactId()) $params['ContactUnID'] = $item->getContactId();
        if($item->getUnid()) $params['DiscountUnID'] = $item->getUnid();
        if($item->getIsSupposed() != '') $params['IsSupposed'] = ''.$item->getIsSupposed();
        if($item->getObjectDiscount() != '') $params['ObjectDiscount'] = ''.$item->getObjectDiscount();
        if($item->getBasicDiscount() != '') $params['BasicDiscount'] = ''.$item->getBasicDiscount();
        if($item->getSampleDiscount() != '') $params['SampleDiscount'] = ''.$item->getSampleDiscount();
        if($item->GetWase() != '') $params['SpecialDiscountText'] = ''.$item->GetWase();
        $params['UseDiscount'] = ''.$item->getUseDiscount();
        if($item->GetFromDate() != '') $params['FromDate'] = ''.$item->GetFromDate();
        if($item->getConditionDuration() != '') $params['DurationDiscount'] = ''.$item->getConditionDuration();
        $params['ConditionDiscount'] = [];
        $params['ConditionDiscount']['ConditionDiscount_1'] = ''.$item->GetConditionDiscount_1();
        $params['ConditionDiscount']['ConditionDiscount_2'] = ''.$item->GetConditionDiscount_2();
        $params['ConditionDiscount']['ConditionDiscount_3'] = ''.$item->GetConditionDiscount_3();
        $params['ConditionDiscount']['ConditionDiscount_4'] = ''.$item->GetConditionDiscount_4();
        $params['ConditionDiscount']['ConditionDiscount_5'] = ''.$item->GetConditionDiscount_5();
        $params['ConditionDiscount']['ConditionDiscount_6'] = ''.$item->GetConditionDiscount_6();
        $params['ConditionDiscount']['ConditionDiscount_7'] = '€';
        
        if ($item->GetSeriesDiscount() && count($item->GetSeriesDiscount())) {
            $arrSeries = $item->GetSeriesDiscountId();
            $arrArticles = $item->GetArticleDiscount();
            $arrSizes = $item->GetSizeDiscount();
            $arrFactories = [];
            $params['Factories'] = [];
            if(is_array($arrSeries)){
                foreach ($arrSeries as $key => $series) {
                    $k = ++$key;
                    $arrFactories = [];
                    $arrFactories['SeriesDiscount'] = $series;
                    $arrFactories['ArticleDiscount'] = isset($arrArticles[$key])?$arrArticles[$key]:'';
                    $arrFactories['SizeDiscount'] = isset($arrSizes[$key])?$arrSizes[$key]:'';
                    if (count($arrFactories) == 0){
                        unset($arrFactories);
                    }else{
                        array_push($params['Factories'], $arrFactories);
                    }
                }
            }
        }
        
        if($discHost = $this->container->getParameter('с1_disc_host')){
            $objSOAPClient = $objSOAPClient = new \SoapClient($discHost, array("cache_wsdl" => 0));
            if (!$objSOAPClient) {
                $strLogText .= "Ошибка: Адрес веб-сервиса не задан.\n";
            }
            try {
                $req = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
                $req = "{'Discounts':['Discount1':".$req."]}";
                $strLogText .= "Запрос".$req."\n\n";
                // $strResponse = $objSOAPClient->__soapCall('SetDiscount', [new \SoapParam(json_encode($params), 'Request')]);
                $strResponse = $objSOAPClient->__soapCall('SetDiscount', array(["Request" => $req]));
                $strLogText .= json_decode($strResponse->return, true);
            } catch (\SOAPFault $exception) {
                $strLogText .= "Ошибка на этапе отправки скидок.\n";
                $strLogText .= $objSOAPClient->__getLastRequest();
                $strLogText .= $objSOAPClient->__getLastRequestHeaders();
                $strLogText .= $exception;
            }
            $C1Log = new C1Log($strLogSubject, $strLogText, microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]);
            $objDM->persist($C1Log);
            $objDM->flush();

            return $strLogText;
        }
    }

    function delDiscountFrom1C($item)
    {
        $strLogSubject = 'Удаление скидки';
        $strLogText = '';
        $objDM = $this->getDM();
        $objSOAPClient = $objSOAPClient = new \SoapClient($this->container->getParameter('с1_disc_host'), array("cache_wsdl" => 0));
        if (!$objSOAPClient) {
            $strLogText .= "Ошибка: Адрес веб-сервиса не задан.\n";
        }
        try {
            $strLogText .= "Запрос\n".$item->getUnid()."\n\n";
            $strResponse = $objSOAPClient->__soapCall('DeleteDiscount', array(["ID"=>$item->getUnid()]));
            $strLogText .= "Ответ\n";
            $strLogText .= json_decode($strResponse->return, true);
        } catch (\SOAPFault $exception) {
            $strLogText .= "Ошибка на этапе отправки скидок.\n";
            $strLogText .= $objSOAPClient->__getLastRequest();
            $strLogText .= $objSOAPClient->__getLastRequestHeaders();
            $strLogText .= $exception;
        }
        $C1Log = new C1Log($strLogSubject, $strLogText, microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]);
        $objDM->persist($C1Log);
        $objDM->flush();
        
        return $strLogText;
    }

    public function sendProfileAction()
    {
        $login = $this->param('login');
        if ($login) {
            return $this->success(['response' => $this->sendProfile($login)]);
        }
        return $this->fail();
    }

    public function removeAction(){
        $data = $this->fromJson();
        if ($this->getUser()->hasRole('PM')) {
            if(isset($data['document']) && isset($data['document']['status'])){
                $unid = $this->param('unid');
                $this->container->get('exporterto1c');
                $exportService = $this->container->get('exporterto1c');
                $response = $exportService->deleteContact($unid);

                return $this->success($response);
            }
            else {
                return $this->fail('Missing require param.');
            }
        }
        else {
            return $this->fail('Error permissions!');
        }
    }
}
