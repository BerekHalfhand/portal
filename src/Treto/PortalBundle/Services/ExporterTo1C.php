<?php
namespace Treto\PortalBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Treto\PortalBundle\Document\C1Log;
use Treto\PortalBundle\Document\Contacts;

class ExporterTo1C
{
    private $container;
    public $objSOAPClient;
    private $synchLogText = '';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function createClient($strWsdlUrl = false) {
        if (!$strWsdlUrl)
        {
            return false;
        }
        $strCachedWsdl = $this->container->get('kernel')->getRootDir().'/console-cache/'.md5($strWsdlUrl) . '.wsdl';
        return new FixedSOAPClient($strWsdlUrl, $strCachedWsdl);
    }

    public function createSoapParams($item) {
        $item = $this->prepareContactFields($item);
        $fields = Contacts::$c1FieldList;
        $VALUE = function ($inp) use ($item) {
            if(array_key_exists($inp,$item)) {
                if(is_string($item[$inp])){ return $item[$inp]; }
                if(is_array($item[$inp])){
                    $ret = []; 
                    foreach($item[$inp] as $valueRow) {
                        if (!empty($valueRow)){
                            $ret []= print_r($valueRow, 1);
                        }
                    }
                    return join('~', $ret);
                }
            }
            return false;
        };
        $ret = [];
        foreach($fields as $k => $v) {
            if ($VALUE($k) !== false){
              $ret [$k] = $VALUE($k);
            }else{
              $ret [$k] = $v;
            }
        }

        $ret = $this->addCustomFileds($ret, $item);
        return $ret;
    }

    private function addCustomFileds($arr, $item){
      $arr['Holidays'] = [];
      if (!empty($item['inHoliday']) && !empty($item['outHoliday'])){
        foreach ($item['inHoliday'] as $key => $value) {
          if(isset($item['outHoliday'][$key])){
            $arr['Holidays'][] = [
                'inHoliday' => $value,
                'outHoliday' => $item['outHoliday'][$key]
            ];
          }
        }
      }
      if (!empty($item['ResponsibleManager_ID'])){
        $arr['ResponsibleManager'] = $item['ResponsibleManager_ID'];
      }
      if(!empty($item['TimePreparationShipping_Treto'])) {
        $arr['TimePreparationShippingTreto'] = $item['TimePreparationShipping_Treto'];
      }
      if(!empty($item['TimePreparationShipping_Te'])) {
        $arr['TimePreparationShippingTe'] = $item['TimePreparationShipping_Te'];
      }

      $addr = $this->addressString("Actual", $item);
      if (!empty($addr)){
        if ($item['DocumentType'] == 'Person'){
          $arr['ResidenceRegistrationAddress'] = $this->addressString("Actual", $item);
        }else{
          $arr['ActualAddress'] = $this->addressString("Actual", $item);
        }
      }

      $addr = $this->addressString("ForDelivery", $item);
      if (!empty($addr)){
        $arr['DeliveryAddress'] = $this->addressString("ForDelivery", $item);
      }elseif(empty($arr['DeliveryAddress'])){
        $arr['DeliveryAddress'] = !empty($arr['ResidenceRegistrationAddress'])?$arr['ResidenceRegistrationAddress']:$arr['ActualAddress'];
      }

      $addr = $this->addressString("ForLegal", $item);
      if (!empty($addr)){
        $arr['LegalAddress'] = $this->addressString("ForLegal", $item);
      }elseif(empty($arr['LegalAddress'])){
        $arr['LegalAddress'] = $arr['ActualAddress'];
      }

      $passp = isset($item['PassportSeries'])?$item['PassportSeries']:''.
               isset($item['PassportNubmer'])?$item['PassportNubmer']:''.
               isset($item['PassportIssuedByOrg'])?$item['PassportIssuedByOrg']:''.
               isset($item['PassportDateIssued'])?$item['PassportDateIssued']:'';
      if(!empty($passp)){
        $arr['Passport'] = $item['PassportSeries']." ".$item['PassportNubmer'].", ".$item['PassportIssuedByOrg'].", ".$item['PassportDateIssued'];
      }

      if(!empty($item['PhoneCellValues'])){
        if(!empty($arr['PhoneValues'])){
          $arr['PhoneValues'] = $arr['PhoneValues']."~".join("~", $item['PhoneCellValues']);
        }else{
          $arr['PhoneValues'] = join("~", $item['PhoneCellValues']);
        }
      }

      $arr["ContactBirthday"] = "";
      $arr["ContactBirthmonth"] = "";
      $arr["ContactBirthyear"] = "";

      return $arr;
    }

    private function addressString($type, $item){
      $fieldsEng = ['AddressZipCode_'=>'', 'AddressCityName_'=>', ', 'AddressStreetName_'=>', ', 'AddressHouseNumber_'=>', ', 'AddressBlockNumber_'=>', ', 'AddressOfficeSuiteNumber_'=>', '];
      $fieldsRus = ['AddressZipCode_'=>'', 'AddressCityName_'=>', г.', 'AddressStreetName_'=>', ул.', 'AddressHouseNumber_'=>', д.', 'AddressBlockNumber_'=>', корп.', 'AddressOfficeSuiteNumber_'=>', кв.'];
      $allAddr = '';
      foreach ($fieldsEng as $key => $value) {
        if (isset($item[$key.$type])){
          $allAddr.=$item[$key.$type];
        }
      }
      if ((isset($item['FormOwnership']) && $item['FormOwnership'] == '6') || preg_match("/[a-z]/i", $allAddr)){
        $fields = $fieldsEng;
      }else{
        $fields = $fieldsRus;
      }
      $result = "";
      $addresses = false;
      foreach ($fields as $field => $pref) {
        if (isset($item[$field.$type]) && !empty($item[$field.$type])){
          $result .= $pref.$item[$field.$type];
        }
      }
      return $result;
    }

    /**
     * Connect to SOAP
     * @param $host
     * @return bool
     */
    public function connectToSoap($host){
        $this->objSOAPClient = false;

        try {
            $this->objSOAPClient = new \SoapClient($host, ["cache_wsdl" => 0]);
        } catch (\SOAPFault $exception) {
            /** @var \Treto\PortalBundle\Services\ConsultService $consult */
            $consult = $this->container->get('consult.service');
            $objRepoPortal = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Portal');
            $task = $objRepoPortal->findOneBy([
                'form' => 'formTask',
                'subject' => 'Auto-task debug',
                'type' => '1c',
                'created' => ['$gte' => date('Ymd\THis', strtotime('-10 minute'))]
            ]);
            $error = "Ошибка синхронизации 1С на этапе создания подключения<br/>\n\r".$exception."\n\r<br/>Line: "
                .__LINE__.' Function: '.__FUNCTION__.' Class: '.__CLASS__;
            if(!$task){
               // $consult->createDebugTask([], $error, true); // TEMP DISABLE
            }

            $this->addLogText($error);
            $this->writeLogText('Ошибка на этапе создания подключения 1C');
        }

        return (boolean) $this->objSOAPClient;
    }

    /**
     * Send data to 1C
     * @param $method
     * @param $paramData
     * @param $paramName
     * @return bool
     */
    public function sendTo1C($method, $paramData, $paramName){
        if($this->objSOAPClient){
            try {
                $this->addLogText("Запрос:\n".json_encode($paramData));
                $strResponse = $this->objSOAPClient->__soapCall($method, [[$paramName => $paramData]]);
            } catch (\SOAPFault $exception) {
                $this->addLogText("Ошибка на этапе отправки.");
                $this->addLogText($this->objSOAPClient->__getLastRequest());
                $this->addLogText($this->objSOAPClient->__getLastRequestHeaders());
                $this->addLogText($exception);
                $this->writeLogText();
                return false;
            }
        }

        return isset($strResponse)?$strResponse:false;
    }

    /**
     * Add log for send process
     * @param $text
     */
    public function addLogText($text){
      $this->synchLogText .= $text ."\n\r";
    }

    /**
     * Get log for send process
     * @return string
     */
    public function getLogText(){
      return $this->synchLogText;
    }

    /**
     * Write send log to DB
     * @param string $subjectLogText
     */
    public function writeLogText($subjectLogText = '1C log'){
        $objDM = $this->container->get('doctrine.odm.mongodb.document_manager');
        $C1Log = new C1Log($subjectLogText, $this->synchLogText, microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]);
        $objDM->persist($C1Log);
        $objDM->flush();
    }

    /**
     * Criteria for send contacts to 1C
     * @param int $limit
     * @return mixed
     */
    public function getContactsForSend($limit = 30){
        $objRepoContacts = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
        $result = $objRepoContacts->findBy([
            '$and' => [
                ['$or' => [
                    ['Status' => 'open'],
                    ['Status' => ['$exists' => false]]
                ]
                ],
                ['Deleted' => ['$ne' => '1']],
                ['AccessOption' => ['$ne' => '2']],
                ['DocumentType' => ['$in' => ['Person', 'Organization']]],
                ['C1WaitSync' => '1'],
                ['$or' => [
                    ['lastFailSynch' => ''],
                    ['lastFailSynch' => ['$exists' => false]],
                    ['lastFailSynch' => ['$lte' => date('Ymd\THis', strtotime('-5 minute'))]],
                ]],
            ]
        ], null, $limit);

        return $result;
    }

    /**
     * Delete contact and send delete request to 1C
     * @param $unid
     * @return array
     * example ['success' => true, 'log' => 'string']
     */
    public function deleteContact($unid){
        $objDM = $this->container->get('doctrine.odm.mongodb.document_manager');
        $result = false;
        $this->synchLogText = '';
        if($unid){
            $objRepoContacts = $this->container->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Contacts');
            /** @var Contacts $contact */
            $contact = $objRepoContacts->findOneBy(['unid' => $unid]);

            if($contact){
                if($contact->GetDeleted() == '1'){
                    $contact->SetDeleted('');
                    $contact->SetStatus('open');
                    $contact->SetC1WaitSync('1');
                }
                else {
                    $contactArray = $contact->getDocument();
                    $contactArray['ReadingChange'] = '2'; //1C status deleted
                    $contactArray = $this->createSoapParams($contactArray);
                    $c1Host = $this->container->getParameter('c1_sotr_host');
                    if($c1Host && $this->connectToSoap($c1Host)){
                        $jsonItems = json_encode(['ArrayContacts' => [$contactArray]], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
                        $response = $this->sendTo1C('GetListOfEmployees', $jsonItems,  "СтрокаJSON");

                        if($response){
                            $result = true;
                            $d = new \DateTime();
                            $d->add(new \DateInterval('PT1H'));
                            $contact->SetXml1CResponse($result[$contact->getUnid()]['C1_Description']);
                            $contact->SetModify1C($d->format('Ymd') . 'T' . $d->format('Gis'));
                            $contact->SetC1WaitSync($result[$contact->getUnid()]['C1_Result']);
                            $contact->SetStatus('deleted');
                            $contact->SetDeleted('1');
                            $contact->SetC1WaitSync('0');
                            $this->addLogText("Ответ:\n".$response->return);
                        }
                        else {
                            $contact->SetLastFailSynch(date('Ymd\THis'));
                        }
                    }
                    else {
                        $this->addLogText("Error connection to $c1Host.");
                    }
                }


                $objDM->persist($contact);
                $objDM->flush();
            }
            else {
                $this->addLogText('Not found contact by unid.');
            }
        }
        else {
            $this->addLogText('Missing require param "unid".');
        }

        $this->writeLogText('Delete contact');
        return ['success' => $result, 'log' => $this->getLogText()];
    }

    /**
     * Prepare fields to send
     * @param $fields
     * @return mixed
     */
    private function prepareContactFields($fields){
        if(isset($fields['section'])){
            $fields['Section'] = $fields['section'];
        }
        return $fields;
    }
}