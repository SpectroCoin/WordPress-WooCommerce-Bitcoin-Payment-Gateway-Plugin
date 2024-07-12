<?php

namespace SpectroCoin\SCMerchantClient\Http;

use SpectroCoin\SCMerchantClient\Utils;
use SpectroCoin\SCMerchantClient\Config;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class OrderCallback
{
    private $userId;
    private $merchantApiId;
    private $merchantId;
    private $apiId;
    private $orderId;
    private $payCurrency;
    private $payAmount;
    private $receiveCurrency;
    private $receiveAmount;
    private $receivedAmount;
    private $description;
    private $orderRequestId;
    private $status;
    private $sign;

    public function __construct($data)
    {
        $this->userId = $data['userId'] ?? null;
        $this->merchantApiId = $data['merchantApiId'] ?? null;
        $this->merchantId = $data['merchantId'] ?? null;
        $this->apiId = $data['apiId'] ?? null;
        $this->orderId = $data['orderId'] ?? null;
        $this->payCurrency = $data['payCurrency'] ?? null;
        $this->payAmount = $data['payAmount'] ?? null;
        $this->receiveCurrency = $data['receiveCurrency'] ?? null;
        $this->receiveAmount = $data['receiveAmount'] ?? null;
        $this->receivedAmount = $data['receivedAmount'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->orderRequestId = $data['orderRequestId'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->sign = $data['sign'] ?? null;

        $this->sanitize();

        $validation = $this->validate();
        if (is_array($validation)) {
            $errorMessage = 'Invalid order callback payload. Failed fields: ' . implode(', ', $validation);
            throw new InvalidArgumentException($errorMessage);
        }
    }

    public function sanitize()
    {
        $this->userId = sanitize_text_field($this->userId);
        $this->merchantApiId = sanitize_text_field($this->merchantApiId);
        $this->merchantId = sanitize_text_field($this->merchantId);
        $this->apiId = sanitize_text_field($this->apiId);
        $this->orderId = sanitize_text_field($this->orderId);
        $this->payCurrency = sanitize_text_field($this->payCurrency);
        $this->payAmount = filter_var($this->payAmount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $this->receiveCurrency = sanitize_text_field($this->receiveCurrency);
        $this->receiveAmount = filter_var($this->receiveAmount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $this->receivedAmount = filter_var($this->receivedAmount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $this->description = sanitize_text_field($this->description);
        $this->orderRequestId = filter_var($this->orderRequestId, FILTER_SANITIZE_NUMBER_INT);
        $this->status = sanitize_text_field($this->status);
        $this->sign = sanitize_text_field($this->sign);
    }

    public function validate()
    {
        $errors = [];

        if (!isset($this->userId) || empty($this->userId)) {
            $errors[] = 'userId';
        }
        if (!isset($this->merchantApiId) || empty($this->merchantApiId)) {
            $errors[] = 'merchantApiId';
        }
        if (!isset($this->merchantId) || empty($this->merchantId)) {
            $errors[] = 'merchantId';
        }
        if (!isset($this->apiId) || empty($this->apiId)) {
            $errors[] = 'apiId';
        }
        if (!isset($this->orderId) || empty($this->orderId)) {
            $errors[] = 'orderId';
        }
        if (!isset($this->payCurrency) || strlen($this->payCurrency) !== 3) {
            $errors[] = 'payCurrency';
        }
        if (!isset($this->payAmount) || !is_numeric($this->payAmount) || $this->payAmount <= 0) {
            $errors[] = 'payAmount';
        }
        if (!isset($this->receiveCurrency) || strlen($this->receiveCurrency) !== 3) {
            $errors[] = 'receiveCurrency';
        }
        if (!isset($this->receiveAmount) || !is_numeric($this->receiveAmount) || $this->receiveAmount <= 0) {
            $errors[] = 'receiveAmount';
        }
        if (!isset($this->receivedAmount) || !is_numeric($this->receivedAmount)) {
            $errors[] = 'receivedAmount';
        }
        if (!isset($this->description) || empty($this->description)) {
            $errors[] = 'description';
        }
        if (!isset($this->orderRequestId) || !is_numeric($this->orderRequestId) || $this->orderRequestId <= 0) {
            $errors[] = 'orderRequestId';
        }
        if (!isset($this->status) || !is_numeric($this->status) || $this->status <= 0) {
            $errors[] = 'status';
        }
        if (!isset($this->sign) || empty($this->sign)) {
            $errors[] = 'sign';
        }

        return empty($errors) ? true : $errors;
    }

    public function validatePayloadSignature()
    {
        $payload = array(
            'merchantId' => $this->merchantId,
            'apiId' => $this->apiId,
            'orderId' => $this->orderId,
            'payCurrency' => $this->payCurrency,
            'payAmount' => $this->payAmount,
            'receiveCurrency' => $this->receiveCurrency,
            'receiveAmount' => $this->receiveAmount,
            'receivedAmount' => $this->receivedAmount,
            'description' => $this->description,
            'orderRequestId' => $this->orderRequestId,
            'status' => $this->status,
        );

        $data = http_build_query($payload);
        $decoded_signature = base64_decode($this->sign);
        $public_key = file_get_contents(Config::PUBLIC_SPECTROCOIN_CERT_LOCATION);
        $public_key_pem = openssl_pkey_get_public($public_key);
        return openssl_verify($data, $decoded_signature, $public_key_pem, OPENSSL_ALGO_SHA1);
    }

    public function getUserId() { return $this->userId; }
    public function getMerchantApiId() { return $this->merchantApiId; }
    public function getMerchantId() { return $this->merchantId; }
    public function getApiId() { return $this->apiId; }
    public function getOrderId() { return $this->orderId; }
    public function getPayCurrency() { return $this->payCurrency; }
    public function getPayAmount() { return Utils::formatCurrency($this->payAmount); }
    public function getReceiveCurrency() { return $this->receiveCurrency; }
    public function getReceiveAmount() { return Utils::formatCurrency($this->receiveAmount); }
    public function getReceivedAmount() { return $this->receivedAmount; }
    public function getDescription() { return $this->description; }
    public function getOrderRequestId() { return $this->orderRequestId; }
    public function getStatus() { return $this->status; }
    public function getSign() { return $this->sign; }
}
?>
