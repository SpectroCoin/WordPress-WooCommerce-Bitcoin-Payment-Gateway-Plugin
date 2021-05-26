<?php
class FormattingUtil {

	/**
	 * Formats currency amount with '0.0#######' format
	 * @param $amount
	 * @return string
	 */
	public static function formatCurrency($amount)
	{
		$decimals = strlen(substr(strrchr(rtrim(sprintf('%.8f', $amount), '0'), "."), 1));
		$decimals = $decimals < 1 ? 1 : $decimals;
		return number_format($amount, $decimals, '.', '');
	}
} 