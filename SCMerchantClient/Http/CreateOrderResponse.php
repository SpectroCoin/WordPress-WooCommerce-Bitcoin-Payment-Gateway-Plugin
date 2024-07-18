<?php

declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient\Http;

use InvalidArgumentException;
use SpectroCoin\SCMerchantClient\Utils;

if (!defined('ABSPATH')) {
    die('Access denied.');
}

class CreateOrderResponse
{
    private ?string $preOrderId;
    private ?string $orderId;
    private ?string $validUntil;
    private ?string $payCurrencyCode;
    private ?string $payNetworkCode;
    private ?string $receiveCurrencyCode;
    private ?float $payAmount;
    private ?float $receiveAmount;
    private ?string $depositAddress;
    private ?string $memo;
    private ?string $redirectUrl;

    /**
     * Constructor to initialize order response properties.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->preOrderId = isset($data['preOrderId']) ? sanitize_text_field((string)$data['preOrderId']) : null;
        $this->orderId = isset($data['orderId']) ? sanitize_text_field((string)$data['orderId']) : null;
        $this->validUntil = isset($data['validUntil']) ? sanitize_text_field((string)$data['validUntil']) : null;
        $this->payCurrencyCode = isset($data['payCurrencyCode']) ? sanitize_text_field((string)$data['payCurrencyCode']) : null;
        $this->payNetworkCode = isset($data['payNetworkCode']) ? sanitize_text_field((string)$data['payNetworkCode']) : null;
        $this->receiveCurrencyCode = isset($data['receiveCurrencyCode']) ? sanitize_text_field((string)$data['receiveCurrencyCode']) : null;
        $this->payAmount = isset($data['payAmount']) ? Utils::sanitizeFloat($data['payAmount']) : null;
        $this->receiveAmount = isset($data['receiveAmount']) ? Utils::sanitizeFloat($data['receiveAmount']) : null;
        $this->depositAddress = isset($data['depositAddress']) ? sanitize_text_field((string)$data['depositAddress']) : null;
        $this->memo = isset($data['memo']) ? sanitize_text_field((string)$data['memo']) : null;
        $this->redirectUrl = isset($data['redirectUrl']) ? Utils::sanitizeUrl($data['redirectUrl']) : null;

        $validation = $this->validate();
        if (is_array($validation)) {
            $errorMessage = 'Invalid order creation payload. Failed fields: ' . implode(', ', $validation);
            throw new InvalidArgumentException($errorMessage);
        }
    }

    /**
     * Validate the data for create order API response.
     *
     * @return bool|array True if validation passes, otherwise an array of error messages.
     */
    public function validate(): bool|array
    {
        $errors = [];

        if (empty($this->getPreOrderId())) {
            $errors[] = 'preOrderId is empty';
        }
        if (empty($this->getOrderId())) {
            $errors[] = 'orderId is empty';
        }
        if (empty($this->getValidUntil())) {
            $errors[] = 'validUntil is empty';
        }
        if (strlen($this->getPayCurrencyCode()) !== 3) {
            $errors[] = 'payCurrencyCode is not 3 characters long';
        }
        if (empty($this->getPayNetworkCode())) {
            $errors[] = 'payNetworkCode is empty';
        }
        if (strlen($this->getReceiveCurrencyCode()) !== 3) {
            $errors[] = 'receiveCurrencyCode is not 3 characters long';
        }
        if (!is_numeric($this->getPayAmount()) || $this->getPayAmount() <= 0) {
            $errors[] = 'payAmount is not a valid positive number';
        }
        if (!is_numeric($this->getReceiveAmount()) || $this->getReceiveAmount() <= 0) {
            $errors[] = 'receiveAmount is not a valid positive number';
        }
        if (empty($this->getDepositAddress())) {
            $errors[] = 'depositAddress is empty';
        }
        if (!filter_var($this->getRedirectUrl(), FILTER_VALIDATE_URL)) {
            $errors[] = 'redirectUrl is not a valid URL';
        }

        return empty($errors) ? true : $errors;
    }

    public function getPreOrderId(): ?string { return $this->preOrderId; }
    public function getOrderId(): ?string { return $this->orderId; }
    public function getValidUntil(): ?string { return $this->validUntil; }
    public function getPayCurrencyCode(): ?string { return $this->payCurrencyCode; }
    public function getPayNetworkCode(): ?string { return $this->payNetworkCode; }
    public function getReceiveCurrencyCode(): ?string { return $this->receiveCurrencyCode; }
    public function getPayAmount(): ?float { return $this->payAmount; }
    public function getReceiveAmount(): ?float { return $this->receiveAmount; }
    public function getDepositAddress(): ?string { return $this->depositAddress; }
    public function getMemo(): ?string { return $this->memo; }
    public function getRedirectUrl(): ?string { return $this->redirectUrl; }
}
?>
