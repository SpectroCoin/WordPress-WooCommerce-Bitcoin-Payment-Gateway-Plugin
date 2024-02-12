<?php

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class SpectroCoin_FormattingUtil
{

	/**
	 * Formats currency amount with '0.0#######' format
	 * @param $amount
	 * @return string
	 */
	protected static function spectrocoin_format_currency($amount)
	{
		$decimals = strlen(substr(strrchr(rtrim(sprintf('%.8f', $amount), '0'), "."), 1));
		$decimals = $decimals < 1 ? 1 : $decimals;
		return number_format($amount, $decimals, '.', '');
	}

	/**
	 * Encrypts the given data using AES-128-CBC encryption algorithm.
	 *
	 * This method takes a plaintext data string and a secret key, then uses
	 * the AES-128-CBC cipher to encrypt the data. The initialization vector (IV)
	 * is generated randomly for each encryption and is appended to the encrypted
	 * data, separated by '::'. The resulting string is then encoded in base64.
	 *
	 * @param string $data The plaintext data to be encrypted.
	 * @param string $key The secret key used for encryption. Must be 16 bytes long (128 bits).
	 * @return string The encrypted data encoded in base64, with the IV appended.
	 * @throws Exception If an error occurs during encryption or IV generation.
	 */
	protected static function encrypt($data, $key) {
		$ivLength = openssl_cipher_iv_length($cipher = "AES-128-CBC");
		$iv = openssl_random_pseudo_bytes($ivLength);
		$encryptedData = openssl_encrypt($data, $cipher, $key, 0, $iv);
		return base64_encode($encryptedData . '::' . $iv);
	}

	/**
	 * Decrypts the given data using AES-128-CBC encryption algorithm.
	 *
	 * This method takes an encrypted data string, which was encrypted by the
	 * `encrypt` method, and a secret key to decrypt the data. The method
	 * extracts the initialization vector (IV) from the encrypted string, then
	 * uses it along with the key to decrypt the data using AES-128-CBC cipher.
	 *
	 * @param string $data The encrypted data in base64 encoding, with the IV appended.
	 * @param string $key The secret key used for decryption. Must be the same key used for encryption.
	 * @return string The decrypted plaintext data.
	 * @throws Exception If an error occurs during decryption or if the data format is invalid.
	 */
	protected static function decrypt($data, $key) {
		list($encryptedData, $iv) = explode('::', base64_decode($data), 2);
		return openssl_decrypt($encryptedData, "AES-128-CBC", $key, 0, $iv);
	}


	
}