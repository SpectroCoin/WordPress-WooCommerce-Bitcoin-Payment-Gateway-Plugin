<?php

namespace SpectroCoin\SCMerchantClient\Http;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class CreateOrderResponse
{
    private $preOrderId;
    private $orderId;
    private $validUntil;
    private $payCurrencyCode;
    private $payNetworkCode;
    private $receiveCurrencyCode;
    private $payAmount;
    private $receiveAmount;
    private $depositAddress;
    private $memo;
    private $redirectUrl;

    /**
     * @param $data
    */
    public function __construct($data)
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
     * Data validation for create order API response
     * @return bool
    */
    public function validate()
    {
        return isset(
            $this->preOrderId,
            $this->orderId,
            $this->validUntil,
            $this->payCurrencyCode,
            $this->payNetworkCode,
            $this->receiveCurrencyCode,
            $this->payAmount,
            $this->receiveAmount,
            $this->depositAddress,
            $this->redirectUrl
        ) &&
        !empty($this->orderId) &&
        !empty($this->preOrderId) &&
        !empty($this->validUntil) &&
        strlen($this->payCurrencyCode) === 3 &&
        strlen($this->receiveCurrencyCode) === 3 &&
        is_numeric($this->payAmount) &&
        is_numeric($this->receiveAmount) &&
        filter_var($this->depositAddress, FILTER_SANITIZE_STRING) &&
        filter_var($this->redirectUrl, FILTER_VALIDATE_URL);
    }

    /**
     * Data sanitization for create order API response
    */
    public function sanitize()
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