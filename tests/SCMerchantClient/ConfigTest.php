<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\CoversClass;
use SpectroCoin\SCMerchantClient\Config;

#[CoversClass(Config::class)]
class ConfigTest extends TestCase
{
    #[TestDox('Config::MERCHANT_API_URL should equal "https://spectrocoin.com/api/public"')]
    public function testMerchantApiUrl(): void
    {
        $this->assertSame('https://spectrocoin.com/api/public', Config::MERCHANT_API_URL);
    }

    #[TestDox('Config::AUTH_URL should equal "https://spectrocoin.com/api/public/oauth/token"')]
    public function testAuthUrl(): void
    {
        $this->assertSame('https://spectrocoin.com/api/public/oauth/token', Config::AUTH_URL);
    }

    #[TestDox('Config::PUBLIC_SPECTROCOIN_CERT_LOCATION should equal "https://spectrocoin.com/files/merchant.public.pem"')]
    public function testPublicSpectrocoinCertLocation(): void
    {
        $this->assertSame('https://spectrocoin.com/files/merchant.public.pem', Config::PUBLIC_SPECTROCOIN_CERT_LOCATION);
    }

    #[TestDox('Config::ACCEPTED_FIAT_CURRENCIES should match the expected currencies')]
    public function testAcceptedFiatCurrencies(): void
    {
        $expected = ["EUR", "USD", "PLN", "CHF", "SEK", "GBP", "AUD", "CAD", "CZK", "DKK", "NOK"];
        $this->assertSame($expected, Config::ACCEPTED_FIAT_CURRENCIES);
    }
}
