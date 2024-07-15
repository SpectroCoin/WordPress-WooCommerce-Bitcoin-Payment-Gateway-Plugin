<?php

declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient\Http;

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
        $this->preOrderId = $data['preOrderId'] ?? null;
        $this->orderId = $data['orderId'] ?? null;
        $this->validUntil = $data['validUntil'] ?? null;
        $this->payCurrencyCode = $data['payCurrencyCode'] ?? null;
        $this->payNetworkCode = $data['payNetworkCode'] ?? null;
        $this->receiveCurrencyCode = $data['receiveCurrencyCode'] ?? null;
        $this->payAmount = $data['payAmount'] ?? null;
        $this->receiveAmount = $data['receiveAmount'] ?? null;
        $this->depositAddress = $data['depositAddress'] ?? null;
        $this->memo = $data['memo'] ?? null;
        $this->redirectUrl = $data['redirectUrl'] ?? null;
    }

    /**
     * Validate the data for create order API response.
     *
     * @return bool|array
     */
    public function validate(): bool|array
    {
        $errors = [];

        if (!isset($this->preOrderId) || empty($this->preOrderId)) {
            $errors[] = 'preOrderId is empty';
        }
        if (!isset($this->orderId) || empty($this->orderId)) {
            $errors[] = 'orderId is empty';
        }
        if (!isset($this->validUntil) || empty($this->validUntil)) {
            $errors[] = 'validUntil is empty';
        }
        if (!isset($this->payCurrencyCode) || strlen($this->payCurrencyCode) !== 3) {
            $errors[] = 'payCurrencyCode is not 3 characters long';
        }
        if (!isset($this->payNetworkCode) || empty($this->payNetworkCode)) {
            $errors[] = 'payNetworkCode is empty';
        }
        if (!isset($this->receiveCurrencyCode) || strlen($this->receiveCurrencyCode) !== 3) {
            $errors[] = 'receiveCurrencyCode is not 3 characters long';
        }
        if (!isset($this->payAmount) || !is_numeric($this->payAmount) || $this->payAmount <= 0) {
            $errors[] = 'payAmount is not a valid positive number';
        }
        if (!isset($this->receiveAmount) || !is_numeric($this->receiveAmount) || $this->receiveAmount <= 0) {
            $errors[] = 'receiveAmount is not a valid positive number';
        }
        if (!isset($this->depositAddress) || empty($this->depositAddress)) {
            $errors[] = 'depositAddress is empty';
        }
        if (!isset($this->redirectUrl) || !filter_var($this->redirectUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'redirectUrl is not a valid URL';
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Sanitize the data for create order API response.
     *
     * @return void
     */
    public function sanitize(): void
    {
        $this->preOrderId = sanitize_text_field($this->preOrderId);
        $this->orderId = sanitize_text_field($this->orderId);
        $this->validUntil = sanitize_text_field($this->validUntil);
        $this->payCurrencyCode = sanitize_text_field($this->payCurrencyCode);
        $this->payNetworkCode = sanitize_text_field($this->payNetworkCode);
        $this->receiveCurrencyCode = sanitize_text_field($this->receiveCurrencyCode);
        $this->payAmount = filter_var($this->payAmount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $this->receiveAmount = filter_var($this->receiveAmount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $this->depositAddress = sanitize_text_field($this->depositAddress);
        $this->memo = sanitize_text_field($this->memo);
        $this->redirectUrl = filter_var($this->redirectUrl, FILTER_SANITIZE_URL);
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
