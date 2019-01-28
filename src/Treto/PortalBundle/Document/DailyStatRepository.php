<?php

namespace Treto\PortalBundle\Document;

class DailyStatRepository extends SecureRepository
{
    public function findDailyStat($sinceDate = null, $untilDate = null) {
        if ( !$sinceDate || !preg_match('/\d{8}/', $sinceDate) ) $sinceDate = '20160101';
        if ( !$untilDate || !preg_match('/\d{8}/', $untilDate) )
            $untilDate = \Treto\PortalBundle\Document\SecureDocument::dt2iso(new \DateTime());
        $dailyStat = $this->findBy(['$and' => [
                                                ['name' => ['$gte' => $sinceDate]],
                                                ['name' => ['$lte' => $untilDate]]
                                              ]]);
        return $dailyStat;
    }
    
}
