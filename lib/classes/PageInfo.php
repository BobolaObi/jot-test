<?php
/**
 * Contains the page information
 * @package JotForm_Site_Management
 * @copyright Copyright (c) 2009, Interlogy LLC
 */
class PageInfo
{
    private static $pages = array();
    private static $notfound = array(
        "title"=>"Page not found",
        "content"=>"lib/includes/notfound.php",
        "css"=>array(),
        "js"=>array(),
        "404" => true
    );
    /**
     * Returns the page info
     * @return 
     * @param object $page
     */
    static function getPage($page){
        if(array_key_exists($page, self::$pages)){
            return self::$pages[$page];
        }else{
            return self::$notfound;
        }
    }
    /**
     * Creates a new page
     * @return 
     * @param object $pageName
     * @param object $pageTitle
     * @param object $contentFile
     * @param object $cssIncludes[optional]
     */
    static function setPage($options){
    
    // $pageName, $pageTitle, $contentFile, $cssIncludes=array(), $jsIncludes=array()){
        $options = array_merge(array(
            "title" => "",
            "content" => "",
            "css" => array(),
            "js" => array(),
            "loginNeeded" => false
        ), $options);
        
        if (!Session::isLoggedIn()) {
            $options["js"] = array_merge($options["js"], array(
                "js/includes/loginForm.js"
            ));
        }
        
        self::$pages[$options["name"]] = $options; 
    }
}