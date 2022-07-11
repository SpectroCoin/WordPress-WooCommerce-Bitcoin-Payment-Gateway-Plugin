<?php

class OrderCallback
{

    private $merchantId;
    private $merchantApiId;
    private $userId;
    private $apiId;
    private $orderId;
    private $payCurrency;
    private $payAmount;
    private $receiveCurrency;
    private $receiveAmount;
    private $receivedAmount;
    private $description;
    private $orderRequestId;
    private $status;
    private $sign;

    function __construct($merchantId, $merchantApiId, $userId, $apiId, $orderId, $payCurrency, $payAmount, $receiveCurrency, $receiveAmount, $receivedAmount, $description, $orderRequestId, $status, $sign)
    {
        $this->merchantId = $merchantId;
        $this->merchantApiId = $merchantApiId;
        $this->userId = $userId;
        $this->apiId = $apiId;
        $this->orderId = $orderId;
        $this->payCurrency = $payCurrency;
        $this->payAmount = $payAmount;
        $this->receiveCurrency = $receiveCurrency;
        $this->receiveAmount = $receiveAmount;
        $this->receivedAmount = $receivedAmount;
        $this->description = $description;
        $this->orderRequestId = $orderRequestId;
        $this->status = $status;
        $this->sign = $sign;
    }

    /**
     * @return mixed
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return mixed
     */
    public function getMerchantApiId()
    {
        return $this->merchantApiId;
    }

    /**
     * @return mixed
     */
    public function getApiId()
    {
        return $this->apiId;
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @return mixed
     */
    public function getPayCurrency()
    {
        return $this->payCurrency;
    }

    /**
     * @return mixed
     */
    public function getPayAmount()
    {
        return FormattingUtil::formatCurrency($this->payAmount == null ? 0.0 : $this->payAmount);
    }

    /**
     * @return mixed
     */
    public function getReceiveCurrency()
    {
        return $this->receiveCurrency;
    }

    /**
     * @return mixed
     */
    public function getReceiveAmount()
    {
        return FormattingUtil::formatCurrency($this->receiveAmount == null ? 0.0 : $this->receiveAmount);
    }

    /**
     * @return mixed
     */
    public function getReceivedAmount()
    {
        return FormattingUtil::formatCurrency($this->receivedAmount == null ? 0.0 : $this->receivedAmount);
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description == null ? '' : $this->description;
    }

    /**
     * @return mixed
     */
    public function getOrderRequestId()
    {
        return $this->orderRequestId;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getSign()
    {
        return $this->sign;
    }

    public function validate()
    {
        $valid = true;

        $valid &= $this->getMerchantId() > 0;
        $valid &= $this->getApiId() > 0;
        $valid &= $this->getOrderId() != '';
        $valid &= $this->getPayCurrency() != '';
        $valid &= $this->getPayAmount() > 0;
        $valid &= $this->getReceiveCurrency() != '';
        $valid &= $this->getReceiveAmount() > 0;
        $valid &= $this->getReceivedAmount() >= 0;
        $valid &= $this->getOrderRequestId() > 0;
        $valid &= $this->getStatus() > 0;
        $valid &= $this->getSign() != '';

        return $valid;
    }


}