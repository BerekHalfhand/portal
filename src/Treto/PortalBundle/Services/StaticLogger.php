<?php
namespace Treto\PortalBundle\Services;

use Treto\PortalBundle\Document\HistoryLog;

trait StaticLogger {
  protected $repo, $cmgr, $userId;
  public function __construct() {
  }
  public function log($forClass, $forMethod, $context = '',$label='') 
  {
    $pi = @explode('/', $_SERVER['REQUEST_URI'] );
    list($x, $y) = $this->callInfo($forClass, $forMethod);
    $check0 = !is_null($this->getUser());
    $check1 = $check0 && !is_null($pi) 
                   && array_key_exists(2,$pi)
                    && $pi[2] == 'api';
    $check2 = $check1 && $y == 'itemAction' || $y == 'getAction' || $y == 'contactAction';
    if(!$check2) {
      return '99999999999999999999'; 
    }
    $parInfo = $this->paramInfo(); //_COOKIE
    return $this->persistLogRecord( array($x, $y, $parInfo), $context, $label);
  }
  private function persistLogRecord($argz, $context, $label) 
  {
    return "99999999999999999999"; // TODO: refactor
    $sp = array_key_exists('currentStateParams',$_COOKIE) ? $_COOKIE['currentStateParams'] : time();
    $this->init();
    $_dateTime = new \DateTime();
    $_dateTime=$_dateTime->sub(new \DateInterval('PT5M'));
    if(!$_dateTime) { return "99999999999999999999"; }
    $q = $this->cmgr->createQueryBuilder('\Treto\PortalBundle\Document\HistoryLog')
        #->field('time')->gte($_dateTime) // won't work(1)
        ->field('userId')->equals($this->userId )
        ->field('href')->equals($context);
    
    $log = $q->getQuery()->execute();
    $count = 0;
    foreach ($log as $value) { 
        # из-за того что (1) не работает приходится проверять здесь.
        if($value->getTime()->sec > $_dateTime->getTimestamp()) {
          return "99999999999999999999";
        }
    }
    $logRecord = new HistoryLog();
    $logRecord -> setTime( time() );
    $logRecord -> setUserId( $this->userId );
    $logRecord -> setController( $argz[0] );
    $logRecord -> setAction( $argz[1] );
    $logRecord -> setEntityClass( $argz[0] ); 
    $logRecord -> setHref($context);
    $logRecord -> setLabel($label);
    $logRecord -> setState($_COOKIE['currentState']);
    $logRecord -> setStateParams($sp);
    //$item->postCreate();
    $this->cmgr->persist($logRecord);
    $this->cmgr->flush();
    $ret = $logRecord->getId();
    return $ret;
  }
  private function init() 
  {
    if(is_null($this->cmgr)) { $this->cmgr = $this->get('doctrine.odm.mongodb.document_manager'); }
    if(is_null($this->repo)) { $this->repo = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:HistoryLog'); }
    if(is_null($this->userId)) { 
      try {
          if($this->getUser())
            $this->userId = $this->getUser()->getId(); 
          else 
            throw new \Exception("missing user");
        } 
        catch (\Exception $x) {
          throw new \Exception('missing user');
        } 
    }
  }
  private function paramInfo() 
  {

    $qs = !empty($_SERVER['QUERY_STRING'])?$_SERVER['QUERY_STRING'] : '';
    $qs = @explode('&',$qs);
    $i = array();
    foreach($qs as $k=>$v) {
      $m = @explode('=', $v);
      if(is_array($m)&&count($m)>1)
        $i[$m[0]]=$m[1];
    }
    return $i;
  }
  private function callInfo($forClass, $forMethod) {
    $c = array_slice(preg_split('@\\\\@',$forClass), -1, 1, TRUE);
    $a = array_slice(preg_split('@\\\\@',$forMethod), -1, 1, TRUE);
    $c = $c[key($c)];
    $a = $a[key($a)];
    return explode('::',$a);
  }
}
