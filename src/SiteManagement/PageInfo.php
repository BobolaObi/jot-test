<?php
/**
 * Contains the page information
 * @package JotForm_Site_Management
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\SiteManagement;

use Legacy\Jot\UserManagement\Session;

class PageInfo
{
    private static $pages = [];
    private static $notfound = [
        "title" => "Page not found",
        "content" => "lib/includes/notfound.php",
        "css" => [],
        "js" => [],
        "404" => true
    ];

    /**
     * Returns the page info
     * @param  $page
     * @return
     */
    static function getPage($page)
    {
        if (array_key_exists($page, self::$pages)) {
            return self::$pages[$page];
        } else {
            return self::$notfound;
        }
    }

    /**
     * Creates a new page
     * @param  $pageName
     * @param  $pageTitle
     * @param  $contentFile
     * @param  $cssIncludes [optional]
     * @return
     */
    static function setPage($options)
    {

        // $pageName, $pageTitle, $contentFile, $cssIncludes=array(), $jsIncludes=array()){
        $options = array_merge([
            "title" => "",
            "content" => "",
            "css" => [],
            "js" => [],
            "loginNeeded" => false
        ], $options);

        if (!Session::isLoggedIn()) {
            $options["js"] = array_merge($options["js"], [
                "js/includes/loginForm.js"
            ]);
        }

        self::$pages[$options["name"]] = $options;
    }
}