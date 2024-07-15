<?php

declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient\Exception;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class ApiError
{
	private int $code;
	private string $message;

	/**
	 * @param int $code
	 * @param string $message
	 */
	public function __construct(int $code, string $message)
	{
		$this->code = $code;
		$this->message = $message;
	}

	/**
	 * @return int|string
	 */
	public function getCode(): int
	{
		return $this->code;
	}

	/**
	 * @return string
	 */
	public function getMessage(): string
	{
		return $this->message;
	}
}
