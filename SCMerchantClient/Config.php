<?php

declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient;
// @codeCoverageIgnoreStart
if (!defined('ABSPATH')) {
    die('Access denied.');
}
// @codeCoverageIgnoreEnd
class Config
{
    const MERCHANT_API_URL = 'https://spectrocoin.com/api/public';
    const AUTH_URL = 'https://spectrocoin.com/api/public/oauth/token';
    const PUBLIC_SPECTROCOIN_CERT_LOCATION = 'https://spectrocoin.com/files/merchant.public.pem';
    const ACCEPTED_FIAT_CURRENCIES = ["EUR", "USD", "PLN", "CHF", "SEK", "GBP", "AUD", "CAD", "CZK", "DKK", "NOK"];  
}

