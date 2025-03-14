<?php

declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient\Http;

use InvalidArgumentException;
use SpectroCoin\SCMerchantClient\Utils;
// @codeCoverageIgnoreStart
if (!defined('ABSPATH')) {
    die('Access denied.');
}
// @codeCoverageIgnoreEnd
class CreateOrderResponse
{
    private ?string $preOrderId;
    private ?string $orderId;
    private ?string $validUntil;
    private ?string $payCurrencyCode;
    private ?string $payNetworkCode;
    private ?string $receiveCurrencyCode;
    private ?string $payAmount;
    private ?string $receiveAmount;
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
        $this->preOrderId = isset($data['preOrderId']) ? Utils::sanitize_text_field((string)$data['preOrderId']) : null;
        $this->orderId = isset($data['orderId']) ? Utils::sanitize_text_field((string)$data['orderId']) : null;
        $this->validUntil = isset($data['validUntil']) ? Utils::sanitize_text_field((string)$data['validUntil']) : null;
        $this->payCurrencyCode = isset($data['payCurrencyCode']) ? Utils::sanitize_text_field((string)$data['payCurrencyCode']) : null;
        $this->payNetworkCode = isset($data['payNetworkCode']) ? Utils::sanitize_text_field((string)$data['payNetworkCode']) : null;
        $this->receiveCurrencyCode = isset($data['receiveCurrencyCode']) ? Utils::sanitize_text_field((string)$data['receiveCurrencyCode']) : null;
        $this->payAmount = isset($data['payAmount']) ? Utils::sanitize_text_field((string)$data['payAmount']) : null;
        $this->receiveAmount = isset($data['receiveAmount']) ? Utils::sanitize_text_field((string)$data['receiveAmount']) : null;
        $this->depositAddress = isset($data['depositAddress']) ? Utils::sanitize_text_field((string)$data['depositAddress']) : null;
        $this->memo = isset($data['memo']) ? Utils::sanitize_text_field((string)$data['memo']) : null;
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
        if (strlen($this->getReceiveCurrencyCode()) !== 3) {
            $errors[] = 'receiveCurrencyCode is not 3 characters long';
        }
        if ($this->getReceiveAmount() === null || (float)$this->getReceiveAmount() <= 0) {
            $errors[] = 'receiveAmount is not a valid positive number';
        }
        if (!filter_var($this->getRedirectUrl(), FILTER_VALIDATE_URL)) {
            $errors[] = 'redirectUrl is not a valid URL';
        }

        return empty($errors) ? true : $errors;
    }

    public function getPreOrderId() { return $this->preOrderId; }
    public function getOrderId() { return $this->orderId; }
    public function getValidUntil() { return $this->validUntil; }
    public function getPayCurrencyCode() { return $this->payCurrencyCode; }
    public function getPayNetworkCode() { return $this->payNetworkCode; }
    public function getReceiveCurrencyCode() { return $this->receiveCurrencyCode; }
    public function getPayAmount() { return $this->payAmount; }
    public function getReceiveAmount() { return $this->receiveAmount; }
    public function getDepositAddress() { return $this->depositAddress; }
    public function getMemo() { return $this->memo; }
    public function getRedirectUrl() { return $this->redirectUrl; }
}
?>
