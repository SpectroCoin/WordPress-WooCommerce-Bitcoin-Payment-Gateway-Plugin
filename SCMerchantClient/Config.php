<?php

namespace SpectroCoin\SCMerchantClient;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

Class Config{
	const SPECTROCOIN_REQUIRED_PHP_VERSION = '8.0';
	const SPECTROCOIN_WP_VERSION = '6.0';

	const MERCHANT_API_URL = 'https://test.spectrocoin.com/api/public';
    const AUTH_URL = 'https://test.spectrocoin.com/api/public/oauth/token';
    const PUBLIC_SPECTROCOIN_CERT_LOCATION = 'https://test.spectrocoin.com/public.pem'; //PROD: https://spectrocoin.com/files/merchant.public.pem

	public static function getPluginFolderName(){
		$plugin_folder = explode ("/", plugin_basename(__FILE__))[0];
		return $plugin_folder;
	}
}
