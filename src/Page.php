<?php
/**
 * Gets the page info from PageInfo class
 * then makes it ready to be used in the page
 * @package JotForm_Site_Management
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Site_Management;
class Page {
    private $page;
    /**
     * Constructer
     * @return 
     * @param object $pageName
     */
    function __construct($pageName){
    	$this->page = PageInfo::getPage($pageName);
    	$this->checkCredentials();
    }
    
    /**
     * 
     * @return 
     */
    function checkCredentials(){
        /**
         * TODO: What will this do?
         */
        
        //if($this->page["loginNeeded"]){
            //if(!Session::isLoggedIn() && !Session::isGuest()){
            //    Utils::redirect("page.php?p=signup");
            //}
        //}
        if(!isset($this->page['name'])){ $this->page['name']=''; }
        // Check for password reset stuff.
        switch($this->page['name']) {
            case 'passwordreset':
                $username = isset($_GET['username'])? $_GET['username'] : "";
                $token = isset($_GET['token'])? $_GET['token'] : "";
                if (empty($username) || empty($token)) {
                    Utils::errorPage("Password reset code has expired. Please issue a reset request again.", "Reset code expired");
                    //$this->page = PageInfo::getPage('passwordresetexpired');
                    break;
                }
                $user = User::find($username);
                if ($user) {
                    $realToken = md5($user->password . date('Y-m-d'));
                    if ($realToken != $token) {
                        Utils::errorPage("Password reset code has expired. Please issue a reset request again.", "Reset code expired");
                        //$this->page = PageInfo::getPage('passwordresetexpired');
                    }
                } else {
                    Utils::errorPage("Password reset code has expired. Please issue a reset request again.", "Reset code expired");
                    //$this->page = PageInfo::getPage('passwordresetexpired');
                }
                break;
        }
        
    } 
    /**
     * Checks if the page hasFullscreen options
     * @return 
     */
    function hasFullScreen(){
        return isset($this->page['hasFullScreen'])? $this->page['hasFullScreen'] : false;
    }
    
    /**
     * Converts css files to html includes 
     * @return 
     */
    function putCSSIncludes(){
        $incs = array();
        foreach($this->page['css'] as $css){
        	if(empty($css)){ continue; }
            array_push($incs, '<link rel="stylesheet" type="text/css" href="'.$css.'"/>');
        }
        echo join("\n", $incs)."\n";
    } 
    /**
     * Convers js files to html includes
     * @return 
     */
    function putJSIncludes(){
        $incs = array();
        foreach($this->page['js'] as $js){
        	if(empty($js)){ continue; }
        	if (PROTOCOL == 'https://' && ($js == 'http://maps.google.com/maps/api/js?sensor=true' || $js == 'js/googlemap.js')) {
        	   continue;   
        	}
            array_push($incs, '<script src="'.$js.'" type="text/javascript"></script>');
        }
        echo join("\n", $incs)."\n";
    }
    /**
     * get page title
     * @return 
     */
    function getTitle(){
        return $this->page['title'];
    }
    /**
     * Include the page content
     * @return 
     */
    function putContent(){
        include ROOT."/".$this->page['content'];
    }
}
