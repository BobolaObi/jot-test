<?php

namespace Quarantine;;

use LDAPException;
use Legacy\Jot\Utils\Console;


/**
 * @file
 * LDAPInterface class definition.
 */
class LDAPInterface
{

    function __construct()
    {
        $this->connection = NULL;
        //http://drupal.org/node/158671
        $this->server = NULL;
        $this->port = "389";
        $this->secretKey = NULL;
        $this->tls = FALSE;
        $this->attr_filter = array('LDAPInterface', '__empty_attr_filter');
        if (!function_exists("ldap_connect")) {
            throw new LDAPException("php_ldap is missing.");
        }
    }

    var $connection;
    var $server;
    var $port;
    var $tls;
    var $attr_filter;
    var $sid;

    // This should be static, but that's not supported in PHP4
    function __empty_attr_filter($sid, $x)
    {
        return $x;
    }

    function setOption($option, $value)
    {
        switch ($option) {
            case 'sid':
                $this->sid = $value;
                break;
            case 'name':
                $this->name = $value;
                break;
            case 'server':
                $this->server = $value;
                break;
            case 'port':
                $this->port = $value;
                break;
            case 'tls':
                $this->tls = $value;
                break;
            case 'encrypted':
                $this->encrypted = $value;
                break;
            case 'user_attr':
                $this->user_attr = $value;
                break;
            case 'attr_filter':
                $this->attr_filter = $value;
                break;
            case 'basedn':
                $this->basedn = $value;
                break;
            case 'mail_attr':
                $this->mail_attr = $value;
                break;
            case 'binddn':
                $this->binddn = $value;
                break;
            case 'bindpw':
                $this->bindpw = $value;
                break;
        }
    }

    /**
     * Gets a record from search results and normalizes it's structure
     * @param  $result
     * @return
     */
    function normalizeSearchResult($result)
    {
        $normalized = array();
        foreach ($result as $key => $value) {
            if ($key == "count") {
                continue;
            } # We don't need count key. Obviously, we can count this stupid array
            if (is_numeric($key)) {
                continue;
            } # This should be an associative array no need for array indexes
            if (is_array($value)) {
                $value = $value[0];
            } # usually the first argument is what we needed
            $normalized[$key] = $value;
        }
        return $normalized;
    }

    function getOption($option)
    {
        $ret = '';
        switch ($option) {
            case 'sid':
                $ret = $this->sid;
                break;
            case 'version':
                $ret = -1;
                ldap_get_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $ret);
                break;
            case 'name':
                $ret = $this->name;
                break;
            case 'port':
                $ret = $this->port;
                break;
            case 'tls':
                $ret = $this->tls;
                break;
            case 'encrypted':
                $ret = $this->encrypted;
                break;
            case 'user_attr':
                $ret = isset($this->user_attr) ? $this->user_attr : NULL;
                break;
            case 'attr_filter':
                $ret = isset($this->attr_filter) ? $this->attr_filter : NULL;
                break;
            case 'basedn':
                $ret = isset($this->basedn) ? $this->basedn : NULL;
                break;
            case 'mail_attr':
                $ret = isset($this->mail_attr) ? $this->mail_attr : NULL;
                break;
            case 'binddn':
                $ret = isset($this->binddn) ? $this->binddn : NULL;
                break;
            case 'bindpw':
                $ret = isset($this->bindpw) ? $this->bindpw : NULL;
                break;
        }
        return $ret;
    }

    function connect($dn = '', $pass = '')
    {
        $ret = FALSE;
        // http://drupal.org/node/164049
        // If a connection already exists, it should be terminated
        $this->disconnect();

        if ($this->connectAndBind($dn, $pass)) {
            $ret = TRUE;
        }

        return $ret;
    }

    function initConnection()
    {
        if (!$con = ldap_connect($this->server, $this->port)) {
            throw new LDAPException('LDAP Connect failure to ' . $this->server . ":" . $this->port . '. Error ' . @ldap_errno() . ': ' . @ldap_error());
            return;
        }

        ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($con, LDAP_OPT_REFERRALS, 0);
        // TLS encryption contributed by sfrancis@drupal.org
        if ($this->tls) {
            ldap_get_option($con, LDAP_OPT_PROTOCOL_VERSION, $vers);
            if ($vers == -1) {
                throw new LDAPException('Could not get LDAP protocol version.');
                return;
            }
            if ($vers != 3) {
                throw new LDAPException('Could not start TLS, only supported by LDAP v3.');
                return;
            } else if (!function_exists('ldap_start_tls')) {
                throw new LDAPException('Could not start TLS. It does not seem to be supported by this PHP setup.');
                return;
            } else if (!ldap_start_tls($con)) {
                throw new LDAPException('Could not start TLS. (Error ' . ldap_errno($con) . ': ' . ldap_error($con) . ').');
                return;
            }
        }
        $this->connection = $con;
    }

    function connectAndBind($dn = '', $pass = '')
    {
        $this->initConnection();

        $con = $this->connection;
        if (!$this->bind($dn, $pass)) {
            throw new LDAPException('LDAP Bind failure for user ' . $dn . '. Error ' . ldap_errno($con) . ': ' . ldap_error($con));
            return NULL;
        }

        return $con;
    }

    function bind($dn, $pass)
    {
        ob_start();
        set_error_handler(array('forms.datalynk.ca\Quarantine\LDAPInterface', 'void_error_handler'));
        $ret = ldap_bind($this->connection, $dn, $pass);
        restore_error_handler();

        ob_end_clean();

        return $ret;
    }

    function disconnect()
    {
        if ($this->connection) {
            ldap_unbind($this->connection);
            $this->connection = NULL;
        }
    }

    function search($base_dn, $filter, $attributes = array())
    {
        $ret = array();

        // For the AD the '\,' should be replaced by the '\\,' in the search filter.
        $filter = preg_replace('/\\\,/', '\\\\\,', $filter);

        #set_error_handler(array('LDAPInterface', 'void_error_handler'));
        $x = ldap_search($this->connection, $base_dn, $filter, $attributes);
        restore_error_handler();

        if ($x && ldap_count_entries($this->connection, $x)) {
            $ret = ldap_get_entries($this->connection, $x);
        }
        return $ret;
    }

    // WARNING! WARNING! WARNING!
    // This function returns its entries with lowercase attribute names.
    // Don't blame me, blame PHP's own ldap_get_entries()
    function retrieveAttributes($dn)
    {
        set_error_handler(array('forms.datalynk.ca\Quarantine\LDAPInterface', 'void_error_handler'));
        $result = ldap_read($this->connection, $dn, 'objectClass=*');
        $entries = ldap_get_entries($this->connection, $result);
        restore_error_handler();

        return call_user_func($this->attr_filter, $this->sid, $entries[0]);
    }

    function retrieveAttribute($dn, $attrname)
    {
        $entries = $this->retrieveAttributes($dn);
        return isset($entries[strtolower($attrname)]) ? $entries[strtolower($attrname)][0] : NULL;
    }

    function retrieveMultiAttribute($dn, $attrname)
    {
        $entries = $this->retrieveAttributes($dn);

        $result = array();
        $retrieved = $entries[strtolower($attrname)];
        $retrieved = $retrieved ? $retrieved : array();
        foreach ($retrieved as $key => $value) {
            if ($key !== 'count') {
                $result[] = $value;
            }
        }
        return $result;
    }

    function writeAttributes($dn, $attributes)
    {
        foreach ($attributes as $key => $cur_val) {
            if ($cur_val == '') {
                unset($attributes[$key]);
                $old_value = $this->retrieveAttribute($dn, $key);
                if (isset($old_value)) {
                    ldap_mod_del($this->connection, $dn, array($key => $old_value));
                }
            }
            if (is_array($cur_val)) {
                foreach ($cur_val as $mv_key => $mv_cur_val) {
                    if ($mv_cur_val == '') {
                        unset($attributes[$key][$mv_key]);
                    } else {
                        $attributes[$key][$mv_key] = $mv_cur_val;
                    }
                }
            }
        }

        return ldap_modify($this->connection, $dn, $attributes);
    }

    function create_entry($dn, $attributes)
    {
        set_error_handler(array('forms.datalynk.ca\Quarantine\LDAPInterface', 'void_error_handler'));
        $ret = ldap_add($this->connection, $dn, $attributes);
        restore_error_handler();

        return $ret;
    }

    function rename_entry($dn, $newrdn, $newparent, $deleteoldrdn)
    {
        set_error_handler(array('forms.datalynk.ca\Quarantine\LDAPInterface', 'void_error_handler'));
        $ret = ldap_rename($this->connection, $dn, $newrdn, $newparent, $deleteoldrdn);
        restore_error_handler();

        return $ret;
    }

    function delete_entry($dn)
    {
        set_error_handler(array('forms.datalynk.ca\Quarantine\LDAPInterface', 'void_error_handler'));
        $ret = ldap_delete($this->connection, $dn);
        restore_error_handler();

        return $ret;
    }

    // This function is used by other modules to delete attributes once they are
    // moved to profiles cause ldap_mod_del does not delete facsimileTelephoneNumber if
    // attribute value to delete is passed to the function.
    // OpenLDAP as per RFC 2252 doesn't have equality matching for facsimileTelephoneNumber
    // http://bugs.php.net/bug.php?id=7168
    function deleteAttribute($dn, $attribute)
    {
        ldap_mod_del($this->connection, $dn, array($attribute => array()));
    }

    // This should be static, but that's not supported in PHP4
    // Made it static and introduced a requirenment of php version 5.0.
    static function void_error_handler($p1, $p2, $p3, $p4, $p5)
    {
        // Do nothing
    }

    /**
     * Used to generate a random salt for crypt-style passwords. Salt strings are used
     * to make pre-built hash cracking dictionaries difficult to use as the hash algorithm uses
     * not only the user's password but also a randomly generated string. The string is
     * stored as the first N characters of the hash for reference of hashing algorithms later.
     *
     * @param int The length of the salt string to generate.
     * @return string The generated salt string.
     */
    function random_salt($length)
    {
        $possible = '0123456789' . 'abcdefghijklmnopqrstuvwxyz' . 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . './';
        $str = '';
        mt_srand((double)microtime() * 1000000);

        while (strlen($str) < $length)
            $str .= substr($possible, (rand() % strlen($possible)), 1);

        return $str;
    }

    /**
     * Given a clear-text password and a hash, this function determines if the clear-text password
     * is the password that was used to generate the hash. This is handy to verify a user's password
     * when all that is given is the hash and a "guess".
     * @param String The hash.
     * @param String The password in clear text to test.
     * @return Boolean True if the clear password matches the hash, and false otherwise.
     */
    function password_check($cryptedpassword, $plainpassword)
    {

        if (preg_match('/{([^}]+)}(.*)/', $cryptedpassword, $matches)) {
            $cryptedpassword = $matches[2];
            $cypher = strtolower($matches[1]);

        } else {
            $cypher = null;
        }

        switch ($cypher) {
            # SSHA crypted passwords
            case 'ssha':
                # Check php hash support before using it
                if (function_exists('hash')) {
                    $hash = base64_decode($cryptedpassword);

                    # OpenLDAP uses a 4 byte salt, SunDS uses an 8 byte salt - both from char 20.
                    $salt = substr($hash, 20);
                    $new_hash = base64_encode(hash('sha1', $plainpassword . $salt, true) . $salt);
                    if (strcmp($cryptedpassword, $new_hash) == 0) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    throw new LDAPException('Your PHP install does not have the hash() function. Cannot do SHA hashes.');
                }
                break;

            # Salted MD5
            case 'smd5':
                # Check php hash support before using it
                if (function_exists('hash')) {
                    $hash = base64_decode($cryptedpassword);
                    $salt = substr($hash, -4);
                    $new_hash = base64_encode(hash('md5', $plainpassword . $salt, true) . $salt);

                    if (strcmp($cryptedpassword, $new_hash) == 0) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    throw new LDAPException('Your PHP install does not have the hash() function. Cannot do SHA hashes.');
                }

                break;

            # SHA crypted passwords
            case 'sha':
                if (strcasecmp($this->password_hash($plainpassword, 'sha'), '{SHA}' . $cryptedpassword) == 0) {
                    return true;
                } else {
                    return false;
                }
                break;

            # MD5 crypted passwords
            case 'md5':
                if (strcasecmp($this->password_hash($plainpassword, 'md5'), '{MD5}' . $cryptedpassword) == 0) {
                    return true;
                } else {
                    return false;
                }

                break;

            # Crypt passwords
            case 'crypt':
                # Check if it's blowfish crypt
                if (preg_match('/^\\$2+/', $cryptedpassword)) {

                    # Make sure that web server supports blowfish crypt
                    if (!defined('CRYPT_BLOWFISH') || CRYPT_BLOWFISH == 0) {
                        throw new LDAPException('Your system crypt library does not support blowfish encryption.');
                    }

                    list($version, $rounds, $salt_hash) = explode('$', $cryptedpassword);

                    if (crypt($plainpassword, '$' . $version . '$' . $rounds . '$' . $salt_hash) == $cryptedpassword) {
                        return true;
                    } else {
                        return false;
                    }

                } else if (strstr($cryptedpassword, '$1$')) { # Check if it's an crypted md5

                    # Make sure that web server supports md5 crypt
                    if (!defined('CRYPT_MD5') || CRYPT_MD5 == 0) {
                        throw new LDAPException('Your system crypt library does not support md5crypt encryption.');
                    }
                    Console::log($cryptedpassword);
                    list($type, $salt, $hash) = explode('$', $cryptedpassword);
                    Console::log("$type, $salt, $hash");
                    Console::log(crypt($plainpassword, '$1$' . $salt));
                    if (crypt($plainpassword, '$1$' . $salt) == $cryptedpassword) {
                        return true;
                    } else {
                        return false;
                    }

                } else if (strstr($cryptedpassword, '_')) { # Check if it's extended des crypt

                    # Make sure that web server supports ext_des
                    if (!defined('CRYPT_EXT_DES') || CRYPT_EXT_DES == 0) {
                        throw new LDAPException('Your system crypt library does not support extended DES encryption.');
                    }

                    if (crypt($plainpassword, $cryptedpassword) == $cryptedpassword) {
                        return true;
                    } else {
                        return false;
                    }

                } else { # Password is plain crypt
                    if (crypt($plainpassword, $cryptedpassword) == $cryptedpassword) {
                        return true;
                    } else {
                        return false;
                    }
                }

                break;

            # No crypt is given assume plaintext passwords are used
            default:
                if ($plainpassword == $cryptedpassword) {
                    return true;
                } else {
                    return false;
                }
        }
    }

    /**
     * Hashes a password and returns the hash based on the specified enc_type.
     *
     * @param string The password to hash in clear text.
     * @param string Standard LDAP encryption type which must be one of
     *        crypt, ext_des, md5crypt, blowfish, md5, sha, smd5, ssha, or clear.
     * @return string The hashed password.
     */
    function password_hash($password_clear, $enc_type)
    {

        $enc_type = strtolower($enc_type);

        switch ($enc_type) {
            case 'crypt':
                $new_value = sprintf('{CRYPT}%s', crypt($password_clear, $this->random_salt(2)));
                break;
            case 'ext_des':
                # Extended des crypt. see OpenBSD crypt man page.
                if (!defined('CRYPT_EXT_DES') || CRYPT_EXT_DES == 0) {
                    throw new LDAPException(('Your system crypt library does not support extended DES encryption.'));
                }
                $new_value = sprintf('{CRYPT}%s', crypt($password_clear, '_' . $this->random_salt(8)));
                break;
            case 'md5crypt':
                if (!defined('CRYPT_MD5') || CRYPT_MD5 == 0) {
                    throw new LDAPException('Your system crypt library does not support md5crypt encryption.');
                }
                $new_value = sprintf('{CRYPT}%s', crypt($password_clear, '$1$' . $this->random_salt(9)));
                break;
            case 'blowfish':
                if (!defined('CRYPT_BLOWFISH') || CRYPT_BLOWFISH == 0) {
                    throw new LDAPException('Your system crypt library does not support blowfish encryption.');
                }
                # Hardcoded to second blowfish version and set number of rounds
                $new_value = sprintf('{CRYPT}%s', crypt($password_clear, '$2a$12$' . $this->random_salt(13)));
                break;

            case 'md5':
                $new_value = sprintf('{MD5}%s', base64_encode(pack('H*', md5($password_clear))));
                break;
            case 'sha':
                # Use php 4.3.0+ sha1 function, if it is available.
                if (function_exists('sha1')) {
                    $new_value = sprintf('{SHA}%s', base64_encode(pack('H*', sha1($password_clear))));
                } else if (function_exists('hash')) {
                    $new_value = sprintf('{SHA}%s', base64_encode(hash('sha1', $password_clear, true)));
                } else {
                    throw new LDAPException('Your PHP install does not have the hash() function. Cannot do SHA hashes.');
                }
                break;
            case 'ssha':
                if (function_exists('hash')) {
                    mt_srand((double)microtime() * 1000000);
                    $salt = $this->hash_keygen_s2k("sha1", $password_clear, substr(pack('h*', md5(mt_rand())), 0, 8), 4);
                    $new_value = sprintf('{SSHA}%s', base64_encode(hash("sha1", $password_clear . $salt, true) . $salt));
                } else {
                    throw new LDAPException('Your PHP install does not have the hash() function. Cannot do S2K hashes.');
                }
                break;
            case 'smd5':
                if (function_exists('hash')) {
                    mt_srand((double)microtime() * 1000000);
                    $salt = $this->hash_keygen_s2k("md5", $password_clear, substr(pack('h*', md5(mt_rand())), 0, 8), 4);
                    $new_value = sprintf('{SMD5}%s', base64_encode(hash("md5", $password_clear . $salt, true) . $salt));
                } else {
                    throw new LDAPException('Your PHP install does not have the hash() function. Cannot do S2K hashes.');
                }
                break;
            case 'clear':
            default:
                $new_value = $password_clear;
        }
        return $new_value;
    }

    /**
     * Trying to duplicate mhash_keygen_s2k function
     * @param  $hash
     * @param  $pass
     * @param  $salt
     * @param  $bytes
     * @return
     */
    function hash_keygen_s2k($hash, $pass, $salt, $bytes)
    {
        if ($hash == "md5") {
            return substr(pack("H*", md5($salt . $pass)), 0, $bytes);
        } else if ($hash == 'sha1') {
            return substr(pack("H*", sha1($salt . $pass)), 0, $bytes);
        }
    }
}