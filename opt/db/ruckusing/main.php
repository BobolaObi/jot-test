<?php

error_reporting(E_ALL);
if(!defined('RUCKUSING_BASE')){
    define('RUCKUSING_BASE', dirname(__FILE__) );
}

//requirements
date_default_timezone_set('UTC');
require_once RUCKUSING_BASE . '/config/config.inc.php';
require_once RUCKUSING_BASE . '/lib/classes/util/class.Ruckusing_Logger.php';
require_once RUCKUSING_BASE . '/config/database.inc.php';
require_once RUCKUSING_BASE . '/lib/classes/class.Ruckusing_FrameworkRunner.php';

if(!isset($argv)){
    $argv=array_merge(["main.php"], $_GET["argv"]);
}

$main = new Ruckusing_FrameworkRunner($ruckusing_db_config, $argv);
$main->execute();

?>
