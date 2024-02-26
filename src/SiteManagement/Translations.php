<?php
/**
 * Used http://www.thefutureoftheweb.com/blog/use-accept-language-header as 
 * reference.
 * See http://www.w3.org/International/questions/qa-accept-lang-locales for 
 * reference on using HTTP Headers for determining user's language.
 *
 * There should be a priority list so that if a user already has made a 
 * Language selection, this could be saved in the database and this has the 
 * highest priority.
 *
 * Priority: 
 * forced language given as method parameter
 * current selection from the user (cookie) 
 * database  
 * browsers accept header  ?
 * @package JotForm_Site_Management
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\SiteManagement;
use Legacy\Jot\UserManagement\Session;
use Legacy\Jot\Utils\Utils;

class Translations
{
    // Static array of available languages.
     public static $languagesAvailable = array(
		"English" => "en-US", 
		"Español" => "es-ES", 
		"Français" => "fr-FR", 
		"Italiano" => "it-IT", 
		"Português" => "pt-PT", 
		"Deutsch" => "de-DE", 
		"Türkçe" => "tr-TR",
		"Català" => "ca-ES",
		"Nederlands" => "nl-NL",
		"Svenska" => "sv-SE",
		"Magyar" => "hu-HU",
		"Norsk" => "no-NO",
		"Danske" => "da-DA",
		"Română" => "ro-RO",
        "Finnish" => "fi-FI"
        #, "Zombie" => "zb-ZB"
    );
    //public static $languagesAvailable = array( "English" => "en-US", "Türkçe" => "tr-TR");
    // Instance variable for the language code, as used internally 
    // (includes country code).
    public static $langCode = false;
    
    public static $language;
    
    /**
     * Returns the correct language for current domain
     * @return 
     */
    public static function getDomainLanguage(){
        list($host, $url) = explode(".", $_SERVER['HTTP_HOST'], 2);
        
        switch($host){
            case "turk":    return "tr-TR";
            case "french":  return "fr-FR";
            case "spanish": return "es-ES";
            case "italian": return "it-IT";
            case "dutch":   return "nl-NL";
            case "german":  return "de-DE";
            case "portuguese":  return "pt-PT";
            case "catalan":  return "ca-ES";
            case "swedish":  return "sv-SE";
            case "magyar":  return "hu-HU";
            case "norsk":  return "no-NO";
            case "danske":  return "da-DA";
            case "romanian":  return "ro-RO";
            case "finnish":   return "fi-FI";
        }
        
        return false;
    }

    public static function getShortLanguageCode($forceLanguage = NULL) {
	    return substr(self::getLanguageCode($forceLanguage), 0, 2);
    }

    public static function getBrowserLang(){
        $langs = array();
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // break up string into pieces (languages and q factors)
            preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?(,|$)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);
            if (count($lang_parse[1])) {
                // create a list like "en" => 0.8
                $langs = array_combine($lang_parse[1], $lang_parse[4]);
                
                // set default to 1 for any without q factor
                // do not think you know this better. Commas are used in a non-
                // semantic way.
                foreach ($langs as $lang => $val) {
                    if ($val === '') $langs[$lang] = 1;
                }
        
                // sort list based on value 
                arsort($langs, SORT_NUMERIC);
            }
        }
        
        foreach ($langs as $lang => $val) {
            if ($match = self::checkLangAvailable($lang)) {
                return $match;
            }
        }
        
        return "en-US";
    }

    /**
     * Determines the language code from cookie or browser options
     * @param  $forceLanguage  // [optional] Forces a language to be used
     * @return 
     */
    public static function getLanguageCode($forceLanguage = NULL) {
        
        if ($forceLanguage) {
            
            self::$langCode = $forceLanguage;
            Utils::setCookie("language", self::$langCode, "+1 Month");
            return self::$langCode;
            
        } else if(self::$langCode){
            
            return self::$langCode;
            
        } else if (Utils::getCookie('language')) {
            
            // If there's a cookie, return the language it has.
            self::$langCode = Utils::getCookie('language');
            return self::$langCode;
            
        } else if(self::getDomainLanguage() !== false){
            
            self::$langCode = self::getDomainLanguage();
            Utils::setCookie("language", self::$langCode, "+1 Month");
            return self::$langCode;
            
        }
        
        // Check the browser accept language header.
        $langs = array();
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // break up string into pieces (languages and q factors)
            preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?(,|$)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);
            if (count($lang_parse[1])) {
                // create a list like "en" => 0.8
                $langs = array_combine($lang_parse[1], $lang_parse[4]);
                
                // set default to 1 for any without q factor
                // do not think you know this better. Commas are used in a non-
                // semantic way.
                foreach ($langs as $lang => $val) {
                    if ($val === '') $langs[$lang] = 1;
                }
        
                // sort list based on value	
                arsort($langs, SORT_NUMERIC);
            }
        }
        
        // look through sorted list and use first one that matches our languages
        foreach ($langs as $lang => $val) {
            if ($match = self::checkLangAvailable($lang)) {
                self::$langCode = $match;
                Utils::setCookie("language", self::$langCode, "+1 Month");
                return self::$langCode;
            }
        }
        
        // Return an empty string if no language configuration could be found. 
        // English language file is always included so no need to include explicitly.
        self::$langCode = "en-US";
        return self::$langCode;
    }

    /**
     * Checks if the language is available as a translation on our end
     * @param  $lang
     * @return 
     */
    public static function checkLangAvailable($lang){
        foreach(self::$languagesAvailable as $localName => $code) {
            if (strcasecmp($lang, $code) == 0) {
                return $code;
            }
            // Check if the language codes are the same, without country code.
            preg_match('/(?P<languageCode>[a-z]{1,8})\s*-\s*(?P<countryCode>[a-z]{1,8})/i', $code, $matches);
            if (strcasecmp($lang, $matches['languageCode']) == 0) {
                return $code;
            }
        }

        return false;
    }
    
    /**
     * Includes the correct script on the page
     * @return 
     */
    public static function getJsInclude() {
        $lang = self::getLanguageCode();
        if ($lang != "en-US") {
            return '<script src="js/locale/locale_' .  $lang . '.js" type="text/javascript"></script>'."\n";
        }
        return "\n";
    }
    
    /**
     * Reads the translation file parses the JSON and puts it into an array
     * @param  $lang
     * @return 
     */
    public static function getLanguageArray($lang){
        $file = join("", file(ROOT."js/locale/locale_".$lang.".js"));
        $file = preg_replace("/\n+/", "", $file);
        $file = preg_replace("/^[\W\w\s\S]*Locale.language\s*=\s*/", "", $file);
        $file = preg_replace("/\",\s*\"/", '","', $file);
        $file = preg_replace("/{\s*\"/", '{"', $file);
        $file = preg_replace("/\"\s*}/", '"}', $file);
        $file = preg_replace("/\":\s*\"/", '":"', $file);
        self::$language = json_decode($file, true);
    }
    public static $notTranslated = array();
    /**
     * translates the given text into the selected language
     * @param  $string  // Word to be translated
     * @return string Translated Word
     */
    public static function getText($string){
        # Zombie language, do enable: uncomment zb-ZB in languages list, go to locale.js un comment zombie converter and uncomment below line 
        # if(self::$langCode == 'zb-ZB'){ return Utils::stretch("Brains!", strlen($string)); }
        
        if(!is_array(self::$language)){ array_push(self::$notTranslated, $string); return $string; }
        
        if(array_key_exists($string, self::$language)){
            return self::$language[$string];
        }else{
            if(!empty($string)){
                array_push(self::$notTranslated, $string);
            }
        }
        
        return $string;
    }
    
    /**
     * Prints the dropdown for language selection
     * @return 
     */
    public static function getLangSelect() {
        $toReturn = '<select id="language-box">'."\n";
        $lang = self::getLanguageCode();
        
        foreach (self::$languagesAvailable as $name => $code) {
            
            $selected = ($code == $lang)? ' selected="selected"' : "";
            
            $toReturn .= '                            <option value="' . $code . '"' . $selected . '>' . $name . "</option>\n";
        }
        $toReturn .= "                        </select>\n";
        return $toReturn;
    }
    
    
    /**
     * Evaluates all page translates all HTML markup then cleans up the locale classes
     * @param  $lang
     * @return 
     */
    public static function translatePage($lang = false){

    	if(!$lang){ $lang = Translations::$langCode; }
        if(!$lang){ return; }
        
        
        $html = ob_get_contents();
        ob_clean();

        /*$md5 = md5($html.$lang);
        
        # Check Cache Here
        if($cache = Utils::cacheGet($md5)){
            Console::log("Cache found", $md5);
            Session::putLoginForm();
            $tmp = ob_get_contents();
            ob_clean();
            $cache = str_replace("{myAccount}", $tmp, $cache);
            echo $cache;
            exit;
        }
        */
        self::getLanguageArray($lang);
        
        include_once ROOT."opt/simple_html_dom.php";
        $dom = str_get_html($html);
        /*
        $myaccount = $dom->find("#myaccount");
        
        $tmp = $myaccount[0]->innertext;
        
        $myaccount[0]->innertext = "{myAccount}";
        */
        foreach($dom->find(".locale") as $locale){
            $locale->class = str_replace("locale", "", $locale->class);
            if(empty($locale->class)){ $locale->class = null; }
            $locale->innertext = self::getText(trim(''.$locale->innertext));
        }
        
        foreach($dom->find(".locale-img") as $locale){
            $locale->class = str_replace("locale-img", "", $locale->class);
            if(empty($locale->class)){ $locale->class = null; }
            $locale->alt = self::getText(trim(''.$locale->alt));
            $locale->title = self::getText(trim(''.$locale->title));
        }
        
        foreach($dom->find(".locale-button") as $locale){
            $locale->class = str_replace("locale-button", "", $locale->class);
            if(empty($locale->class)){ $locale->class = null; }
            $locale->value = self::getText(trim(''.$locale->value));
        }
        # Console::error(json_encode(self::$notTranslated));
        foreach($dom->find("script") as $script){
            if(!$script->src){continue;}
            
            # Convert the minifer css files.
            if (ENABLE_CDN ){
                $minConstant = "min/g=";
                if ( strstr($script->src, $minConstant) ){
                    $groupName = substr($script->src, strlen($minConstant) );
                    ##### Start of generate min url ######
                    # Put the groupname
                    $_GET['g'] = $groupName;
                    $_GET[VERSION] = "";
                    require_once( DROOT . DIRECTORY_SEPARATOR .  "min/generateMinUrl.php");
                    $cacheId = generateMinUrl(); # Generate cache ID.
                    $cacheId = str_replace(".gz", ".jgz", $cacheId); 
                    unset($_GET[VERSION]);
                    if (isset($_REQUEST['g'])){
                        $_GET['g'] = $_REQUEST['g'];
                    }
                    ##### End of generate min url ########
                    $script->src = Utils::getCloudURL() . VERSION . '/min/' . $cacheId;
                }
            }

            if(strstr($script->src, '?')){
                $script->src .= '&v='.VERSION;
            }else if(strstr($script->src, 'min/')){
                if (ENABLE_CDN){
                    $script->src .= '?'.VERSION; 
                }else{
                    $script->src .= '&'.VERSION; 
                }
            }else{
                $script->src .= '?v='.VERSION; 
            }
            
            # Prevent server includes to be cached
            if(strstr($script->src, 'server.php') !== false){
                $script->src .= "&nocache=".time();
            }
        }

        foreach($dom->find("link") as $link){
            if(!$link->href){continue;}
            
            if (ENABLE_CDN && strpos($link->href, "opt") !== 0 && strpos($link->href, "http") !== 0 ){
                
                $minConstant = "min/g=";
                # Convert the minifer css files.
                if ( strpos($link->href, $minConstant) !== FALSE ){
                    $groupName = substr($link->href, strlen($minConstant) );
                    ##### Start of generate min url ######
                    # Put the groupname
                    $_GET['g'] = $groupName;
                    $_GET[VERSION] = "";
                    require_once( DROOT . DIRECTORY_SEPARATOR .  "min/generateMinUrl.php");
                    $cacheId = generateMinUrl(); # Generate cache ID.
                    unset($_GET[VERSION]);
                    if (isset($_REQUEST['g'])){
                        $_GET['g'] = $_REQUEST['g'];
                    }
                    $cacheId = str_replace(".gz", ".jgz", $cacheId); 
                    ##### End of generate min url ########
                    $link->href = Utils::getCloudURL() . Utils::path(VERSION . '/min/') . $cacheId;
                }else{
                    $link->href = Utils::getCloudURL() . Utils::path(VERSION . '/' . $link->href, true) ;
                }
            }
            
            # Add version to links after 
            if(strstr($link->href, '?')){
                $link->href .= '&v='.VERSION;
            }else if(strstr($link->href, 'min/')){
                if (ENABLE_CDN){
                    $link->href .= '?'.VERSION; 
                }else{
                    $link->href .= '&'.VERSION; 
                }
            }else{
                $link->href .= '?v='.VERSION; 
            }
        }

        if ( false ){
            foreach($dom->find("img") as $link){
                # For each images convert the image link.
                $link->src = Utils::getCloudURL() . VERSION . '/' . $link->src;
            }
        }
        
        $html = preg_replace("/^\s+/m", "", ((string) $dom));
        
        # Store cahce here
        /*Utils::cacheStore($md5, $html);
        Console::log('Cache Stored', $md5);*/
       
        //$html = str_replace("{myAccount}", $tmp, $html);
        echo $html;
    }
    
    /**
     * Prints a Help message on the screen
     * @return 
     */
    static function helpText(){
        # return ""; #close while there is a banner
        
        if(APP){return;} # Don't show on Application
        
        # If cookie is set don't show themessage;
        if(Utils::getCookie('no-translate') && !Session::isGuest()){ return; }
        
        $code = self::getLanguageCode();
        if( $code != "en-US" ){ 
            echo '<div id="total-translate-container">
            <!--[if gt IE 7]>
            <style>
            #footer{
                height:450px;
            }
            #main{
                padding-bottom:450px;
            }
            .footer-box {
                height:300px;
            }
            .footer-box > div {
                padding:10px 0;
            }
            
            </style>
            <![endif]-->
            <style type="text/css">
                #footer{
                    height:400px !important;
                }
                .main, #footer-content {
                    padding-bottom:410px !important;
                }
                #translate-text a {
                    text-decoration: underline; 
                    color: white;
                }
                #remove-messsage{
                    margin:5px;                    
                    font-size:12px;
                    cursor:pointer;
                    top:2px;
                    right:2px;
                    position:absolute;
                    display:none;
                }
                #translate-text:hover #remove-messsage{
                    display:block;
                }
                #translate-text{
                    position:relative;
                    height:90px !important;
                    margin:0 auto -20px;
                    text-align:center;
                    top:0;
                }
            </style>
            <div id="translate-text" class="footer-box index-footer-back">
                <h2 style="margin-bottom:5px;">Help us Translate JotForm!</h2>
                We are sorry about the translation problems during transition to JotForm 3.0. Please help us fix them!<br> 
                <a href="/page.php?p=locale/'.$code.'">Download this document</a>, fix any problems you see and <a href="mailto:jotform@jotform.com">send it back to us</a>. Thank you!
                <br>';
                
                if(!Session::isGuest()){
                    echo '<button class="big-button buttons buttons-black" id="remove-messsage" onclick="$(\'total-translate-container\').remove(); document.createCookie(\'no-translate\', \'1\')"><img src="images/blank.gif" class="index-cross" align="absmiddle" /></button>';
                }
                
            echo '</div></div>';
        }
    }
    
    /**
     * Prompt language file for download
     * @param  $requestedPage
     * @return 
     */
    static function downloadLanguageFile($requestedPage){        
        if( substr($requestedPage, 0, 6) == "locale" ){
            header("Content-type: text/plain; charset=utf-8");
            include("js/locale/locale_".substr($requestedPage, 7, 5).".js");
            exit;
        }
    }
}
