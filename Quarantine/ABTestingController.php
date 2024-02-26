<?php

namespace Quarantine;;

use ABTesting;
use Legacy\Jot\UserManagement\User;
use Legacy\Jot\Utils\DB;


class ABTestingController
{
    /**
     * Currently registered tests
      * @var //array
     */
    public static $activeTests = [];

    /**
     * Adds a test into active tests list
     * @param  $testName
     * @return
     */
    public static function registerTest($testClass)
    {
        self::$activeTests[$testClass] = call_user_func([$testClass, "getInstance"], $testClass);
    }

    /**
     * Check if the participiant has an assignment already
     * @return
     */
    static function hasAssignment()
    {
        return isset($_SESSION[ABTesting::SESSION]);
    }

    /**
     * Automatically asigns users to tests
     * @return
     */
    static function assignTest($username = false)
    {
        if (!self::hasAssignment()) {
            foreach (self::$activeTests as $test) {
                if ($username) {
                    $test->user = User::find($username);
                } else {
                    $test->user = $_SESSION[COOKIE_KEY];
                }
                if ($test->isAvailable() && $test->checkParticipant()) {
                    $test->joinTest();
                }
            }
        } else {
            $instance = call_user_func([$_SESSION[ABTesting::SESSION]['test_name'], 'getInstance'], $_SESSION[ABTesting::SESSION]['test_name']);
            $instance->user = $_SESSION[COOKIE_KEY];
        }
    }

    /**
     * Assigns a user to a given test
     * @param  $testName
     * @return
     */
    static function assignUserTo($testName, $username = false)
    {
        # if test is not registered, then move along!!!
        if (!isset(self::$activeTests[$testName])) {
            return false;
        }

        if (!self::hasAssignment()) {
            $test = self::$activeTests[$testName];
            if ($username) {
                $test->user = User::find($username);
            } else {
                $test->user = $_SESSION[COOKIE_KEY];
            }
            if ($test->isAvailable() && $test->checkParticipant()) {
                return $test->joinTest();
            }
            return "Not Available";
        } else {
            $instance = call_user_func([$_SESSION[ABTesting::SESSION]['test_name'], 'getInstance'], $_SESSION[ABTesting::SESSION]['test_name']);
            $instance->user = $_SESSION[COOKIE_KEY];
        }
    }

    static function dropGuest()
    {
        foreach (self::$activeTests as $test) {
            $test->dropGuest();
        }
    }

    /**
     *
     * @return
     */
    static function updateGuest()
    {
        foreach (self::$activeTests as $test) {
            $test->user = $_SESSION[COOKIE_KEY];
            $test->updateGuest();
        }
    }

    #############################################
    ##            For Data Listings            ##
    #############################################    

    static function getAllTestsInfo()
    {
        $res = DB::read("SELECT `test_name`, `group_name`, count(`username`) as `total_participant` FROM `test_participants` GROUP BY `test_name`, `group_name`");
        $testsInfo = [];
        $tests = [];
        foreach ($res->result as $line) {
            if (!isset($line['test_name'])) {
                $tests[$line['test_name']] = ["groups" => [], "goals" => []];
            }

            $testName = $line['test_name'];

            $gnames = call_user_func($testName . '::getGroupNames');
            $tests[$line['test_name']]['groups'][$gnames[$line['group_name']]] = $line['total_participant'];
        }

        $res = DB::read("SELECT `test_name`, `goal_name`, count(`goal_name`) as `total_goals`
                         FROM `test_participants` as `tp`, `test_goals` as `tg`
                         WHERE `tp`.`username` = `tg`.`username` GROUP BY `goal_name`, `test_name`");

        foreach ($res->result as $line) {
            $tests[$line['test_name']]['goals'][$line['goal_name']] = $line['total_goals'];
        }

        foreach ($tests as $name => $details) {

            $testsInfo[] = [
                "name" => $name,
                "groups" => $details['groups'],
                "goals" => $details['goals']
            ];
        }

        return $testsInfo;
    }

    /**
     * Returns the total of the given goals by given time range
     * @param  $goals
     * @param  $start
     * @param  $end
     * @return
     */
    static function getGoalsDataByDate($testName, $goals, $group, $start, $end)
    {
        $goalsArray = explode(",", $goals);
        $total = 0;

        $gnames = call_user_func($testName . '::getGroupNames');

        if (is_array($gnames)) {
            $groupNumber = array_search($group, $gnames);

        } else {
            $groupNumber = strpos($gnames, $group);
        }

        if ($group != "all") {
            $totalres = DB::read("SELECT count(*) as `total` FROM `test_participants`
                                  WHERE `test_name`=':testname' 
                                  AND `group_name`=':group'
                                  AND `created_at` > ':start'
                                  AND `created_at` < ':end'", $testName, $groupNumber, $start, $end);

            $total = $totalres->first['total'];
            $res = DB::read("SELECT count(`goal_name`) as `total`, `goal_name`
                             FROM `test_goals` as `tg`, `test_participants` as `tp`
                             WHERE
                                 `tg`.`username`=`tp`.`username`
                                 AND `tp`.`username` IN (
                                     SELECT `username` FROM `test_participants`
                                     WHERE `test_name`=':testname' 
                                     AND `group_name`=':group'
                                     AND `created_at` > ':start'
                                     AND `created_at` < ':end'
                                 )
                                 AND `goal_name` IN ('" . join("', '", $goalsArray) . "')
                             GROUP BY `goal_name`", $testName, $groupNumber, $start, $end);
        } else {
            $totalres = DB::read("SELECT count(*) as `total` FROM `test_participants` WHERE `test_name`=':testname' AND `created_at` > ':start' AND `created_at` < ':end'", $testName, $start, $end);
            $total = $totalres->first['total'];
            $res = DB::read("SELECT count(`goal_name`) as `total`, `goal_name`
                             FROM `test_goals`
                             WHERE
                                 `username` IN (
                                    SELECT `username` FROM `test_participants`
                                    WHERE `test_name`=':testname'
                                    AND `created_at` > ':start'
                                    AND `created_at` < ':end'
                                 )
                                 AND `goal_name` IN ('" . join("', '", $goalsArray) . "')
                             GROUP BY `goal_name`", $testName, $start, $end);
        }

        $goals = [];
        foreach ($res->result as $line) {
            $goals[$line['goal_name']] = $line['total'];
        }
        return ["goals" => $goals, "participantTotal" => $total];
    }
}