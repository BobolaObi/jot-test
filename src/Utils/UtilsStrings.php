<?php
/**
 * String Manipulation Utilities for JotForm
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Utils;

class UtilsStrings extends UtilsRequests{
    /**
     * Regular expression for matching emails
     * @var string
     */

    /**
     * Fixes path slashes
     * @param object $path
     * @param object $isfile [optional]
     * @param object $addSlashes [optional]
     * @return
     */
    static function path($path, $isfile = false, $addSlashes = false){
        $close = $isfile? "" : "/";
        $path = preg_replace("/\/+/", "/", $path.$close);
        $path = preg_replace("/\:\/(\w)/", "://$1", $path);

        if(false){ // if server is windows then do this fix.
            $path = preg_replace("/\\\+/", "\\", str_replace(":\\\\", "://", str_replace("/", "\\", preg_replace("/\:\/(\w)/", "://$1", $path))));
            if(strstr($path, "http://")){
                $path = str_replace("\\", "/", $path);
            }
            if($addSlashes){
                return addslashes($path);
            }
        }

        return $path;
    }
    /**
     * Display bytes as human readable strings
     * @param object $octets
     * @return string human readable bytes
     */
    static function bytesToHuman($octets){
        $units = array('B', 'kB', 'MB', 'GB', 'TB'); // ...etc

        for ($i = 0, $size =$octets; $size>1024; $size=$size/1024)
            $i++;

        return number_format($size, 2) . ' ' . $units[min($i, count($units) -1 )];
    }
    /**
     * This will try to convert given array to JSON string if not array will be returned
     * @param object $object
     * @return string|array
     */
    static function safeJsonEncode($object){
        if(is_object($object) || is_array($object)){
            if($res = @json_encode($object)){
                return $res;
            }
        }
        return $object;
    }

    /**
     * This will try to convert given tring to an array. if not string will be returned
     * @param object $string
     * @return array|string
     */
    static function safeJsonDecode($string){
        if(!is_string($string)){ return $string; }

        if(empty($string) && ($string !== "0" && $string !== 0)){ return ""; }

        // if it looks like a JSON then try decoding
        if($string[0] == "{" || $string[0] == "["){
            if($res = @json_decode($string, true)){
                return $res;
            }
        }
        if($string == "[]"){
            return array();
        }
        return $string;
    }
    /**
     * Converts unicode characters
     * @param object $str
     * @return
     */
    public static function fixUTF($str){
        $lowerCase = array("a" => "00E1:0103:01CE:00E2:00E4:0227:1EA1:0201:00E0:1EA3:0203:0101:0105:1D8F:1E9A:00E5:1E01:2C65:00E3:0251:1D90","b" => "1E03:1E05:0253:1E07:1D6C:1D80:0180:0183","c" => "0107:010D:00E7:0109:0255:010B:0188:023C","d" => "010F:1E11:1E13:0221:1E0B:1E0D:0257:1E0F:1D6D:1D81:0111:0256:018C","e" => "00E9:0115:011B:0229:00EA:1E19:00EB:0117:1EB9:0205:00E8:1EBB:0207:0113:2C78:0119:1D92:0247:1EBD:1E1B","f" => "1E1F:0192:1D6E:1D82","g" => "01F5:011F:01E7:0123:011D:0121:0260:1E21:1D83:01E5","h" => "1E2B:021F:1E29:0125:2C68:1E27:1E23:1E25:0266:1E96:0127","i" => "0131:00ED:012D:01D0:00EE:00EF:1ECB:0209:00EC:1EC9:020B:012B:012F:1D96:0268:0129:1E2D","j" => "01F0:0135:029D:0249","k" => "1E31:01E9:0137:2C6A:A743:1E33:0199:1E35:1D84:A741","l" => "013A:019A:026C:013E:013C:1E3D:0234:1E37:2C61:A749:1E3B:0140:026B:1D85:026D:0142:0269:1D7C","m" => "1E3F:1E41:1E43:0271:1D6F:1D86","n" => "0144:0148:0146:1E4B:0235:1E45:1E47:01F9:0272:1E49:019E:1D70:1D87:0273:00F1","o" => "00F3:014F:01D2:00F4:00F6:022F:1ECD:0151:020D:00F2:1ECF:01A1:020F:A74B:A74D:2C7A:014D:01EB:00F8:00F5","p" => "1E55:1E57:A753:01A5:1D71:1D88:A755:1D7D:A751","q" => "A759:02A0:024B:A757","r" => "0155:0159:0157:1E59:1E5B:0211:027E:0213:1E5F:027C:1D72:1D89:024D:027D","s" => "015B:0161:015F:015D:0219:1E61:1E63:0282:1D74:1D8A:023F","t" => "0165:0163:1E71:021B:0236:1E97:2C66:1E6B:1E6D:01AD:1E6F:1D75:01AB:0288:0167","u" => "00FA:016D:01D4:00FB:1E77:00FC:1E73:1EE5:0171:0215:00F9:1EE7:01B0:0217:016B:0173:1D99:016F:0169:1E75:1D1C:1D7E","v" => "2C74:A75F:1E7F:028B:1D8C:2C71:1E7D","w" => "1E83:0175:1E85:1E87:1E89:1E81:2C73:1E98","x" => "1E8D:1E8B:1D8D","y" => "00FD:0177:00FF:1E8F:1EF5:1EF3:01B4:1EF7:1EFF:0233:1E99:024F:1EF9","z" => "017A:017E:1E91:0291:2C6C:017C:1E93:0225:1E95:1D76:1D8E:0290:01B6:0240","ae" => "00E6:01FD:01E3","dz" => "01F3:01C6","3" => "0292:01EF:0293:1D9A:01BA:01B7:01EE");
        $upperCase = array("A" => "00C1:0102:01CD:00C2:00C4:0226:1EA0:0200:00C0:1EA2:0202:0100:0104:00C5:1E00:023A:00C3","B" => "1E02:1E04:0181:1E06:0243:0182","C" => "0106:010C:00C7:0108:010A:0187:023B","D" => "010E:1E10:1E12:1E0A:1E0C:018A:1E0E:0110:018B","E" => "00C9:0114:011A:0228:00CA:1E18:00CB:0116:1EB8:0204:00C8:1EBA:0206:0112:0118:0246:1EBC:1E1A","F" => "1E1E:0191","G" => "01F4:011E:01E6:0122:011C:0120:0193:1E20:01E4:0262:029B","H" => "1E2A:021E:1E28:0124:2C67:1E26:1E22:1E24:0126","I" => "00CD:012C:01CF:00CE:00CF:0130:1ECA:0208:00CC:1EC8:020A:012A:012E:0197:0128:1E2C:026A:1D7B","J" => "0134:0248","K" => "1E30:01E8:0136:2C69:A742:1E32:0198:1E34:A740","L" => "0139:023D:013D:013B:1E3C:1E36:2C60:A748:1E3A:013F:2C62:0141:029F:1D0C","M" => "1E3E:1E40:1E42:2C6E","N" => "0143:0147:0145:1E4A:1E44:1E46:01F8:019D:1E48:0220:00D1","O" => "00D3:014E:01D1:00D4:00D6:022E:1ECC:0150:020C:00D2:1ECE:01A0:020E:A74A:A74C:014C:019F:01EA:00D8:00D5","P" => "1E54:1E56:A752:01A4:A754:2C63:A750","Q" => "A758:A756","R" => "0154:0158:0156:1E58:1E5A:0210:0212:1E5E:024C:2C64","S" => "015A:0160:015E:015C:0218:1E60:1E62","T" => "0164:0162:1E70:021A:023E:1E6A:1E6C:01AC:1E6E:01AE:0166","U" => "00DA:016C:01D3:00DB:1E76:00DC:1E72:1EE4:0170:0214:00D9:1EE6:01AF:0216:016A:0172:016E:0168:1E74","V" => "A75E:1E7E:01B2:1E7C","W" => "1E82:0174:1E84:1E86:1E88:1E80:2C72","X" => "1E8C:1E8A","Y" => "00DD:0176:0178:1E8E:1EF4:1EF2:01B3:1EF6:1EFE:0232:024E:1EF8","Z" => "0179:017D:1E90:2C6B:017B:1E92:0224:1E94:01B5","AE" => "00C6:01FC:01E2","DZ" => "01F1:01C4");

        foreach($lowerCase as $letter => $variations){
            $regexp = "/\x{". join("}|\x{", explode(":", $variations)) . "}/u";
            $str = preg_replace($regexp, $letter, $str);
        }


        foreach($upperCase as $letter => $variations){
            $regexp = "/\x{". join("}|\x{", explode(":", $variations)) . "}/u";
            $str = preg_replace($regexp, $letter, $str);
        }

        return $str;
    }


    /**
     * Translates a camel case string into a string with underscores (e.g. firstName -&gt; first_name)
     * @param    string   $str    String in camel case format
     * @return    string            $str Translated into underscore format
     */
    static function fromCamelCase($str, $to = "_") {
        $str[0] = strtolower($str[0]);
        $func = create_function('$c', 'return "'.$to.'" . strtolower($c[1]);');
        return preg_replace_callback('/([A-Z])/', $func, $str);
    }

    /**
     * Translates a string with underscores into camel case (e.g. first_name -&gt; firstName)
     * @param    string   $str                     String in underscore format
     * @param    bool     $capitalise_first_char   If true, capitalise the first char in $str
     * @return   string                              $str translated into camel caps
    */
    static function stringToCamel($str, $capitalise_first_char = false) {
        if($capitalise_first_char) {
            $str[0] = strtoupper($str[0]);
        }
        return preg_replace_callback('/_([a-z])/',
            fn($c)=>strtoupper($c[1]), $str);
    }

    /**
     * Method for making a word camel case.
     * Taken from http://php.net/manual/en/function.strtoupper.php
     *
     * @param object $str
     * @param object $capitalizeFirst [optional]
     * @param object $allowed [optional]
     * @return
     */
    static function stringToCamel2($str, $capitalizeFirst = false, $allowed = 'A-Za-z0-9') {
        return preg_replace(
            array(
                "/([A-Z][a-z])/e", // all occurances of caps followed by lowers
                "/([a-zA-Z])([a-zA-Z]*)/e", // all occurances of words w/ first char captured separately
                "/[^'.$allowed.']+/e", // all non allowed chars (non alpha numerics, by default)
                "/^([a-zA-Z])/e" // first alpha char
            ),
            array(
                '" ".$1', // add spaces
                'strtoupper("$1").strtolower("$2")', // capitalize first, lower the rest
                '', // delete undesired chars
                'strto'.($capitalizeFirst ? 'upper' : 'lower').'("$1")' // force first char to upper or lower
            ),
            $str
        );
    }

    /**
     * Gets the substring between two given strings
     * especially useful for XML parsing
     * @param object $haystack
     * @param object $start
     * @param object $end
     * @return
     */
    static function substringBetween($haystack,$start,$end) {
        if (strpos($haystack,$start) === false || strpos($haystack,$end) === false){
            return false;
        } else {
            $start_position = strpos($haystack,$start)+strlen($start);
            $end_position = strpos($haystack,$end);
            return substr($haystack,$start_position,$end_position-$start_position);
        }
    }
    /**
     * Returns the abbrevation of the given country
     * @param object $country
     * @return string abbrivation "US" if non of them found
     */
    static function getCountryAbbr($country){
        foreach(Utils::$countries as $abbr => $name){
            if(strtolower($name) == strtolower($country)){
                return $abbr;
            }
        }
        return "US";
    }
    /**
     * returns state abbrivations
     * @param object $state
     * @return string state abbrivation return original if not found
     */
    static function getStateAbbr($state){


        if(isset(Utils::$stateAbbr[$state])){
            return Utils::$stateAbbr[$state];
        }

        if(isset(Utils::$stateAbbr[ucwords(strtolower($state))])){
            return Utils::$stateAbbr[ucwords(strtolower($state))];
        }

        return $state;
    }
    /**
     * Returns the brand of the credit card.
     * @param object $workStr Credit card number
     * @return string card type returns VISA if not found
     */
    static function identifyCreditCard($workStr){

        $workStr = preg_replace('~[\.\|\-\ ]~', '', $workStr);

        // if the string is not 13..16 digits exactly it is not valid
        $len = strlen($workStr);

        $chars1 = $workStr[0];
        $chars2 = substr($workStr, 0, 2);
        $chars3 = substr($workStr, 0, 3);
        $chars4 = substr($workStr, 0, 4);

        if ( ($chars1 == '4') && (($len == 16) || ($len == 13)) ) { return 'Visa'; }              // Visa
        if ( ($len == 16) && ($chars2 >= '51') && ($chars2 <= '55')) { return 'MasterCard'; }     // MasterCard
        if ( ($len == 15) && (($chars2 == '34') || ($chars2 == '37')) ) { return 'Amex'; }        // AmEx
        if ( ($chars4 == '6011') && ($len == 16) ) { return 'Discover'; }                         // Discover
        if ( ($chars2 == '38') && ($len == 14) ) { return 'CarteBlanche'; }                       // CarteBlanche
        if ( (($chars4 == '2014') || ($chars4 == '2149')) && ($len == 15) ) { return 'EnRoute'; } // EnRoute
        if ( (($chars4 == '2131') || ($chars4 == '1800')) && ($len == 15) ) { return 'JCB'; }     // JCB
        if ( ($chars1 == '3') && ($len == 16) ) { return 'JCB'; }                                 // JCB Also
        return "VISA";                                                                            // VISA By Default
    }
    /**
     * fixes the upload files name for non usable chars
     * @return
     * @param object $name
     */
    static function fixUploadName($name){

        if(is_array($name)){
            foreach($name as $i => &$n){
                $n = preg_replace("/[^a-zA-Z0-9()._]/", "_", $n);
                $n = preg_replace("/_+/", "_", $n);
            }
            return $name;
        }

        //$name = Utils::fixUTF($name);
        $name = preg_replace("/[^a-zA-Z0-9()._]/", "_", $name);
        $name = preg_replace("/_+/", "_", $name);

        return $name;
    }

    /**
     * Decodes the encoded URL string
     * @param object $encoded
     * @return
     */
    static function decodeURI($encoded){
        $encoded = urldecode($encoded);                 // Get encoded value from request  
        $encoded = strtr($encoded, '-_,', '+/=');       // Fix URL markup
        $decoded = base64_decode($encoded);             // Decode and get the json string
        if(!$decoded){ return false; }                  // If value cannot be converted then return
        parse_str($decoded, $parameters);               // Decode query s
        return $parameters;
    }

    /**
     * Creates an encoded parameter string
     * @param object $uri
     * @return
     */
    static function encodeURI($parameters){
        $uriparts = array();
        foreach($parameters as $key => $value){
            $uriparts[] = $key."=".urlencode($value);
        }
        $uri = join("&", $uriparts);
        $encoded = base64_encode($uri);
        return strtr($encoded, '+/=', '-_,');
    }
    /**
     * Fixes the missing HTTP prefix on urls
     * @param object $url
     * @return
     */
    static function fixHTTP($url){
        if(substr($url, 0, 8) == "https://"){
            return $url;
        }
        if(substr($url, 0, 7) != "http://"){
            return "http://" . $url;
        }
        return $url;
    }

    /**
     * Check if string is containing the given string or not
     * @param object $needle
     * @param object $haystack
     * @return
     */
    static function contains($needle, $haystack){
        return strpos($haystack, $needle) !== false;
    }

    /**
     * Shortens a string by given length
     * @param object $string String to get shortened
     * @param object $length [optional]  length of the new string defaults to 30
     * @param object $closure [optional] ending string defaults to "..."
     * @return string shortened string with closure
     */
    static function shorten($string, $length = "30", $closure = "..."){
        $sh  = substr($string, 0, $length);
        $sh .= (strlen($string) > $length)? $closure : "";
        return $sh;
    }

    /**
     * Checks if the string starts with gvent string or not
     * @param object $haystack
     * @param object $needle
     * @param object $case [optional]
     * @return
     */
    static function startsWith($haystack, $needle, $case=true) {
        if($case){ return (strcmp(substr($haystack, 0, strlen($needle)),$needle)===0); }
        return (strcasecmp(substr($haystack, 0, strlen($needle)), $needle)===0);
    }
    /**
     * Checks if the string ends with gvent string or not
     * @param object $haystack
     * @param object $needle
     * @param object $case [optional]
     * @return
     */
    static function endsWith($haystack, $needle, $case=true) {
        if($case){ return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0); }
        return (strcasecmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);
    }
    /**
     * Uses PHP's strip_tags and also removes &nbsp; spaces
     * @param object $str
     * @return clean string
     */
    static function stripTags($str){
        $str = preg_replace("/<br\s*\/?>/", "\n", $str);
        $str = strip_tags($str);
        return str_replace("&nbsp;", " ", $str);
    }

    static function stretch($str, $length){
       if(strlen($str) > $length){ return $str; }
       $slength = strlen($str)-1;
       $diff = ceil($length / $slength);
       $sum = 0;
       $newWord = array();
       for($x = 0; $x < $slength; $x++){
            $r = rand(1, $diff);
            $sum += $r;
            $newWord[$x] = $r;
       }
       $newWord[$x] = ($length - $sum);
       $stretched = "";
       foreach($newWord as $i => $wc){
            $stretched .= str_repeat($str[$i], abs($wc));
       }
       return $stretched;
    }

    /**
     * While stripping the HTML tags keeps the formatting of document
     * and clean ups unnecessary white spaces
     * @param object $string
     * @return
     */
    static function stripHTML($string, $loose = false){
        $v = $string;
        if(!$loose){
            $v = preg_replace("/\s+/im", ' ', $v);
        }
        $v = preg_replace("/<th(.*?)>(.*?)<\/th>/im", '<th> $2 </th>'."\t\t", $v);
        $v = preg_replace("/<\/td>/im", "</td>\t\t", $v);
        $v = preg_replace("/<\/tr>/im", "</tr>\n", $v);
        $v = preg_replace("/<\/li>/im", "</li>\n", $v);
        $v = preg_replace("/<\/div>/im", "</div>\n", $v);
        $v = preg_replace("/\&nbsp\;/im", ' ', $v);
        $v = preg_replace("/\<br.*?\>/im", "\n", $v);
        $v = strip_tags($v);
        $v = preg_replace('/^\s+$/im', '', $v);
        $v = preg_replace("/^[\s\t]+(.*)/im", '$1', $v);
        return $v;
    }
    /**
     * Draws a line and marks the place with given percantage and length
     * Utils::drawSlider("70", "250", "30"); prints [0 -----------(100)------------------ 250]
     * @param object $value
     * @param object $max [optional]
     * @param object $length [optional]
     * @param object $marker [optional]
     * @param object $filled [optional]
     * @param object $empty [optional]
     * @return
     */
    static function drawSlider($value, $max = 100, $length = 30, $marker=null, $filled = "-",  $empty="-"){
        # chars to use in empty and filled parts of the slider
        if($marker === null){
            $marker = "(".$value.")";
        }

        # empty slider
        $line = str_repeat($empty, $length);

        # percentage of the value over 100
        $percent = $value * 100 / $max;

        # position of the marker on our shorter slider bar
        $markerPos = $length * $percent / 100;
        $MP = $markerPos > 0? $markerPos-1 : 0;
        # Place Marker to it's position
        // $line[$markerPos-1] = $marker;

        # Replace bits before the slider to indicate filled parts of the line
        $line = str_repeat($filled, $MP) . $marker . substr($line, $MP, -1);

        return "[0 ".$line." ".$max."]";
    }


    function dateToWords($time){

        /* Change the following constants to suit your language */
        $STRING_TODAY = "Today";
        $STRING_YESTERDAY = "Yesterday";
        $STRING_DAYS = "%d Days ago";
        $STRING_WEEK = "1 Week ago";
        $STRING_WEEKS = "%d Weeks ago";

        /* Change the following date format to your taste */
        $DATE_FORMAT = "m-d-Y";

        $_word = "";

        /* Get the difference between the current time 
           and the time given in days */
        $days = intval((time() - $time) / 86400);

        /* If some forward time is given return error */
        if($days < 0) {
            return -1;
        }

        switch($days) {
            case 0: $_word = $STRING_TODAY;
                    break;
            case 1: $_word = $STRING_YESTERDAY;
                    break;
            case ($days >= 2 && $days <= 6):
                  $_word =  sprintf($STRING_DAYS, $days);
                  break;
            case ($days >= 7 && $days < 14):
                  $_word = $STRING_WEEK;
                  break;
            case ($days >= 14 && $days <= 365):
                  $_word =  sprintf($STRING_WEEKS, intval($days / 7));
                  break;
            default : return date($DATE_FORMAT, $time);

        }

        return $_word;
    }

    /**
     * Fins and replace stupid windows characters that breaks Excel reports and etc
     * @param object $str
     * @return
     */
    public static function fixMSWordChars($str){

        # Convert string to UTF-16 to prevent russion or japanese chars to mix with 148
        $str = mb_convert_encoding($str, 'UTF-16', 'UTF-8');

        // First, replace UTF-8 characters.
        $text = str_replace(
            array(              # List of harmfull characters
                "\xe2\x80\x98",
                "\xe2\x80\x99",
                "\xe2\x80\x9c",
                "\xe2\x80\x9d",
                "\xe2\x80\x93",
                "\xe2\x80\x94",
                "\xe2\x80\xa6"
            ),
            array("'", "'", '"', '"', '-', '--', '...'), # Correct replacements for them
            $str                # Original String
        );

        // Next, replace their Windows-1252 equivalents.
        $text = str_replace(
            array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
            array("'", "'", '"', '"', '-', '--', '...'),
            $text
        );

        # Convert back to UTF-8 to make everything normal
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-16');

        return $text;
    }
}
