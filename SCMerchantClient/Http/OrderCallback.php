<?php

namespace SpectroCoin\SCMerchantClient\Http;

use SpectroCoin\SCMerchantClient\Utils;

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
        $requiredFields = [
            'userId', 'merchantApiId', 'merchantId', 'apiId', 'orderId', 
            'payCurrency', 'payAmount', 'receiveCurrency', 'receiveAmount', 
            'receivedAmount', 'description', 'orderRequestId', 'status', 'sign'
        ];

        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        if (strlen($this->payCurrency) !== 3 || strlen($this->receiveCurrency) !== 3) {
            return false;
        }

        if (!is_numeric($this->payAmount) || $this->payAmount <= 0) {
            return false;
        }

        if (!is_numeric($this->receiveAmount) || $this->receiveAmount <= 0) {
            return false;
        }

        if (!is_numeric($this->orderRequestId) || $this->orderRequestId <= 0) {
            return false;
        }

        if (!is_numeric($this->status) || $this->status <= 0) {
            return false;
        }

        return true;
    }

    public function getUserId() { return $this->userId; }
    public function getMerchantApiId() { return $this->merchantApiId; }
    public function getMerchantId() { return $this->merchantId; }
    public function getApiId() { return $this->apiId; }
    public function getOrderId() { return $this->orderId; }
    public function getPayCurrency() { return $this->payCurrency; }
    public function getPayAmount() { return Utils::spectrocoinformatCurrency($this->payAmount == null ? 0.0 : $this->payAmount); }
    public function getReceiveCurrency() { return $this->receiveCurrency; }
    public function getReceiveAmount() { return Utils::spectrocoinformatCurrency($this->receiveAmount == null ? 0.0 : $this->receiveAmount); }
    public function getReceivedAmount() { return $this->receivedAmount; }
    public function getDescription() { return $this->description; }
    public function getOrderRequestId() { return $this->orderRequestId; }
    public function getStatus() { return $this->status; }
    public function getSign() { return $this->sign; }
}
?>