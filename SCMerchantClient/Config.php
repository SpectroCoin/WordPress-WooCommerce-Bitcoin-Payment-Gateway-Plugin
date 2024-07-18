<?php

declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient;

if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Config
{
    const MERCHANT_API_URL = 'https://test.spectrocoin.com/api/public';
    const AUTH_URL = 'https://test.spectrocoin.com/api/public/oauth/token';
    const PUBLIC_SPECTROCOIN_CERT_LOCATION = 'https://test.spectrocoin.com/public.pem'; //PROD: https://spectrocoin.com/files/merchant.public.pem
    const ACCEPTED_FIAT_CURRENCIES = ["EUR", "USD", "PLN", "CHF", "SEK", "GBP", "AUD", "CAD", "CZK", "DKK", "NOK"];

    /**
     * Get the plugin folder name.
     *
     * @return string The plugin folder name.
     */
    public static function getPluginFolderName(): string
    {
        $plugin_folder = explode("/", plugin_basename(__FILE__))[0];
        return $plugin_folder;
    }

    // Optional configuration based on CMS:
    const SPECTROCOIN_REQUIRED_PHP_VERSION = '8.0';
    const SPECTROCOIN_WP_VERSION = '6.0';
}
