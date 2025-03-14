<?php

declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient\Http;

use SpectroCoin\SCMerchantClient\Utils;
use SpectroCoin\SCMerchantClient\Config;
use Exception;
use InvalidArgumentException;
// @codeCoverageIgnoreStart
if (!defined('ABSPATH')) {
    die('Access denied.');
}
// @codeCoverageIgnoreEnd
class OrderCallback
{
    private ?string $userId;
    private ?string $merchantApiId;
    private ?string $merchantId;
    private ?string $apiId;
    private ?string $orderId;
    private ?string $payCurrency;
    private ?string $payAmount;
    private ?string $receiveCurrency;
    private ?string $receiveAmount;
    private ?string $receivedAmount;
    private ?string $description;
    private ?string $orderRequestId;
    private ?string $status;
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
        $this->userId = isset($data['userId']) ? sanitize_text_field((string)$data['userId']) : null;
        $this->merchantApiId = isset($data['merchantApiId']) ? sanitize_text_field((string)$data['merchantApiId']) : null;
        $this->merchantId = isset($data['merchantId']) ? sanitize_text_field((string)$data['merchantId']) : null;
        $this->apiId = isset($data['apiId']) ? sanitize_text_field((string)$data['apiId']) : null;
        $this->orderId = isset($data['orderId']) ? sanitize_text_field((string)$data['orderId']) : null;
        $this->payCurrency = isset($data['payCurrency']) ? sanitize_text_field((string)$data['payCurrency']) : null;
        $this->payAmount = isset($data['payAmount']) ? sanitize_text_field((string)$data['payAmount']) : null; // Changed to string
        $this->receiveCurrency = isset($data['receiveCurrency']) ? sanitize_text_field((string)$data['receiveCurrency']) : null;
        $this->receiveAmount = isset($data['receiveAmount']) ? sanitize_text_field((string)$data['receiveAmount']) : null; // Changed to string
        $this->receivedAmount = isset($data['receivedAmount']) ? sanitize_text_field((string)$data['receivedAmount']) : null; // Changed to string
        $this->description = isset($data['description']) ? sanitize_text_field((string)$data['description']) : null;
        $this->orderRequestId = isset($data['orderRequestId']) ? sanitize_text_field((string)$data['orderRequestId']) : null;
        $this->status = isset($data['status']) ? sanitize_text_field((string)$data['status']) : null;
        $this->sign = isset($data['sign']) ? sanitize_text_field((string)$data['sign']) : null;

        $validation_result = $this->validate();
        if (is_array($validation_result)) {
            $errorMessage = 'Invalid order callback payload. Failed fields: ' . implode(', ', $validation_result);
            throw new InvalidArgumentException($errorMessage);
        }

        if (!$this->validatePayloadSignature()) {
            throw new Exception('Invalid payload signature.');
        }
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
        if (empty($this->getStatus())){
            $errors[] = 'status is empty';
        }
        if (strlen($this->getPayCurrency()) !== 3) {
            $errors[] = 'payCurrency is not 3 characters long';
        }
        if (!is_numeric($this->getPayAmount()) || (float)$this->getPayAmount() <= 0) {
            $errors[] = 'payAmount is not a valid positive number';
        }
        if (strlen($this->getReceiveCurrency()) !== 3) {
            $errors[] = 'receiveCurrency is not 3 characters long';
        }
        if (!is_numeric($this->getReceiveAmount()) || (float)$this->getReceiveAmount() <= 0) {
            $errors[] = 'receiveAmount is not a valid positive number';
        }
        if (!isset($this->receivedAmount)) {
            $errors[] = 'receivedAmount is not set';
        }
        if (!is_numeric($this->getOrderRequestId()) || (float)$this->getOrderRequestId() <= 0) {
            $errors[] = 'orderRequestId is not a valid positive number';
        }
        if (empty($this->getSign())) {
            $errors[] = 'signature is empty';
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
    public function getReceivedAmount() { return Utils::formatCurrency($this->receivedAmount); }
    public function getDescription() { return $this->description; }
    public function getOrderRequestId() { return $this->orderRequestId; }
    public function getStatus() { return $this->status; }
    public function getSign() { return $this->sign; }
}
?>
