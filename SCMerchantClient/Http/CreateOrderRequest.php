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
    private ?string $orderId;
    private ?string $description;
    private ?string $receiveAmount;
    private ?string $receiveCurrencyCode;
    private ?string $callbackUrl;
    private ?string $successUrl;
    private ?string $failureUrl;

    /**
     * CreateOrderRequest constructor.
     *
     * @param array $data
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $data) {
        $this->orderId = isset($data['orderId']) ? sanitize_text_field((string)$data['orderId']) : null;
        $this->description = isset($data['description']) ? sanitize_text_field((string)$data['description']) : null;
        $this->receiveAmount = isset($data['receiveAmount']) ? sanitize_text_field((string)$data['receiveAmount']) : null;
        $this->receiveCurrencyCode = isset($data['receiveCurrencyCode']) ? sanitize_text_field((string)$data['receiveCurrencyCode']) : null;
        // $this->callbackUrl = isset($data['callbackUrl']) ? Utils::sanitizeUrl($data['callbackUrl']) : null;
        // $this->successUrl = isset($data['successUrl']) ? Utils::sanitizeUrl($data['successUrl']) : null;
        // $this->failureUrl = isset($data['failureUrl']) ? Utils::sanitizeUrl($data['failureUrl']) : null;
        $this->callbackUrl = "https://webhook.site/";
        $this->successUrl = "https://webhook.site/";
        $this->failureUrl = "https://webhook.site/";

        $validation = $this->validate();
        if (is_array($validation)) {
            $errorMessage = 'Invalid order creation payload. Failed fields: ' . implode(', ', $validation);
            throw new InvalidArgumentException($errorMessage);
        }
    }

    /**
     * Data validation for create order API request.
     *
     * @return bool|array True if validation passes, otherwise an array of error messages.
     */
    private function validate(): bool|array
    {
        $errors = [];

        if (empty($this->getOrderId())) {
            $errors[] = 'orderId is required';
        }
        if (empty($this->getDescription())) {
            $errors[] = 'description is required';
        }
        if ($this->getReceiveAmount() === null || (float)$this->getReceiveAmount() <= 0) {
            $errors[] = 'receiveAmount must be greater than zero';
        }
        if (empty($this->getReceiveCurrencyCode()) || strlen($this->getReceiveCurrencyCode()) !== 3) {
            $errors[] = 'receiveCurrencyCode must be 3 characters long';
        }
        if (empty($this->getCallbackUrl()) || !filter_var($this->getCallbackUrl(), FILTER_VALIDATE_URL)) {
            $errors[] = 'invalid callbackUrl';
        }
        if (empty($this->getSuccessUrl()) || !filter_var($this->getSuccessUrl(), FILTER_VALIDATE_URL)) {
            $errors[] = 'invalid successUrl';
        }
        if (empty($this->getFailureUrl()) || !filter_var($this->getFailureUrl(), FILTER_VALIDATE_URL)) {
            $errors[] = 'invalid failureUrl';
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Convert CreateOrderRequest object to array.
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'orderId' => $this->getOrderId(),
            'description' => $this->getDescription(),
            'receiveAmount' => $this->getReceiveAmount(),
            'receiveCurrencyCode' => $this->getReceiveCurrencyCode(),
            'callbackUrl' => $this->getCallbackUrl(),
            'successUrl' => $this->getSuccessUrl(),
            'failureUrl' => $this->getFailureUrl()
        ];
    }

    /**
     * Convert CreateOrderRequest array to JSON.
     *
     * @return string|false
     */
    public function toJson(): string|false {
        return json_encode($this->toArray());
    }

    public function getOrderId() { return $this->orderId; }
    public function getDescription() { return $this->description; }
    public function getReceiveAmount() { return Utils::formatCurrency((float)$this->receiveAmount); }
    public function getReceiveCurrencyCode() { return $this->receiveCurrencyCode; }
    public function getCallbackUrl() { return $this->callbackUrl; }
    public function getSuccessUrl() { return $this->successUrl; }
    public function getFailureUrl() { return $this->failureUrl; }
}
?>
