<?php

declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient;

if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Utils
{
    /**
     * Formats currency amount with '0.0#######' format.
     *
     * @param float $amount The amount to format.
     * @return float The formatted currency amount.
     */
    public static function formatCurrency(float $amount): float
    {
        $decimals = strlen(substr(strrchr(rtrim(sprintf('%.8f', $amount), '0'), "."), 1));
        $decimals = $decimals < 1 ? 1 : $decimals;
        return (float)number_format($amount, $decimals, '.', '');
    }


    /**
     * Encrypts the given data using the given encryption key.
     *
     * @param string $data The data to encrypt.
     * @param string $encryptionKey The encryption key to use.
     * @return string The encrypted data.
     */
    public static function encryptAuthData(string $data, string $encryptionKey): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $encryptionKey, 0, $iv);
        return base64_encode($encryptedData . '::' . $iv); // Store $iv with encrypted data
    }

    /**
     * Decrypts the given encrypted data using the given encryption key.
     *
     * @param string $encryptedDataWithIv The encrypted data to decrypt.
     * @param string $encryptionKey The encryption key to use.
     * @return string The decrypted data.
     */
    public static function decryptAuthData(string $encryptedDataWithIv, string $encryptionKey): string
    {
        list($encryptedData, $iv) = explode('::', base64_decode($encryptedDataWithIv), 2);
        return openssl_decrypt($encryptedData, 'aes-256-cbc', $encryptionKey, 0, $iv);
    }

    /**
     * Generates a random 128-bit secret key for AES-128-CBC encryption.
     *
     * @return string The generated secret key encoded in base64.
     */
    public static function generateEncryptionKey(): string
    {
        $key = openssl_random_pseudo_bytes(32); // 256 bits
        return base64_encode($key); // Encode to base64 for easy storage
    }
}
?>
