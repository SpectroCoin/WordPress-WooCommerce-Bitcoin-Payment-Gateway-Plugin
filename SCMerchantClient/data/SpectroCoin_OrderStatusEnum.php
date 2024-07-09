<?php

namespace SpectroCoin\SCMerchantClient\Data;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class SpectroCoin_OrderStatusEnum
{
	public static $New = 1;
	public static $Pending = 2;
	public static $Paid = 3;
	public static $Failed = 4;
	public static $Expired = 5;
}