<?php
/**
 * Utilities for JotForm lots of useful function goes here
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */
class Utils extends UtilsArrays
{	
    public static $urlMatch = "/https?\:\/\/(\b[A-Za-z0-9]+\.\b)?(\bjotform\.com|jotfor\.ms|interlogy\.com\b)\/(.*?\/)?(\d+).*/i";
    
    public static $countries = array('AF' => 'Afghanistan', 'AX' => 'Åland Islands', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AS' => 'American Samoa', 'AD' => 'Andorra', 'AO' => 'Angola', 'AI' => 'Anguilla', 'AQ' => 'Antarctica', 'AG' => 'Antigua and Barbuda', 'AR' => 'Argentina', 'AM' => 'Armenia', 'AW' => 'Aruba', 'AU' => 'Australia', 'AT' => 'Austria', 'AZ' => 'Azerbaijan', 'BS' => 'Bahamas', 'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BB' => 'Barbados', 'BY' => 'Belarus', 'BE' => 'Belgium', 'BZ' => 'Belize', 'BJ' => 'Benin', 'BM' => 'Bermuda', 'BT' => 'Bhutan', 'BO' => 'Bolivia', 'BA' => 'Bosnia and Herzegovina', 'BW' => 'Botswana', 'BV' => 'Bouvet Island', 'BR' => 'Brazil', 'IO' => 'British Indian Ocean Territory', 'VG' => 'British Virgin Islands', 'BN' => 'Brunei Darussalam', 'BG' => 'Bulgaria', 'BF' => 'Burkina Faso', 'BI' => 'Burundi', 'KH' => 'Cambodia', 'CM' => 'Cameroon', 'CA' => 'Canada', 'CV' => 'Cape Verde', 'KY' => 'Cayman Islands', 'CF' => 'Central African Republic', 'TD' => 'Chad', 'CL' => 'Chile', 'CN' => 'China', 'CX' => 'Christmas Island', 'CC' => 'Cocos (Keeling) Islands', 'CO' => 'Colombia', 'KM' => 'Comoros', 'CD' => 'Congo, Democratic Republic of', 'CG' => 'Congo, People\'s Republic of', 'CK' => 'Cook Islands', 'CR' => 'Costa Rica', 'CI' => 'Cote D\'Ivoire, Ivory Coast', 'CU' => 'Cuba', 'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'DJ' => 'Djibouti', 'DM' => 'Dominica', 'DO' => 'Dominican Republic', 'TL' => 'Timor-Leste', 'EC' => 'Ecuador', 'EG' => 'Egypt', 'SV' => 'El Salvador', 'GQ' => 'Equatorial Guinea', 'ER' => 'Eritrea', 'EE' => 'Estonia', 'ET' => 'Ethiopia', 'FO' => 'Faeroe Islands', 'FK' => 'Falkland Islands (Malvinas)', 'FJ' => 'Fiji', 'FI' => 'Finland', 'FR' => 'France', 'GF' => 'French Guiana', 'PF' => 'French Polynesia', 'TF' => 'French Southern Territories', 'GA' => 'Gabon', 'GM' => 'Gambia', 'GE' => 'Georgia', 'DE' => 'Germany', 'GH' => 'Ghana', 'GI' => 'Gibraltar', 'GR' => 'Greece', 'GL' => 'Greenland', 'GD' => 'Grenada', 'GP' => 'Guadaloupe', 'GU' => 'Guam', 'GT' => 'Guatemala', 'GN' => 'Guinea', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana', 'HT' => 'Haiti', 'HM' => 'Heard and McDonald Islands', 'VA' => 'Holy See (Vatican City State)', 'HN' => 'Honduras', 'HK' => 'Hong Kong', 'HR' => 'Hrvatska (Croatia)', 'HU' => 'Hungary', 'IS' => 'Iceland', 'IN' => 'India', 'ID' => 'Indonesia', 'IR' => 'Iran', 'IQ' => 'Iraq', 'IE' => 'Ireland', 'IL' => 'Israel', 'IT' => 'Italy', 'JM' => 'Jamaica', 'JP' => 'Japan', 'JO' => 'Jordan', 'KZ' => 'Kazakhstan', 'KE' => 'Kenya', 'KI' => 'Kiribati', 'KP' => 'Korea', 'KR' => 'Korea', 'KW' => 'Kuwait', 'KG' => 'Kyrgyz Republic', 'LA' => 'Lao People\'s Democratic Republic', 'LV' => 'Latvia', 'LB' => 'Lebanon', 'LS' => 'Lesotho', 'LR' => 'Liberia', 'LY' => 'Libya', 'LI' => 'Liechtenstein', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MO' => 'Macao', 'MK' => 'Macedonia', 'MG' => 'Madagascar', 'MW' => 'Malawi', 'MY' => 'Malaysia', 'MV' => 'Maldives', 'ML' => 'Mali', 
        'MT' => 'Malta', 'MH' => 'Marshall Islands', 'MQ' => 'Martinique', 'MR' => 'Mauritania', 'MU' => 'Mauritius', 'YT' => 'Mayotte', 'MX' => 'Mexico', 'FM' => 'Micronesia', 'MD' => 'Moldova', 'MC' => 'Monaco', 'MN' => 'Mongolia', 'MS' => 'Montserrat', 'MA' => 'Morocco', 'MZ' => 'Mozambique', 'MM' => 'Myanmar', 'NA' => 'Namibia', 'NR' => 'Nauru', 'NP' => 'Nepal', 'AN' => 'Netherlands Antilles', 'NL' => 'Netherlands', 'NC' => 'New Caledonia', 'NZ' => 'New Zealand', 'NI' => 'Nicaragua', 'NE' => 'Niger', 'NG' => 'Nigeria', 'NU' => 'Niue', 'NF' => 'Norfolk Island', 'MP' => 'Northern Mariana Islands', 'NO' => 'Norway', 'OM' => 'Oman', 'PK' => 'Pakistan', 'PW' => 'Palau', 'PS' => 'Palestinian Territory', 'PA' => 'Panama', 'PG' => 'Papua New Guinea', 'PY' => 'Paraguay', 'PE' => 'Peru', 'PH' => 'Philippines', 'PN' => 'Pitcairn Island', 'PL' => 'Poland', 'PT' => 'Portugal', 'PR' => 'Puerto Rico', 'QA' => 'Qatar', 'RE' => 'Reunion', 'RO' => 'Romania', 'RU' => 'Russian Federation', 'RW' => 'Rwanda', 'SH' => 'St. Helena', 'KN' => 'St. Kitts and Nevis', 'LC' => 'St. Lucia', 'PM' => 'St. Pierre and Miquelon', 'VC' => 'St. Vincent and the Grenadines', 'WS' => 'Samoa', 'SM' => 'San Marino', 'ST' => 'Sao Tome and Principe', 'SA' => 'Saudi Arabia', 'SN' => 'Senegal', 'SC' => 'Seychelles', 'SL' => 'Sierra Leone', 'SG' => 'Singapore', 'SK' => 'Slovakia', 'SI' => 'Slovenia', 'SB' => 'Solomon Islands', 'SO' => 'Somalia', 'ZA' => 'South Africa', 'GS' => 'South Georgia and the South Sandwich Islands', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SD' => 'Sudan', 'SR' => 'Suriname', 'SJ' => 'Svalbard & Jan Mayen Islands', 'SZ' => 'Swaziland', 'SE' => 'Sweden', 'CH' => 'Switzerland', 'SY' => 'Syrian Arab Republic', 'TW' => 'Taiwan', 'TJ' => 'Tajikistan', 'TZ' => 'Tanzania', 'TH' => 'Thailand', 'TG' => 'Togo', 'TK' => 'Tokelau (Tokelau Islands)', 'TO' => 'Tonga', 'TT' => 'Trinidad and Tobago', 'TN' => 'Tunisia', 'TR' => 'Turkey', 'TM' => 'Turkmenistan', 'TC' => 'Turks and Caicos Islands', 'TV' => 'Tuvalu', 'VI' => 'US Virgin Islands', 'UG' => 'Uganda', 'UA' => 'Ukraine', 'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom', 'UM' => 'United States Minor Outlying Islands', 'US' => 'United States', 'UY' => 'Uruguay', 'UZ' => 'Uzbekistan', 'VU' => 'Vanuatu', 'VE' => 'Venezuela', 'VN' => 'Viet Nam', 'WF' => 'Wallis and Futuna Islands', 'EH' => 'Western Sahara', 'YE' => 'Yemen', 'CS' => 'Serbia and Montenegro', 'ZM' => 'Zambia','ZW' => 'Zimbabwe');
        
    public static $stateAbbr = array("Alabama" => "AL", "Alaska" => "AK","Arizona" => "AR","Arkansas" => "AZ","California" => "CA","Colorado" => "CO","Connecticut" => "CT","District of Columbia" => "DC","Delaware" => "DE","Florida" => "FL","Georgia" => "GA","Hawaii" => "HI","Idaho" => "ID","Illinois" => "IL","Indiana" => "IN","Iowa" => "IA","Kansas" => "KS","Kentucky" => "KY","Louisiana" => "LA","Maine" => "ME","Maryland" => "MD","Massachusetts" => "MA","Michigan" => "MI","Minnesota" => "MN","Mississippi" => "MS","Missouri" => "MO","Montana" => "MT","Nebraska" => "NE","Nevada" => "NV","New Hampshire" => "NH","New Jersey" => "NJ","New Mexico" => "NM","New York" => "NY","North Carolina" => "NC","North Dakota" => "ND","Ohio" => "OH","Oklahoma" => "OK","Oregon" => "OR","Pennsylvania" => "PA","Rhode Island" => "RI","South Carolina" => "SC","South Dakota" => "SD","Tennessee" => "TN","Texas" => "TX","Utah" => "UT","Vermont" => "VT","Virginia" => "VA","Washington" => "WA","West Virginia" => "WV","Wisconsin" => "WI","Wyoming" => "WY");
    
    private static $residInstances = array();
    
    /**
     * Echos a string on the page.
     * Works only in debug mode
     * @param object $str String to print
     * @param object $exit [optional] stop script after print
     * @return 
     */
    static function debug($str, $exit =false){
        # if not in the debug mode then return
        if(!DEBUGMODE){ return $str; }
        # if not string then use print_r method
        if(!is_string($str)){
            return Utils::print_r($str, $exit);
        }
        # if string then print on the page
        echo $str . "<br title=\"Placed by debug\" />\n";
        # if exit send then quit programme
        if($exit){ exit; }
        # return the value
        return $str;
    }
    
    /**
     * Cleans the buffer and prints the word
     * @param object $str
     * @return 
     */
    static function clean($str){
        echo $str."<br>";
        ob_flush();
        flush();
        return $str;
    }
    
    /**
     * Gets the error message from exception
     * @param object $exception
     * @return 
     */
    static function error($exception){
        list($message, $notes) = Utils::generateErrorMessage($exception);
        Utils::errorPage($message, 'Oops!', $notes);
    }
    
    /**
     * 
     * @param $exception
     * @return array => $message , $notes
     */
    static function generateErrorMessage ($exception) {
        $message = $exception->getMessage()."<hr>";
        $trace = $exception->getTrace();
        $trace = $trace[0];
        $notes = "File: ".$trace['file']."\nLine: ".$trace['line']."\n";
        
        return array($message, $notes); 
    }
    
    /**
     * Will show an error page containing the given message
     * @param object $error_message Message to show on the screen
     * @param object $error_title [optional] Page title for the error
     * @param object $error_notes [optional] These notes will be placed in the page as a comment. to identify the problem
     * @return 
     */
    static function errorPage($error_message, $error_title="Error", $error_notes="", $status = 500){
        ob_end_clean();
        header("Content-type: text/html; charset=utf-8", true, $status);
        
        $whiteLabel = true;
        
        $content = ROOT."lib/includes/error.php";
        include ROOT."opt/templates/notification_template.html";
        if($status != 200 && $status != 404){
            Console::error($error_message."\n----\n".$error_notes,  $error_title);
        }
        # TODO: do not forget to remove this.
        if (trim($error_title) === "Temporarily Unavailable"){
        	Console::customLog("temp", $_SERVER['REQUEST_URI'] . "\n" . $error_notes);
        }
        exit;
    }
    
    /**
     * Will show an error page containing the given message
     * @param object $error_message Message to show on the screen
     * @param object $error_title [optional] Page title for the error
     * @param object $error_notes [optional] These notes will be placed in the page as a comment. to identify the problem
     * @return 
     */
    static function successPage($success_message, $success_title="Congratulations!", $success_notes=""){
        
        ob_end_clean();
        $whiteLabel = true;
        $content = ROOT."lib/includes/success.php";
        include ROOT."opt/templates/notification_template.html";
        exit;
    }
    
    /**
     * Finds the command's location on the server
     * @param object $command
     * @return 
     */
    static function findCommand($command){
        
        # Get all php executable paths
        exec("echo \$PATH", $path_output);
        $defaultpaths = explode(":", $path_output[0]);
        
        # list all possible paths
        $possibleLocations = array(
            "/usr/bin",
            "/usr/local/bin",
            "/bin",
            "/sbin",
            "/opt/local/bin",
            "/usr/sbin",
            "/usr/local/sbin",
            "/sw/bin",
            "/sw/sbin",
        );
        
        # Merge paths together
        $locations = array_unique(array_merge($defaultpaths, $possibleLocations));
        
        foreach($locations as $location){
            
            if(file_exists($location."/".$command)){
                return $location."/".$command;
            }
            
            /*
            $output = array();
            exec("ls ".$location."/".$command, $output);
            if(isset($output[0]) && (trim($output[0]) == trim($location."/".$command))){
                return trim($location."/".$command);
            }*/
        }
        
        return false;
    }
    
    /**
     * Chart beat tracking code
     * @param object $type
     * @return 
     */
    static function usageTracking($type){
        if(APP || Server::isLocalhost() || Session::isAdmin() || Session::isSupport()){ return ""; }
        
        if($type == 'head'){
            echo '<script type="text/javascript">var _sf_startpt=(new Date()).getTime()</script>'."\n";
        }else{
            echo
                "<script type=\"text/javascript\">\n".
                "   var _sf_async_config={uid:17337, domain:'jotform.com'};\n".
                "   (function(){\n".
                "      function loadChartbeat() {\n".
                "          window._sf_endpt=(new Date()).getTime();\n".
                "          var e = document.createElement('script');\n".
                "          e.setAttribute('language', 'javascript');\n".
                "          e.setAttribute('type', 'text/javascript');\n".
                "          e.setAttribute('src',\n".
                "             (('https:' == document.location.protocol) ? 'https://a248.e.akamai.net/chartbeat.download.akamai.com/102508/' : 'http://static.chartbeat.com/') +\n".
                "             'js/chartbeat.js');\n".
                "          document.body.appendChild(e);\n".
                "      }\n".
                "      var oldonload = window.onload;\n".
                "      window.onload = (typeof window.onload != 'function') ?\n".
                "        loadChartbeat : function() { oldonload(); loadChartbeat(); };\n".
                "   })();\n".
                "</script>\n";
        }
        
        return "";
    }
    
    /**
     * Place Google anlytcis code on the page
     * @param object $code
     * @return 
     */
    static function putAnalytics($code){
        if(empty($code) || Server::isLocalhost()){ return; } // Don't put analytics for localhost        
        echo "<script type=\"text/javascript\">\n".
             "\n".
             " var _gaq = _gaq || [];\n".
             " _gaq.push(['_setAccount', '".$code."']);\n".
             " _gaq.push(['_trackPageview']);\n".
             "\n".
             " (function() {\n".
             "   var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;\n".
             "   ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';\n".
             "   var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);\n".
             " })();\n".
             "\n".
             "</script>\n";
    }
    private static $cache=array();
    /**
     * Stores the data on APC Cache
     * @param object $key
     * @param object $value
     * @param object $ttl [optional] Time To live
     * @return 
     */
    public static function cacheStore($key, $value, $ttl = "86400"){
        if(!function_exists("apc_store")){ return self::$cache[$key] = $value; }
        return apc_store($key, $value, $ttl);
    }
    
    /**
     * Gets the data from cache
     * @param object $key
     * @return 
     */
    public static function cacheGet($key, $returnInstead = false){
        if(!function_exists("apc_fetch")){ 
        	if(isset(self::$cache[$key])){
        		return self::$cache[$key];
        	}
        	return $returnInstead;
        }
        $val = apc_fetch($key);
        if($val === false){
            return $returnInstead;
        }
        return $val;
    }
    
    /**
     * Deletes the data from cache
     * @param object $key
     * @return 
     */
    public static function cacheDelete($key){
        if(!function_exists("apc_delete")){ unset(self::$cache[$key]); return true; }
        return apc_delete($key);
    }
    
    /**
     * Clears the APC cache completely
     * @param object $type [optional]
     * @return 
     */
    public static function cacheClear($type = 'user'){
        if(!function_exists("apc_clear_cache")){ return false; }
        return apc_clear_cache($type);
    }
    
    
    /**
     * Returns the correct file extension
     * @param object $filepath
     * @return 
     */
    static function getFileExtension($filepath){
        $path_info = pathinfo($filepath);
        return $path_info['extension'];
    }
    
    /**
     * Returns the file name from an array
     * @param object $path
     * @return 
     */
    static function getFileName($path){
        $url_array = explode("/", $path);
        return array_pop($url_array);
    }
    
    /**
     * Recursively creates the paths
     * @param object $path
     * @return 
     */
    static function recursiveMkdir($path, $mod = 0777){
        
        if(IS_WINDOWS){
            $path = str_replace("//", "/", $path);
            $path = str_replace("/", "\\", $path);
        }
        
        if (!file_exists($path)){
            if(Utils::recursiveMkdir(dirname($path), $mod)){
                if(!@mkdir($path, $mod)){
                    Console::error(error_get_last(), "$path");
                    return false;
                }
            }else{
                return false;
            }
        }
        return true;
    }
    /**
     * Recursively deletes a folder with it's contents
     * @param object $folderName
     * @return 
     */
    static function recursiveRmdir($folderName){  
        # look if it is a directory
        if (!file_exists($folderName)) {
            return false;
        }
        
        # Simple delete for a file
        if (is_file($folderName)) {
            return unlink($folderName);
        }
        
        # Loop through the folder
        $dir = dir($folderName);
        while (false !== $entry = $dir->read()) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            Utils::recursiveRmdir("$folderName/$entry");
        }   
        
        $dir->close();
        return rmdir($folderName);
    }
    
    /**
     * Includes a template from includes folder
     * @param object $name
     */
    static function put($name){
        include ROOT."/lib/includes/".$name.".php";
    }
    /**
     * Returns the cloud url
     * @return unknown_type
     */
    public static function getCloudURL(){
    	if (IS_SECURE){
            return "https://d3mc0rm5ezl95j.cloudfront.net/";
    	}else{
            return "http://cdn.jotfor.ms/";
    	}
    }
    /**
     * Creates the upload URL
     * @param object $username
     * @param object $formID
     * @param object $submissionID
     * @param object $fileName
     * @return 
     */
    public static function getUploadURL($username, $formID, $submissionID, $fileName) {
        
        $uploadURL = false;
        
        # If UFS is enabled, use it. 
        if ( defined('ENABLE_UFS') && ENABLE_UFS ){
            # Create UFSController.
            $uploadURL = AmazonS3Controller::getUploadUrl($username, $formID, $submissionID, $fileName);
        }else{
            $uploadURL = FileController::getUploadUrl($username, $formID, $submissionID, $fileName);
        }
        
        return $uploadURL;
    }
    
    /**
     * Retuns the PHP's temp directory
     * @return 
     */
    static function getTempDir(){
        if ( !function_exists('sys_get_temp_dir')) {
            function sys_get_temp_dir() {
                if( $temp=getenv('TMP') )        return $temp;
                if( $temp=getenv('TEMP') )        return $temp;
                if( $temp=getenv('TMPDIR') )    return $temp;
                $temp=tempnam(__FILE__,'');
                if (file_exists($temp)) {
                    unlink($temp);
                    return dirname($temp);
                }
                return null;
            }
        }
        return realpath(sys_get_temp_dir());
    }
    
    /**
     * This function converts the old note to array from old database.
     * @param unknown_type $note
     */
    static function convertOldPaymentLogToArray($note){
        $result = array();
        $lines = preg_split('/\n/', $note);
        foreach ($lines as $line){
            $temp_array = preg_split("/:/", $line);
            $key = array_splice($temp_array, 0, 1);
            $result[trim($key[0])] = trim( implode(":", $temp_array));
        }
        return $result;
    }
    /**
     * Returns the dimensions of given image
     * @param object $fileName
     * @return 
     */
    static function getImageDimensions($fileName){
        $info = getimagesize($fileName);
        preg_match('/\bwidth\b\=\"(?P<width>\d+)\"\s\bheight\b\=\"(?P<height>\d+)\"/', $info[3], $match);
        return array("width" => $match['width'], "height" => $match['height'], "text" => $match[0]);
    }
    
    /**
     * Gets the total number and sub number and returns the percentage
     * @param object $total
     * @param object $number
     * @return 
     */
    static function percent($total, $number){
        $total  = (float) $total;
        $number = (float) $number;
        $zero   = (float) 0;
        
        if($number === $zero || $total === $zero){ return 0; }
        return number_format(($number / $total) * 100, 2);
    }
    
    /**
     * Phishing filter functions from old version is using
     * this function a lot. no time for me to testing (seyhun).
     * @param $query
     * @return unknown_type
     */
    static function do_query($query){
        $result = mysql_query($query);
        if (!$result){
            die($query);
        }
        return $result;
    }
    
    static function getRedis($databaseIndex = 0){
        require_once(ROOT."lib/classes/utils/Predis.php");
        if(!isset(self::$residInstances[$databaseIndex])){
            if(JOTFORM_ENV == 'PRODUCTION' && !Server::isHost(array('yang', '10.202.1.216'))){
                self::$residInstances[$databaseIndex] = new Predis_Client(array(
                    "host" => Configs::REDIS_HOST,
                    "port" => Configs::REDIS_PORT,
                    "password" => Configs::REDIS_PASSWORD,
                    "database" => $databaseIndex
                ));
            }else{
                $h = "127.0.0.1";
                $yang = Server::$servers->siblings->local->yang;
                // Check if 'local' property is set before accessing it
                if ($yang !== null && Server::isHost(array('yang', $yang))) {
                    $h = $yang;
                }
                self::$residInstances[$databaseIndex] = new Predis_Client(array(
                    "host"     => $h,
                    "password" => Configs::REDIS_PASSWORD,
                    "database" => $databaseIndex
                ));
            }
        }
        
        # Test redis existance
        # For some reason Redis::isConnected() didn't work on client-side PHP
        try{
            self::$residInstances[$databaseIndex]->set('testRedis', 'test');
            if(self::$residInstances[$databaseIndex]->get('testRedis') != 'test'){
                throw new Exception('Value missmatch');
            }
            self::$residInstances[$databaseIndex]->del('testRedis');
        }catch(Exception $e){
            return false; # If redis not found return false instead
        }
        return self::$residInstances[$databaseIndex];
    }    
}
