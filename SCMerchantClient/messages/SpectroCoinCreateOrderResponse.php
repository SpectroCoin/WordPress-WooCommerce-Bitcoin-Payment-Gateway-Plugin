<?php

namespace SpectroCoin\SCMerchantClient\Messages;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class SpectroCoinCreateOrderResponse
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
     * @param $preOrderId
     * @param $orderId
     * @param $validUntil
     * @param $payCurrencyCode
     * @param $payNetworkCode
     * @param $receiveCurrencyCode
     * @param $payAmount
     * @param $receiveAmount
     * @param $depositAddress
     * @param $memo
     * @param $redirectUrl
     */
    function __construct($preOrderId, $orderId, $validUntil, $payCurrencyCode, $payNetworkCode, $receiveCurrencyCode, $payAmount, $receiveAmount, $depositAddress, $memo, $redirectUrl)
    {
        $this->preOrderId = $preOrderId;
        $this->orderId = $orderId;
        $this->validUntil = $validUntil;
        $this->payCurrencyCode = $payCurrencyCode;
        $this->payNetworkCode = $payNetworkCode;
        $this->receiveCurrencyCode = $receiveCurrencyCode;
        $this->payAmount = $payAmount;
        $this->receiveAmount = $receiveAmount;
        $this->depositAddress = $depositAddress;
        $this->memo = $memo;
        $this->redirectUrl = $redirectUrl;
    }

    // Getter methods for each property
    public function getPreOrderId()
    {
        return $this->preOrderId;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function getValidUntil()
    {
        return $this->validUntil;
    }

    public function getPayCurrencyCode()
    {
        return $this->payCurrencyCode;
    }

    public function getPayNetworkCode()
    {
        return $this->payNetworkCode;
    }

    public function getReceiveCurrencyCode()
    {
        return $this->receiveCurrencyCode;
    }

    public function getPayAmount()
    {
        return $this->payAmount;
    }

    public function getReceiveAmount()
    {
        return $this->receiveAmount;
    }

    public function getDepositAddress()
    {
        return $this->depositAddress;
    }

    public function getMemo()
    {
        return $this->memo;
    }

    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }
}
