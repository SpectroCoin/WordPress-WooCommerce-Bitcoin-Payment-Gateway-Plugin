<?php

/**
 * Created by UAB Spectro Fincance.
 * This is a sample SpectroCoin Merchant v1.1 API PHP client
 */

include_once('httpful.phar');
include_once('components/FormattingUtil.php');
include_once('data/ApiError.php');
include_once('data/OrderStatusEnum.php');
include_once('data/OrderCallback.php');
include_once('messages/CreateOrderRequest.php');
include_once('messages/CreateOrderResponse.php');


class SCMerchantClient
{

	private $merchantApiUrl;
	private $privateMerchantCert;
	private $publicSpectroCoinCertLocation;

	private $merchantId;
	private $apiId;
	private $debug;

	/**
	 * @param $merchantApiUrl
	 * @param $merchantId
	 * @param $apiId
	 * @param bool $debug
	 */
	function __construct($merchantApiUrl, $merchantId, $apiId, $privateMerchantCert, $debug = false)
	{
		$this->privateMerchantCert = $privateMerchantCert;
		$this->publicSpectroCoinCertLocation = 'https://spectrocoin.com/files/merchant.public.pem';
		$this->merchantApiUrl = $merchantApiUrl;
		$this->merchantId = $merchantId;
		$this->apiId = $apiId;
		$this->debug = $debug;
	}

	/**
	 * @param CreateOrderRequest $request
	 * @return ApiError|CreateOrderResponse
	 */
	public function createOrder(CreateOrderRequest $request)
	{
		$payload = array(
			'merchantId' => $this->merchantId,
			'apiId' => $this->apiId,
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

		$formHandler = new \Httpful\Handlers\FormHandler();
		$data = $formHandler->serialize($payload);
		$signature = $this->generateSignature($data);
		$payload['sign'] = $signature;

		if (!$this->debug) {
			$response = \Httpful\Request::post($this->merchantApiUrl . '/createOrder', $payload, \Httpful\Mime::FORM)->expects(\Httpful\Mime::JSON)->send();
			if ($response != null) {
				$body = $response->body;
				if ($body != null) {
					if (is_array($body) && count($body) > 0 && isset($body[0]->code)) {
						return new ApiError($body[0]->code, $body[0]->message);
					} else if (isset($body->orderRequestId)) {
						return new CreateOrderResponse($body->orderRequestId, $body->orderId, $body->depositAddress, $body->payAmount, $body->payCurrency, $body->receiveAmount, $body->receiveCurrency, $body->validUntil, $body->redirectUrl);
					}
				}
			}
		} else {
			$response = \Httpful\Request::post($this->merchantApiUrl . '/createOrder', $payload, \Httpful\Mime::FORM)->send();
			exit('<pre>'.print_r($response, true).'</pre>');
		}
	}

	private function generateSignature($data)
	{
		$pkeyid = openssl_pkey_get_private($this->privateMerchantCert);

		// compute signature
		$s = openssl_sign($data, $signature, $pkeyid, OPENSSL_ALGO_SHA1);
		$encodedSignature = base64_encode($signature);
		// free the key from memory
		openssl_free_key($pkeyid);

		return $encodedSignature;
	}

	/**
	 * @param $r $_REQUEST
	 * @return OrderCallback|null
	 */
	public function parseCreateOrderCallback($r)
	{
		$result = null;

		if ($r != null && isset($r['merchantId'], $r['apiId'], $r['orderId'], $r['payCurrency'], $r['payAmount'], $r['receiveCurrency'], $r['receiveAmount'], $r['receivedAmount'], $r['description'], $r['orderRequestId'], $r['status'], $r['sign'])) {
			$result = new OrderCallback($r['merchantId'], $r['apiId'], $r['orderId'], $r['payCurrency'], $r['payAmount'], $r['receiveCurrency'], $r['receiveAmount'], $r['receivedAmount'], $r['description'], $r['orderRequestId'], $r['status'], $r['sign']);
		}

		return $result;
	}

	/**
	 * @param OrderCallback $c
	 * @return bool
	 */
	public function validateCreateOrderCallback(OrderCallback $c)
	{
		$valid = false;

		if ($c != null) {

			if ($this->merchantId != $c->getMerchantId() || $this->apiId != $c->getApiId())
				return $valid;

			if (!$c->validate())
				return $valid;

			$payload = array(
				'merchantId' => $c->getMerchantId(),
				'apiId' => $c->getApiId(),
				'orderId' => $c->getOrderId(),
				'payCurrency' => $c->getPayCurrency(),
				'payAmount' => $c->getPayAmount(),
				'receiveCurrency' => $c->getReceiveCurrency(),
				'receiveAmount' => $c->getReceiveAmount(),
				'receivedAmount' => $c->getReceivedAmount(),
				'description' => $c->getDescription(),
				'orderRequestId' => $c->getOrderRequestId(),
				'status' => $c->getStatus(),
			);

			$formHandler = new \Httpful\Handlers\FormHandler();
			$data = $formHandler->serialize($payload);
			$valid = $this->validateSignature($data, $c->getSign());
		}

		return $valid;
	}

	/**
	 * @param $data
	 * @param $signature
	 * @return int
	 */
	private function validateSignature($data, $signature)
	{
		$sig = base64_decode($signature);
		$publicKey = file_get_contents($this->publicSpectroCoinCertLocation);
		$public_key_pem = openssl_pkey_get_public($publicKey);
		$r = openssl_verify($data, $sig, $public_key_pem, OPENSSL_ALGO_SHA1);
		openssl_free_key($public_key_pem);

		return $r;
	}

}