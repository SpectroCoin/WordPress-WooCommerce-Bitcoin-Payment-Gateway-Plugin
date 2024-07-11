<?php

namespace SpectroCoin\SCMerchantClient;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class Utils
{
	/**
	 * Formats currency amount with '0.0#######' format
	 * @param $amount
	 * @return string
	 */
	public static function formatCurrency($amount)
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
	public static function encryptAuthData($data, $encryption_key) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted_data = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv);
        return base64_encode($encrypted_data . '::' . $iv); // Store $iv with encrypted data
    }

	/**
	 * Decrypts the given encrypted data using the given encryption key.
	 * @param string $encrypted_data_with_iv The encrypted data to decrypt.
	 * @param string $encryption_key The encryption key to use.
	 * @return string The decrypted data.
	 */
	public static function decryptAuthData($encrypted_data_with_iv, $encryption_key) {
        list($encrypted_data, $iv) = explode('::', base64_decode($encrypted_data_with_iv), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
    }

	/**
	 * Generates a random 128-bit secret key for AES-128-CBC encryption.
	 * @return string The generated secret key encoded in base64.
	 */
	public static function generateEncryptionKey() {
		$key = openssl_random_pseudo_bytes(32); // 256 bits
		return base64_encode($key); // Encode to base64 for easy storage
	}	
}