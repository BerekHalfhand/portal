<?php

namespace Treto\PortalBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Treto\PortalBundle\Document\DailyStat;
use Treto\PortalBundle\Document\Portal;
use Treto\UserBundle\Document\User;

class CollectDailyStatCommand extends ContainerAwareCommand{
    
    private $sinceDate;
    private $untilDate;
    private $fullnameToLoginTranslationCache = [];
    private $portal_repository = null;
    
    protected function configure() {
        $this
            ->setName('DailyStatistics:GetDailyStat')
            ->setDescription('Collects information for daily statictics for given period of dates.')
            ->addArgument('sinceDate', InputArgument::OPTIONAL, 'Date since which statistics should be collected.')
            ->addArgument('untilDate', InputArgument::OPTIONAL, 'Date until which statistics should be collected.');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        $dateSinceStr = $input->getArgument('sinceDate');
        $dateUntilStr = $input->getArgument('untilDate');
        
        if ($dateSinceStr) {
            $this->sinceDate = new \DateTime($dateSinceStr);
        } else {
            $this->sinceDate = new \DateTime();
            $this->sinceDate->sub(new \DateInterval("P1D"));
        }
        
        if ($dateUntilStr) {
            if ($dateUntilStr > $dateSinceStr) {
                $this->untilDate = new \DateTime($dateUntilStr);
            } else {
                $this->untilDate = new \DateTime($this->sinceDate->format('Ymd'));
            }
        } else {
            $this->untilDate = new \DateTime($this->sinceDate->format('Ymd'));
        }
        
        echo "\n=====\nGetting statistics since date ".$this->sinceDate->format('Ymd');
        echo " until date ".$this->untilDate->format('Ymd')."\n=====\n\n";
        
        $date = $this->sinceDate;
        do {
            echo $date->format('Ymd')."\n";
            $this->getDailyStat($date);
            $date = $date->add(new \DateInterval("P1D"));
        } while ($date->format('Ymd') <= $this->untilDate->format('Ymd'));
        
        echo "done\n";
    }
    
    private function getRepo($shortDocumentName) {
        $repo = $this->getContainer()->get('doctrine_mongodb')->getRepository('TretoPortalBundle:'.$shortDocumentName);
        if ($repo instanceof \Treto\PortalBundle\Document\SecureRepository) {
            $repo->releaseUser();
        }
        return $repo;
    }
    
    private function getUserBundleRepo($shortDocumentName) {
        $repo = $this->getContainer()->get('doctrine_mongodb')->getRepository('TretoUserBundle:'.$shortDocumentName);
        /*if ($repo instanceof \Treto\UserBundle\Document\UserRepository) {
            $repo->releaseUser();
        }*/
        return $repo;
    }

    private function getUserLoginByFullName($fullname) {
        if (isset($this->fullnameToLoginTranslationCache[$fullname]))
            return $this->fullnameToLoginTranslationCache[$fullname];

        if ($this->portal_repository === null) $this->portal_repository = $this->getRepo('Portal');

        $res = $this->portal_repository->findOneBy(['form' => 'Empl', 'FullName' => $fullname]);
        $login = !empty($res) ? $res->GetLogin() : $fullname;
        $this->fullnameToLoginTranslationCache[$fullname] = $login;
        return $login;
    }

    private function CountLikesBetweenDates($likes, $date_from, $date_to, $isLike = true) {
        $isLike = $isLike === true ? 1 : 0;
        $count = 0;
        foreach ($likes as $like) {
        if ($like['isLike'] === $isLike && $like['timestamp'] >= $date_from && $like['timestamp'] < $date_to)
            $count++;
        }
        return $count;
    }
    
    private function getDailyStat($date) {
        $dd =($this->getContainer()->get('doctrine_mongodb.odm.default_configuration')->getDefaultDB());
        $db = $this->getContainer()->get('doctrine_mongodb.odm.default_connection')->selectDatabase($dd);
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        
        $portal_rep = $db->{'Portal'};
        $stat_rep = $this->getRepo('DailyStat');
        $result = [];
        $result['like'] = [
            'likes' => [],
            'dislikes' => []
        ];
        $result['msg'] = [
            'messages' => [],
            'themes' => [],
            'tasks' => [],
            'tasksEnded' => []
        ];
        $result['users'] = [
            'working' => [],
            'employed' => [],
            'fired' => []
        ];
        $result['count'] = [
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
        ];
        $result['fastSlowWorkers'] = [
            'solved' => [],
            'overdue' => []
        ];
        $result['rocketChatMsgs'] = [];
        
        $today = $date->format('Ymd');
        $date_from = $today.'T000000';
        $date_to = $today.'T235959';
        $mongo_start_time = new \MongoDate(strtotime($date_from));
        $mongo_end_time = new \MongoDate(strtotime($date_to));

        $monthAgo = new \DateTime($today);
        $monthAgo->sub(new \DateInterval("P1M"));
        $monthAgo = $monthAgo->format("Ymd");

        $weekAgo = new \DateTime($today);
        $weekAgo->sub(new \DateInterval("P7D"));
        $weekAgo = $weekAgo->format("Ymd");
        
        echo "\n===== Start getting employed and fired people this day\n";
        $query = ['$and' => [['form' => 'Empl'], ['$or' => [['$or' => [['DtDismiss' => ''], ['DtDismiss' => ['$exists' => false]]]], ['DtDismiss' => $today], ['DtWork' => $today]]]]];
        $users = $portal_rep->find($query);

        if ($users != null) {

          while($users->hasNext()) {
            $users->next();
            $cur = $users->current();
            $login = $cur['Login'];

            $user = [];
            $user['workGroup'] = isset($cur['WorkGroup']) ? implode(', ', $cur['WorkGroup']) : '';
            $user['NameInRus'] = (isset($cur['LastName']) ? $cur['LastName'].' ' : '') . (isset($cur['name']) ? $cur['name'] : '');
            $user['FullNameInRus'] = isset($cur['FullNameInRus']) ? $cur['FullNameInRus'] : '';
            $user['FullName'] = isset($cur['FullName']) ? $cur['FullName'] : '';
            $user['Birthday'] = isset($cur['Birthday']) ? $cur['Birthday'] : '';
            $user['DtWork'] = isset($cur['DtWork']) ? $cur['DtWork'] : '';
            $user['DtDismiss'] = isset($cur['DtDismiss']) ? $cur['DtDismiss'] : '';
            $user['Sex'] = $cur['Sex'];

            if (!isset($this->fullnameToLoginTranslationCache[$user['FullName']]))
                $this->fullnameToLoginTranslationCache[$user['FullName']] = $login;
            
            if (isset($cur['DtDismiss']) && $cur['DtDismiss'] === $today) {
              echo "user {$login} has been fired\n";
              $result['users']['fired'][$login] = $user;
            } else {
              echo "user {$login} is working\n";
              $result['users']['working'][$login] = $user;

              if (isset($cur['DtWork']) && $cur['DtWork'] === $today) {
                echo "user {$login} has been employed\n";
                $result['users']['employed'][$login] = $user;
              }
            }
          }
        }
        echo "===== End getting employed and fired people this day\n";
        
        echo "\n===== Start getting count of new messages, tasks and themes\n";
        $query = ['$and' => [['created' => ['$gte' => $date_from]], ['created' => ['$lte' => $date_to]]]];
        
        $docs = $portal_rep->find($query);
        while($docs->hasNext()) {
            $docs->next();
            $cur = $docs->current();
            $result['count']['msg']['messages']++;
            if (isset($cur['authorLogin'])) {
                if (!isset($result['msg']['messages'][$cur['authorLogin']])) {
                    $result['msg']['messages'][$cur['authorLogin']] = 1;
                } else {
                    $result['msg']['messages'][$cur['authorLogin']]++;
                }

                if (isset($cur['form']) && $cur['form'] === 'formTask') {
                    $result['count']['msg']['tasks']++;
                    if (!isset($result['msg']['tasks'][$cur['authorLogin']])) {
                        $result['msg']['tasks'][$cur['authorLogin']] = 1;
                    } else {
                        $result['msg']['tasks'][$cur['authorLogin']]++;
                    }
                }

                if (isset($cur['form']) && $cur['form'] === 'formProcess') {
                    $result['count']['msg']['themes']++;
                    if (!isset($result['msg']['themes'][$cur['authorLogin']])) {
                        $result['msg']['themes'][$cur['authorLogin']] = 1;
                    } else {
                        $result['msg']['themes'][$cur['authorLogin']]++;
                    }
                }
            }
            
        }
        echo "===== End getting count of new messages, tasks and themes\n";

        echo "\n===== Start getting completed tasks\n";
        $query = ['$and' => [['form' => 'formTask'], ['TaskStateCurrent' => 25], ['taskDateCompleted' => ['$gte' => $date_from]], ['taskDateCompleted' => ['$lte' => $date_to]]]];

        $tasksCompleted = $portal_rep->find($query);
        while ($tasksCompleted->hasNext()) {
            $tasksCompleted->next();
            $cur = $tasksCompleted->current();
            if (!isset($cur['taskPerformerLat'])) continue;
            foreach ($cur['taskPerformerLat'] as $performer) {
                if ($performer === '' || $performer === 'null' || $performer === null) {
                    echo "task unid={$cur['unid']} has malformed performer='{$performer}'\n";
                    continue;
                }
                if (strpos($performer, 'CN=') !== -1) {
                    $performerLogin = $this->getUserLoginByFullName($performer);
                } else $performerLogin = $performer;
                if (!isset($result['msg']['tasksEnded'][$performerLogin]))
                    $result['msg']['tasksEnded'][$performerLogin] = ['login' => $performerLogin, 'count' => 0, 'difficulty' => 0];
                $result['msg']['tasksEnded'][$performerLogin]['count']++;
                $result['msg']['tasksEnded'][$performerLogin]['difficulty'] += (isset($cur['Difficulty']) ? $cur['Difficulty'] : 4);
            }
        }
        echo "===== End getting completed tasks\n";
        
        echo "\n===== Start getting likes\n";
        $query = ['$or' => [
            ['$and' => [['LikeDate' => ['$gte' => $date_from]], ['LikeDate' => ['$lte' => $date_to]]]],
            ['$and' => [['LikeNotDate' => ['$gte' => $date_from]], ['LikeNotDate' => ['$lte' => $date_to]]]]
        ]];
        
        $docs = $portal_rep->find($query);
        while($docs->hasNext()) {
            $docs->next();
            $cur = $docs->current();
            $doc = [];
            $doc['subject'] = isset($cur['subject']) ? $cur['subject'] : '';
            $doc['body'] = isset($cur['body']) ? (mb_substr(html_entity_decode(strip_tags($cur['body']), ENT_NOQUOTES, "UTF-8"), 0, (mb_strlen($cur['body'], "UTF-8") > 250 ? 250 : null), "UTF-8") . (mb_strlen($cur['body'], "UTF-8") > 250 ? '...' : '')) : '';
            $doc['parsedSubject'] = (mb_strlen($doc['subject'], "UTF-8") ? $doc['subject'] : (mb_strlen($doc['body'], "UTF-8") > 80 ? mb_substr($doc['body'], 0, 80, "UTF-8") . '...' : $doc['body']));
            $likeList = isset($cur['likes']) ? $cur['likes'] : [];
            $doc['likes'] = $this->CountLikesBetweenDates(@$likeList, $date_from, $date_to, true);
            $doc['dislikes'] = $this->CountLikesBetweenDates(@$likeList, $date_from, $date_to, false);
            $doc['author'] = $cur['authorLogin'];
            $doc['unid'] = $cur['unid'];
            $doc['created'] = $cur['created'];
            if ($doc['likes'] > 0) $result['like']['likes'][$cur['unid']] = $doc;
            if ($doc['dislikes'] > 0) $result['like']['dislikes'][$cur['unid']] = $doc;
        }
        echo "===== End getting likes\n";

        echo "\n===== Connecting to rocketchat base\n";
        if ($this->getContainer()->hasParameter('rocketchatdb_db') && $this->getContainer()->getParameter('rocketchatdb_db') != 'no_rocketchat') {
            $rcu_repo = $this
                ->getContainer()
                ->get('doctrine_mongodb.odm.rocketchat_connection')
                ->selectDatabase($this
                    ->getContainer()
                    ->getParameter('rocketchatdb_db'))
                ->selectCollection('users');

            $rcm_repo = $this
                ->getContainer()
                ->get('doctrine_mongodb.odm.rocketchat_connection')
                ->selectDatabase($this
                    ->getContainer()
                    ->getParameter('rocketchatdb_db'))
                ->selectCollection('rocketchat_message');

            $user_repo = $this->getUserBundleRepo('User');
            if (!$user_repo || !$rcm_repo || !$rcu_repo) echo "user repo or rocketchat base wasn't open\n";
            else {

                $rocketChatUsers = [];

                echo "\n===== Start getting rocketchat users\n";
                $user_data = $rcu_repo->find();
                if ($user_data != null) {
                    while ($user_data->hasNext()) {
                        $user_data->next();
                        $cur = $user_data->current();
                        if (!isset($cur['type']) || $cur['type'] !== 'user') continue;
                        $email = isset($cur['emails'][0]['address']) ? $cur['emails'][0]['address'] : '';
                        if (isset($cur['_id']) && $email !== '') {
                            $user = $user_repo->findOneBy(['$or' => [['email' => $email], ['usernameCanonical' => substr($email, 0, strpos($email, '@'))]]]);
                            if (!$user) {
                                echo "user ".$email." isn't found on the Portal, skipping\n";
                                continue;
                            }
                            $names = $user->getNames();
                            $login = $names[0];
                            $rocketChatUsers[$cur['_id']] = $login;
                        }
                    }
                }
                echo "===== End getting rocketchat users\n";
                
                echo "\n===== Start getting rocketchat messages\n";
                $query = ['$and' => [['ts' => ['$gte' => $mongo_start_time]], ['ts' => ['$lte'=> $mongo_end_time]]]];

                $msg_data = $rcm_repo->find($query);

                if ($msg_data != null) {
                    while ($msg_data->hasNext()) {
                        $msg_data->next();
                        $cur = $msg_data->current();
                        $id = $cur['u']['_id'];
                        if (!isset($rocketChatUsers[$id])) {
                            echo "skipping message: user with id={$id} is not a user of the Portal\n";
                            continue;
                        }
                        $userLogin = $rocketChatUsers[$id];
                        if ($userLogin === '') {
                            echo "skipping message: username (login) resolved to '' for user with id={$id}\n";
                            continue;
                        }
                        if (isset($result['rocketChatMsgs'][$id])) {
                            $result['rocketChatMsgs'][$id]['msgCount']++;
                        } else {
                            $result['rocketChatMsgs'][$id]['login'] = $userLogin;
                            $result['rocketChatMsgs'][$id]['msgCount'] = 1;
                        }
                    }
                }
                echo "===== End getting rocketchat messages\n";

            }
        }

        echo "\n===== Start getting overdued tasks\n";
        $taskStateAccepted = ['TaskStateCurrent' => ['$in' => [5, 15]]];
        $taskStateNotAccepted = ['TaskStateCurrent' => ['$in' => [0, 3, 15]]];

        $overdueTaskAcceptedQuery = ['$and' => [['taskDateRealEnd' => ['$exists' => true]], ['taskDateRealEnd' => ['$ne' => '']], ['taskDateRealEnd' => ['$lt' => $today]], $taskStateAccepted]];
        
        $overdueTaskNotAcceptedQuery = ['$and' => [['taskDateEnd' => ['$exists' => true]], ['taskDateEnd' => ['$ne' => '']], ['taskDateEnd' => ['$lt' => $today]], $taskStateNotAccepted]];

        $overdueQuery = ['$and' => [['form' => 'formTask'], ['$or' => [$overdueTaskAcceptedQuery, $overdueTaskNotAcceptedQuery]]]];

        $overdueTasks = $portal_rep->find($overdueQuery);
        if ($overdueTasks != null) {
            while ($overdueTasks->hasNext()) {
                $overdueTasks->next();
                $cur = $overdueTasks->current();
                if (!isset($cur['taskPerformerLat'])) continue;
                foreach ($cur['taskPerformerLat'] as $performer) {
                    if ($performer === '' || $performer === 'null' || $performer === null) {
                        echo "task unid={$cur['unid']} has malformed performer='{$performer}'\n";
                        continue;
                    }
                    if (strpos($performer, 'CN=') !== -1) {
                        $performerLogin = $this->getUserLoginByFullName($performer);
                    } else $performerLogin = $performer;
                    if (!isset($result['fastSlowWorkers']['overdue'][$performerLogin]))
                        $result['fastSlowWorkers']['overdue'][$performerLogin] = ['login' => $performerLogin, 'count' => 0, 'data' => []];
                    $task = [];
                    $task['unid'] = $cur['unid'];
                    $task['subject'] = isset($cur['subject']) ? $cur['subject'] : (isset($cur['body']) ? mb_substr(html_entity_decode(strip_tags($cur['body']), ENT_NOQUOTES, "UTF-8"), 0, mb_strrpos($doc['body'], ' ', 80), "UTF-8") + (strlen($doc['body']) > 81 ? '...' : '') : '[без заголовка]');
                    $result['fastSlowWorkers']['overdue'][$performerLogin]['count']++;
                    array_push($result['fastSlowWorkers']['overdue'][$performerLogin]['data'], $task);
                }
            }
        }
        echo "===== End getting overdued tasks\n";
        
        echo "\n===== Start parsing fastSlowWorkers from overdued tasks\n";
        foreach ($result['users']['working'] as $user => $val) {
            if (!isset($result['fastSlowWorkers']['overdue'][$user]))
                $result['fastSlowWorkers']['overdue'][$user] = ['login' => $user, 'count' => 0, 'data' => []];
        }
        echo "===== End parsing overdued tasks\n";

        echo "\n===== Start getting solved tasks\n";
        $tasksSolved = $portal_rep->find(['$and' => [['form' => 'formTask'], ['TaskStateCurrent' => 25], ['taskDateCompleted' => ['$gte' => $monthAgo]], ['taskDateCompleted' => ['$lte' => $date_to]]]]);
        if ($tasksSolved != null) {
            while ($tasksSolved->hasNext()) {
                $tasksSolved->next();
                $cur = $tasksSolved->current();
                if (!isset($cur['taskPerformerLat'])) continue;
                foreach ($cur['taskPerformerLat'] as $performer) {
                    if ($performer === '' || $performer === 'null' || $performer === null) {
                        echo "task unid={$cur['unid']} has malformed performer='{$performer}'\n";
                        continue;
                    }
                    if (strpos($performer, 'CN=') !== -1) {
                        $performerLogin = $this->getUserLoginByFullName($performer);
                    } else $performerLogin = $performer;
                    if (!isset($result['fastSlowWorkers']['solved'][$performerLogin]))
                        $result['fastSlowWorkers']['solved'][$performerLogin] = ['login' => $performerLogin, 'count' => 0, 'avgSolveTime' => 0, 'data' => []];
                    $task = [];
                    $task['unid'] = $cur['unid'];
                    $task['subject'] = isset($cur['subject']) ? $cur['subject'] : (isset($cur['body']) ? mb_substr(html_entity_decode(strip_tags($cur['body']), ENT_NOQUOTES, "UTF-8"), 0, mb_strrpos($doc['body'], ' ', 80), "UTF-8") + (strlen($doc['body']) > 81 ? '...' : '') : '[без заголовка]');
                    $start = new \DateTime(substr($cur['taskDateRealStart'],0,15));
                    $end = new \DateTime(substr($cur['taskDateCompleted'],0,15));
                    $task['solvingTime'] = $end->getTimestamp() - $start->getTimestamp();
                    $result['fastSlowWorkers']['solved'][$performerLogin]['count']++;
                    array_push($result['fastSlowWorkers']['solved'][$performerLogin]['data'], $task);
                }
            }
            foreach ($result['fastSlowWorkers']['solved'] as $performer => $val) {
                for ($i = 0; $i < count($result['fastSlowWorkers']['solved'][$performer]['data']); $i++) 
                    $result['fastSlowWorkers']['solved'][$performer]['avgSolveTime'] += $result['fastSlowWorkers']['solved'][$performer]['data'][$i]['solvingTime'];
                $result['fastSlowWorkers']['solved'][$performer]['avgSolveTime'] = round($result['fastSlowWorkers']['solved'][$performer]['avgSolveTime'] / $result['fastSlowWorkers']['solved'][$performer]['count']);
            }
        }
        echo "===== End getting solved tasks\n";

        echo "\n===== Saving collected data\n";
        $statInBase = $stat_rep->findOneBy(['name' => $date->format('Ymd')]);
        if (!$statInBase) {
            echo "===== Creating new DailyStat document\n";
            $statInBase = new DailyStat();
            $statInBase->SetName($today);
        }

        echo "===== Saving data to DailyStat document \n";
        $statInBase->SetMessagesCount($result['count']['msg']['messages']);
        $statInBase->SetTasksCount($result['count']['msg']['tasks']);
        $statInBase->SetThemesCount($result['count']['msg']['themes']);
        $statInBase->SetWorking($result['users']['working']);
        $statInBase->SetEmployed($result['users']['employed']);
        $statInBase->SetFired($result['users']['fired']);
        $statInBase->SetMessagesUsers($result['msg']['messages']);
        $statInBase->SetTasksByUsers($result['msg']['tasks']);
        $statInBase->SetThemesByUsers($result['msg']['themes']);
        $statInBase->SetTasksEndedByUsers($result['msg']['tasksEnded']);
        $statInBase->SetLikes($result['like']['likes']);
        $statInBase->SetDislikes($result['like']['dislikes']);
        $statInBase->SetRocketChatMsgs($result['rocketChatMsgs']);
        $statInBase->SetFastSlowWorkers($result['fastSlowWorkers']);
        
        echo "===== Flushing \n";
        $dm->persist($statInBase);
        $dm->flush();
        
        return $result;
    }
    
}