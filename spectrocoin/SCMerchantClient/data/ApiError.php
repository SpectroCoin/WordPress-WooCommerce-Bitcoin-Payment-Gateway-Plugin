<?php

class ApiError
{
	private $code;
	private $message;

	/**
	 * @param $code
	 * @param $message
	 */
	function __construct($code, $message)
	{
		$this->code = $code;
		$this->message = $message;
	}

	/**
	 * @return Integer
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @return String
	 */
	public function getMessage()
	{
		return $this->message;
	}


}