<?php
namespace Treto\PortalBundle\EventListener;

use Treto\PortalBundle\Controller\v1\FormatDeterminationController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Treto\PortalBundle\Services\RoboXmlService;
use Treto\PortalBundle\Services\RoboJsonService;

class FormatListener
{
    protected $roboXML;
    protected $roboJSON;
    protected $fields = [
        'DocumentAuthor' => 'Author',
        'DocumentSubject' => 'subject',
        'DocumentParentUnID' => 'ParentUnID',
        'DocumentParticipant' => 'Participants',
        'Hidden' => 'AccessType',
        'DocumentFile' => 'attachments',
        'DocumentBody' => 'body',
        'DocumentAnswersData' => 'AnswersData',
        'DocumentAnswerLim' => 'AnswersLim',
        'DocumentTypeCount' => 'TypeCount',
        'DocumentTypePoll' => 'TypePoll',
        'DocumentPeriodPoll' => 'PeriodPoll',
        'DocumentCategory' => 'C1',
        'DocumentPerformer' => 'taskPerformerLat',
        'DocumentChecker' => 'Checker',

        /// Contact fields ///
        'FirstName' => 'FirstName',
        'LastName' => 'LastName',
        'MiddleName' => 'MiddleName',

        'EmailValues' => 'EmailValues', //array
        'PhoneValues' => 'PhoneValues', //array
        'PhoneCellValues' => 'PhoneCellValues', //array

        'AddressBlockNumberActual' => 'AddressBlockNumber_Actual',
        'AddressCityNameActual' => 'AddressCityName_Actual',
        'AddressHouseNumberActual' => 'AddressHouseNumber_Actual',
        'AddressOfficeSuiteNumberActual' => 'AddressOfficeSuiteNumber_Actual',
        'AddressStreetNameActual' => 'AddressStreetName_Actual',
        'AddressZipCodeActual' => 'AddressZipCode_Actual',

        'AddressBlockNumberForDelivery' => 'AddressBlockNumber_ForDelivery',
        'AddressCityNameForDelivery' => 'AddressCityName_ForDelivery',
        'AddressHouseNumberForDelivery' => 'AddressHouseNumber_ForDelivery',
        'AddressOfficeSuiteNumberForDelivery' => 'AddressOfficeSuiteNumber_ForDelivery',
        'AddressStreetNameForDelivery' => 'AddressStreetName_ForDelivery',
        'AddressZipCodeForDelivery' => 'AddressZipCode_ForDelivery',
        'DeliveryAddressIsDiff' => 'DeliveryAddressIsDiff', // bool

        /// Contact fields for organization ///
        'AddressBlockNumberForLegal' => 'AddressBlockNumber_ForLegal',
        'AddressCityNameForLegal' => 'AddressCityName_ForLegal',
        'AddressHouseNumberForLegal' => 'AddressHouseNumber_ForLegal',
        'AddressOfficeSuiteNumberForLegal' => 'AddressOfficeSuiteNumber_ForLegal',
        'AddressStreetNameForLegal' => 'AddressStreetName_ForLegal',
        'AddressZipCodeForLegal' => 'AddressZipCode_ForLegal',
        'LegalAddressIsDiff' => 'LegalAddressIsDiff', // bool

        'MainName' => 'MainName',
        'OtherName' => 'OtherName',
        'SiteName' => 'SiteName',
        'NameCompany' => 'NameCompany'
    ];

    public function __construct(RoboXmlService $roboXML, RoboJsonService $roboJSON)
    {
        $this->roboXML = $roboXML;
        $this->roboJSON = $roboJSON;
    }

    /**
     * Replace invalid charsets
     * @param $content
     * @return mixed
     */
    private function clearInvalidCharesets($content){
        $content = str_replace("%D0%A3", "£", $content);
        $content = str_replace("%A3", "£", $content);

        return $content;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        if ($controller[0] instanceof FormatDeterminationController){
            if($event->getRequest()->server->get('REQUEST_METHOD') === 'GET'){
                if(strpos($event->getRequest()->server->get('REQUEST_URI'),'api/v1/1c/reception') !== false){
                    $controller[0]->robo = $this->roboXML;
                } else {
                    $controller[0]->robo = $this->roboJSON;
                }
            }

            $content = $this->clearInvalidCharesets($event->getRequest()->getContent());

            if($content && $this->isValidJson($content)){
                $controller[0]->robo = $this->roboJSON;

                $params = json_decode($content, true);
                $controller[0]->params = $params;
            }

            if($content && $this->isValidXml($content)){
                $controller[0]->robo = $this->roboXML;
                $controller[0]->params = $content;
                $fileName = KERNEL_DIR . '/resume/log_1C.txt';
                $fp = @fopen($fileName, "a+");
                @fwrite($fp, $content);
                fclose($fp);

               $xml = new \SimpleXMLElement($content);
               $json = json_encode($xml);
               $array = json_decode($json, TRUE);
               $params = ['document' => []];

               if(array_key_exists('DocumentParent', $array)){
                   if(array_key_exists('DocumentBody', $array['DocumentParent'])){
                      if ($xml->DocumentParent->DocumentBody->children()->asXML()){
                      $array['DocumentParent']['DocumentBody'] = $xml->DocumentParent->DocumentBody->children()->asXML();
                      }
                   }
                   $action = $array['DocumentParent']['@attributes']['Action'];
                   $type = $array['DocumentParent']['@attributes']['Type'];
                   $params['document']['action'] = $action;
                   $params['document']['type'] = $type;

                   foreach($array['DocumentParent'] as $key => $item){
                       if($key !== '@attributes'){
                           $strKey = $key;
                           if(array_key_exists($key, $this->fields)){
                               $strKey = $this->fields[$key];
                           }
                           if(!is_array($item)){
                               $params['document'][$strKey] = html_entity_decode(urldecode($item));
                               if ($strKey == 'ParentUnID'){
                                $params['document'][$strKey] = strtoupper($params['document'][$strKey]);
                               }
                           } elseif(count($item)) {
                               foreach($item as $_item){
                                   $params['document'][$strKey][] = $_item;
                               }
                           }
                           else {
                               $params['document'][$strKey] = '';
                           }
                       }
                   }
               }

               if(array_key_exists('DocumentResponse', $array)){
                   if(!is_array($array['DocumentResponse'])){
                       $array['DocumentResponse'] = [$array['DocumentResponse']];
                   }

                   foreach($array['DocumentResponse'] as $_key => $_item){
                       foreach($_item as $key => $item) {
                           $action = $_item['@attributes']['Action'];
                           $type = $_item['@attributes']['Type'];
                           if ($key !== '@attributes') {
                               $strKey = $key;
                               if (array_key_exists($key, $this->fields)) {
                                   $strKey = $this->fields[$key];
                               }
                               if (!is_array($item)) {
                                   $params['childs'][$_key][$type][$strKey] = $item;
                               } else {
                                   foreach ($item as $_item) {
                                       $params['childs'][$_key][$type][$strKey][] = $_item;
                                   }
                               }
                           }
                       }
                   }
               }
               $controller[0]->params = $params;
            }
        }
    }

    public function isValidXml($string) {
        libxml_use_internal_errors( true );
        $doc = new \DOMDocument('1.0', 'utf-8');
        $doc->loadXML($string);
        $errors = libxml_get_errors();
        return empty($errors);
    }

    public function isValidJson($string) {
        return json_decode($string, true);
    }
}