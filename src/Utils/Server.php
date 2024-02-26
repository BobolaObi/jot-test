<?php
/**
 * Definition off all sigling and friend servers of JotForm
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Utils;

class Server
{

    public static $servers = array();

    /**
     * Set servers in list and convert the list into an object
     * @param  $servers
     * @return
     */
    static function setServers($servers)
    {
        self::$servers = Utils::toObject($servers);
    }

    /**
     * Returns the host name of the server
     * @return // server name or false if not found
     */
    static function whoAmI($otherIP = false)
    {

        if (Server::isLocalhost()) {
            return "localhost";
        }

        $siblings = self::getServerList();

        $myIP = $otherIP ? $otherIP : $_SERVER['SERVER_ADDR'];
        foreach ($siblings['local'] as $name => $ip) {
            if ($ip == $myIP) {
                return $name;
            }
        }

        foreach ($siblings['remote'] as $name => $ip) {
            if ($ip == $myIP) {
                return $name;
            }
        }
        # Exceptional for monk we should fix this and remove it from here
        if ($myIP == '174.34.48.219') {
            return true;
        }

        return false;
    }

    /**
     * Checks if the given ip is a sibling or not?
     * @param  $ip
     * @return // sibling name or false if not found
     */
    static function isSibling($ip)
    {
        return self::whoAmI($ip);
    }

    /**
     * Returns the information of specific server instance
     * If no name provided returns the self information
     * @param  $name // [optional]
     * @return // false if not an instance or localhost
     */
    static function getInstance($name = false)
    {
        if ($name === false) {
            $name = self::whoAmI();
        }

        $siblings = self::getServerList();

        if (isset($siblings['remote'][$name])) {
            return array(
                "remote_ip" => $siblings['remote'][$name],
                "local_ip" => $siblings['local'][$name],
                "name" => $name
            );
        }

        return false;
    }

    /**
     * Returns the other servers except this
     * @return // array of sibling names
     */
    static function getSiblings()
    {
        $me = self::whoAmI();
        if ($me == 'localhost') {
            return array();
        } // localhost has no sibling
        $siblingList = self::getServerList();
        $siblings = array();
        foreach ($siblingList['remote'] as $name => $ip) {
            if ($name != $me) {
                $siblings[] = array("name" => $name, "ip" => $ip);
            }
        }
        return $siblings;
    }

    /**
     * Check if the server is localhost
     * @return // boolean
     */
    static function isLocalhost()
    {
        $addr = $_SERVER['SERVER_ADDR'];
        $host = $_SERVER["HTTP_HOST"];

        if (strstr($addr, "192.168") || strstr($addr, "127.0.0.1") || strstr($addr, "::1") || strstr($host, "10.0.") || strstr($host, "localhost")) {
            return true;
        }

        if (strstr($host, "192.168") || strstr($host, "127.0.0.1") || strstr($addr, "::1") || strstr($host, "10.0.") || strstr($host, "localhost")) {
            return true;
        }
        return false;
    }

    /**
     * Returns the IP address of the current server
     * @return
     */
    static function getLocalIP()
    {
        return isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : false;
    }

    /**
     * Check if script runs on the the given host or hosts.
     *
     * @param string|array $hostname single hostname or array of hostnames to check
     * @return // boolean
     */
    static function isHost($hostname)
    {
        # make sure you always have the host name
        if (!isset($_SERVER["HTTP_HOST"])) {
            $_SERVER["HTTP_HOST"] = trim('' . `hostname`) . 'interlogy.com';
        }

        # defined domains for checking
        $domains = array("interlogy.com", "jotform.com", "jotfor.ms");

        # check host name for each domain
        foreach ($domains as $domain) {
            if (!is_array($hostname)) {
                $hostname = array($hostname);
            }
            # check each host name
            foreach ($hostname as $host) {
                # make sure www also covers no subdomain
                if ($host == "www" && ($_SERVER["HTTP_HOST"] == $domain)) {
                    return true;
                }

                # check host name
                if (($_SERVER["HTTP_HOST"] == $host . "." . $domain)) {
                    return true;
                }

                # check if IP was given as a host name
                if (($_SERVER["HTTP_HOST"] == $host)) {
                    return true;
                }
            }
            # if none matched then return false
            continue;
        }
        # fail safe
        return false;
    }

    /**
     * Check if the php version is bigger then given number
     * @param  $version // [optional]
     * @return
     */
    static function isPHP($version = '5.0.0')
    {
        return (version_compare(PHP_VERSION, (string)$version) < 0) ? false : true;
    }

    /**
     * Add a new server to the list on database
     * @param  $name
     * @param  $remoteIP
     * @param  $localIP
     * @return
     */
    static function addServer($name, $publicIP, $localIP)
    {
        Settings::setSetting("servers", $name, $publicIP . ":" . $localIP);
        Utils::cacheDelete("servers");
    }

    /**
     * Removes the server from list on database
     * @param  $name
     * @return
     */
    static function removeServer($name)
    {
        Settings::removeSetting("servers", $name);
        Utils::cacheDelete("servers");
    }

    /**
     * Returns the list of all available servers
     * @return
     */
    static function getServerList()
    {
        $listRemote = (array)self::$servers->siblings->remote;
        $listLocal = (array)self::$servers->siblings->local;

        /**
         *
         * Stop Database list for servers
         * Creates too many connections on serverTalk
         * @TODO Find a better and optimized way of doing this
         *
         * $servers = Settings::getByIdentifier('servers');
         * if($servers){
         * foreach($servers as $line){
         * list($remote, $local) = explode(":", $line['value']);
         * $listLocal[$line['key']] = $local;
         * $listRemote[$line['key']] = $remote;
         * }
         * }
         */
        return array(
            "remote" => $listRemote,
            "local" => $listLocal
        );
    }

    /**
     * Builds and distributes jotform to all servers
     * @return
     */
    static function deployServers()
    {
        $username = "deploy";
        $password = "deploy123";
        if ($wget = Utils::findCommand('wget')) {
            shell_exec($wget . " --auth-no-challenge --http-user=" . $username . " --http-password=" . $password . " http://salmon.interlogy.com:8080/job/jotform/build?delay=0sec > /dev/null &");
        } else {
            throw new \Exception('wget is not installed. Please install wget CLI');
        }

        // "curl --user ".$username.":".$password." http://64.34.169.225:8080/job/jotform/".$buildNum."/logText/progressiveHtml"
    }

    /**
     *
     */
    static function isMaxCDN()
    {
        if (isset($_SERVER['HTTP_X_REAL_IP'])) {
            if (Utils::startsWith($_SERVER['HTTP_X_REAL_IP'], '92.60.242')) {
                return true;
            }
            if (Utils::startsWith($_SERVER['HTTP_X_REAL_IP'], '69.174')) {
                return true;
            }
        }
        return false;
    }

    static function isCacheable()
    {
        $list = array("/jsform/", "/form/");

        foreach ($list as $path) {
            if (Utils::startsWith($_SERVER['REQUEST_URI'], $path)) {
                return true;
            }
        }

        return false;
    }

    static function getHost()
    {
        return $_SERVER['HTTP_HOST'];
    }
}
