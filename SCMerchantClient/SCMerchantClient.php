<?php

namespace SpectroCoin\SCMerchantClient;

use SpectroCoin\SCMerchantClient\Utils;
use SpectroCoin\SCMerchantClient\Exceptions\ApiError;
use SpectroCoin\SCMerchantClient\Http\OrderCallback;
use SpectroCoin\SCMerchantClient\Http\CreateOrderRequest;
use SpectroCoin\SCMerchantClient\Http\CreateOrderResponse;
use SpectroCoin\SCMerchantClient\Http\HttpClient;
use SpectroCoin\SCMerchantClient\Config;
use SpectroCoin\SCMerchantClient\Services\OrderManager;
use SpectroCoin\SCMerchantClient\Services\TokenManager;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

require_once __DIR__ . '/../vendor/autoload.php';

class SCMerchantClient
{
    private $token_manager;
    private $order_manager;

	function __construct($project_id, $client_id, $client_secret)
	{
        $this->token_manager = new tokenManager($client_id, $client_secret);
        $this->order_manager = new OrderManager($project_id, $this->token_manager);
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
				$order_callback = new OrderCallback($sanitized_data['userId'], $sanitized_data['merchantApiId'], $sanitized_data['merchantId'], $sanitized_data['apiId'], $sanitized_data['orderId'], $sanitized_data['payCurrency'], $sanitized_data['payAmount'], $sanitized_data['receiveCurrency'], $sanitized_data['receiveAmount'], $sanitized_data['receivedAmount'], $sanitized_data['description'], $sanitized_data['orderRequestId'], $sanitized_data['status'], $sanitized_data['sign']);
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
	public function spectrocoinValidateCallbackPayload(OrderCallback $order_callback)
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
		$public_key = file_get_contents(Config::PUBLIC_SPECTROCOIN_CERT_LOCATION);
		$public_key_pem = openssl_pkey_get_public($public_key);
		$r = openssl_verify($data, $sig, $public_key_pem, OPENSSL_ALGO_SHA1);
		return $r;
	}
}