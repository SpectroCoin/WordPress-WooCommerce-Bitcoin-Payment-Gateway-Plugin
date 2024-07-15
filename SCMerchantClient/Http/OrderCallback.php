<?php

declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient\Http;

use SpectroCoin\SCMerchantClient\Utils;
use SpectroCoin\SCMerchantClient\Config;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    die('Access denied.');
}

class OrderCallback
{
    private ?string $userId;
    private ?string $merchantApiId;
    private ?string $merchantId;
    private ?string $apiId;
    private ?string $orderId;
    private ?string $payCurrency;
    private ?float $payAmount;
    private ?string $receiveCurrency;
    private ?float $receiveAmount;
    private ?float $receivedAmount;
    private ?string $description;
    private ?int $orderRequestId;
    private ?int $status;
    private ?string $sign;

    /**
     * Constructor for OrderCallback.
     *
     * @param array $data The data for initializing the callback.
     *
     * @throws InvalidArgumentException If the payload is invalid.
     */
    public function __construct(array $data)
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

        $validation_result = $this->validate();
        if (is_array($validation_result)) {
            $errorMessage = 'Invalid order callback payload. Failed fields: ' . implode(', ', $validation_result);
            throw new InvalidArgumentException($errorMessage);
        }

        if (!$this->validatePayloadSignature()) {
            throw new InvalidArgumentException('Invalid payload signature.');
        }
    }

    /**
     * Sanitize the input data.
     *
     * @return void
     */
    public function sanitize(): void
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

    /**
     * Validate the input data.
     *
     * @return bool|array True if validation passes, otherwise an array of error messages.
     */
    private function validate(): bool|array
    {
        $errors = [];

        if (empty($this->getUserId())) {
            $errors[] = 'userId is empty';
        }
        if (empty($this->getMerchantApiId())) {
            $errors[] = 'merchantApiId is empty';
        }
        if (empty($this->getMerchantId())) {
            $errors[] = 'merchantId is empty';
        }
        if (empty($this->getApiId())) {
            $errors[] = 'apiId is empty';
        }
        if (empty($this->getOrderId())) {
            $errors[] = 'orderId is empty';
        }
        if (strlen($this->getPayCurrency()) !== 3) {
            $errors[] = 'payCurrency is not 3 characters long';
        }
        if (!is_numeric($this->getPayAmount()) || $this->getPayAmount() <= 0) {
            $errors[] = 'payAmount is not a valid positive number';
        }
        if (strlen($this->getReceiveCurrency()) !== 3) {
            $errors[] = 'receiveCurrency is not 3 characters long';
        }
        if (!is_numeric($this->getReceiveAmount()) || $this->getReceiveAmount() <= 0) {
            $errors[] = 'receiveAmount is not a valid positive number';
        }
        if (!isset($this->receivedAmount)) {
            $errors[] = 'receivedAmount is not set';
        } elseif ($this->getStatus() == 6) {
            if (!is_numeric($this->getReceivedAmount())) {
                $errors[] = 'receivedAmount is not a valid number';
            }
        } else {
            if (!is_numeric($this->getReceivedAmount()) || $this->getReceivedAmount() < 0) {
                $errors[] = 'receivedAmount is not a valid non-negative number';
            }
        }
        if (empty($this->getDescription())) {
            $errors[] = 'description is empty';
        }
        if (!is_numeric($this->getOrderRequestId()) || $this->getOrderRequestId() <= 0) {
            $errors[] = 'orderRequestId is not a valid positive number';
        }
        if (!is_numeric($this->getStatus()) || $this->getStatus() <= 0) {
            $errors[] = 'status is not a valid positive number';
        }
        if (empty($this->getSign())) {
            $errors[] = 'sign is empty';
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Validate the payload signature.
     *
     * @return bool True if the signature is valid, otherwise false.
     */
    public function validatePayloadSignature(): bool
    {
        $payload = [
            'merchantId' => $this->getMerchantId(),
            'apiId' => $this->getApiId(),
            'orderId' => $this->getOrderId(),
            'payCurrency' => $this->getPayCurrency(),
            'payAmount' => $this->getPayAmount(),
            'receiveCurrency' => $this->getReceiveCurrency(),
            'receiveAmount' => $this->getReceiveAmount(),
            'receivedAmount' => $this->getReceivedAmount(),
            'description' => $this->getDescription(),
            'orderRequestId' => $this->getOrderRequestId(),
            'status' => $this->getStatus(),
        ];

        $data = http_build_query($payload);
        $decoded_signature = base64_decode($this->sign);
        $public_key = file_get_contents(Config::PUBLIC_SPECTROCOIN_CERT_LOCATION);
        $public_key_pem = openssl_pkey_get_public($public_key);
        return openssl_verify($data, $decoded_signature, $public_key_pem, OPENSSL_ALGO_SHA1) === 1;
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

