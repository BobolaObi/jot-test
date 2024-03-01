<?php

/**
 * Initial configuration file, Set everything for JotForm
 * @remember Line order of this file is really important don't change the order of any line
 * @package JotForm_Site_Management
 * @version $Rev: 4098 $
 * @copyright Copyright (c) 2009, Interlogy LLC
 */
# Fix for proxy forwarded IP addresses

// Define the ROOT constant to point to the base directory of your project
use Legacy\Jot\UserManagement\User;
use Legacy\Jot\Utils\Server;
use Legacy\Jot\Utils\TimeZone;
use Legacy\Jot\Utils\Utils;

define('ROOT', __DIR__);

/* old code base will be littered with warnings now,
our strategy is to ignore them. */
DEFINE("ERROR_LEVEL_LENIENT", E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
error_reporting(ERROR_LEVEL_LENIENT);

/* die with as much info as possible.
NOT FOR COMMERCIAL DEPLOYMENT.
*/

register_shutdown_function(function (...$_) {
    $err = error_get_last();
    if($err['type'] & ~ERROR_LEVEL_LENIENT){
        // normal exit condition, not thrown exception.
        return;
    }
    $wtf = error_reporting();
    $fuckedWith = $wtf !== ERROR_LEVEL_LENIENT;
    function_exists('xdebug_break') && xdebug_break();
    echo json_encode(["error"=>$err, 'trace'=>debug_backtrace()]);
});

// Error handler function
set_error_handler(function($errNo, $errStr, $errFile, $errLine) {
    $wtf = error_reporting();
    $fuckedWith = $wtf !== ERROR_LEVEL_LENIENT;
    function_exists('xdebug_break') && xdebug_break();
    echo(json_encode(get_defined_vars() + ['trace'=>debug_backtrace()]));
},
    E_ALL & ~E_WARNING & ~E_NOTICE
);

set_exception_handler(
    function (Throwable $x) {
        echo(json_encode([
            $x->getMessage(),
            $x->getFile(),
            $x->getLine(),
            $x->getTrace(),
        ], JSON_PRETTY_PRINT));
    });


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

function autoload($class_name)
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
spl_autoload_register('autoload');

# Get all the options from this file
include_once dirname(__FILE__) . "/ConfigsClass.php";

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

$host = $_SERVER['HTTP_HOST'] ?? '';
$folder = ($host == "localhost" || preg_match('/^192/', $host)) ? Configs::SUBFOLDER : "/"; # Folder where jotform is located under document root

# On application always use subfolder from settings
//if (Configs::APP) {
//    $folder = Configs::SUBFOLDER;
//}

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
$server = trim('' . $server);

$server_path = ((preg_match("/^forms.*\.datalynk\.ca$/", $server) || preg_match("/^forms.*\.intranet$/", $server)) ? '' : '/forms');

$user_agent = empty($_SERVER['HTTP_USER_AGENT']) ? 'none' : $_SERVER['HTTP_USER_AGENT'];
$isIE = false;
if (preg_match('/MSIE/i', $user_agent)) {
    $isIE = true;
}

# Determine the protocol HTTP VS HTTPS
$protocol = 'https://';
if (preg_match("/\.intranet$/", $server)) {
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
                 # Use this name for support noreply emails
define('BETA', false);                                                  # Mark this installation as beta
define('SCHEMA_FILE_PATH', P(ROOT . "/opt/db_schema/jotform_new.json", true));    # Database schema file, @DEPRECATED
define('API_URL_BASE', "api");                                          # This is the base url folder name for the API
$d = (class_exists('Dropbox_OAuth_PHP') || class_exists('Dropbox_OAuth_PEAR'));
define('DROPBOX_AVAILABLE', $d);

#





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

# Database Name



$compress = false;



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

# This function will handle the encoded reuqests


# If you receieve an installation completed request on applications
# Auto update script once
if (APP && Utils::getCookie('INSTALLCOMPLETE')) {
    include ROOT . "opt/autoUpdate.php";
    exit;
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

//
//User::login('USER_TABLES', 'sandbox', true, true, []);

//# Include the Datalynk library
//require_once ROOT . "lib/datalynk.php";
