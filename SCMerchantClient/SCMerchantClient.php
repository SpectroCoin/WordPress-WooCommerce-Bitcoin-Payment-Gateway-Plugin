<?php

if (!defined('ABSPATH')) {
	die('Access denied.');
}

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

include_once('components/SpectroCoin_Utilities.php');
include_once('data/SpectroCoin_ApiError.php');
include_once('data/SpectroCoin_OrderStatusEnum.php');
include_once('data/SpectroCoin_OrderCallback.php');
include_once('messages/SpectroCoin_CreateOrderRequest.php');
include_once('messages/SpectroCoin_CreateOrderResponse.php');

require_once __DIR__ . '/../vendor/autoload.php';

class SCMerchantClient
{

	private $merchant_api_url;
	private $project_id;
	private $client_id;
	private $client_secret;
	private $auth_url;
	
	private $access_token_data;
	private $encryption_key;
	private $access_token_transient_key;
	private $public_spectrocoin_cert_location;
	protected $guzzle_client;

	/**
	 * @param $merchant_api_url
	 * @param $project_id
	 * @param $client_id
	 * @param $client_secret
	 * @param $auth_url
	 */
	function __construct($merchant_api_url, $project_id, $client_id, $client_secret, $auth_url)
	{
		$this->merchant_api_url = $merchant_api_url;
		$this->project_id = $project_id;
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->auth_url = $auth_url;

		$this->guzzle_client = new Client();
		$this->encryption_key = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . NONCE_KEY);
		$this->access_token_transient_key = "spectrocoin_transient_key";
		$this->public_spectrocoin_cert_location = "https://test.spectrocoin.com/public.pem"; //PROD:https://spectrocoin.com/files/merchant.public.pem
	}

	/**
	 * Creates a new order with SpectroCoin and returns the order details or an error.
	 * This method first obtains an access token, then uses it to create an order with the provided request parameters.
	 * If successful, it returns a `SpectroCoin_CreateOrderResponse` object with the order details.
	 * In case of failure, it returns a `SpectroCoin_ApiError` object with the error details.
	 *
	 * @param SpectroCoin_CreateOrderRequest $request The order request parameters.
	 * @return SpectroCoin_ApiError|SpectroCoin_CreateOrderResponse The response object with order details or an error object.
	 * @throws GuzzleException If there's an error in the HTTP request.
	 */
	public function spectrocoinCreateOrder(SpectroCoin_CreateOrderRequest $request)
	{
		$this->access_token_data = $this->spectrocoinGetAccessTokenData();
		if (!$this->access_token_data) {
			return new SpectroCoin_ApiError('AuthError', 'Failed to obtain or refresh access token');
		}

		$payload = [
			"orderId" => $request->getOrderId(),
			"projectId" => $this->project_id,
			"description" => $request->getDescription(),
			"payAmount" => $request->getPayAmount(),
			"payCurrencyCode" => $request->getPayCurrencyCode(),
			"receiveAmount" => $request->getReceiveAmount(),
			"receiveCurrencyCode" => $request->getReceiveCurrencyCode(),
			'callbackUrl' => 'http://localhost.com',
			'successUrl' => 'http://localhost.com',
			'failureUrl' => 'http://localhost.com'
		];

		$sanitized_payload = $this->spectrocoinSanitizeOrderPayload($payload);
		if (!$this->spectrocoinValidateOrderPayload($sanitized_payload)) {
			return new SpectroCoin_ApiError(-1, 'Invalid order creation payload');
		}

		$json_payload = json_encode($sanitized_payload);

		try {
			$response = $this->guzzle_client->request('POST', $this->merchant_api_url . '/merchants/orders/create', [
				RequestOptions::HEADERS => [
					'Authorization' => 'Bearer ' . $this->access_token_data['access_token'],
					'Content-Type' => 'application/json'
				],
				RequestOptions::BODY => $json_payload
			]);

			$body = json_decode($response->getBody()->getContents(), true);

			return new SpectroCoin_CreateOrderResponse(
					$body['depositAddress'],
					$body['memo'],
					$body['orderId'],
					$body['payAmount'],
					$body['payCurrencyCode'],
					$body['preOrderId'],
					$body['receiveAmount'],
					$body['receiveCurrencyCode'],
					$body['redirectUrl'],
					$body['validUntil']
			);

		} catch (RequestException $e) {
			if ($e->getResponse() && $e->getResponse()->getStatusCode() == 403) {
				$this->access_token_data = $this->spectrocoinRefreshAccessToken(time());

				if (!$this->access_token_data) {
					return new SpectroCoin_ApiError('AuthError', 'Failed to refresh access token');
				}

				return $this->spectrocoinRetryCreateOrder($json_payload);
			} else {
				return new SpectroCoin_ApiError($e->getCode(), $e->getMessage());
			}
		} catch (GuzzleException $e) {
			return new SpectroCoin_ApiError($e->getCode(), $e->getMessage());
		}

		return new SpectroCoin_ApiError('UnknownError', 'An unknown error occurred during order creation');
	}

		/**
	 * Retries the order creation request with a refreshed token.
	 *
	 * @param string $json_payload The JSON-encoded payload for the order creation request.
	 * @return SpectroCoin_ApiError|SpectroCoin_CreateOrderResponse The response object with order details or an error object.
	 */
	private function spectrocoinRetryCreateOrder($json_payload)
	{
		try {
			$response = $this->guzzle_client->request('POST', $this->merchant_api_url . '/merchants/orders/create', [
				RequestOptions::HEADERS => [
					'Authorization' => 'Bearer ' . $this->access_token_data['access_token'],
					'Content-Type' => 'application/json'
				],
				RequestOptions::BODY => $json_payload
			]);

			$body = json_decode($response->getBody()->getContents(), true);

			return new SpectroCoin_CreateOrderResponse(
				$body['depositAddress'],
				$body['memo'],
				$body['orderId'],
				$body['payAmount'],
				$body['payCurrencyCode'],
				$body['preOrderId'],
				$body['receiveAmount'],
				$body['receiveCurrencyCode'],
				$body['redirectUrl'],
				$body['validUntil']
			);

		} catch (GuzzleException $e) {
			return new SpectroCoin_ApiError($e->getCode(), $e->getMessage());
		}
	}
	
	/**
	 * Retrieves the current access token data, checking if it's still valid based on its expiration time. If the token is expired or not present, it attempts to refresh the token.
	 * The function uses WordPress transients for token storage, providing a reliable and persistent storage mechanism within WordPress environments.
	 *
	 * @return array|null Returns the access token data array if the token is valid or has been refreshed successfully. Returns null if the token is not present and cannot be refreshed.
	 */
	private function spectrocoinGetAccessTokenData() {
        $current_time = time();
		$encrypted_access_token_data = get_transient($this->access_token_transient_key);
		if ($encrypted_access_token_data) {
			$access_token_data = json_decode(SpectroCoin_Utilities::spectrocoinDecryptAuthData($encrypted_access_token_data, $this->encryption_key), true);
			$this->access_token_data = $access_token_data;
			if ($this->spectrocoinIsTokenValid($current_time)) {
				return $this->access_token_data;
			}
		}
        return $this->spectrocoinRefreshAccessToken($current_time);
    }

	/**
	 * Refreshes the access token by making a request to the SpectroCoin authorization server using client credentials. If successful, it updates the stored token data in WordPress transients.
	 * This method ensures that the application always has a valid token for authentication with SpectroCoin services.
	 *
	 * @param int $current_time The current timestamp, used to calculate the new expiration time for the refreshed token.
	 * @return array|null Returns the new access token data if the refresh operation is successful. Returns null if the operation fails due to a network error or invalid response from the server.
	 * @throws GuzzleException Thrown if there is an error in the HTTP request to the SpectroCoin authorization server.
	 */
    private function spectrocoinRefreshAccessToken($current_time) {
		try {
			$response = $this->guzzle_client->post($this->auth_url, [
				'form_params' => [
					'grant_type' => 'client_credentials',
					'client_id' => $this->client_id,
					'client_secret' => $this->client_secret,
				],
			]);
	
			$data = json_decode($response->getBody(), true);
			if (!isset($data['access_token'], $data['expires_in'])) {
				return new SpectroCoin_ApiError('Invalid access token response', 'No valid response received.');
			}
	
			delete_transient($this->access_token_transient_key);
	
			$data['expires_at'] = $current_time + $data['expires_in'];
			$encrypted_access_token_data = SpectroCoin_Utilities::spectrocoinEncryptAuthData(json_encode($data), $this->encryption_key);
	
			set_transient($this->access_token_transient_key, $encrypted_access_token_data, $data['expires_in']);
	
			$this->access_token_data = $data;
			return $this->access_token_data;
		} catch (GuzzleException $e) {
			return new SpectroCoin_ApiError('Failed to refresh access token', $e->getMessage());
		}
	}


	/**
	 * Checks if the current access token is valid by comparing the current time against the token's expiration time. A buffer can be applied to ensure the token is refreshed before it actually expires.
	 *
	 * @param int $current_time The current timestamp, typically obtained using `time()`.
	 * @return bool Returns true if the token is valid (i.e., not expired), false otherwise.
	 */
	private function spectrocoinIsTokenValid($current_time) {
		return isset($this->access_token_data['expires_at']) && $current_time < $this->access_token_data['expires_at'];
	}

	// --------------- VALIDATION AND SANITIZATION BEFORE REQUEST -----------------

	/**
     * Payload data sanitization for create order
     * @param array $payload
     * @return array
     */
    private function spectrocoinSanitizeOrderPayload($payload) {
		$sanitized_payload = [
			'orderId' => sanitize_text_field($payload['orderId']),
			'projectId' => sanitize_text_field($payload['projectId']), // Assuming you need to sanitize this as well
			'description' => sanitize_text_field($payload['description']),
			'payAmount' => filter_var($payload['payAmount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
			'payCurrencyCode' => sanitize_text_field($payload['payCurrencyCode']),
			'receiveAmount' => filter_var($payload['receiveAmount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
			'receiveCurrencyCode' => sanitize_text_field($payload['receiveCurrencyCode']),
			'callbackUrl' => filter_var($payload['callbackUrl'], FILTER_SANITIZE_URL),
			'successUrl' => filter_var($payload['successUrl'], FILTER_SANITIZE_URL),
			'failureUrl' => filter_var($payload['failureUrl'], FILTER_SANITIZE_URL),
		];
		return $sanitized_payload;
    }

    /**
     * Payload data validation for create order
     * @param array $sanitized_payload
     * @return bool
     */
	private function spectrocoinValidateOrderPayload($sanitized_payload) {
		return isset(
			$sanitized_payload['orderId'],
			$sanitized_payload['projectId'],
			$sanitized_payload['description'],
			$sanitized_payload['payAmount'],
			$sanitized_payload['payCurrencyCode'],
			$sanitized_payload['receiveAmount'],
			$sanitized_payload['receiveCurrencyCode'],
			$sanitized_payload['callbackUrl'],
			$sanitized_payload['successUrl'],
			$sanitized_payload['failureUrl']
		) &&
		!empty($sanitized_payload['orderId']) &&
		!empty($sanitized_payload['projectId']) && 
		strlen($sanitized_payload['payCurrencyCode']) === 3 &&
		is_numeric($sanitized_payload['payAmount']) &&
		is_numeric($sanitized_payload['receiveAmount']) &&
		strlen($sanitized_payload['receiveCurrencyCode']) === 3 &&
		filter_var($sanitized_payload['callbackUrl'], FILTER_VALIDATE_URL) &&
		filter_var($sanitized_payload['successUrl'], FILTER_VALIDATE_URL) &&
		filter_var($sanitized_payload['failureUrl'], FILTER_VALIDATE_URL) &&
		($sanitized_payload['payAmount'] > 0 || $sanitized_payload['receiveAmount'] > 0);
	}
		
	// --------------- VALIDATION AND SANITIZATION AFTER CALLBACK -----------------

	/**
	 * @param $post_data
	 * @return SpectroCoin_OrderCallback|null
	 */
	public function spectrocoinProcessCallback($post_data) {
		if ($post_data != null) {
			$sanitized_data = $this->spectrocoinSanitizeCallback($post_data);
			$is_valid = $this->spectrocoinValidateCallback($sanitized_data);
			if ($is_valid) {
				$order_callback = new SpectroCoin_OrderCallback($sanitized_data['userId'], $sanitized_data['merchantApiId'], $sanitized_data['merchantId'], $sanitized_data['apiId'], $sanitized_data['orderId'], $sanitized_data['payCurrency'], $sanitized_data['payAmount'], $sanitized_data['receiveCurrency'], $sanitized_data['receiveAmount'], $sanitized_data['receivedAmount'], $sanitized_data['description'], $sanitized_data['orderRequestId'], $sanitized_data['status'], $sanitized_data['sign']);
				if ($this->spectrocoinValidateCallbackPayload($order_callback)) {
					return $order_callback;
				}
			}
			
		}
		return null;
	}

	/**
	 * Order callback data sanitization
	 * @param $post_data
	 * @return array
	 */
	public function spectrocoinSanitizeCallback($post_data) {
		return [
            'userId' => sanitize_text_field($post_data['userId']),
			'merchantApiId' => sanitize_text_field($post_data['merchantApiId']),
            'merchantId' => sanitize_text_field($post_data['merchantId']),
            'apiId' => sanitize_text_field($post_data['apiId']),
			'orderId' => sanitize_text_field($post_data['orderId']),
			'payCurrency' => sanitize_text_field($post_data['payCurrency']),
			'payAmount' => filter_var($post_data['payAmount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
			'receiveCurrency' => sanitize_text_field($post_data['receiveCurrency']),
			'receiveAmount' => filter_var($post_data['receiveAmount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
			'receivedAmount' => filter_var($post_data['receivedAmount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
			'description' => sanitize_text_field($post_data['description']),
			'orderRequestId' => filter_var($post_data['orderRequestId'], FILTER_SANITIZE_NUMBER_INT),
			'status' => sanitize_text_field($post_data['status']),
			'sign' => sanitize_text_field($post_data['sign']),
		];
	}

	/**
	 * Order callback data validation
	 * @param $sanitized_data
	 * @return bool
	 */
	public function spectrocoinValidateCallback($sanitized_data) {
		$is_valid = true;
		$failedFields = [];

		if (!isset(
            $sanitized_data['userId'], 
			$sanitized_data['merchantApiId'], 
            $sanitized_data['merchantId'], 
            $sanitized_data['apiId'],
			$sanitized_data['orderId'], 
			$sanitized_data['payCurrency'], 
			$sanitized_data['payAmount'], 
			$sanitized_data['receiveCurrency'], 
			$sanitized_data['receiveAmount'], 
			$sanitized_data['receivedAmount'], 
			$sanitized_data['description'], 
			$sanitized_data['orderRequestId'], 
			$sanitized_data['status'], 
			$sanitized_data['sign']
		)) {
			$is_valid = false;
			$failedFields[] = 'One or more required fields are missing.';
		} else {
            if (empty($sanitized_data['userId'])) {
				$is_valid = false;
				$failedFields[] = 'userId is empty.';
			}
			if (empty($sanitized_data['merchantApiId'])) {
				$is_valid = false;
				$failedFields[] = 'merchantApiId is empty.';
			}
            if (empty($sanitized_data['merchantId'])) {
                $is_valid = false;
                $failedFields[] = 'merchantId is empty.';
            }
            if (empty($sanitized_data['apiId'])) {
                $is_valid = false;
                $failedFields[] = 'apiId is empty.';
            }
			if (strlen($sanitized_data['payCurrency']) !== 3) {
				$is_valid = false;
				$failedFields[] = 'payCurrency is not 3 characters long.';
			}
			if (strlen($sanitized_data['receiveCurrency']) !== 3) {
				$is_valid = false;
				$failedFields[] = 'receiveCurrency is not 3 characters long.';
			}
			if (!is_numeric($sanitized_data['payAmount']) || $sanitized_data['payAmount'] <= 0) {
				$is_valid = false;
				$failedFields[] = 'payAmount is not a valid positive number.';
			}
			if (!is_numeric($sanitized_data['receiveAmount']) || $sanitized_data['receiveAmount'] <= 0) {
				$is_valid = false;
				$failedFields[] = 'receiveAmount is not a valid positive number.';
			}
			if ($sanitized_data['status'] == 6) {
				if (!is_numeric($sanitized_data['receivedAmount'])) {
					$is_valid = false;
					$failedFields[] = 'receivedAmount is not a valid number.';
				}
			} else {
				if (!is_numeric($sanitized_data['receivedAmount']) || $sanitized_data['receivedAmount'] < 0) {
					$is_valid = false;
					$failedFields[] = 'receivedAmount is not a valid non-negative number.';
				}
			}
			if (!is_numeric($sanitized_data['orderRequestId']) || $sanitized_data['orderRequestId'] <= 0) {
				$is_valid = false;
				$failedFields[] = 'orderRequestId is not a valid positive number.';
			}
			if (!is_numeric($sanitized_data['status']) || $sanitized_data['status'] <= 0) {
				$is_valid = false;
				$failedFields[] = 'status is not a valid positive number.';
			}
		}

		if (!$is_valid) {
			error_log('SpectroCoin error: Callback validation failed fields: ' . implode(', ', $failedFields));
		}
		return $is_valid;
	}

	/**
	 * Order callback payload validation
	 * @param SpectroCoin_OrderCallback $order_callback
	 * @return bool
	 */
	public function spectrocoinValidateCallbackPayload(SpectroCoin_OrderCallback $order_callback)
	{
		if ($order_callback != null) {

			$payload = array(
				'merchantId' => $order_callback->getMerchantId(),
				'apiId' => $order_callback->getApiId(),
				'orderId' => $order_callback->getOrderId(),
				'payCurrency' => $order_callback->getPayCurrency(),
				'payAmount' => $order_callback->getPayAmount(),
				'receiveCurrency' => $order_callback->getReceiveCurrency(),
				'receiveAmount' => $order_callback->getReceiveAmount(),
				'receivedAmount' => $order_callback->getReceivedAmount(),
				'description' => $order_callback->getDescription(),
				'orderRequestId' => $order_callback->getOrderRequestId(),
				'status' => $order_callback->getStatus(),
			);
			
			$data = http_build_query($payload);
            if ($this->spectrocoinValidateSignature($data, $order_callback->getSign()) == 1) {
				return true;
			} else {
				error_log('SpectroCoin Error: Signature validation failed');
			}
		}

		return false;
	}


	/**
	 * @param $data
	 * @param $signature
	 * @return int
	 */
	private function spectrocoinValidateSignature($data, $signature)
	{
		$sig = base64_decode($signature);
		$public_key = file_get_contents($this->public_spectrocoin_cert_location);
		$public_key_pem = openssl_pkey_get_public($public_key);
		$r = openssl_verify($data, $sig, $public_key_pem, OPENSSL_ALGO_SHA1);
		return $r;
	}

}