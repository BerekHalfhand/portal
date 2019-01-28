<?php

namespace Treto\PortalBundle\Services;
/**
 *  aknowledgements to http://bugs.php.net
 *
 *
 */
class FixedSOAPClient extends \SoapClient{
    public $response;
    private $method;
    private $argumentCount;
    /**
    Loading the WSDL through PHP instead of letting the SoapClient do this job,
    avoids breaking Apache. I noticed it breaks just here, while loading the wsdl through *HTTPS*.
    Note: I believe that the __doRequest method should also be called when loading the .wsdl
     */
    public function __construct($url, $saveTo){
        $s = file_get_contents($url);
        file_put_contents($saveTo, $s);
        parent::__construct($url, array(
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP
        ));
    }
    public function __soapCall($function, $arguments, $options = NULL, $input_headers = NULL, &$output_headers = NULL) {
        return $this->__call($function, $arguments);
    }
    public function __call($function, $arguments){
        $this->argumentCount = count($arguments);
        /*
        Adding a bogus parameter to the beginning, since the SoapClient is "eating" the first argument.
        */
        array_unshift($arguments, 0);
        return parent::__call($this->method = $function, $arguments);
    }
    public function __doRequest($request, $location, $action, $version, $oneWay = 0){
        assert($this->method != '');
        $xml = new \DOMDocument('1.0', 'utf-8');
        $xml->loadXML($request);
        $d = $xml->documentElement;
        /*
        Placing the "lost" arguments inside the function node, their right place.
        */
        for($o = $d->getElementsByTagName($this->method)->item(0);
            $o->nextSibling;
            $o->appendChild($o->nextSibling));

        $xml = $xml->saveXML();
        /*
        The operation expected parameters to be named as arg1, arg2, insted of what PHP built, which was param1, param2...
        */
        if($this->argumentCount)
            foreach(range($this->argumentCount, 0) as $i)
                $xml = str_replace('param' . ($i + 1), 'arg' . $i, $xml);


        if(isset($_GET['dump']) && $_GET['dump']==true){
#      header('Content-type:text/xml; charset=utf8');
            $r = $xml;
            $r = preg_replace('/></', ">\n<", $r);
            $r = preg_replace('/>\n<\//', "></", $r);
            print_r($r);
            exit;
        }

        /*
        Removing boundary from the XML result, this must be part of a standard as the calls works fine on other tools, the SoapClient should be able to handle it.
        */
        $this->response = parent::__doRequest($xml, $location, $action, $version, $oneWay);
        $s = preg_replace('/--.*?--$/', '', preg_replace('/^(?:.|\n|\r)*?<soap:/', '<soap:', $this->response));
        return $s;
    }
}