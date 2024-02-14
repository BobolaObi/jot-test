<?php

# Get all the options from this file

use Legacy\Jot\Configs as Configs;

require_once(__DIR__."/../vendor/autoload.php");

/**
 * Initial configuration file, Set everything for JotForm
 * @remember Line order of this file is really important don't change the order of any line
 * @package JotForm_Site_Management
 * @version $Rev: 4098 $
 * @copyright Copyright (c) 2009, Interlogy LLC
 */
# Fix for proxy forwarded IP addresses

/* old code base will be littered with warnings now. */
DEFINE("ERROR_LEVEL_LENIENT", E_ALL & ~E_WARNING & ~E_NOTICE);
error_reporting(ERROR_LEVEL_LENIENT);


if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    # This addresses comes as comma seperated if more than one proxy was used.
    $forwardedIps = preg_split("/\s*,\s*/",
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''); # Split addresses
    $ip = $forwardedIps[0];                                                    # Use the first address
    $_SERVER['REMOTE_ADDR'] = $ip;                                             # Overwrite remote address to be used in code
}

# If request behind the proxy is HTTPS then notify code to use SSL options
if (isset($_SERVER["HTTP_FRONT_END_HTTPS"])) {
    $_SERVER['HTTPS'] = $_SERVER['HTTP_FRONT_END_HTTPS'];
}

/**
 * Auto load function which loads all classes automatically no need to write includes for each class
 * @param object $class_name
 * @return
 */

function pending_pedrecation_autoload($class_name)
{

    # In order to provide a decent warning message
    $failedPaths = [];

    # this is a JotForm specific situation. We have collected all exceptions together
    if (strpos($class_name, 'Exception') !== false) {
        $path = ROOT . "/lib/classes/exceptions/AllExceptions.php";
        if (file_exists($path)) {
            require_once $path;
            return true; # file included no need to go forward
        }
    }

    # If file name contains unserscore convert them to folder marks
    if (strpos($class_name, '_') !== false) {
        $className = str_replace("_", "/", $class_name);
    } else {
        $className = $class_name;
    }

    # This where we usually contain all our classes
    $path = ROOT . "/lib/classes/" . $className . '.php';

    # Check the obvious place first
    if (file_exists($path)) {
        require_once $path;
        return true; # file included no need to go forward
    } else {
        # If not found then we should check PHPs include paths
        $includePaths = explode(":", get_include_path());

        # Loop through all defined paths and search for the file
        foreach ($includePaths as $ipath) {

            if ($ipath == ".") {
                continue;
            }

            $triedPath = $ipath . "/" . $className . ".php";
            if (file_exists($triedPath)) {
                require_once $triedPath;
                return true; # file included no need to go forward
            } else {
                $failedPaths[] = $triedPath;
            }
        }
    }

    # Add last tried path to failed paths list
    $failedPaths[] = $path;
    $error = $class_name . " class cannot be found under jotform library.<br>\nFollowing paths have been checked:<br>\n  " . join("<br>\n  ", $failedPaths) . "<br>\n";

    return false;
}

# Should register autoloader for phpunit
//spl_autoload_register('pending_pedrecation_autoload');

/**
 * Fix extra slashes in path
 * @param object $path
 * @param object $isfile [optional]
 * @return
 */
function P($path, $isfile = false)
{
    $fixedpath = preg_replace("/\:\/(\w)/", "://$1", preg_replace("/\/+/", "/", $path . ($isfile ? "" : "/")));
    return $fixedpath;
}

function isDev()
{
    return $_SERVER['JOTFORMS_MODE'] == 'dev'
        || (Server::isLocalhost() && !getenv("DOCKER_MODE"));
}

$host = $_SERVER['HTTP_HOST'] ?? '';
$folder = ($host == "localhost" || preg_match('/^192/', $host)) ? Configs::SUBFOLDER : "/"; # Folder where jotform is located under document root

# On application always use subfolder from settings 
if (Configs::APP) {
    $folder = Configs::SUBFOLDER;
}

$domain = "";
if (preg_match('/localhost$/', $host) == 0) {
    [$www, $domain, $com] = explode(".", $host);
    $domain = $domain . "." . $com;
}

$server = $_SERVER['HTTP_HOST'] ?? '';
if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $a = explode(',', $_SERVER['HTTP_X_FORWARDED_HOST']);
    $server = $a[sizeof($a) - 1];
}
$server = trim(''.'' . $server);

$server_path = ((preg_match("/^forms.*\.datalynk\.ca$/", $server) || preg_match("/^forms.*\.intranet$/", $server)) ? '' : '/forms');

$user_agent = empty($_SERVER['HTTP_USER_AGENT']) ? 'none' : $_SERVER['HTTP_USER_AGENT'];
$isIE = false;
if (preg_match('/MSIE/i', $user_agent)) {
    $isIE = true;
}

# Determine the protocol HTTP VS HTTPS
$protocol = 'https://';
if (isDev()) {
    $protocol = 'http://';
}

if (($_SERVER['HTTP_X_FORWARDED_PORT'] ?? 0) > 0 &&
    !in_array($_SERVER['HTTP_X_FORWARDED_PORT'], [80, 443])) {
    $server = "$server:" . $_SERVER['HTTP_X_FORWARDED_PORT'];
}

$server_with_www = $server;
if ((strrpos($server, "www") === false) && (strrpos($server, "auxiliumgroup.ca") !== false)) {
    $server_with_www = "www." . $server;
}

$css_path = '.';

define('DROOT', $_SERVER["DOCUMENT_ROOT"]);                             # Document Root
define('APP', defined('Configs::APP') ? Configs::APP : false);           # Defines if this working copy is an application or not
define('INST_FOLDER', ((strstr(DROOT, $folder)) ? "/" : $folder));      # Installation folder
define('SUB_FOLDER', "");                                               # Installed also under a sub folder
define('ROOT', P(DROOT . "/" . INST_FOLDER . "/" . SUB_FOLDER . "/"));            # Root Path
define("DOMAIN", $domain);                                              # Site domain, ex: jotform.com, use empty if localhost
define("HTTP_URL", P($protocol . $server . $server_path));  # SSL version of Current domain
define("SSL_URL", P("https://" . $server . $server_path));  # SSL version of Current domain
define("UPLOAD_HTTP_URL", P($protocol . $server_with_www . $server_path));
define("CSS_PATH", $css_path);
define("IS_SECURE", ($protocol == "https://"));                         # Checks if the URL is secure or not
define("UPLOAD_URL", P(UPLOAD_HTTP_URL . "/uploads/"));                   # Uploads URL
define("CLOUD_UPLOAD_URL", Configs::CLOUD_UPLOAD_ALIAS);                # Upload URL for S3 cloud
define('IS_WINDOWS', strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');        # Check if the server is windows or not
define('VERSION', '3.0.REV');                                           # This is changed using Hudson, REV here is replaced with Hudson build number.
define('SVNREV', '3.0.SVN_REV');                                        # This is changed using Hudson, REV here is replaced with Hudson build number.
define('IMAGE_FOLDER', P(ROOT . "/images/", true));                      # Keeps where all images stored
define('SPRITE_FOLDER', P(ROOT . "/sprite/", true));                     # Keeps where the sprite images will be stored
define('JS_FOLDER', P(ROOT . "/js", true));                               # Keeps where javascript files are stored
define('CSS_FOLDER', P(ROOT . "/css", true));                            # Keeps where all css files are stored
define('STYLE_FOLDER', P(CSS_FOLDER . "/" . "styles", true));           # Folder that we keep styles in it
define('COOKIE_KEY', 'jtuc');                                           # Define session and cookie keys
define('CONTENT_COOKIE_NAME', 'jcmc');                                   # Content manager user connection cookie name
define('CONTENT_COOKIE', 'jcm');
define('DATALYNK_SLICES', 'dlc');                                        # NEW Content manager user connection cookie name
define('PROTOCOL', $protocol);                                          # "http" or "https"
define('DELAY_EMAILS', false);                                          # Stores email on database instead of sending them
define('NOREPLY', Configs::NOREPLY);                                    # Use this address for noreply emails
define('NOREPLY_NAME', Configs::NOREPLY_NAME);                          # Use this name for noreply emails
define('NOREPLY_SUPPORT', Configs::NOREPLY_SUPPORT);                    # Use this name for support noreply emails
define('BETA', false);                                                  # Mark this installation as beta
define('SCHEMA_FILE_PATH', P(ROOT . "/opt/db_schema/jotform_new.json", true));    # Database schema file, @DEPRECATED
define('API_URL_BASE', "api");                                          # This is the base url folder name for the API

# Include the Amazon Web Services SDK
include_once ROOT . "lib/classes/AWSSDK/sdk.class.php";

# Include the API library
include_once ROOT . "lib/classes/API/lib.config.php";

# Include the Deploy library
include_once ROOT . "lib/classes/deploy/lib.config.php";

# Include the Payments library
include_once ROOT . "lib/classes/Payments/lib.config.php";

# Include the utils library
include_once ROOT . "lib/classes/utils/lib.config.php";

# Include the utils library
include_once ROOT . "lib/classes/couch/lib.config.php";

if (Server::isMaxCDN() && !Server::isCacheable() && HTTP_URL !== "http://www.jotform.com/") {
    Utils::redirect("http://www.jotform.com");
}

# Sets everything in debug mode
define('DEBUGMODE', Utils::getCookie("DEBUG") == 'debug=yes');

# if we are in the debug mode then set the debug options
if (DEBUGMODE && Utils::getCookie('debug_options')) {
    $GLOBALS['debug_options'] = json_decode(stripslashes(Utils::getCookie('debug_options')), true);
    if (!is_array($GLOBALS['debug_options'])) {
        $GLOBALS['debug_options'] = [];
    }
}

# if jotform.com host is used then move user to www.jotform.com
if ($host == "jotform.com" && ($_SERVER['REQUEST_URI'] == "" || $_SERVER['REQUEST_URI'] == "/")) {
    header("HTTP/1.1 301 Moved Permanently");
    header("Status: 301 Moved Permanently");
    header("Location: http://www.jotform.com/");
    exit;
}

# This will disable all the accesses to submission pages and reports
# will display a maintenance message. These messages placed on
# lib/includes/submissions.php
# lib/classes/DataListings.php
# js/common.js
# files.
define('DISABLE_SUBMISSON_PAGES', false);

# This will force jotform to use a different database to get submissions and reports
# This database is defined in config.php as DB::setConnection('submissions',
define('USE_DIFFERENT_DB_FOR_SUBMISSONS', false);

# Define the missing functions in older php versions
include ROOT . "lib/missingFunctions.php";

# Define trash for holding the zip folders. This folder will be cleaned periodically
define('TRASH_FOLDER', P(ROOT . "/trash/", true));
if (!is_dir(TRASH_FOLDER) && !mkdir(TRASH_FOLDER)) {
    Console::error(TRASH_FOLDER . ' folder cannot be created', 'Cannot create trash folder');
}

/**
 * Set all servers we have
 */
Server::setServers(Configs::$servers);

# Database Name
$DB_NAME = Configs::DBNAME;

# if we are on localhost
if (isDev()) {
    $DB_HOST = getenv('MYSQL_HOST') ?: Configs::DEV_DB_HOST;
    $DB_USER = Configs::DEV_DB_USER;
    $DB_PASS = Configs::DEV_DB_PASS;
    $JOTFORM_ENV = 'DEVELOPMENT';
    $CACHEPATH = ROOT . "cache/";
    $UPLOAD_FOLDER = ROOT . "uploads/";

    /* debug break for untrapped exceptions... */
//    set_exception_handler(function ($x) {
//        function_exists('xdebug_break') && xdebug_break();
//        return;
//    });

    /* todo:
    error level needs to permit warnings.....
    */

    /* debug inspection for errors */
    /* debug inspection for errors */
    set_error_handler(function ($errno, $errstr, $errfile, $errline)
    use ($targetErrorLevel) {
        // no error handling if the error was suppressed with the @-operator
        $level = error_reporting();
        $lastError = error_get_last();
        $lastErrorCode = error_get_last()['type'];
        if(!($lastErrorCode & E_WARNING) ){
            return true;
        }
//        $PHP_8_SUPPRESSED = E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE;
        if ($lastErrorCode === null
            || $level === $PHP_8_SUPPRESSED
            || $level === 0
        ) {
            //  error_log("Suppressed error: [$errno] $errstr on line $errline in file $errfile");
            return true; // Don't execute PHP's internal error handler
        }

        // break with usefull info otherwise...
        if (function_exists('xdebug_break')) {
            $wat = get_defined_constants(categorize: true);
            $eConstants = array_filter(get_defined_constants(), function ($value, $key) {
                return preg_match('/^E_/', $key);
            }, ARRAY_FILTER_USE_BOTH);
            $errFlags = [];
            $errMatch = [];
            foreach ($eConstants as $lable => $bit) {
                if ($bit === ($bit & $level)) {
                    $errFlags[$lable] = $bit;
                }
                if ($bit & $errno) {
                    $errMatch[$lable] = $bit;
                }
            }
            // xdebug_break();
        }
        return false; // Execute PHP's internal error handler
    });

    Console::setLogFolder(ROOT . "logs/");
    Console::setBacktrace(true);
    Console::setLogLevel(E_ALL & ~E_NOTICE);

} else { # On production
    $DB_HOST = getenv('MYSQL_HOST') ?: Configs::PRO_DB_HOST;
    $DB_USER = Configs::PRO_DB_USER;
    $DB_PASS = Configs::PRO_DB_PASS;
    $CACHEPATH = Configs::CACHEPATH;
    $UPLOAD_FOLDER = Configs::UPLOADPATH;

    # If not an application then get our database host
    if (!APP) {
        # Salmon and goby are on different networks so they need remote IPs to connect to each other.
        if (Server::isHost(["salmon"]) || strpos($_SERVER["HTTP_HOST"], "184") === 0) { // salmon or ec2
            $DB_HOST = Server::$servers->db->remote->yunus;
        } else if (Server::isHost(["dolphin", "dolphinv3staging", "forms"])) {
            $DB_HOST = "localhost";
            $DB_USER = "readonly"; #only used for form views on replication server
        } else {
            $DB_HOST = Server::$servers->db->local->yunus;
        }
    }

    Console::setLogFolder(Configs::LOGFOLDER);
    if (BETA === true) {
        Console::setLogFolder(ROOT . "logs/");
    }
    # Debug mode to put us on DEVELOPMENT
    if (DEBUGMODE) {
        $JOTFORM_ENV = 'DEVELOPMENT';
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        Console::setLogLevel(E_ALL & ~E_WARNING & ~E_NOTICE);
        Console::setBacktrace(true);
    } else {
        $JOTFORM_ENV = 'PRODUCTION';
        if (BETA === true) {
            $CACHEPATH = ROOT . "cache/";
            error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
            Console::setLogLevel(E_ALL);
        } else {
            error_reporting(0);
            Console::setLogLevel(E_ALL & ~E_WARNING & ~E_NOTICE);
        }
    }
}

$compress = false;

if ((Configs::USECDN || Utils::debugOption('useCDN')) && !BETA) {

    # If production, compress the page by default
    $compress = ($JOTFORM_ENV === 'PRODUCTION');
    if (DEBUGMODE) {
        # we may want to disable compression on debug mode
        $compress = !Utils::debugOption("decompressPage");
    }
    # If the page is compressed always use CDN by default
    if ($compress) {
        $useCDN = true;
    } else {
        $useCDN = false;
    }

    # In a problem with CDN open this.
    # $useCDN = false; 

    # Disable or force CDN usage on debug mode
    if (DEBUGMODE) {
        $useCDN = Utils::debugOption('useCDN');
    }

    # If salmon or beta do not use CDN
    if (Server::isHost(['salmon', 'beta'])) {
        $useCDN = false;
    }
} else {
    $useCDN = false;
}

$useUFS = Configs::USEUFS && (!Server::isLocalhost() || Utils::debugOption("uploadToAmazonS3"));

define("CACHEPATH", $CACHEPATH);                # Cache folder
define('UPLOAD_FOLDER', $UPLOAD_FOLDER);        # Upload folder
define('TMP_UPLOAD_FOLDER', $UPLOAD_FOLDER . "tmp_uploads/"); # Temp upload folders for multiple ajax uploads
define('DB_NAME', $DB_NAME);                    # Database Name
define('DB_HOST', $DB_HOST);                    # Database Host name
define('DB_USER', $DB_USER);                    # Database Username
define('DB_PASS', $DB_PASS);                    # Database password
define('JOTFORM_ENV', $JOTFORM_ENV);            # Environment "DEVELOPMENT" or "PRODUCTION"
define('MB', 1024 * 1024);                        # One Megabyte in bytes
define('SEC', 1000000);                         # Seconds in microsec for usleep(1*SEC) or usleep(0.5*SEC)
define('COMPRESS_PAGE', $compress);             # Will the page be commpressed or not
define('ENABLE_CDN', ($useCDN && $compress));  # Will CDN be used in includes
define('ENABLE_UFS', $useUFS);

$useRedisCache = false; // Utils::getRedis() !== false;
define('USE_REDIS_CACHE', $useRedisCache);
define('CACHEDB', 1);

if (USE_REDIS_CACHE === false) {
    //Console::error('Did not use redis cache');
}

ob_start();                                     # Output Buffer is important for error reports
ini_set("memory_limit", "2048M");               # Increase ram limit for us

# Set the servers time zone for calculations
//TimeZone::setServerTimeZone('America/New_York');
TimeZone::setServerTimeZone(@date_default_timezone_get());

# This function will handle the encoded reuqests
Utils::handleBase64Requests();

# Funnel::register("Funnel");
# MailingTest::register("MailingTest");

# Contains all configurations.
include_once ROOT . "/lib/config.php";

# If a local configuration file exists, include it to overwrite som global values for your local machine  
if (file_exists(ROOT . "/lib/localConfig.php")) {
    include_once ROOT . "/lib/localConfig.php";
}

# If you receieve an installation completed request on applications
# Auto update script once
if (APP && Utils::getCookie('INSTALLCOMPLETE')) {
    include ROOT . "opt/autoUpdate.php";
    exit;
}

if (isset($_GET["extra_id"])) {
    Utils::setCookie("jotform_form", $_GET["extra_id"], "+1 Month");
    Utils::getCookie("jotform_form");
} else {
    /**
     * Make sure create application always show up as new page
     */
    Utils::deleteCookie("jotform_form");
}

# Never allow guests to create forms
/*
 * HACK ALERT: disabling will allow anyone on the internet
 * to create forms under forms.datalynk.ca
 *
if(APP && Session::isGuest()){
    # Check if current page needs to be accessed without passwords
	if(!(preg_match('/\/ipns\//',$_SERVER['PHP_SELF']) || preg_match('/complete.php/',$_SERVER['PHP_SELF']))) {
		if(Utils::get('p') != 'passwordreset'){
			Utils::redirect(HTTP_URL."login/");
    }
}
}
*/

if ($JOTFORM_ENV === 'DEVELOPMENT') {
    set_exception_handler(
        function (Throwable $x) {
            echo(json_encode([
                $x->getMessage(),
                $x->getFile(),
                $x->getLine(),
                $x->getTrace(),
            ], JSON_PRETTY_PRINT));
        });
}

// autologin to datalynk gneric account
User::login('USER_TABLES', 'sandbox', true, true, []);
