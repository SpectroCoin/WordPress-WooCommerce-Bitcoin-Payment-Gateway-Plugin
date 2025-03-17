<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use SpectroCoin\SCMerchantClient\Http\CreateOrderRequest;
use SpectroCoin\SCMerchantClient\Utils;

#[CoversClass(CreateOrderRequest::class)]
#[UsesClass(Utils::class)]
class CreateOrderRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[DataProvider('validCreateOrderRequestProvider')]
    #[TestDox('Test CreateOrderRequest initialization with valid data')]
    public function testCreateOrderRequestWithValidData(array $order_data, $expected): void
    {
        $create_order_request = new CreateOrderRequest($order_data);
        $this->assertSame($expected, $create_order_request->toArray());
    }


    public static function validCreateOrderRequestProvider(): array
    {
        return [
            'Valid payload' => [
                [
                    'orderId' => 'ORD123',
                    'description' => 'Test order',
                    'receiveAmount' => '500.23',
                    'receiveCurrencyCode' => 'EUR',
                    'callbackUrl' => 'https://example.com/callback',
                    'successUrl' => 'https://example.com/success',
                    'failureUrl' => 'https://example.com/failure'
                ],
                [
                    'orderId' => 'ORD123',
                    'description' => 'Test order',
                    'receiveAmount' => '500.23',
                    'receiveCurrencyCode' => 'EUR',
                    'callbackUrl' => 'https://example.com/callback',
                    'successUrl' => 'https://example.com/success',
                    'failureUrl' => 'https://example.com/failure'
                ],
            ],
            'Valid payload with random payload order' => [
                [
                    'description' => 'Test order',
                    'callbackUrl' => 'https://example.com/callback',
                    'receiveCurrencyCode' => 'USD',
                    'failureUrl' => 'https://example.com/failure',
                    'orderId' => 'ORD123',
                    'receiveAmount' => '100.00',
                    'successUrl' => 'https://example.com/success',
                ],
                [
                    'orderId' => 'ORD123',
                    'description' => 'Test order',
                    'receiveAmount' => '100.0',
                    'receiveCurrencyCode' => 'USD',
                    'callbackUrl' => 'https://example.com/callback',
                    'successUrl' => 'https://example.com/success',
                    'failureUrl' => 'https://example.com/failure'
                ],
            ],
        ];
    }

    #[DataProvider('invalidCreateOrderRequestProvider')]
    #[TestDox('Test CreateOrderRequest initialization with invalid data')]
    public function testCreateOrderRequestWithInvalidData(array $order_data): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CreateOrderRequest($order_data);
    }


    public static function invalidCreateOrderRequestProvider(): array
    {
        return [
            'No orderId' => [[
                'description' => 'Test order',
                'receiveAmount' => '500.23',
                'receiveCurrencyCode' => 'EUR',
                'callbackUrl' => 'https://example.com/callback',
                'successUrl' => 'https://example.com/success',
                'failureUrl' => 'https://example.com/failure'
            ]],
            'No description' => [[
                'orderId' => 'ORD123',
                'receiveAmount' => '500.23',
                'receiveCurrencyCode' => 'EUR',
                'callbackUrl' => 'https://example.com/callback',
                'successUrl' => 'https://example.com/success',
                'failureUrl' => 'https://example.com/failure'
            ]],
            'receiveAmount is 0' => [[
                'orderId' => 'ORD123',
                'receiveAmount' => '0',
                'receiveCurrencyCode' => 'EUR',
                'callbackUrl' => 'https://example.com/callback',
                'successUrl' => 'https://example.com/success',
                'failureUrl' => 'https://example.com/failure'
            ]],
            'receiveAmount is -1' => [[
                'orderId' => 'ORD123',
                'receiveAmount' => '-1',
                'receiveCurrencyCode' => 'EUR',
                'callbackUrl' => 'https://example.com/callback',
                'successUrl' => 'https://example.com/success',
                'failureUrl' => 'https://example.com/failure'
            ]],
            'No receiveAmount' => [[
                'orderId' => 'ORD123',
                'receiveCurrencyCode' => 'EUR',
                'callbackUrl' => 'https://example.com/callback',
                'successUrl' => 'https://example.com/success',
                'failureUrl' => 'https://example.com/failure'
            ]],
            'No receiveCurrencyCode' => [[
                'orderId' => 'ORD123',
                'receiveAmount' => '100.0',
                'callbackUrl' => 'https://example.com/callback',
                'successUrl' => 'https://example.com/success',
                'failureUrl' => 'https://example.com/failure'
            ]],
            'receiveCurrencyCode is less than 3 characters' => [[
                'orderId' => 'ORD123',
                'receiveAmount' => '100.0',
                'receiveCurrencyCode' => 'EU',
                'callbackUrl' => 'https://example.com/callback',
                'successUrl' => 'https://example.com/success',
                'failureUrl' => 'https://example.com/failure'
            ]],
            'receiveCurrencyCode is more than 3 characters' => [[
                'orderId' => 'ORD123',
                'receiveAmount' => '100.0',
                'receiveCurrencyCode' => 'EURO',
                'callbackUrl' => 'https://example.com/callback',
                'successUrl' => 'https://example.com/success',
                'failureUrl' => 'https://example.com/failure'
            ]],
            'Invalid callbackUrl with missing TLD' => [[
                'orderId' => 'ORD123',
                'description' => 'Test order',
                'receiveAmount' => '500.23',
                'receiveCurrencyCode' => 'EUR',
                'callbackUrl' => 'http://example',
                'successUrl' => 'https://example.com/success',
                'failureUrl' => 'https://example.com/failure'
            ]],
            'Invalid callbackUrl with dot' => [[
                'orderId' => 'ORD123',
                'description' => 'Test order',
                'receiveAmount' => '500.23',
                'receiveCurrencyCode' => 'EUR',
                'callbackUrl' => 'http://example.',
                'successUrl' => 'https://example.com/success',
                'failureUrl' => 'https://example.com/failure'
            ]],
            'Invalid successUrl with double dot in domain' => [[
                'orderId' => 'ORD123',
                'description' => 'Test order',
                'receiveAmount' => '500.23',
                'receiveCurrencyCode' => 'EUR',
                'callbackUrl' => 'https://example.com/callback',
                'successUrl' => 'https://example..com',
                'failureUrl' => 'https://example.com/failure'
            ]],
            'Invalid failureUrl with invalid domain label' => [[
                'orderId' => 'ORD123',
                'description' => 'Test order',
                'receiveAmount' => '500.23',
                'receiveCurrencyCode' => 'EUR',
                'callbackUrl' => 'https://example.com/callback',
                'successUrl' => 'https://example.com/success',
                'failureUrl' => 'https://-example.com'
            ]],
            'Empty callbackUrl' => [[
                'orderId' => 'ORD123',
                'description' => 'Test order',
                'receiveAmount' => '500.23',
                'receiveCurrencyCode' => 'EUR',
                'callbackUrl' => '',
                'successUrl' => 'https://example.com/success',
                'failureUrl' => 'https://example.com/failure'
            ]],
            'Empty successUrl' => [[
                'orderId' => 'ORD123',
                'description' => 'Test order',
                'receiveAmount' => '500.23',
                'receiveCurrencyCode' => 'EUR',
                'callbackUrl' => 'https://example.com/callback',
                'successUrl' => '',
                'failureUrl' => 'https://example.com/failure'
            ]],
            'Empty failureUrl' => [[
                'orderId' => 'ORD123',
                'description' => 'Test order',
                'receiveAmount' => '500.23',
                'receiveCurrencyCode' => 'EUR',
                'callbackUrl' => 'https://example.com/callback',
                'successUrl' => 'https://example.com/success',
                'failureUrl' => ''
            ]],
        ];
    }
}
