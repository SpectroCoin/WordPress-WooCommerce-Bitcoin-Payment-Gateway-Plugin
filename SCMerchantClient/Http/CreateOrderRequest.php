<?php

declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient\Http;

use SpectroCoin\SCMerchantClient\Utils;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class CreateOrderRequest
{
    private string $orderId;
    private string $description;
    private float $payAmount;
    private string $payCurrencyCode;
    private float $receiveAmount;
    private string $receiveCurrencyCode;
    private string $callbackUrl;
    private string $successUrl;
    private string $failureUrl;

    public function __construct(
        string $orderId,
        string $description,
        float $payAmount,
        string $payCurrencyCode,
        float $receiveAmount,
        string $receiveCurrencyCode,
        string $callbackUrl,
        string $successUrl,
        string $failureUrl
    ) {
        $this->orderId = $orderId;
        $this->description = $description;
        $this->payAmount = $payAmount;
        $this->payCurrencyCode = $payCurrencyCode;
        $this->receiveAmount = $receiveAmount;
        $this->receiveCurrencyCode = $receiveCurrencyCode;
        $this->callbackUrl = $callbackUrl;
        $this->successUrl = $successUrl;
        $this->failureUrl = $failureUrl;

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

    private function sanitize(): void
    {
        $this->orderId = sanitize_text_field($this->orderId);
        $this->description = sanitize_text_field($this->description);
        $this->payAmount = (float)filter_var($this->payAmount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $this->payCurrencyCode = sanitize_text_field($this->payCurrencyCode);
        $this->receiveAmount = (float)filter_var($this->receiveAmount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $this->receiveCurrencyCode = sanitize_text_field($this->receiveCurrencyCode);
        $this->callbackUrl = filter_var($this->callbackUrl, FILTER_SANITIZE_URL);
        $this->successUrl = filter_var($this->successUrl, FILTER_SANITIZE_URL);
        $this->failureUrl = filter_var($this->failureUrl, FILTER_SANITIZE_URL);
    }

    public function getOrderId(): string { return $this->orderId; }
    public function getDescription(): string { return $this->description; }
    public function getPayAmount(): float { return Utils::formatCurrency($this->payAmount); }
    public function getPayCurrencyCode(): string { return $this->payCurrencyCode; }
    public function getReceiveAmount(): float { return Utils::formatCurrency($this->receiveAmount); }
    public function getReceiveCurrencyCode(): string { return $this->receiveCurrencyCode; }
    public function getCallbackUrl(): string { return $this->callbackUrl; }
    public function getSuccessUrl(): string { return $this->successUrl; }
    public function getFailureUrl(): string { return $this->failureUrl; }
}
?>
