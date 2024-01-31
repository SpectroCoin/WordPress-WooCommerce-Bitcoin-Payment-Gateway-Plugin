<?php

if (!defined('ABSPATH')) {
	die('Access denied.');
}

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

include_once('components/SpectroCoin_FormattingUtil.php');
include_once('components/SpectroCoin_ValidationUtil.php');
include_once('data/SpectroCoin_ApiError.php');
include_once('data/SpectroCoin_OrderStatusEnum.php');
include_once('data/SpectroCoin_OrderCallback.php');
include_once('messages/SpectroCoin_CreateOrderRequest.php');
include_once('messages/SpectroCoin_CreateOrderResponse.php');

require_once __DIR__ . '/../vendor/autoload.php';

class SCMerchantClient
{

	private $merchant_api_url;
	private $private_key;
	private $public_spectrocoin_cert_location;

	private $merchant_id;
	private $project_id;

	protected $guzzle_client;

	/**
	 * @param $merchant_api_url
	 * @param $merchant_id
	 * @param $project_id
	 */
	function __construct($merchant_api_url, $merchant_id, $project_id, $private_key)
	{
		$this->merchant_api_url = $merchant_api_url;
		$this->merchant_id = $merchant_id;
		$this->project_id = $project_id;
		$this->private_key = $private_key;
		$this->public_spectrocoin_cert_location = 'https://spectrocoin.com/files/merchant.public.pem';

		$this->guzzle_client = new Client();

	}

	/**
	 * @param SpectroCoin_CreateOrderRequest $request
	 * @return SpectroCoin_ApiError|SpectroCoin_CreateOrderResponse
	 */
	public function spectrocoin_create_order(SpectroCoin_CreateOrderRequest $request)
	{
		$payload = array(
			'userId' => $this->merchant_id,
			'merchantApiId' => $this->project_id,
			'orderId' => $request->getOrderId(),
			'payCurrency' => $request->getPayCurrency(),
			'payAmount' => $request->getPayAmount(),
			'receiveCurrency' => $request->getReceiveCurrency(),
			'receiveAmount' => $request->getReceiveAmount(),
			'description' => $request->getDescription(),
			'culture' => $request->getCulture(),
			'callbackUrl' => $request->getCallbackUrl(),
			'successUrl' => $request->getSuccessUrl(),
			'failureUrl' => $request->getFailureUrl()

		);
		
		$form_params = $payload;
		$signature = $this->spectrocoin_generate_signature(http_build_query($form_params));
		$payload['sign'] = $signature;
		$sanitized_payload = $this->spectrocoin_sanitize_create_order_payload($payload);
		if (!$this->spectrocoin_validate_create_order_payload($sanitized_payload)) {
            return new SpectroCoin_ApiError(-1, 'Invalid order creation payload, payload: ' . json_encode($sanitized_payload));
        }
		try {
			$response = $this->guzzle_client->post($this->merchant_api_url . '/createOrder', [
				'form_params' => $sanitized_payload,
				'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
			]);

			$body = json_decode($response->getBody());

			if (is_array($body) && count($body) > 0 && isset($body[0]->code)) {
				return new SpectroCoin_ApiError($body[0]->code, $body[0]->message);
			} else {
				return new SpectroCoin_CreateOrderResponse(
					$body->orderRequestId,
					$body->orderId,
					$body->depositAddress,
					$body->payAmount,
					$body->payCurrency,
					$body->receiveAmount,
					$body->receiveCurrency,
					$body->validUntil,
					$body->redirectUrl
				);
			}
		} catch (RequestException $e) {
			$errorBody = json_decode($e->getResponse()->getBody());
			if ($errorBody !== null && is_array($errorBody) && count($errorBody) > 0 && isset($errorBody[0]->code)) {
				return new SpectroCoin_ApiError($errorBody[0]->code, $errorBody[0]->message);
			} else {
				return new SpectroCoin_ApiError(-1, 'Unexpected error');
				}
			}
	}


	// --------------- VALIDATION AND SANITIZATION BEFORE REQEUST -----------------

	/**
     * Payload data sanitization for create order
     * @param array $payload
     * @return array
     */
    private function spectrocoin_sanitize_create_order_payload($payload) {
		$sanitized_payload = [
			'userId' => sanitize_text_field($payload['userId']),
			'merchantApiId' => sanitize_text_field($payload['merchantApiId']),
			'orderId' => sanitize_text_field($payload['orderId']),
			'payCurrency' => sanitize_text_field($payload['payCurrency']),
			'payAmount' => filter_var($payload['payAmount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
			'receiveCurrency' => sanitize_text_field($payload['receiveCurrency']),
			'receiveAmount' => filter_var($payload['receiveAmount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
			'description' => sanitize_text_field($payload['description']),
			'culture' => sanitize_text_field($payload['culture']),
			'callbackUrl' => filter_var($payload['callbackUrl'], FILTER_SANITIZE_URL),
			'successUrl' => filter_var($payload['successUrl'], FILTER_SANITIZE_URL),
			'failureUrl' => filter_var($payload['failureUrl'], FILTER_SANITIZE_URL),
			'sign' => sanitize_text_field($payload['sign'])
		];


		return $sanitized_payload;
    }

    /**
     * Payload data validation for create order
     * @param array $sanitized_payload
     * @return bool
     */
	private function spectrocoin_validate_create_order_payload($sanitized_payload) {
		return isset(
			$sanitized_payload['userId'],
			$sanitized_payload['merchantApiId'],
			$sanitized_payload['orderId'],
			$sanitized_payload['payCurrency'],
			$sanitized_payload['payAmount'],
			$sanitized_payload['receiveCurrency'],
			$sanitized_payload['receiveAmount'],
			$sanitized_payload['description'],
			$sanitized_payload['culture'],
			$sanitized_payload['callbackUrl'],
			$sanitized_payload['successUrl'],
			$sanitized_payload['failureUrl']
		) &&
		!empty($sanitized_payload['userId']) &&
		!empty($sanitized_payload['merchantApiId']) &&
		!empty($sanitized_payload['orderId']) &&
		strlen($sanitized_payload['payCurrency']) === 3 &&
		is_numeric($sanitized_payload['payAmount']) &&
		is_numeric($sanitized_payload['receiveAmount']) &&
		strlen($sanitized_payload['receiveCurrency']) === 3 &&
		filter_var($sanitized_payload['callbackUrl'], FILTER_VALIDATE_URL) &&
		filter_var($sanitized_payload['successUrl'], FILTER_VALIDATE_URL) &&
		filter_var($sanitized_payload['failureUrl'], FILTER_VALIDATE_URL) &&
		!empty($sanitized_payload['sign']) &&
		($sanitized_payload['payAmount'] > 0 || $sanitized_payload['receiveAmount'] > 0);
	}
		
	// --------------- VALIDATION AND SANITIZATION AFTER CALLBACK -----------------

	/**
	 * @param $post_data
	 * @return SpectroCoin_OrderCallback|null
	 */
	public function spectrocoin_process_callback($post_data) {
		
		if ($post_data != null) {
			
			$sanitized_data = $this->spectrocoin_sanitize_callback($post_data);
			$isValid = $this->spectrocoin_validate_callback($sanitized_data);

			if ($isValid) {
				$order_callback = new SpectroCoin_OrderCallback($sanitized_data['userId'], $sanitized_data['merchantApiId'], $sanitized_data['merchantId'], $sanitized_data['apiId'], $sanitized_data['orderId'], $sanitized_data['payCurrency'], $sanitized_data['payAmount'], $sanitized_data['receiveCurrency'], $sanitized_data['receiveAmount'], $sanitized_data['receivedAmount'], $sanitized_data['description'], $sanitized_data['orderRequestId'], $sanitized_data['status'], $sanitized_data['sign']);

				if ($this->spectrocoin_validate_callback_payload($order_callback)) {
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
	public function spectrocoin_sanitize_callback($post_data) {
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

	public function spectrocoin_validate_callback($sanitized_data) {
		$isValid = true;
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
			$isValid = false;
			$failedFields[] = 'One or more required fields are missing.';
		} else {
            if (empty($sanitized_data['userId'])) {
				$isValid = false;
				$failedFields[] = 'userId is empty.';
			}
			if (empty($sanitized_data['merchantApiId'])) {
				$isValid = false;
				$failedFields[] = 'merchantApiId is empty.';
			}
            if (empty($sanitized_data['merchantId'])) {
                $isValid = false;
                $failedFields[] = 'merchantId is empty.';
            }
            if (empty($sanitized_data['apiId'])) {
                $isValid = false;
                $failedFields[] = 'apiId is empty.';
            }
			if (strlen($sanitized_data['payCurrency']) !== 3) {
				$isValid = false;
				$failedFields[] = 'payCurrency is not 3 characters long.';
			}
			if (strlen($sanitized_data['receiveCurrency']) !== 3) {
				$isValid = false;
				$failedFields[] = 'receiveCurrency is not 3 characters long.';
			}
			if (!is_numeric($sanitized_data['payAmount']) || $sanitized_data['payAmount'] <= 0) {
				$isValid = false;
				$failedFields[] = 'payAmount is not a valid positive number.';
			}
			if (!is_numeric($sanitized_data['receiveAmount']) || $sanitized_data['receiveAmount'] <= 0) {
				$isValid = false;
				$failedFields[] = 'receiveAmount is not a valid positive number.';
			}
			if ($sanitized_data['status'] == 6) {
				if (!is_numeric($sanitized_data['receivedAmount'])) {
					$isValid = false;
					$failedFields[] = 'receivedAmount is not a valid number.';
				}
			} else {
				if (!is_numeric($sanitized_data['receivedAmount']) || $sanitized_data['receivedAmount'] < 0) {
					$isValid = false;
					$failedFields[] = 'receivedAmount is not a valid non-negative number.';
				}
			}
			if (!is_numeric($sanitized_data['orderRequestId']) || $sanitized_data['orderRequestId'] <= 0) {
				$isValid = false;
				$failedFields[] = 'orderRequestId is not a valid positive number.';
			}
			if (!is_numeric($sanitized_data['status']) || $sanitized_data['status'] <= 0) {
				$isValid = false;
				$failedFields[] = 'status is not a valid positive number.';
			}
		}

		if (!$isValid) {
			error_log('SpectroCoin Callback Failed fields: ' . implode(', ', $failedFields));
		}
		error_log('SpectroCoin Callback Field Validation Success: ');
		return $isValid;
	}

	/**
	 * Order callback payload validation
	 * @param SpectroCoin_OrderCallback $order_callback
	 * @return bool
	 */
	public function spectrocoin_validate_callback_payload(SpectroCoin_OrderCallback $order_callback)
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
            if ($this->spectrocoin_validate_signature($data, $order_callback->getSign()) == 1) {
				error_log('SpectroCoin: Signature validation succeeded');
				return true;
			} else {
				error_log('SpectroCoin Error: Signature validation failed');
			}
		}

		return false;
	}

	// ------------------------ SIGNATURE ------------------------

	/**
	 * Function which generates signature;
	 * if the retrieval of the private key fails, the function returns false and logs to error_log;
	 * if the signature generation fails, the function returns false and logs to error_log;
	 * if the signature generation succeeds, the function returns the signature;
	 * @param $data
	 * @return bool/string
	 */
	private function spectrocoin_generate_signature($data)
	{

		$pkey_id = openssl_pkey_get_private($this->private_key);

		if ($pkey_id  === false) {
			error_log("SpectroCoin Error: Unable to load private key");
			return false;
		}

		$s = openssl_sign($data, $signature, $pkey_id, OPENSSL_ALGO_SHA1);
		if ($s === false) {
			error_log("SpectroCoin Error: Signature generation failed");
			return false;
		}

		$encodedSignature = base64_encode($signature);

		return $encodedSignature;
	}

	/**
	 * @param $data
	 * @param $signature
	 * @return int
	 */
	private function spectrocoin_validate_signature($data, $signature)
	{
		$sig = base64_decode($signature);
		$publicKey = file_get_contents($this->public_spectrocoin_cert_location);
		$public_key_pem = openssl_pkey_get_public($publicKey);
		$r = openssl_verify($data, $sig, $public_key_pem, OPENSSL_ALGO_SHA1);
		return $r;
	}

}