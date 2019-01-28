<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Treto\PortalBundle\Document\Portal;
use Treto\PortalBundle\Document\DailyStat;
use Treto\PortalBundle\Document\MainStat;

class StatController extends AbstractDiscussionController
{
  
  private $fullnameToLoginTranslationCache = [];
  private $portal_repository = null;

  private function getUserLoginByFullName($fullname) {
    if (isset($this->fullnameToLoginTranslationCache[$fullname]))
      return $this->fullnameToLoginTranslationCache[$fullname];

    if ($this->portal_repository === null)
      $this->portal_repository = $this->getRepo('Portal');

    $res = $this->portal_repository->findOneBy(['form' => 'Empl', 'FullName' => $fullname]);
    $login = !empty($res) ? $res->GetLogin() : $fullname;
    $this->fullnameToLoginTranslationCache[$fullname] = $login;
    return $login;
  }

  private function filterLikesNewerThanDate($likes, $datetime, $isLike = true) {
    $isLike = $isLike === true ? 1 : 0;
    $res = [];
    foreach ($likes as $like) {
      if ($like['isLike'] === $isLike && $like['timestamp'] > $datetime)
        $res[] = $like['timestamp'];
    }
    usort($res, function($a, $b) {
      return $a > $b;
    });
    return $res;
  }

  private function getLikesDislikes($days, $limit, $isLike) {
    $username = $this->getUser()->getNames()[0];
    $searchParams = [
      'lastLikeDate' => $isLike ? 'LikeDate' : 'LikeNotDate',
      'likeDateList' => $isLike ? 'LikeDateList' : 'LikeNotDateList',
      'liveListSubType' => $isLike ? 'likes' : 'dislikes',
      'sortByFirstPredicate' => $isLike ? 'likes' : 'dislikes',
      'sortBySecondPredicate' => $isLike ? 'LikeDate' : 'LikeNotDate'
    ];

    $now = new \DateTime();
    $timestamp = '';

    $expireDate = new \DateTime();
    $expireDate->sub(new \DateInterval("P".($days + 1)."D"));
    $expireDate = $expireDate->format('Ymd').'T'.$expireDate->format('His');

    $daysAgo = new \DateTime();
    $daysAgo->sub(new \DateInterval("P{$days}D"));
    $daysAgo = $daysAgo->format('Ymd').'T'.$daysAgo->format('His');

    $list = $this->getRepo('MainStat')->findOneBy(
      ['$and' => [
                  ['type' => 'LiveList'],
                  ['subType' => $searchParams['liveListSubType']],
                  ['forUser' => $username],
                  ['daysCount' => $days],
                  ['limit' => $limit]
                 ]
      ]);
    if ($list != null && !$list->GetUpdateNeeded() && $list->GetTimestamp() > $expireDate) {
      return $list->GetLiveList();
    }

    $result = [];

    $likes = $this->getSecureRepo('Portal')
                  ->findBy([$searchParams['lastLikeDate'] => ['$gte' => $expireDate]]);
    if ($likes != null) {
      foreach ($likes as $like){

        if ($like->HasParent()) {
          $main = $this->getMainDocFor($like, true);
          if ( !$main  ) continue;
        }

        $cur = $like->GetDocument();
        $doc = [];

        $doc['subject'] = isset($cur['subject']) ? $cur['subject'] : '';
        $doc['body'] = isset($cur['body']) ? (mb_substr(html_entity_decode(strip_tags($cur['body']), ENT_NOQUOTES, "UTF-8"), 0, (mb_strlen($cur['body'], "UTF-8") > 250 ? 250 : null), "UTF-8") . (mb_strlen($cur['body'], "UTF-8") > 250 ? '...' : '')) : '';
        $doc['parsedSubject'] = (mb_strlen($doc['subject'], "UTF-8") ? $doc['subject'] : (mb_strlen($doc['body'], "UTF-8") > 80 ? mb_substr($doc['body'], 0, 80, "UTF-8") . '...' : $doc['body']));
        $likeList = isset($cur['likes']) ? $cur['likes'] : [];
        $doc[$searchParams['likeDateList']] = $this->filterLikesNewerThanDate(@$likeList, $daysAgo, $isLike);
        $doc[$searchParams['liveListSubType']] = count($doc[$searchParams['likeDateList']]);
        $doc['sendShareFrom'] = isset($cur['sendShareFrom']) ? $cur['sendShareFrom'] : null;
        $doc['shareAuthorLogin'] = isset($cur['shareAuthorLogin']) ? $cur['shareAuthorLogin'] : null;
        $doc['AuthorRus'] = isset($cur['AuthorRus']) ? $cur['AuthorRus'] : null;
        $doc['author'] = $cur['authorLogin'];
        $doc['unid'] = $cur['unid'];
        $doc['created'] = $cur['created'];
        $doc[$searchParams['lastLikeDate']] = isset($cur[$searchParams['lastLikeDate']]) ? $cur[$searchParams['lastLikeDate']] : '';

        $expiredLikeDateList = $this->filterLikesNewerThanDate(@$likeList, $expireDate, $isLike);
        $expiredLikeDateList = array_slice($expiredLikeDateList, 0, count($expiredLikeDateList) - count($doc[$searchParams['likeDateList']]));

        $doc['expiredCount'] = count($expiredLikeDateList);

        foreach ($expiredLikeDateList as $expired) {
          $exDate = new \DateTime($expired);
          $exDate->add(new \DateInterval("P1D"));
          $exDate = $exDate->format('Ymd').'T'.$exDate->format('His');
          if ($timestamp === '' || $timestamp > $exDate) $timestamp = $exDate;
        }

        if ( $doc[$searchParams['liveListSubType']] > 0)
          $result[] = $doc;
      }
    }

    usort($result, function($a, $b) use ($searchParams) {
      $res = $b[$searchParams['sortByFirstPredicate']] - $a[$searchParams['sortByFirstPredicate']];
      if (!$res) return $b[$searchParams['sortBySecondPredicate']] > $a[$searchParams['sortBySecondPredicate']] ? 1 : -1;
      return $res;
    });

    $result = array_slice($result, 0, $limit);

    foreach ($result as $like) {
      foreach ($like[$searchParams['likeDateList']] as $date)
        if ($timestamp === '' || $timestamp > $date) $timestamp = $date;
    }

    if (!$list) {
      $list = new MainStat();
      $list->SetType('LiveList');
      $list->SetSubType($searchParams['liveListSubType']);
      $list->SetDaysCount($days);
      $list->SetLimit($limit);
      $list->SetForUser($username);
    }

    $list->SetLiveList($result);
    $list->SetUpdateNeeded(false);
    $list->SetTimestamp($timestamp);
    $list->SetModified();
    $this->getDM()->persist($list);
    $this->getDM()->flush();
    $this->getDM()->clear();

    return $result;
  }

  public function getLikesAction() {
    $days = (int) $this->param('daysCount', 7);
    $limit = (int) $this->param('limit', 20);

    return $this->success(['documents' => $this->getLikesDislikes($days, $limit, true)]);
  }

  public function getDislikesAction() {
    $days = (int) $this->param('daysCount', 7);
    $limit = (int) $this->param('limit', 20);

    return $this->success(['documents' => $this->getLikesDislikes($days, $limit, false)]);
  }

  public function getPopularThemesAction() {
    $days = (int) $this->param('daysCount', 7);
    $limit = (int) $this->param('limit', 10);
    $user = $this->param('user', 'all');

    $list = [];
    $log = $this->GetRepo('MainStat')->findReadWriteLog();
    if ($user === 'all') {
      $tmp = $log->GetReadWriteLog();
      foreach ($tmp as $user) {
        foreach ($user as $unid => $data) {
          if ( !isset($list[$unid]) )  $list[$unid] = ['read' => 0, 'write' => 0, 'unid' => $unid];
          if ( isset($data['read']) )  $list[$unid]['read']  += sizeof($data['read']);
          if ( isset($data['write']) ) $list[$unid]['write'] += sizeof($data['write']);
        }
      }
    } else {
      $tmp = $log->GetReadWriteLogForUser($user);
      foreach ($tmp as $unid => $data) {
        if ( !isset($list[$unid]) )  $list[$unid] = ['read' => 0, 'write' => 0, 'unid' => $unid];
        if ( isset($data['read']) )  $list[$unid]['read']  += sizeof($data['read']);
        if ( isset($data['write']) ) $list[$unid]['write'] += sizeof($data['write']);
      }
    }

    usort($list, function($a, $b) {
      $res = $b['read'] - $a['read'];
      if ($res === 0) return $b['write'] - $a['write'];
      return $res;
    });

    $unids = [];
    foreach ($list as $i => $data) {
      $unids[] = $data['unid'];
    }

    $offset = 0;
    $result = [];
    do {
      $docs = $this->getSecureRepo('Portal')->findBy(['unid' => ['$in' => array_slice($unids, $offset, $limit - sizeof($result))]]);
      $offset += $limit - sizeof($result);
      foreach ($list as $data) {
        foreach ($docs as $theme) {
          if ($data['unid'] === $theme->GetDocument()['unid']) {
            $cur = $theme->GetDocument();
            $doc = [];          
            $doc['subject'] = isset($cur['subject']) ? $cur['subject'] : '';
            $doc['body'] = isset($cur['body']) ? (mb_substr(html_entity_decode(strip_tags($cur['body']), ENT_NOQUOTES, "UTF-8"), 0, (mb_strlen($cur['body'], "UTF-8") > 250 ? 250 : null), "UTF-8") . (mb_strlen($cur['body'], "UTF-8") > 250 ? '...' : '')) : '';
            $doc['parsedSubject'] = (mb_strlen($doc['subject'], "UTF-8") ? $doc['subject'] : (mb_strlen($doc['body'], "UTF-8") > 80 ? mb_substr($doc['body'], 0, 80, "UTF-8") . '...' : $doc['body']));
            $doc['unid'] = $cur['unid'];
            $doc['created'] = $cur['created'];
            $doc['modified'] = $cur['modified'];
            $doc['shortModifiedDate'] = substr($doc['modified'], 6, 2).'.'.substr($doc['modified'], 4, 2);
            $doc['countMess'] = isset($cur['countMess']) ? (int) $cur['countMess'] : 0;
            $doc['countOpen'] = isset($cur['countOpen']) ? (int) $cur['countOpen'] : 0;
            $doc['authorLastMess'] = isset($cur['authorLastMess']) ? $cur['authorLastMess'] : '';
            $doc['dateLastMess'] = isset($cur['dateLastMess']) ? $cur['dateLastMess'] : '';
            if (isset($cur['authorLogin'])) {
              $doc['authorLogin'] = $cur['authorLogin'];
            } else {
              $space_1 = strpos($cur['Author'], ' ');
              $space_2 = strpos($cur['Author'], ' ', $space_1 + 1);
              $login1 = substr($cur['Author'], 0, 1);
              $login2 = substr($cur['Author'], $space_1 + 1, $space_2 > 0 ? $space_2 - $space_1 - 1 : strlen($cur['Author']) - $space_1 - 1);
              $doc['composedLogin'] = true;
              $login = strtolower($login1.$login2);
              $doc['authorLogin'] = $login;
            }
            $doc['countOpenDuringPeriod'] = $data['read'];
            $doc['countMessDuringPeriod'] = $data['write'];
            $result[] = $doc;
            break;
          }
        }
      }
    } while (sizeof($result) < $limit && $offset < sizeof($unids));

    return $this->success(['documents' => $result]);
  }

  public function getMainStatAction() {
    $mainStat = $this->getRepo('MainStat')->findMainStat();
    if ($mainStat->GetModified() > $mainStat->dt2iso((new \DateTime())->sub(new \DateInterval("PT1H")), true) &&
        isset( $mainStat->GetLiveList()['users'] )) {
      return $this->success( ['documents' => $mainStat->GetLiveList()] );
    }

    $yesterday = $mainStat->dt2iso((new \DateTime())->sub(new \DateInterval("P1D")));
    $weekAgo = $mainStat->dt2iso((new \DateTime())->sub(new \DateInterval("P7D")));
    $monthAgo = $mainStat->dt2iso((new \DateTime())->sub(new \DateInterval("P30D")));

    $workers = [];
    $result = [
      'users' => [
        'count' => 0,
        'agedCount' => 0,
        'averageAge' => 0,
        'loggedTodayCount' => 0,
        'maleCount' => 0,
        'femaleCount' => 0,
        'notMaleNotFemaleCount' => 0,
        'employedByDate' => [],
        'firedByDate' => []
      ],
      'chatterboxes' => [],
      'fastSlowWorkers' => [
        'solved' => [],
        'overdue' => []
      ]
    ];

    $db = $this->get('doctrine_mongodb.odm.default_connection')
               ->selectDatabase(
                 $this->get('doctrine_mongodb.odm.default_configuration')
                      ->getDefaultDB()
               );

    $portalRepo = $this->getRepo('Portal');
    $dailyStatRepo = $this->getRepo('DailyStat');
    $userRepo = $this->get('doctrine_mongodb')->getRepository('TretoUserBundle:User');

    $users = $portalRepo->findBy(['$and' => [
                                            ['form' => 'Empl'],
                                            ['$or' => [
                                                        ['DtDismiss' => ['$exists' => false]],
                                                        ['DtDismiss' => ''],
                                                        ['DtDismiss' => ['$gte' => $monthAgo]]
                                                      ]
                                            ]
                                          ]
                                ]);

    if ($users !== null) {
      foreach ($users as $user) {
        $tmp = [];
        $tmp['Login'] = $user->GetLogin();
        $tmp['Birthday'] = $user->GetBirthday();
        $tmp['Sex'] = $user->GetSex();
        $tmp['DtWork'] = $user->GetDtWork();
        $tmp['DtDismiss'] = $user->GetDtDismiss();
        
        if ($tmp['DtDismiss'] !== null &&
            $tmp['DtDismiss'] !== '') {
          if ( !isset($result['users']['firedByDate'][ $tmp['DtDismiss'] ]) ) {
            $result['users']['firedByDate'][ $tmp['DtDismiss'] ] = [];
          }
          $result['users']['firedByDate'][ $tmp['DtDismiss'] ][] = $tmp;
        } else {
          $workers[ $tmp['Login'] ] = true;
          if ($tmp['DtWork'] !== null &&
              $tmp['DtWork'] !== '' &&
              $tmp['DtWork'] > $monthAgo) {
            if ( !isset($result['users']['employedByDate'][ $tmp['DtWork'] ]) ) {
              $result['users']['employedByDate'][ $tmp['DtWork'] ] = [];
            }
            $result['users']['employedByDate'][ $tmp['DtWork'] ][] = $tmp;
          }
        }

        $result['users']['count'] += 1;
        
        if ($tmp['Sex'] == 2) {
          $result['users']['femaleCount'] += 1;
        } elseif ($tmp['Sex'] == 1) {
          $result['users']['maleCount'] += 1;
        } else {
          $result['users']['notMaleNotFemaleCount'] += 1;
        }

        if ($tmp['Birthday'] !== null &&
            $tmp['Birthday'] !== '') {
          $result['users']['agedCount'] += 1;
          $result['users']['averageAge'] += ( (new \DateTime())->GetTimestamp() -
                                     (new \DateTime($tmp['Birthday']))->GetTimestamp() );
        }

      }

      if ($result['users']['agedCount'] !== 0) {
        $result['users']['averageAge'] /= $result['users']['agedCount'];
      }
      $result['users']['averageAge'] = round( $result['users']['averageAge'] / (3600*24*365) );
    }
    
    $todayFilter = new \MongoDate(strtotime( (new \DateTime())->format('Y-m-d') ));
    $result['users']['loggedTodayCount'] = $db->selectCollection('User')
                                              ->find(['lastLogin' => ['$gte' => $todayFilter]])
                                              ->count();

    $result['messagesTotalCount'] = $db->selectCollection('Portal')
                                        ->find(['form' => [
                                                            '$in' => [
                                                                        'formTask',
                                                                        'formProcess',
                                                                        'message',
                                                                        'messagebb'
                                                                      ]
                                                          ]
                                              ])
                                        ->count();

    $weekStatData = $dailyStatRepo->findBy(['name' => ['$gte' => $weekAgo]]);
    if ($weekStatData !== null) {

      foreach ($workers as $username => $value) {
        $result['chatterboxes'][$username] = ['msgCount' => 0, 'login' => $username];
      }

      foreach ($weekStatData as $day) {

        $messagesByUsers = $day->GetMessagesUsers();
        foreach ($messagesByUsers as $username => $msgCount) {
          if ( isset($workers[$username]) ) {
            $result['chatterboxes'][$username]['msgCount'] += $msgCount;
          }
        }

        if ($day->GetName() === $yesterday && $day->GetFastSlowWorkers() !== null) {
          $fastSlowWorkers = $day->GetFastSlowWorkers();
          foreach ($fastSlowWorkers as $type => $users) {
            foreach ($users as $username => $data) {
              if ( isset($workers[$username]) ) {
                $result['fastSlowWorkers'][$type][$username] = $data;
                if ($type === 'solved' && $result['fastSlowWorkers'][$type][$username]['avgSolveTime'] < 0) {
                  $result['fastSlowWorkers'][$type][$username]['avgSolveTime'] *= -1;
                }
              }
            }
          }
        }

      }

      usort($result['chatterboxes'], function($a, $b) {
        return $b['msgCount'] - $a['msgCount'];
      });

      usort($result['fastSlowWorkers']['solved'], function($a, $b) {
        return $b['avgSolveTime'] - $a['avgSolveTime'];
      });

      usort($result['fastSlowWorkers']['overdue'], function($a, $b) {
        return $b['count'] - $a['count'];
      });
    }

    $mainStat->SetLiveList($result);
    $mainStat->SetModified();
    $this->getDM()->persist($mainStat);
    $this->getDM()->flush();
    $this->getDM()->clear();

    return $this->success(['documents' => $result]);
  }
  
  public function statAction() {
    $sinceDate = $this->param('sinceDate', null);
    $untilDate = $this->param('untilDate', null);

    $dd =($this->get('doctrine_mongodb.odm.default_configuration')->getDefaultDB());
    $db = $this->get('doctrine_mongodb.odm.default_connection')->selectDatabase($dd);
    $dm = $this->getDM();
    
    $result = [];
    
    $stat_rep = $this->getRepo('DailyStat');

    function getDefaultObj() {
      return [
        'like' => [
            'likes' => [],
            'dislikes' => []
        ],
        'msg' => [
            'messages' => [],
            'themes' => [],
            'tasks' => [],
            'tasksEnded' => [],
            'rocketChatMsgs' => []
        ],
        'users' => [
            'working' => [],
            'employed' => [],
            'fired' => []
        ],
        'count' => [
            'msg' => [
                'messages' => 0,
                'themes' => 0,
                'tasks' => 0
            ],
            'users' => [
                'working' => 0,
                'employed' => 0,
                'fired' => 0
            ],
            'like' => [
                'likes' => 0,
                'dislikes' => 0
            ]
        ],
        'fastSlowWorkers' => [
          'overdue' => [],
          'solved' => []
        ],
        'date' => ''
      ];
    };
    
    $stat = $stat_rep->findDailyStat($sinceDate, $untilDate);
    if ($stat) {
      foreach ($stat as $entry) {
        $cur = $entry->getDocument();

        $i = count($result);
        $result[$i] = getDefaultObj();

        $result[$i]['date'] = $cur['name'];
        if (!isset($result[$i]['date']) || $result[$i]['date'] == null) $result[$i]['date'] = '';

        if (isset($cur['messagesCount'])) {
          $result[$i]['count']['msg']['messages'] = $cur['messagesCount'];
        }

        if (isset($cur['tasksCount'])) {
          $result[$i]['count']['msg']['tasks'] = $cur['tasksCount'];
        }

        if (isset($cur['themesCount'])) {
          $result[$i]['count']['msg']['themes'] = $cur['themesCount'];
        }
        
        if (isset($cur['working'])) {
          $result[$i]['users']['working'] = $cur['working'];
        }
        
        if (isset($cur['employed'])) {
          $result[$i]['users']['employed'] = $cur['employed'];
        }
        
        if (isset($cur['fired'])) {
          $result[$i]['users']['fired'] = $cur['fired'];
        }

        if (isset($cur['messagesUsers'])) {
          $result[$i]['msg']['messages'] = $cur['messagesUsers'];
        }

        if (isset($cur['tasksByUsers'])) {
          $result[$i]['msg']['tasks'] = $cur['tasksByUsers'];
        }

        if (isset($cur['themesByUsers'])) {
          $result[$i]['msg']['themes'] = $cur['themesByUsers'];
        }

        if (isset($cur['tasksEndedByUsers'])) {
          $result[$i]['msg']['tasksEnded'] = $cur['tasksEndedByUsers'];
        }

        if (isset($cur['rocketChatMsgs'])) {
          $result[$i]['msg']['rocketChatMsgs'] = $cur['rocketChatMsgs'];
        }
        
        if (isset($cur['likes'])) {
          $result[$i]['like']['likes'] = $cur['likes'];
        }
        
        if (isset($cur['dislikes'])) {
          $result[$i]['like']['dislikes'] = $cur['dislikes'];
        }
      }
    }
    
    return $this->success(['dailyStat' => count($result) === 0 ? [getDefaultObj()] : $result]);
  }

  public function getMessagesByUserAction($query) {
    $query = json_decode(base64_decode($query), true);
    $query['since'] = (string) $query['since'];
    $query['until'] = (string) $query['until'];

    if ($query['user'] === '') {
      return $this->fail('User was not provided');
    }

    if ($query['since'] === '') {
      $query['since'] = '20160101T000000';
    } else {
      $query['since'] .= 'T000000';
    }

    if ($query['until'] === '') {
      $query['until'] = new \DateTime();
      $query['until'] = $query['until']->format('Ymd').'T235959';
    } else {
      $query['until'] .= 'T235959';
    }

    $result = [];
    $portal = $this->getSecureRepo('Portal');
    $messages = $portal->findBy(['$and' => [['authorLogin' => $query['user']], ['created' => ['$gte' => $query['since']]], ['created' => ['$lte' => $query['until']]]]]);

    if ($messages !== null) {
      $parentUnidsUnique = [];
      $parentUnids = [];
      foreach ($messages as $message) {
        if ( $message->HasParent() && !isset($parentUnidsUnique[$message->getParentID()]) ) {
          $parentUnids[] = $message->getParentID();
          $parentUnidsUnique[$message->getParentID()] = true;
        }
      }

      $parents = $portal->findBy(['unid' => ['$in' => $parentUnids]]);
      $canReadThemes = [];
      if ($parents !== null) {
        foreach ($parents as $parent) {
          $canReadThemes[$parent->getUnid()] = true;
        }
      }

      foreach ($messages as $message) {
        if ( $message->HasParent() && !isset($canReadThemes[$message->getParentID()]) )
          continue;
        
        $msg = [
          'authorLogin' => $message->GetAuthorLogin(),
          'subject' => $message->GetSubject(),
          'body' => $message->GetBody(),
          'created' => $message->GetCreated(),
          'modified' => $message->GetModified(),
          'unid' => $message->GetUnid()
        ];
        $result[] = $msg;
      }
    }

    return $this->success(['documents' => $result]);
  }

  public function logClickAction(Request $request) {
    $data = json_decode($request->getContent(), true);
    $label = $data['label'];

    if (!$label) return $this->fail('Button label wasn\'t provided'.':'.json_encode($data));

    $log = $this->GetRepo('MainStat')->findClickLogForToday();
    $list = $log->GetLiveList();
    if ( !isset($list[$label]) ) $list[$label] = 1;
    else $list[$label]++;
    $log->SetLiveList($list);

    $log->SetModified();
    $this->getDM()->persist($log);
    $this->getDM()->flush();
    $this->getDM()->clear();

    return $this->success();
  }

  public function getClickStatAction() {
    $sinceDate = $this->param('sinceDate', null);
    $untilDate = $this->param('untilDate', null);
    
    $result = [];
    $logs = $this->GetRepo('MainStat')->findClickLogs($sinceDate, $untilDate);
    if ($logs) {
      foreach ($logs as $log) {
        $result[$log->GetTimestamp()] = $log->GetLiveList();
      }
    }

    return $this->success(['documents' => $result]);
  }
}

