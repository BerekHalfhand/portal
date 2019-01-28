<?php

namespace Treto\PortalBundle\Controller\v1;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Treto\PortalBundle\Document\Contacts;
use Treto\PortalBundle\Services\RoboService;

class ContactController extends ApiController implements CheckHashInterface
{
  private $logger = false;

  /**
   * Return sync logger
   * @return object
   */
  private function getLogger(){
    if(!$this->logger){
      $this->logger = $this->container->get('monolog.logger.sync');
    }
    return $this->logger;
  }

    /**
     * Check exist contact by unid or email
     * @return JsonResponse
     */
  public function getAction(){
      if(isset($this->params['contact'])){
          $param = $this->params['contact'];
          $request = [];
          if(isset($param['unid']) && $param['unid']){
              $request[] = ['unid' => $param['unid']];
          }
          if(isset($param['email']) && $param['email']){
              $request[] = ['EmailValues' => $param['email']];
          }

          if($request){
              $contactRepo = $this->getRepo('Contacts');

              $result = $contactRepo->findBy(['$or' => $request], ['created' => "DESC"]);
              if($result){
                  $response = [];
                  foreach ($result as $contact) {
                      /** @var $contact Contacts */
                      $response[] = $contact->getDocument();
                  }

                  return $this->success(['data' => $response]);
              }
              else {
                  return $this->fail('Contact not found.');
              }
          }
          else {
              return $this->fail('Invalid params.');
          }
      }
      else {
          return $this->fail('Missing require params.');
      }
  }

  /**
   * Create contact form site
   * @return JsonResponse
   */
  public function setAction(){
    if(isset($this->params['contact'])){
      $contactParams = $this->params['contact'];
      if(isset($contactParams['unid'])){
        unset($contactParams['unid']);
      }
      if(isset($contactParams['DocumentType'])){
        if($contactParams['DocumentType'] == 'Mixed'){
          $contactParams = $this->createOrganizationForMixedType($contactParams);
        }
        if($contactParams){
            $contact = $this->robo->createContact($contactParams);
            $this->exportTo1C($contact);
            $response = ['contactUnid' => $contact->getUnid()];
            if(isset($contactParams['OrganizationID']) && $contactParams['OrganizationID']){
                $response['OrganizationUnid'] = is_array($contactParams['OrganizationID'])?$contactParams['OrganizationID'][0]:$contactParams['OrganizationID'];
            }
            return $this->success($response);
        }
        else {
            $error = 'Error! Contact do not create.';
        }
      }
      else {
        $error = 'DocumentType is required field!';
      }
    }
    else {
      $error = 'No fields for contact!';
    }

    return $this->fail($error);
  }

  /**
   * Create organization for Mixed DocumentType
   * @param $contact
   * @return mixed
   */
  private function createOrganizationForMixedType($contact){
    $contactRepo = $this->getRepo('Contacts');

    if(isset($contact['OrgOtherName'], $contact['OrgContactStatus'], $contact['OrgCountry'])){
      $contactOrg['DocumentType'] = 'Organization';
      $contactOrg['OtherName'] = $contact['OrgOtherName'];
      $contactOrg['ContactStatus'] = $contact['OrgContactStatus'];
      $contactOrg['Country'] = $contact['OrgCountry'];

      if(isset($contact['VATNo'])){
        $contactOrg['VATNo'] = $contact['VATNo'];
        unset($contact['VATNo']);
      }

      unset($contact['OrgOtherName']);
      unset($contact['OrgContactStatus']);
      unset($contact['OrgCountry']);

      if(isset($contact['unid']) && isset($contact['OrganizationUpdate'])){
        unset($contact['OrganizationUpdate']);

        /** @var $personContact \Treto\PortalBundle\Document\Contacts */
        $personContact = $contactRepo->findOneBy(['unid' => $contact['unid']]);
        $contactStatus = $personContact->GetContactStatus();
        $contactStatus = is_array($contactStatus)?$contactStatus:[$contactStatus];
        if(in_array(14, $contactStatus)){
          return false;
        }
        /** @var $organization \Treto\PortalBundle\Document\Contacts */
        $oUnid = $personContact->GetOrganizationID();
        $organization = $contactRepo->findOneBy([
            'unid' => is_array($oUnid)?$oUnid[0]:$oUnid
        ]);
        if($organization && !$organization->GetBanApi()){
          $oldContactName = $organization->GetContactName();
          $organization->setDocument($contactOrg);

          if($organization->GetDocumentType() == 'Organization' && $oldContactName != $organization->GetContactName()){
            /** @var \Treto\PortalBundle\Services\RoboService $robo */
            $robo = $this->container->get('service.site_robojson');
            $robo->changePersonOrganizationName($organization);
          }

          $this->getDM()->persist($organization);
          $this->getDM()->flush();
        }
      }

      if(!isset($organization) || !$organization){
        $organization = $this->robo->createContact($contactOrg);
      }

      $orgUnid = $organization->getUnid();
      if($orgUnid){
        $this->exportTo1C($organization);
        $contact['Organization'] = [$contactOrg['OtherName']];
        $contact['OrganizationID'] = [$orgUnid];
      }
      else {
        $this->getLogger()->info('('.__FUNCTION__.') Error create organization.');
      }
    }
    else {
      $this->getLogger()->info('('.__FUNCTION__.') Missing required fields.');
    }

    $contact['DocumentType'] = 'Person';
    return $contact;
  }

  /**
   * Update contact form site
   * @return JsonResponse
   */
  public function updateAction(){
    if(isset($this->params['contact'])){
      $contactParams = $this->params['contact'];
      if(isset($contactParams['DocumentType']) && $contactParams['DocumentType'] == 'Mixed'){
        $contactParams = $this->createOrganizationForMixedType($contactParams);
      }
      $contact = $this->robo->updateContact(['document' => $contactParams], RoboService::UPDATE_FROM_SITE);
    }

    if(isset($contact) && $contact){
      $this->exportTo1C($contact);
      $response = ['contactUnid' => $contact->getUnid()];
      if(isset($contactParams['OrganizationID']) && $contactParams['OrganizationID']){
        $response['OrganizationUnid'] = is_array($contactParams['OrganizationID'])?$contactParams['OrganizationID'][0]:$contactParams['OrganizationID'];
      }
      return $this->success($response);
    } else {
      return $this->fail('Contact not found!');
    }
  }

  /**
   * Enable export to 1c and run sync
   * @param $contact
   */
  private function exportTo1C($contact){
    /** @var $contact \Treto\PortalBundle\Document\Contacts */
    $contact->SetC1WaitSync(true);
    $this->getDM()->persist($contact);
    $this->getDM()->flush();
    @file_get_contents(Contacts::C1_SYNCH_URL);
  }
}