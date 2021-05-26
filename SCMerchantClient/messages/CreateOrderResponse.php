<?php

class CreateOrderResponse
{

	private $depositAddress;
	private $orderId;
	private $orderRequestId;
	private $payAmount;
	private $payCurrency;
	private $receiveAmount;
	private $receiveCurrency;
	private $validUntil;
	private $redirectUrl;

	/**
	 * @param $orderRequestId
	 * @param $orderId
	 * @param $depositAddress
	 * @param $payAmount
	 * @param $payCurrency
	 * @param $receiveAmount
	 * @param $receiveCurrency
	 * @param $validUntil
	 * @param $redirectUrl
	 */
	function __construct($orderRequestId, $orderId, $depositAddress, $payAmount, $payCurrency, $receiveAmount, $receiveCurrency, $validUntil, $redirectUrl)
	{
		$this->orderRequestId = $orderRequestId;
		$this->orderId = $orderId;
		$this->depositAddress = $depositAddress;
		$this->payAmount = $payAmount;
		$this->payCurrency = $payCurrency;
		$this->receiveAmount = $receiveAmount;
		$this->receiveCurrency = $receiveCurrency;
		$this->validUntil = $validUntil;
		$this->redirectUrl = $redirectUrl;
	}

	/**
	 * @return String
	 */
	public function getDepositAddress()
	{
		return $this->depositAddress;
	}

	/**
	 * @return String
	 */
	public function getOrderId()
	{
		return $this->orderId;
	}

	/**
	 * @return Integer
	 */
	public function getOrderRequestId()
	{
		return $this->orderRequestId;
	}

	/**
	 * @return Float
	 */
	public function getPayAmount()
	{
		return $this->payAmount;
	}

	/**
	 * @return String
	 */
	public function getPayCurrency()
	{
		return $this->payCurrency;
	}

	/**
	 * @return Float
	 */
	public function getReceiveAmount()
	{
		return $this->receiveAmount;
	}

	/**
	 * @return String
	 */
	public function getReceiveCurrency()
	{
		return $this->receiveCurrency;
	}

	/**
	 * @return Integer
	 */
	public function getValidUntil()
	{
		return $this->validUntil;
	}

	/**
	 * @return mixed
	 */
	public function getRedirectUrl()
	{
		return $this->redirectUrl;
	}
}