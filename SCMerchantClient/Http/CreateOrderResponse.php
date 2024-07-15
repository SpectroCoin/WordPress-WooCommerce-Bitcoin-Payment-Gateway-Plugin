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
