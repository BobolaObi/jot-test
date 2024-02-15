<?php

use Legacy\Jot\Utils\Utils;

# no idea why, but this doesn't work with the name RequestServer....


class LDAP{
    
    static $instance;
    
    public $conn;
    
    
    /**
     * Constructs LDAP object
     * @param object $host
     * @param object $port [optional]
     * @return 
     */
    public function __construct($host,  $port = 389){
        
        // Connecting to LDAP
        $this->conn = ldap_connect($host, $port);
        
        if(!$this->conn){
            throw new Exception("Could not connect to $host");  
        }
        ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    } 
    /**
     * Bind to LDAP server
     * @param object $username [optional]
     * @param object $domain [optional]
     * @param object $password [optional]
     * @return 
     */
    public function bind($username = null, $domain="", $password=null){
        $rdn = null;
        if($username){
            $rdn = "cn=".$username.",dc=".$domain.",dc=com";
        }
        return ldap_bind($this->conn, $rdn, $password);
    }
    
    /**
     * Search LDAP user with given information
     * @return 
     */
    public function search($person){
        
        $dn = "dc=interlogy,dc=com";
        $filter="(|(sn=$person*)(givenname=$person*))";
        $justthese = array("ou", "sn", "givenname", "mail");
        $sr = ldap_search($this->conn, $dn, $filter, $justthese);
        
        $info = ldap_get_entries($this->conn, $sr);
        
        Utils::print_r($info);
    }
}
?>