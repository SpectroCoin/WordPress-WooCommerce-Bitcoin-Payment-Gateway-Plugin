<?php

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class SpectroCoin_Utilities
{
	/**
	 * Formats currency amount with '0.0#######' format
	 * @param $amount
	 * @return string
	 */
	public static function spectrocoinFormatCurrency($amount)
	{
		$decimals = strlen(substr(strrchr(rtrim(sprintf('%.8f', $amount), '0'), "."), 1));
		$decimals = $decimals < 1 ? 1 : $decimals;
		return number_format($amount, $decimals, '.', '');
	}

	/**
	 * Encrypts the given data using the given encryption key.
	 * @param string $data The data to encrypt.
	 * @param string $encryption_key The encryption key to use.
	 * @return string The encrypted data.
	 */
	public static function spectrocoinEncryptAuthData($data, $encryption_key) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv);
        return base64_encode($encryptedData . '::' . $iv); // Store $iv with encrypted data
    }

	/**
	 * Decrypts the given encrypted data using the given encryption key.
	 * @param string $encryptedDataWithIv The encrypted data to decrypt.
	 * @param string $encryption_key The encryption key to use.
	 * @return string The decrypted data.
	 */
	public static function spectrocoinDecryptAuthData($encryptedDataWithIv, $encryption_key) {
        list($encryptedData, $iv) = explode('::', base64_decode($encryptedDataWithIv), 2);
        return openssl_decrypt($encryptedData, 'aes-256-cbc', $encryption_key, 0, $iv);
    }

	/**
	 * Generates a random 128-bit secret key for AES-128-CBC encryption.
	 * @return string The generated secret key encoded in base64.
	 */
	public static function spectrocoinGenerateEncryptionKey() {
		$key = openssl_random_pseudo_bytes(32); // 256 bits
		return base64_encode($key); // Encode to base64 for easy storage
	}	
}