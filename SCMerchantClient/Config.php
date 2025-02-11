<?php

declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient;

if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Config
{
    const MERCHANT_API_URL = 'https://spectrocoin.com/api/public';
    const AUTH_URL = 'https://spectrocoin.com/api/public/oauth/token';
    const PUBLIC_SPECTROCOIN_CERT_LOCATION = 'https://spectrocoin.com/files/merchant.public.pem';
    const ACCEPTED_FIAT_CURRENCIES = ["EUR", "USD", "PLN", "CHF", "SEK", "GBP", "AUD", "CAD", "CZK", "DKK", "NOK"];

    // Optional configuration based on CMS:
    const SPECTROCOIN_REQUIRED_PHP_VERSION = '8.0';
    const SPECTROCOIN_WP_VERSION = '6.0';    
    const CALLBACK_NAME = 'spectrocoin_callback';
}

