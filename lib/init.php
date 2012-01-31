<?php
/** 
 * Initial configuration file, Set everything for JotForm
 * @remember Line order of this file is really important don't change the order of any line
 * @package JotForm_Site_Management
 * @version $Rev: 4098 $
 * @copyright Copyright (c) 2009, Interlogy LLC
 */
# Fix for proxy forwarded IP addresses
if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
    # This addresses comes as comma seperated if more than one proxy was used.
    $forwardedIps = preg_split("/\s*,\s*/", $_SERVER['HTTP_X_FORWARDED_FOR']); # Split addresses 
    $ip = $forwardedIps[0];                                                    # Use the first address
    $_SERVER['REMOTE_ADDR'] = $ip;                                             # Overwrite remote address to be used in code
}

# If request behind the proxy is HTTPS then notify code to use SSL options
if(isset($_SERVER["HTTP_FRONT_END_HTTPS"])){
    $_SERVER['HTTPS'] = $_SERVER['HTTP_FRONT_END_HTTPS'];    
}

/**
 * Auto load function which loads all classes automatically no need to write includes for each class
 * @param object $class_name
 * @return 
 */
function __autoload($class_name){
    
    # In order to provide a decent warning message
    $failedPaths = array();
    
    # this is a JotForm specific situation. We have collected all exceptions together
    if(strpos($class_name, 'Exception') !== false){
        $path = ROOT."/lib/classes/exceptions/AllExceptions.php";
        if(file_exists($path)){
            require_once $path;
            return true; # file included no need to go forward
        }
    }
    
    # If file name contains unserscore convert them to folder marks
    if(strpos($class_name, '_') !== false){
        $className = str_replace("_", "/", $class_name);
    }else{
        $className = $class_name;
    }
    
    # This where we usually contain all our classes
    $path = ROOT."/lib/classes/" . $className . '.php';
    
    # Check the obvious place first
    if(file_exists($path)){
        require_once $path;
        return true; # file included no need to go forward
    }else{
        # If not found then we should check PHPs include paths
        $includePaths = explode(":", get_include_path());
        
        # Loop through all defined paths and search for the file
        foreach($includePaths as $ipath){
            
            if($ipath == "."){ continue; }
            
            $triedPath = $ipath."/".$className.".php";
            if(file_exists($triedPath)){
                require_once $triedPath;
                return true; # file included no need to go forward
            }else{
                array_push($failedPaths, $triedPath);
            }
        }
    }
	
    # Add last tried path to failed paths list
    array_push($failedPaths, $path);
    $error = $class_name." class cannot be found under jotform library.<br>\nFollowing paths have been checked:<br>\n  ".join("<br>\n  ", $failedPaths)."<br>\n";
    
    return false;
}

# Should register autoloader for phpunit
spl_autoload_register('__autoload');

# Get all the options from this file
include_once dirname(__FILE__)."/ConfigsClass.php";

/**
 * Fix extra slashes in path
 * @param object $path
 * @param object $isfile [optional]
 * @return 
 */
function P($path, $isfile = false){
    $fixedpath = preg_replace("/\:\/(\w)/", "://$1", preg_replace("/\/+/", "/", $path.($isfile? "" : "/"))); 
    return $fixedpath;
}

$host   = $_SERVER['HTTP_HOST'];
$folder = ($host == "localhost" || preg_match('/^192/', $host) )? Configs::SUBFOLDER : "/"; # Folder where jotform is located under document root
if($host == "192.168.1.223"){ $folder = "/"; } # this is actually temporary

# On application always use subfolder from settings 
if(Configs::APP){
    $folder = Configs::SUBFOLDER;
}
# Check if this is a secure URL or not
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")? "https://" : "http://";
$domain = "";
if(preg_match('/localhost$/', $host) == 0) {
    list($www, $domain, $com) = explode(".", $host);
    $domain = $domain.".".$com;
}

define('DROOT', $_SERVER["DOCUMENT_ROOT"]);                             # Document Root
define('APP', defined('Configs::APP')? Configs::APP : false);           # Defines if this working copy is an application or not
define('INST_FOLDER', ((strstr(DROOT, $folder))? "/" : $folder) );      # Installation folder
define('SUB_FOLDER', "");                                               # Installed also under a sub folder
define('ROOT', P(DROOT."/".INST_FOLDER."/".SUB_FOLDER."/"));            # Root Path
define("DOMAIN", $domain);                                              # Site domain, ex: jotform.com, use empty if localhost
define("HTTP_URL", P($protocol.$host."/".INST_FOLDER."/".SUB_FOLDER));  # Current Domain
define("SSL_URL", P("https://".$host."/".INST_FOLDER."/".SUB_FOLDER));  # SSL version of Current domain
define("IS_SECURE", ($protocol == "https://"));                         # Checks if the URL is secure or not
define("UPLOAD_URL", P(HTTP_URL."/uploads/"));                          # Uploads URL
define("CLOUD_UPLOAD_URL", Configs::CLOUD_UPLOAD_ALIAS);                # Upload URL for S3 cloud
define('IS_WINDOWS', strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');        # Check if the server is windows or not
define('VERSION', '3.0.REV');                                           # This is changed using Hudson, REV here is replaced with Hudson build number.
define('SVNREV', '3.0.SVN_REV');                                        # This is changed using Hudson, REV here is replaced with Hudson build number.
define('IMAGE_FOLDER', P(ROOT."/images/", true) );                      # Keeps where all images stored
define('SPRITE_FOLDER', P(ROOT."/sprite/", true) );                     # Keeps where the sprite images will be stored
define('JS_FOLDER', P(ROOT."/js", true));                               # Keeps where javascript files are stored
define('CSS_FOLDER', P(ROOT."/css", true) );                            # Keeps where all css files are stored
define('STYLE_FOLDER', P(CSS_FOLDER . "/" . "styles", true));           # Folder that we keep styles in it
define('COOKIE_KEY', 'jtuc');                                           # Define session and cookie keys
define('CONTENT_COOKIE_NAME','jcmc');                                   # Content manager user connection cookie name
define('CONTENT_COOKIE','jcm');                                         # NEW Content manager user connection cookie name
define('PROTOCOL', $protocol);                                          # "http" or "https"
define('DELAY_EMAILS', false);                                          # Stores email on database instead of sending them
define('NOREPLY', Configs::NOREPLY);                                    # Use this address for noreply emails
define('NOREPLY_NAME', Configs::NOREPLY_NAME);                          # Use this name for noreply emails
define('NOREPLY_SUPPORT', Configs::NOREPLY_SUPPORT);                    # Use this name for support noreply emails
define('BETA', false);                                                  # Mark this installation as beta
define('SCHEMA_FILE_PATH', P(ROOT."/opt/db_schema/jotform_new.json", true));    # Database schema file, @DEPRECATED
define('API_URL_BASE', "api");                                          # This is the base url folder name for the API
$d = (class_exists('Dropbox_OAuth_PHP') || class_exists('Dropbox_OAuth_PEAR'));
define('DROPBOX_AVAILABLE', $d);
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


if(Server::isMaxCDN() && !Server::isCacheable() && HTTP_URL !== "http://www.jotform.com/"){
    Utils::redirect("http://www.jotform.com"); 
}

# Sets everything in debug mode
define('DEBUGMODE', Utils::getCookie("DEBUG") == 'debug=yes');

# if we are in the debug mode then set the debug options
if(DEBUGMODE && Utils::getCookie('debug_options')){
    $GLOBALS['debug_options'] = json_decode(stripslashes(Utils::getCookie('debug_options')), true);
    if(!is_array($GLOBALS['debug_options'])){ $GLOBALS['debug_options'] = array(); }
}

# if jotform.com host is used then move user to www.jotform.com
if($host == "jotform.com" && ($_SERVER['REQUEST_URI'] == "" || $_SERVER['REQUEST_URI'] == "/") ){
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
include ROOT."lib/missingFunctions.php";

# Define trash for holding the zip folders. This folder will be cleaned periodically
define('TRASH_FOLDER', P("/tmp/trash/", true) );
if ( !is_dir(TRASH_FOLDER) && !mkdir(TRASH_FOLDER) ){
    Console::error(TRASH_FOLDER. ' folder cannot be created', 'Cannot create trash folder');
}

/**
 * Set all servers we have 
 */
Server::setServers(Configs::$servers);

# Database Name
$DB_NAME = Configs::DBNAME;
# if we are on localhost
if ( Server::isLocalhost()) {
    
    $DB_HOST = Configs::DEV_DB_HOST;
    $DB_USER = Configs::DEV_DB_USER;
    $DB_PASS = Configs::DEV_DB_PASS;
    $JOTFORM_ENV = 'DEVELOPMENT';
    $CACHEPATH = ROOT."cache/";
    $UPLOAD_FOLDER = ROOT."uploads/";
    
    error_reporting(E_ALL);
    
    Console::setLogFolder(ROOT."logs/");
    Console::setBacktrace(true);
    Console::setLogLevel(E_ALL);

} else { # On production
    
    $DB_HOST        = Configs::PRO_DB_HOST;
    $DB_USER        = Configs::PRO_DB_USER;
    $DB_PASS        = Configs::PRO_DB_PASS;
    $CACHEPATH      = Configs::CACHEPATH;
    $UPLOAD_FOLDER  = Configs::UPLOADPATH;
    
    # If not an application then get our database host
    if(!APP){
        # Salmon and goby are on different networks so they need remote IPs to connect to each other.
        if (Server::isHost(array("salmon")) || strpos($_SERVER["HTTP_HOST"], "184") === 0 ){ // salmon or ec2
            $DB_HOST = Server::$servers->db->remote->yunus;
        } else if (Server::isHost(array("dolphin", "dolphinv3staging", "forms")) ){
            $DB_HOST = "localhost";
            $DB_USER = "readonly"; #only used for form views on replication server
        } else {
            $DB_HOST = Server::$servers->db->local->yunus;
        }
    }
    
    Console::setLogFolder(Configs::LOGFOLDER);
    if(BETA === true){
        Console::setLogFolder(ROOT."logs/");
    }
    # Debug mode to put us on DEVELOPMENT
    if (DEBUGMODE) {
        $JOTFORM_ENV = 'DEVELOPMENT';
        error_reporting(E_ALL);
        Console::setLogLevel(E_ALL);
        Console::setBacktrace(true);
    } else {
        $JOTFORM_ENV = 'PRODUCTION';
        if(BETA === true){
            $CACHEPATH = ROOT."cache/";
            error_reporting(E_ALL);
            Console::setLogLevel(E_ALL);
        }else{
            error_reporting(0); 
            Console::setLogLevel(E_ALL ^ E_NOTICE);
        }
    } 
}

$compress = false;

if((Configs::USECDN || Utils::debugOption('useCDN')) && !BETA){
    
	# If production, compress the page by default
    $compress = ($JOTFORM_ENV === 'PRODUCTION');
    if(DEBUGMODE){
        # we may want to disable compression on debug mode
        $compress = !Utils::debugOption("decompressPage");
    }
    # If the page is compressed always use CDN by default
    if ($compress){
        $useCDN = true;
    }else{
        $useCDN = false;
    }
    
    # In a problem with CDN open this.
    # $useCDN = false; 
    
    # Disable or force CDN usage on debug mode
    if(DEBUGMODE){
        $useCDN = Utils::debugOption('useCDN');
    }
    
    # If salmon or beta do not use CDN
    if (Server::isHost(array('salmon', 'beta'))){
        $useCDN = false;
    }
}else{
    $useCDN = false;
}

$useUFS = Configs::USEUFS && (!Server::isLocalhost() || Utils::debugOption("uploadToAmazonS3"));

define("CACHEPATH", $CACHEPATH);                # Cache folder
define('UPLOAD_FOLDER', $UPLOAD_FOLDER);        # Upload folder
define('TMP_UPLOAD_FOLDER', $UPLOAD_FOLDER."tmp_uploads/"); # Temp upload folders for multiple ajax uploads
define('DB_NAME', $DB_NAME);                    # Database Name
define('DB_HOST', $DB_HOST);                    # Database Host name
define('DB_USER', $DB_USER);                    # Database Username
define('DB_PASS', $DB_PASS);                    # Database password
define('JOTFORM_ENV', $JOTFORM_ENV);            # Environment "DEVELOPMENT" or "PRODUCTION"
define('MB', 1024*1024);                        # One Megabyte in bytes
define('SEC', 1000000);                         # Seconds in microsec for usleep(1*SEC) or usleep(0.5*SEC)
define('COMPRESS_PAGE', $compress);             # Will the page be commpressed or not
define('ENABLE_CDN', ($useCDN && $compress) );  # Will CDN be used in includes
define('ENABLE_UFS', $useUFS);


$useRedisCache = false; // Utils::getRedis() !== false;
define('USE_REDIS_CACHE', $useRedisCache);
define('CACHEDB', 1);

if(USE_REDIS_CACHE === false){
    Console::error('Did not use redis cache');
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
if(file_exists(ROOT . "/lib/localConfig.php")){
    include_once ROOT . "/lib/localConfig.php";   
}

# If you receieve an installation completed request on applications
# Auto update script once
if(APP && Utils::getCookie('INSTALLCOMPLETE')){
    include ROOT."opt/autoUpdate.php";
    exit;
}

# Never allow guests to create forms
if(APP && Session::isGuest()){
    # Check if current page needs to be accessed without passwords
    if(Utils::get('p') != 'passwordreset'){
        Utils::redirect(HTTP_URL."login/");
    }
}
