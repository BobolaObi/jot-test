<?php
/**
 * Collects the information from clients
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Utils;

class Client{
    
    public $useragent,$language,$encoding,$charset,$ip;
    
    private $fp = false;
    static $currentClient = false;
    
    function Client($params = false){
        if ($params === false){
            $params	= $_SERVER;
        }
    	$this->useragent = @$params['HTTP_USER_AGENT'];
        $this->language  = @$params['HTTP_ACCEPT_LANGUAGE'];
        $this->encoding  = @$params['HTTP_ACCEPT_ENCODING'];
        $this->charset   = @$params['HTTP_ACCEPT_CHARSET'];
        $this->ip        = @$params['REMOTE_ADDR'];        
    }
    /**
     * create singleton client
     * @return 
     */
    static function create(){
        if(self::$currentClient === false){
            self::$currentClient = new Client();
        }
        return self::$currentClient;
    }
    
    /**
     * Singleton for fingerprint
     * @return 
     */
    static function getFingerPrint(){
        $client = self::create();
        return $client->fingerPrint();
    }
    
    /**
     * Generates a finger print
     * @return 
     */
    public function fingerPrint(){
        if($this->fp !== false){
            return $this->fp;
        }
        # IP is removed from the fingerPrint because it's changing too much
        return md5($this->useragent.":".$this->language.":".$this->encoding.":".$this->charset /*.":".$this->ip */);
    }
    /**
     * Matches the client fingerprint
     * @param  $fingerPrint
     * @return 
     */
    static function match($fingerPrint){
        
        return self::getFingerPrint() == $fingerPrint;
    }
    /**
     * Returns the Internet explorer version
     * @return // false if not an IE browser
     */
    static function getIEVersion($agent = false){
        if($agent !== false){
            $userAgent = $agent;
        }else{
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
        }
        
        if(preg_match("/MSIE\s([0-9]{1,}[\.0-9]{0,})/", $userAgent, $m)){
            $ver = $m[1];
            return (float) $ver;
        }
        return false;
    }
    
}