<?php declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient;
// @codeCoverageIgnoreStart
if (!defined('ABSPATH')) {
    die('Access denied.');
}
// @codeCoverageIgnoreEnd
class Utils
{
    /**
     * Get the plugin folder name.
     *
     * @return string The plugin folder name.
     */
    public static function getPluginFolderName(string $filePath = __FILE__): string
    {
        // Normalize Windows path separators to forward slashes.
        $filePath = str_replace('\\', '/', $filePath);
        $dir = dirname($filePath);
        if ($dir === '/' || $dir === '.' || $dir === '') {
            return pathinfo($filePath, PATHINFO_FILENAME);
        }
        return basename($dir);
    }
    
    /**
     * Formats currency amount with '0.0#######' format.
     *
     * @param mixed $amount The amount to format.
     * @return string The formatted currency amount.
     */
    public static function formatCurrency($amount): string
    {   
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('The provided amount must be numeric.');
        }
		$decimals = strlen(substr(strrchr(rtrim(sprintf('%.8f', $amount), '0'), "."), 1));
		$decimals = $decimals < 1 ? 1 : $decimals;
		return number_format((float)$amount, $decimals, '.', '');
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
        return base64_encode($encryptedData . '::' . $iv);
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
        $decrypted = openssl_decrypt($encryptedData, 'aes-256-cbc', $encryptionKey, 0, $iv);
        if ($decrypted === false){
            throw new \RuntimeException('Decryption failed: Invalid encryption key or corrupted data.');
        }
        return $decrypted;
    }

    /**
     * Sanitize URL values.
     *
     * @param mixed $value
     * @return string|null
     */
    public static function sanitizeUrl($value): ?string
    {
        if ($value === null) {
            return null;
        }
    
        $value = (string)$value;
        
        $value = trim($value);
        
        if ($value === '') {
            return '';
        }
        
        $value = str_replace(' ', '', $value);
        
        $value = preg_replace('/[<>^]/', '', $value);
        
        return $value;
    }
    
    

    /**
	 * Generate random string
	 * @param int $length
	 * @return string
	 */
    public static function generateRandomStr($length) : string
    {
        if (!is_int($length) || $length < 0) {
            throw new \InvalidArgumentException("Invalid length parameter. Must be a non-negative integer.");
        }
        
        // If length is 0, return an empty string.
        if ($length === 0) {
            return "";
        }
        
        $random_str = substr(md5((string)rand(1, pow(2, 16))), 0, $length);
        return $random_str;
    }

    /**
     * 1. Checks for invalid UTF-8 characters.
     * 2. Converts single less-than characters (<) to entities.
     * 3. Strips all HTML and PHP tags.
     * 4. Removes line breaks, tabs, and extra whitespace.
     * 5. Strips percent-encoded characters.
     * 6. Removes any remaining invalid UTF-8 characters.
     *
     * @param string $str The text to be sanitized.
     * @return string The sanitized text.
     */
    public static function sanitize_text_field(string $str): string {
        $str = mb_check_encoding($str, 'UTF-8') ? $str : '';
        $str = preg_replace('/<(?=[^a-zA-Z\/\?\!\%])/u', '&lt;', $str);
        $str = strip_tags($str);
        $str = preg_replace('/[\r\n\t ]+/', ' ', $str);
        $str = trim($str);
        $str = preg_replace('/%[a-f0-9]{2}/i', '', $str);
        $str = preg_replace('/[^\x20-\x7E]/', '', $str);
        return $str;
    }
    
}
?>
