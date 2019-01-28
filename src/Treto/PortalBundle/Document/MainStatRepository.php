<?php

namespace Treto\PortalBundle\Document;

class MainStatRepository extends SecureRepository
{
    public function findReadWriteLog($days = 7) {
        $log = $this->findOneBy(['type' => 'LiveList', 'subType' => 'readWriteLog', 'daysCount' => $days]);
        if (!$log) {
            $log = new MainStat();
            $log->SetType('LiveList');
            $log->SetSubType('readWriteLog');
            $log->SetDaysCount($days);
            $log->SetTimestamp($log->GetCreated());
            $log->SetLiveList([]);
        }
        return $log->Sanitize(); // remove old entries and return (See Sanitize() in MainStat.php)
    }

    public function findMainStat() {
        $mainStat = $this->findOneBy(['type' => 'LiveList', 'subType' => 'mainStat']);
        if (!$mainStat) {
            $mainStat = new MainStat();
            $mainStat->SetType('LiveList');
            $mainStat->SetSubType('mainStat');
            $mainStat->SetLiveList([]);
        }
        return $mainStat;
    }

    public function findClickLogForToday() {
        $todayDate = \Treto\PortalBundle\Document\SecureDocument::dt2iso(new \DateTime());
        $log = $this->findOneBy(['type' => 'LiveList', 'subType' => 'clickLog', 'timestamp' => $todayDate]);
        if (!$log) {
            $log = new MainStat();
            $log->SetType('LiveList');
            $log->SetSubType('clickLog');
            $log->SetTimestamp($todayDate);
            $log->SetLiveList([]);
        }
        return $log;
    }

    public function findClickLogs($sinceDate = null, $untilDate = null) {
        if ( !$sinceDate || !preg_match('/\d{8}/', $sinceDate) ) $sinceDate = '20170414';
        if ( !$untilDate || !preg_match('/\d{8}/', $untilDate) )
            $untilDate = \Treto\PortalBundle\Document\SecureDocument::dt2iso(new \DateTime());
        $logs = $this->findBy([
                                'type' => 'LiveList',
                                'subType' => 'clickLog',
                                'timestamp' => ['$gte' => $sinceDate],
                                'timestamp' => ['$lte' => $untilDate]
                              ]);
        return $logs;
    }
    
}
