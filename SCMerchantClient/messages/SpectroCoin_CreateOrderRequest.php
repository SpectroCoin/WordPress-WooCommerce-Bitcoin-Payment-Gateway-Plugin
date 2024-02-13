<?php

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class SpectroCoin_CreateOrderRequest
{
	private $orderId;
	private $projectId;
	private $description;
	private $payAmount;
	private $payCurrencyCode;
	private $receiveAmount;
	private $receiveCurrencyCode;
	private $callbackUrl;
	private $successUrl;
	private $failureUrl;

	/**
	 * @param $orderId
	 * @param $projectId
	 * @param $description
	 * @param $payAmount
	 * @param $payCurrencyCode
	 * @param $receiveAmount
	 * @param $receiveCurrencyCode
	 * @param $callbackUrl
	 * @param $successUrl
	 * @param $failureUrl
	 */
	function __construct($orderId, $projectId, $description, $payAmount, $payCurrencyCode, $receiveAmount, $receiveCurrencyCode, $callbackUrl, $successUrl, $failureUrl)
	{
		$this->orderId = $orderId;
		$this->projectId = $projectId;
		$this->description = $description;
		$this->payAmount = $payAmount;
		$this->payCurrencyCode = $payCurrencyCode;
		$this->receiveAmount = $receiveAmount;
		$this->receiveCurrencyCode = $receiveCurrencyCode;
		$this->callbackUrl = $callbackUrl;
		$this->successUrl = $successUrl;
		$this->failureUrl = $failureUrl;
	}

	/**
	 * @return string
	 */
	public function getOrderId()
	{
		return $this->orderId == null ? '' : $this->orderId;
	}

	/**
	 * @return string
	 */
	public function getProjectId()
	{
		return $this->projectId == null ? '' : $this->projectId;
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description == null ? '' : $this->description;
	}

	/**
	 * @return string
	 */
	public function getPayAmount()
	{
		return SpectroCoin_Utilities::spectrocoin_format_currency($this->payAmount == null ? 0.0 : $this->payAmount);
	}

	/**
	 * @return string
	 */
	public function getPayCurrencyCode()
	{
		return $this->payCurrencyCode == null ? '' : $this->payCurrencyCode;
	}

	/**
	 * @return string
	 */
	public function getReceiveAmount()
	{
		return SpectroCoin_Utilities::spectrocoin_format_currency($this->receiveAmount == null ? 0.0 : $this->receiveAmount);
	}

	/**
	 * @return string
	 */
	public function getReceiveCurrencyCode()
	{
		return $this->receiveCurrencyCode == null ? '' : $this->receiveCurrencyCode;
	}


	/**
	 * @return string
	 */
	public function getCallbackUrl()
	{
		return $this->callbackUrl == null ? '' : $this->callbackUrl;
	}

	/**
	 * @return string
	 */
	public function getSuccessUrl()
	{
		return $this->successUrl == null ? '' : $this->successUrl;
	}

	/**
	 * @return string
	 */
	public function getFailureUrl()
	{
		return $this->failureUrl == null ? '' : $this->failureUrl;
	}


}