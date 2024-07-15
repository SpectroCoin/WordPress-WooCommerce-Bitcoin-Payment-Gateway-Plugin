<?php

declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient\Exception;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class ApiError
{
	private string $message;
	private int $code;

	/**
	 * @param string $message
	 * @param int $code
	 */
	public function __construct(string $message, int $code = 0)
	{
		$this->message = $message;
		$this->code = $code;
	}

	/**
	 * @return string
	 */
	public function getMessage(): string
	{
		return $this->message;
	}

	/**
	 * @return int
	 */
	public function getCode(): int
	{
		return $this->code;
	}


}
