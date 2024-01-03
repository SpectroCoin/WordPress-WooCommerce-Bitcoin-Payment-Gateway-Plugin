<?php

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class SpectroCoin_ValidationUtil
{
    /**
     * Validates private key string using openssl_pkey_get_private function.
     * @param string $privateKey The string to validate.
     * @return bool Returns true if the string is a valid private key, false otherwise.
     */
    static function spectrocoin_validate_private_key($privateKey) {
        $res = openssl_pkey_get_private($privateKey);
    
        if ($res === false) {
            return false;
        }
        

        return true;
    }
	
}