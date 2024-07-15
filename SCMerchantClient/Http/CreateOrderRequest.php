<?php

declare (strict_types = 1);

namespace SpectroCoin\SCMerchantClient\Http;

use SpectroCoin\SCMerchantClient\Utils;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class CreateOrderRequest
{
    private ?string $orderId;
    private ?string $description;
    private ?float $payAmount;
    private ?string $payCurrencyCode;
    private ?float $receiveAmount;
    private ?string $receiveCurrencyCode;
    private ?string $callbackUrl;
    private ?string $successUrl;
    private ?string $failureUrl;

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
    private function validate(): bool|array
    {
        $errors = [];

        if (!isset($this->orderId) || empty($this->orderId)) {
            $errors[] = 'orderId';
        }
        if (!isset($this->description) || empty($this->description)) {
            $errors[] = 'description';
        }
        if (!isset($this->payAmount) || !is_numeric($this->payAmount)) {
            $errors[] = 'payAmount';
        }
        if (!isset($this->payCurrencyCode) || strlen($this->payCurrencyCode) !== 3) {
            $errors[] = 'payCurrencyCode';
        }
        if (!isset($this->receiveAmount) || !is_numeric($this->receiveAmount)) {
            $errors[] = 'receiveAmount';
        }
        if (!isset($this->receiveCurrencyCode) || strlen($this->receiveCurrencyCode) !== 3) {
            $errors[] = 'receiveCurrencyCode';
        }
        if (!isset($this->callbackUrl) || !filter_var($this->callbackUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'callbackUrl';
        }
        if (!isset($this->successUrl) || !filter_var($this->successUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'successUrl';
        }
        if (!isset($this->failureUrl) || !filter_var($this->failureUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'failureUrl';
        }
        if (($this->payAmount <= 0) && ($this->receiveAmount <= 0)) {
            $errors[] = 'payAmount or receiveAmount must be greater than zero';
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Sanitize input data.
     *
     * @return void
     */
    private function sanitize(): void
    {
        $this->orderId = sanitize_text_field((string) $this->orderId);
        $this->description = sanitize_text_field((string) $this->description);
        $this->payAmount = filter_var($this->payAmount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $this->payCurrencyCode = sanitize_text_field((string) $this->payCurrencyCode);
        $this->receiveAmount = filter_var($this->receiveAmount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $this->receiveCurrencyCode = sanitize_text_field((string) $this->receiveCurrencyCode);
        $this->callbackUrl = filter_var($this->callbackUrl, FILTER_SANITIZE_URL);
        $this->successUrl = filter_var($this->successUrl, FILTER_SANITIZE_URL);
        $this->failureUrl = filter_var($this->failureUrl, FILTER_SANITIZE_URL);
    }

    /**
     * Convert object to array.
     *
     * @return array
     */
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

    /**
     * Convert CreateOrderRequest object to JSON.
     *
     * @return string|false
     */
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
