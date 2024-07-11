<?php

namespace SpectroCoin\SCMerchantClient\Services;

use SpectroCoin\SCMerchantClient\Config;
use SpectroCoin\SCMerchantClient\Utils;
use SpectroCoin\SCMerchantClient\Http\HttpClient;
use SpectroCoin\SCMerchantClient\Exceptions\ApiError;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class TokenManager
{   
    private $client_id;
    private $client_secret;
    private $encryption_key;
    private $access_token_transient_key;
    private $http_client;

    function __construct($client_id, $client_secret){
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;

        $this->encryption_key = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . NONCE_KEY);
        $this->access_token_transient_key = "spectrocoin_transient_key";

        $this->http_client = new Client();
    }

    /**
	 * Retrieves the current access token data, checking if it's still valid based on its expiration time. If the token is expired or not present, it attempts to refresh the token.
	 * The function uses WordPress transients for token storage, providing a reliable and persistent storage mechanism within WordPress environments.
	 *
	 * @return array|null Returns the access token data array if the token is valid or has been refreshed successfully. Returns null if the token is not present and cannot be refreshed.
	*/
    public function getAccessTokenData(){
        $current_time = time();
		$encrypted_access_token_data = get_transient($this->access_token_transient_key);
        if ($encrypted_access_token_data) {
			$access_token_data = json_decode(Utils::DecryptAuthData($encrypted_access_token_data, $this->encryption_key), true);
			if ($this->IsTokenValid($access_token_data, $current_time)) {
				return $access_token_data;
			}
		}
        return $this->RefreshAccessToken($current_time);
    }

    /**
	 * Refreshes the access token by making a request to the SpectroCoin authorization server using client credentials. If successful, it updates the stored token data in WordPress transients.
	 * This method ensures that the application always has a valid token for authentication with SpectroCoin services.
	 *
	 * @param int $current_time The current timestamp, used to calculate the new expiration time for the refreshed token.
	 * @return array|null Returns the new access token data if the refresh operation is successful. Returns null if the operation fails due to a network error or invalid response from the server.
	 * @throws GuzzleException Thrown if there is an error in the HTTP request to the SpectroCoin authorization server.
	*/
    public function refreshAccessToken($current_time) {
		try {
			$response = $this->http_client->post(Config::AUTH_URL, [
				'form_params' => [
					'grant_type' => 'client_credentials',
					'client_id' => $this->client_id,
					'client_secret' => $this->client_secret,
				],
			]);
	
			$access_token_data = json_decode($response->getBody(), true);
			if (!isset($access_token_data['access_token'], $access_token_data['expires_in'])) {
				return new ApiError('Invalid access token response', 'No valid response received.');
			}
	
			delete_transient($this->access_token_transient_key);
	
			$access_token_data['expires_at'] = $current_time + $access_token_data['expires_in'];
			$encrypted_access_token_data = Utils::EncryptAuthData(json_encode($access_token_data), $this->encryption_key);
	
			set_transient($this->access_token_transient_key, $encrypted_access_token_data, $access_token_data['expires_in']);
	
			return $access_token_data;

		} catch (GuzzleException $e) {
			return new ApiError('Failed to refresh access token', $e->getMessage());
		}
	}


    /**
	 * Checks if the current access token is valid by comparing the current time against the token's expiration time. A buffer can be applied to ensure the token is refreshed before it actually expires.
	 *
	 * @param int $current_time The current timestamp, typically obtained using `time()`.
	 * @return bool Returns true if the token is valid (i.e., not expired), false otherwise.
	*/
    private function isTokenValid($access_token_data, $current_time)
    {
        return isset($access_token_data['expires_at']) && $current_time < $access_token_data['expires_at'];
    }


}