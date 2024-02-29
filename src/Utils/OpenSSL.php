<?php

namespace Legacy\Jot\Utils;

use Legacy\Jot\Configs;


/**
 * Class OpenSSL
 *
 * Singleton class
 */
class OpenSSL
{

    /**
     * Static instance of the
     * OpenSSL object
     * @var //OpenSSL
     */
    static private $instance;

    private $privateKeyPath;
    private $publicKeyPath;
    private $passPhrase;

    /**
     * Get the instance of the OpenSSL class
     * @return // OpenSSL
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }

    /**
     *
     * @return // OpenSSL
     */
    private function __construct()
    {
        $this->privateKeyPath = Configs::PRIVATE_KEY_PATH;
        $this->publicKeyPath = Configs::PUBLIC_KEY_PATH;
        $this->passPhrase = Configs::PASS_PHRASE;
    }

    public function encryptData($data)
    {
        $pubKey = openssl_pkey_get_public('file:///' . $this->publicKeyPath);
        openssl_public_encrypt($data, $encryptedData, $pubKey);
        return $encryptedData;
    }

    public function decryptData($encryptedData)
    {
        $privateKey = openssl_pkey_get_private('file:///' . $this->privateKeyPath, $this->passPhrase);
        openssl_private_decrypt($encryptedData, $data, $privateKey);
        return $data;
    }

}
