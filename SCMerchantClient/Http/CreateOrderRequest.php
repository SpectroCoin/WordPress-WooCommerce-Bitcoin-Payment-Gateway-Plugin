<?php

namespace SpectroCoin\SCMerchantClient\Http;

use SpectroCoin\SCMerchantClient\Utils;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class CreateOrderRequest
{
    private $orderId;
    private $description;
    private $payAmount;
    private $payCurrencyCode;
    private $receiveAmount;
    private $receiveCurrencyCode;
    private $callbackUrl;
    private $successUrl;
    private $failureUrl;

    public function __construct(array $data) {
        $this->orderId = $data['orderId'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->payAmount = $data['payAmount'] ?? null;
        $this->payCurrencyCode = $data['payCurrencyCode'] ?? null;
        $this->receiveAmount = $data['receiveAmount'] ?? null;
        $this->receiveCurrencyCode = $data['receiveCurrencyCode'] ?? null;
        $this->callbackUrl = $data['callbackUrl'] ?? null;
        $this->successUrl = $data['successUrl'] ?? null;
        $this->failureUrl = $data['failureUrl'] ?? null;

        $this->sanitize();

        $validation = $this->validate();
        if (is_array($validation)) {
            $errorMessage = 'Invalid order creation payload. Failed fields: ' . implode(', ', $validation);
            throw new InvalidArgumentException($errorMessage);
        }
    }

    /**
     * Data validation for create order API request
     * @return bool|array
     */
    private function validate()
    {
        $errors = [];

        if (!isset($this->userId) || empty($this->userId)) {
            $errors[] = 'userId is empty';
        }
        if (!isset($this->merchantApiId) || empty($this->merchantApiId)) {
            $errors[] = 'merchantApiId is empty';
        }
        if (!isset($this->merchantId) || empty($this->merchantId)) {
            $errors[] = 'merchantId is empty';
        }
        if (!isset($this->apiId) || empty($this->apiId)) {
            $errors[] = 'apiId is empty';
        }
        if (!isset($this->orderId) || empty($this->orderId)) {
            $errors[] = 'orderId is empty';
        }
        if (!isset($this->payCurrency) || strlen($this->payCurrency) !== 3) {
            $errors[] = 'payCurrency is not 3 characters long';
        }
        if (!isset($this->payAmount) || !is_numeric($this->payAmount) || $this->payAmount <= 0) {
            $errors[] = 'payAmount is not a valid positive number';
        }
        if (!isset($this->receiveCurrency) || strlen($this->receiveCurrency) !== 3) {
            $errors[] = 'receiveCurrency is not 3 characters long';
        }
        if (!isset($this->receiveAmount) || !is_numeric($this->receiveAmount) || $this->receiveAmount <= 0) {
            $errors[] = 'receiveAmount is not a valid positive number';
        }
        if (!isset($this->receivedAmount)) {
            $errors[] = 'receivedAmount is not set';
        } elseif ($this->status == 6) {
            if (!is_numeric($this->receivedAmount)) {
                $errors[] = 'receivedAmount is not a valid number';
            }
        } else {
            if (!is_numeric($this->receivedAmount) || $this->receivedAmount < 0) {
                $errors[] = 'receivedAmount is not a valid non-negative number';
            }
        }
        if (!isset($this->description) || empty($this->description)) {
            $errors[] = 'description is empty';
        }
        if (!isset($this->orderRequestId) || !is_numeric($this->orderRequestId) || $this->orderRequestId <= 0) {
            $errors[] = 'orderRequestId is not a valid positive number';
        }
        if (!isset($this->status) || !is_numeric($this->status) || $this->status <= 0) {
            $errors[] = 'status is not a valid positive number';
        }
        if (!isset($this->sign) || empty($this->sign)) {
            $errors[] = 'sign is empty';
        }

        return empty($errors) ? true : $errors;
    }

    private function sanitize()
    {
        $this->orderId = sanitize_text_field($this->orderId);
        $this->description = sanitize_text_field($this->description);
        $this->payAmount = filter_var($this->payAmount, FILTER_SANITIZE_NUMBER_, FILTER_FLAG_ALLOW_FRACTION);
        $this->payCurrencyCode = sanitize_text_field($this->payCurrencyCode);
        $this->receiveAmount = filter_var($this->receiveAmount, FILTER_SANITIZE_NUMBER_, FILTER_FLAG_ALLOW_FRACTION);
        $this->receiveCurrencyCode = sanitize_text_field($this->receiveCurrencyCode);
        $this->callbackUrl = filter_var($this->callbackUrl, FILTER_SANITIZE_URL);
        $this->successUrl = filter_var($this->successUrl, FILTER_SANITIZE_URL);
        $this->failureUrl = filter_var($this->failureUrl, FILTER_SANITIZE_URL);
    }

    public function toArray() {
        return [
            'orderId' => $this->getOrderId(),
            'description' => $this->getDescription(),
            'payAmount' => $this->getPayAmount(),
            'payCurrencyCode' => $this->getPayCurrencyCode(),
            'receiveAmount' => $this->getReceiveAmount(),
            'receiveCurrencyCode' => $this->getReceiveCurrencyCode(),
            'callbackUrl' => $this->getCallbackUrl(),
            'successUrl' => $this->getSuccessUrl(),
            'failureUrl' => $this->getFailureUrl()
        ];
    }

    public function toJson() {
        return json_encode($this->toArray());
    }

    public function getOrderId(){ return $this->orderId; }
    public function getDescription() { return $this->description; }
    public function getPayAmount() { return Utils::formatCurrency($this->payAmount); }
    public function getPayCurrencyCode() { return $this->payCurrencyCode; }
    public function getReceiveAmount() { return Utils::formatCurrency($this->receiveAmount); }
    public function getReceiveCurrencyCode() { return $this->receiveCurrencyCode; }
    public function getCallbackUrl() { return $this->callbackUrl; }
    public function getSuccessUrl() { return $this->successUrl; }
    public function getFailureUrl()  { return $this->failureUrl; }
}
?>
