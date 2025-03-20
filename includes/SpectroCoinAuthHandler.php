<?php declare (strict_types = 1);

namespace SpectroCoin\Includes;

use SpectroCoin\SCMerchantClient\Utils;

// @codeCoverageIgnoreStart
if (!defined('ABSPATH')) {
    die('Access denied.');
}
// @codeCoverageIgnoreEnd

class SpectroCoinAuthHandler
{
    private string $encryptionKey;
    private string $accessTokenTransientKey;

    public function __construct(){
        $this->encryptionKey = $this->generateEncryptionKey();
        $this->accessTokenTransientKey = "spectrocoin_transient_key";
    }

    /**
     * Generate encryption key using WordPress authentication keys
     * 
     * @return string
     */
    private function generateEncryptionKey(): string{
        $this->encryptionKey = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . NONCE_KEY);
        return $this->encryptionKey;
    }

    /**
     * Get saved auth token from Wordpress transient
     */
    public function getSavedAuthToken(): string{
        return get_transient($this->accessTokenTransientKey);
    }

    public function saveAuthToken($accessTokenData): void{
        delete_transient($this->accessTokenTransientKey);
        $currentTime = time();
        $accessTokenData['expires_at'] = $currentTime + $accessTokenData['expires_in'];
        $encryptedAccessTokenData = Utils::EncryptAuthData(json_encode($accessTokenData), $this->encryptionKey);
        set_transient($this->accessTokenTransientKey, $encryptedAccessTokenData, $accessTokenData['expires_in']);
    }

    /**
     * Get the value of encryptionKey
     */ 
    public function getEncryptionKey()
    {
        return $this->encryptionKey;
    }

    /**
     * Get the value of accessTokenTransientKey
     */ 
    public function getAccessTokenTransientKey()
    {
        return $this->accessTokenTransientKey;
    }
}