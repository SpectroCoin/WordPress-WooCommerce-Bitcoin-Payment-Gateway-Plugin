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

    /**
     * CreateOrderRequest constructor.
     *
     * @param array $data
     *
     * @throws InvalidArgumentException
     */
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
 * Data validation for create order API request.
 *
 * @return bool|array True if validation passes, otherwise an array of error messages.
 */
    private function validate(): bool|array
    {
        $errors = [];

        if (empty($this->getOrderId())) {
            $errors[] = 'orderId';
        }
        if (empty($this->getDescription())) {
            $errors[] = 'description';
        }
        if (!is_numeric($this->getPayAmount())) {
            $errors[] = 'payAmount';
        }
        if (strlen($this->getPayCurrencyCode()) !== 3) {
            $errors[] = 'payCurrencyCode';
        }
        if (!is_numeric($this->getReceiveAmount())) {
            $errors[] = 'receiveAmount';
        }
        if (strlen($this->getReceiveCurrencyCode()) !== 3) {
            $errors[] = 'receiveCurrencyCode';
        }
        if (!filter_var($this->getCallbackUrl(), FILTER_VALIDATE_URL)) {
            $errors[] = 'callbackUrl';
        }
        if (!filter_var($this->getSuccessUrl(), FILTER_VALIDATE_URL)) {
            $errors[] = 'successUrl';
        }
        if (!filter_var($this->getFailureUrl(), FILTER_VALIDATE_URL)) {
            $errors[] = 'failureUrl';
        }
        if (($this->getPayAmount() <= 0) && ($this->getReceiveAmount() <= 0)) {
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
     * Convert CreateOrderRequest object to array.
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
     * Convert CreateOrderRequest array to JSON.
     *
     * @return string|false
     */
    public function toJson() {
        return json_encode($this->toArray());
    }

    public function getOrderId(): ?string { return $this->orderId; }
    public function getDescription(): ?string { return $this->description; }
    public function getPayAmount(): ?float { return Utils::formatCurrency($this->payAmount); }
    public function getPayCurrencyCode(): ?string { return $this->payCurrencyCode; }
    public function getReceiveAmount(): ?float { return Utils::formatCurrency($this->receiveAmount); }
    public function getReceiveCurrencyCode(): ?string { return $this->receiveCurrencyCode; }
    public function getCallbackUrl(): ?string { return $this->callbackUrl; }
    public function getSuccessUrl(): ?string { return $this->successUrl; }
    public function getFailureUrl(): ?string  { return $this->failureUrl; }
}
?>
