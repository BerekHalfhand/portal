<?php

namespace Treto\PortalBundle\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Treto\PortalBundle\Document\Portal;
use Symfony\Component\Process\Process;

class WorkPlanController extends Controller {
  public function saveAction(Request $request, $unid, $dateTag) {
    $monthYear = preg_split("@\.@", $dateTag);
    /** @var Portal $empl */
    $empl = $this->findEmpl($unid);

    if(!$empl) {
      throw new \Exception ("not found user with unid $unid");
      return;
    }

    $dd = $this->getDaysData($request, $empl, $monthYear);
    $wp = $this->setWp($empl, $monthYear, $dd);
    $plan = $this->prepareToFront($wp);
    /** @var $nodeService \Treto\PortalBundle\Services\NodeService  */
    $nodeService = $this->get('node.service');
    $nodeService->refreshUsers();

    return new JsonResponse($plan);
  }

  /**
   * Update or create WP data from request
   * @param $empl
   * @param $monthYear
   * @param $dd
   * @param bool $wp
   * @return Portal
   */
  private function setWp($empl, $monthYear, $dd, $wp = false){
    /** @var $empl Portal */
    /** @var $wp Portal */
    $wp = $wp?$wp:$this->findPlan($empl->GetUnid(), $monthYear);
    if(!$wp) {
      // constructor with user
      $wp = new Portal($this->getUser());
      $wp->SetDaysData($dd); //"DaysData"
      $wp->SetForm('WorkPlan');
      $wp->SetMonth($monthYear[0]); //"Month" : "02",
      $wp->SetYear($monthYear[1]); //"Year" : "2013",
      $wp->setUnid(uniqid(time())); //"unid" : "3C0E859B9D148F1344257B9000548B89",
      $wp->SetEmplUNID($empl->GetUnid()); //"EmplUNID" : "08D560BEC8E1F4CE44257A5400483A97",
      #$wp->SetSection       ($empl->GetSection()); //"section" : "Отдел разработки портала",
      $wp->SetLevel         ($empl->GetLevel()); //"level" : "SOT",
      $wp->SetWorkGroup     (is_array($empl->GetWorkGroup())?$empl->GetWorkGroup():[$empl->GetWorkGroup()]); //"WorkGroup"
      $wp->SetRegion        ($empl->GetRegion());//"Region" : "Москва",
      $wp->SetFullName      ($empl->GetFullName()); //"FullName" : "CN=Aleksey Polozov/O=skvirel",
      $wp->SetFullNameInRus ($empl->GetFullNameInRus()); //"FullNameInRus" : "Полозов Алексей",
      $wp->SetNoteId        ($empl->GetNoteId()); # "noteid" : "baaa",
      $wp->SetSequence      ($empl->GetSequence()+1); # "sequence" : "2",
      $wp->SetCreated       ($this->createIsoTimestamp());
      $wp->SetModified      ($this->createIsoTimestamp());
    }
    else {
      $wp->SetDaysData($dd);
      $wp->SetModified($this->createIsoTimestamp());
    }
    $cmgr = $this->get('doctrine.odm.mongodb.document_manager');
    $cmgr->persist($wp);
    $cmgr->flush(null, array('safe' => true, 'fsync' => true));
    $this->callToSynch($monthYear, $dd, $empl->GetLogin());
    return $wp;
  }

  public function sectionAction($tag, $dateTag) {
    $monthYear = preg_split("@\.@", $dateTag);
    if($tag=='_')
        $tag = ($this->getUser()->GetDocument()['portalData']['section'][0]);
		$repo = $this->getRepo('Portal');
    $document = $repo->getDocumentManager();
    $search_array = ["form" => "Empl",
      'status' => ['$not' => ['$in' => ['deleted']]],
      'WorkGroup' => ['$not' => ['$in'=>['нет должности']]],
      'section'=> ['$not'=>['$in'=>['Салон']]],
      '$or' => [ ['DtDismiss'=>''],['DtDismiss'=>['$exists'=>false]] ]
    ];
    $search_array ['section'] = $tag;
    $p = $repo ->findBy($search_array);
    $list = [];
    $sections = [];
    foreach ($p as $e) {
      $unid = $e->GetUnid();
      $wp = $this->findPlan($unid, $monthYear);
      $data = $wp?$this->prepareToFront($wp):$this->defaultMonthModel($monthYear);
      $workGroups = is_array($e->GetWorkGroup())?$e->GetWorkGroup():[$e->GetWorkGroup()];

      foreach ($workGroups as $wgItem) {
        if(trim($wgItem) == '') continue;
        if(!array_key_exists($wgItem, $list)){
          $list[$wgItem] =
              ['name'=>$wgItem,'planList'=>[]];
        }
        $ar = [
            'unid' => $e->GetUnid(),
            'WorkGroup'=> $wgItem,
            'FullNameInRus' => $e->GetFullNameInRus(),
            'Login' =>$e->GetLogin(),// is used to controll PMness
            'data'=>$data
        ];

        $list[$wgItem]['planList'][$e->GetUnid()] = $ar;
      }
    }
    $list = array_values($list);

    $model = ['model'=>[
      'workGroupList'=>$list,
      'dateLabel' =>$dateTag
    ]];
    return new JsonResponse($model);
  }

  public function userAction($userUnid, $dateTag) {
    $repo = $this->getRepo("Portal");
      
    $dayMonthYear = preg_split("@\.@", $dateTag);
    $monthYear = [$dayMonthYear[1], $dayMonthYear[2]];
    $ret = [];
    $found = false;
    $daysOff = 0;
    $iterated = 0;
    
    while (!$found && $iterated < 12) {

      $num = $dayMonthYear[1].'.'.$dayMonthYear[2];
      $ret[$num] = [];
      $rs = $repo->findOneBy([
          'form' => 'WorkPlan',
          'EmplUNID' => $userUnid,
          'Year' => $dayMonthYear[2],
          'Month' => $dayMonthYear[1]
      ]);

      if($rs) {
        foreach($rs->GetDaysData() as $label) {
          $ret[$num][] = ['label'=>$label];
        }
      }
      else{
        $ret[$num] = $this->defaultMonthModel($monthYear);
      }

      for ($i = $dayMonthYear[0]-2; $i >= 0; $i--) {
        if (isset($ret[$num][$i]) && $ret[$num][$i]['label'] == 'р') {
          $found = true;
          break;
        }
        $daysOff++;
      }
      $dayMonthYear[1]--;
      if($dayMonthYear[1] == 0) {
        $dayMonthYear[1] = 12;
        $dayMonthYear[2]--;
      }
      if(strlen($dayMonthYear[1].'') < 2){
        $dayMonthYear[1] = '0'.$dayMonthYear[1];
      }
      $monthYear[0] = $dayMonthYear[1];
      $monthYear[1] = $dayMonthYear[2];
      $dayMonthYear[0] = $this->getMonthDayCount($monthYear);

      $iterated++;
    }

    $ret['daysOff'] = $daysOff;
    
    return new JsonResponse($ret);
  }

  /**
   * prepare data to view in frontend
   * @param $rs
   * @return array
   */
  protected function prepareToFront($rs) {
    $ret = [];
    foreach($rs->GetDaysData() as $label) {
      if(is_array($label) && isset($label['type'])){
        $data = ['label' => $label['type']];
        if(isset($label['deputyLogin'])){
          $data['deputyLogin'] = $label['deputyLogin'];
        }
        if(isset($label['deputySal'])){
          $data['deputySal'] = $label['deputySal'];
        }
      }
      else {
        $data = ['label' => $label];
      }
      $ret[] = $data;
    }

    return $ret;
  }

  protected function defaultMonthModel($from, $forBack = false) {
    $t = sprintf("%s-%s-01",$from[1], $from[0]);
    $s = date_create_from_format("Y-m-d", $t);
    $n = $this->getMonthDayCount($from);
    $ret = [];
    $di = new \DateInterval('P1D');
    foreach(range(0, $n-1) as $q) {
      $f = ((6 + $s->format("w"))%7);
      $R = ['р','р','р','р','р','в','в'];
      $ret []= !$forBack?['label'=>$R[$f]]:$R[$f];
      $s = date_add($s, $di);
    }
    return $ret;
  }

  protected function getMonthDayCount($from) {
    $next = $from;
    $next[0]++;
    if($next[0]==13) {
      $next[0]-=12;
      $next[1]++;
    }
    if(strlen($next[0].'') < 2)$next[0] = '0'.$next[0];
    $s = date_create_from_format("Ymd", sprintf("%s%s01",$from[1], $from[0]));
    $e = date_create_from_format("Ymd", sprintf("%s%s01",$next[1], $next[0]));
    $i = date_diff($s, $e);
    return 0+$i->format("%a");
  }

  protected function getData($request) {
    $content = json_decode($request->getContent());
    $ret = [];
    foreach($content->workPlan as $key=>$element) {
      $ret [$key] = $element;
    }
    return $ret;
  }

  /**
   * Return days data
   * @param $request
   * @param bool $empl
   * @param bool $monthYear
   * @return array
   */
  protected function getDaysData($request, $empl = false, $monthYear = false) {
    $content = json_decode($request->getContent(), true);
    $ret = [];
    foreach($content['workPlan']['data'] as $dayArr) {
      if(isset($dayArr['deputyLogin']) && isset($dayArr['deputySal'])){
        if(isset($dayArr['deputyRange']) && $monthYear && $empl){ //wp range must be < 6 month
          $dateStart = date_create(date('Y-m-d', strtotime($dayArr['deputyRange']['startDate'])));
          $dateEnd = date_create(date('Y-m-d', strtotime($dayArr['deputyRange']['endDate'])));
          $interval = date_diff($dateStart, $dateEnd);
          $monthRange = $interval->format('%m');
        }

        if(isset($monthRange) && $monthRange <= 6){
          $ret = $this->setWpRange($dayArr, $monthYear, $empl);
          break;
        }
        else {
          $inf = [
              'type' => $dayArr['label'],
              'deputyLogin' => $dayArr['deputyLogin'],
              'deputySal' => $dayArr['deputySal']
          ];
        }
      }
      else {
        $inf = $dayArr['label'];
      }

      $ret [] = $inf;
    }
    return $ret;
  }

  /**
   * Set work plan range
   * @param $dayArr
   * @param $monthYear
   * @param $empl
   * @return array
   */
  private function setWpRange($dayArr, $monthYear, $empl){
    $ddForReturn = [];
    $timeStart = strtotime($dayArr['deputyRange']['startDate']);
    $timeEnd = strtotime($dayArr['deputyRange']['endDate']);

    if($timeStart < $timeEnd){
      $startMonthYear = date('m.Y', $timeStart);
      $endMonthYear = date('m.Y', $timeEnd);
      $i = 0;

      do {
        $currentMonthYear = date('m.Y', strtotime($dayArr['deputyRange']['startDate']."+$i month"));
        $currentMonthYearArr = explode(".", $currentMonthYear);
        /** @var Portal $wp */
        $wp = $this->findPlan($empl->GetUnid(),  $currentMonthYearArr);
        $dd = $wp?$wp->GetDaysData():$this->defaultMonthModel($currentMonthYearArr, true);

        foreach ($dd as $key => $d) {
          $moreStart = $key+1 >= date('j', $timeStart);
          $lessEnd = $key < date('j', $timeEnd);
          $withinOneMonth = $endMonthYear == $startMonthYear;

          if(($withinOneMonth && $moreStart && $lessEnd) ||
              ($currentMonthYear == $startMonthYear && !$withinOneMonth && $moreStart) ||
              ($currentMonthYear == $endMonthYear && !$withinOneMonth && $lessEnd) ||
              ($currentMonthYear != $endMonthYear && $currentMonthYear != $startMonthYear)){
            $dd[$key] = [
                'type' => $dayArr['label'],
                'deputyLogin' => $dayArr['deputyLogin'],
                'deputySal' => $dayArr['deputySal']
            ];
          }
        }
        $this->setWp($empl, $currentMonthYearArr, $dd, $wp);
        if($currentMonthYear == implode('.', $monthYear)){
          $ddForReturn = $dd;
        }
        $i++;
      } while($currentMonthYear != $endMonthYear && $i < 6);
    }

    return $ddForReturn;
  }

  /**
   * @param $date
   * @param $dd
   * @param $login
   */
  private function callToSynch($date, $dd, $login){
     $paramsToSynch = ['date' => $date, 'login' => $login, 'dd' => $dd];
     $process = new Process($this->get('kernel')->getRootDir().'/console synchronize wp \''.serialize($paramsToSynch).'\'');
     $process->start();
  }

  protected function findEmpl($unid) {
    $repo = $this->getRepo('Portal');
    return $repo->findOneBy(['unid' => $unid, 'form'=>'Empl']);//F30ED00DD5061CDAC325751100673CC5
  }

  protected function findPlan($unid, $monthYear) {
    $repo = $this->getRepo('Portal');
    return $repo->findOneBy(['EmplUNID' => $unid, 'Year'=>$monthYear[1], 'Month'=>$monthYear[0],'form'=>'WorkPlan']);
  }
}

