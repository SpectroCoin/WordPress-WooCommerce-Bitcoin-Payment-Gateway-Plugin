<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use SpectroCoin\SCMerchantClient\Http\CreateOrderResponse;
use SpectroCoin\SCMerchantClient\Utils;

#[CoversClass(CreateOrderResponse::class)]
#[UsesClass(Utils::class)]
class CreateOrderResponseTest extends TestCase
{

    #[DataProvider('validCreateOrderResponseProvider')]
    #[TestDox('Test CreateOrderResponse initialization with valid data')]
    public function testCreateOrderResponseWithValidData(array $order_data, $expected): void
    {
        $createOrderResponse = new CreateOrderResponse($order_data);
        $proccesed_order_response_array = [
            'preOrderId' => $createOrderResponse->getPreOrderId(),
            'orderId' => $createOrderResponse->getOrderId(),
            'validUntil' => $createOrderResponse->getValidUntil(),
            'payCurrencyCode' => $createOrderResponse->getPayCurrencyCode(),
            'payNetworkCode' => $createOrderResponse->getPayNetworkCode(),
            'receiveCurrencyCode' => $createOrderResponse->getreceiveCurrencyCode(),
            'payAmount' => $createOrderResponse->getPayAmount(),
            'receiveAmount' => $createOrderResponse->getReceiveAmount(),
            'depositAddress' => $createOrderResponse->getDepositAddress(),
            'memo' => $createOrderResponse->getMemo(),
            'redirectUrl' => $createOrderResponse->getRedirectUrl(),
        ];
        $this->assertSame($expected, $proccesed_order_response_array);
    }

    public static function validCreateOrderResponseProvider(): array
    {
        return [
            'Valid response data' => [
                [
                    'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                    'orderId' => 'ORD123',
                    'validUntil' => '2023-01-01T00:00:00Z',
                    'payCurrencyCode' => null,
                    'payNetworkCode' => null,
                    'receiveCurrencyCode' => 'SOL',
                    'payAmount' => null,
                    'receiveAmount' => '0.5',
                    'depositAddress' => null,
                    'memo' => 'test',
                    'redirectUrl' => 'https://spectrocoin.com/en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
                ],
                [
                    'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                    'orderId' => 'ORD123',
                    'validUntil' => '2023-01-01T00:00:00Z',
                    'payCurrencyCode' => null,
                    'payNetworkCode' => null,
                    'receiveCurrencyCode' => 'SOL',
                    'payAmount' => null,
                    'receiveAmount' => '0.5',
                    'depositAddress' => null,
                    'memo' => 'test',
                    'redirectUrl' => 'https://spectrocoin.com/en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
                ],
            ],
            'Valid response data with no "validUntil", "payCurrencyCode", "payNetworkCode", "payAmount", "depositAddress", "memo"' => [
                [
                    'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                    'orderId' => 'ORD123',
                    'validUntil' => '2023-01-01T00:00:00Z',
                    'receiveCurrencyCode' => 'SOL',
                    'receiveAmount' => '0.5',
                    'redirectUrl' => 'https://spectrocoin.com/en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
                ],
                [
                    'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                    'orderId' => 'ORD123',
                    'validUntil' => '2023-01-01T00:00:00Z',
                    'payCurrencyCode' => null,
                    'payNetworkCode' => null,
                    'receiveCurrencyCode' => 'SOL',
                    'payAmount' => null,
                    'receiveAmount' => '0.5',
                    'depositAddress' => null,
                    'memo' => null,
                    'redirectUrl' => 'https://spectrocoin.com/en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
                ],
            ],
            'Valid response data with random order' => [
                [
                    'payCurrencyCode' => null,
                    'validUntil' => '2023-01-01T00:00:00Z',
                    'payNetworkCode' => null,
                    'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                    'orderId' => 'ORD123',
                    'receiveCurrencyCode' => 'SOL',
                    'memo' => 'test',
                    'redirectUrl' => 'https://spectrocoin.com/en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
                    'receiveAmount' => '0.5',
                    'payAmount' => null,
                    'depositAddress' => null,
                ],
                [
                    'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                    'orderId' => 'ORD123',
                    'validUntil' => '2023-01-01T00:00:00Z',
                    'payCurrencyCode' => null,
                    'payNetworkCode' => null,
                    'receiveCurrencyCode' => 'SOL',
                    'payAmount' => null,
                    'receiveAmount' => '0.5',
                    'depositAddress' => null,
                    'memo' => 'test',
                    'redirectUrl' => 'https://spectrocoin.com/en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
                ],
            ],
        ];
    }


    #[DataProvider('invalidCreateOrderResponseProvider')]
    #[TestDox('Test CreateOrderResponse initialization with invalid data')]
    public function testCreateOrderResponseWithInvalidData(array $order_data): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CreateOrderResponse($order_data);
    }


    public static function invalidCreateOrderResponseProvider(): array
    {
        return [
            'No preOrderId' => [[
                'orderId' => 'ORD123',
                'validUntil' => '2023-01-01T00:00:00Z',
                'payCurrencyCode' => null,
                'payNetworkCode' => null,
                'receiveCurrencyCode' => 'SOL',
                'payAmount' => null,
                'receiveAmount' => '0.5',
                'depositAddress' => null,
                'memo' => null,
                'redirectUrl' => 'https://spectrocoin.com/en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
            ]],
            'No orderId' => [[
                'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                'validUntil' => '2023-01-01T00:00:00Z',
                'payCurrencyCode' => null,
                'payNetworkCode' => null,
                'receiveCurrencyCode' => 'SOL',
                'payAmount' => null,
                'receiveAmount' => '0.5',
                'depositAddress' => null,
                'memo' => null,
                'redirectUrl' => 'https://spectrocoin.com/en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
            ]],
            'receiveCurrencyCode is less than 3 characters (wrong format)' => [[
                'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                'orderId' => 'ORD123',
                'validUntil' => '2023-01-01T00:00:00Z',
                'payCurrencyCode' => null,
                'payNetworkCode' => null,
                'receiveCurrencyCode' => 'SO',
                'payAmount' => null,
                'receiveAmount' => '0.5',
                'depositAddress' => null,
                'memo' => null,
                'redirectUrl' => 'https://spectrocoin.com/en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
            ]],
            'receiveCurrencyCode is more than 3 characters (wrong format)' => [[
                'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                'orderId' => 'ORD123',
                'validUntil' => '2023-01-01T00:00:00Z',
                'payCurrencyCode' => null,
                'payNetworkCode' => null,
                'receiveCurrencyCode' => 'SOLA',
                'payAmount' => null,
                'receiveAmount' => '0.5',
                'depositAddress' => null,
                'memo' => null,
                'redirectUrl' => 'https://spectrocoin.com/en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
            ]],
            'receiveAmount is negative number' => [[
                'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                'orderId' => 'ORD123',
                'validUntil' => '2023-01-01T00:00:00Z',
                'payCurrencyCode' => null,
                'payNetworkCode' => null,
                'receiveCurrencyCode' => 'SOL',
                'payAmount' => null,
                'receiveAmount' => '-1',
                'depositAddress' => null,
                'memo' => null,
                'redirectUrl' => 'https://spectrocoin.com/en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
            ]],
            'receiveAmount is zero' => [[
                'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                'orderId' => 'ORD123',
                'validUntil' => '2023-01-01T00:00:00Z',
                'payCurrencyCode' => null,
                'payNetworkCode' => null,
                'receiveCurrencyCode' => 'SOL',
                'payAmount' => null,
                'receiveAmount' => '0',
                'depositAddress' => null,
                'memo' => null,
                'redirectUrl' => 'https://spectrocoin.com/en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
            ]],
            'redirectUrl with missing TLD' => [[
                'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                'orderId' => 'ORD123',
                'validUntil' => '2023-01-01T00:00:00Z',
                'payCurrencyCode' => null,
                'payNetworkCode' => null,
                'receiveCurrencyCode' => 'SOL',
                'payAmount' => null,
                'receiveAmount' => '10',
                'depositAddress' => null,
                'memo' => null,
                'redirectUrl' => 'https://spectrocoin/en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
            ]],
            'redirectUrl with dot' => [[
                'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                'orderId' => 'ORD123',
                'validUntil' => '2023-01-01T00:00:00Z',
                'payCurrencyCode' => null,
                'payNetworkCode' => null,
                'receiveCurrencyCode' => 'SOL',
                'payAmount' => null,
                'receiveAmount' => '10',
                'depositAddress' => null,
                'memo' => null,
                'redirectUrl' => 'https://spectrocoin./en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
            ]],
            'redirectUrl with double dot' => [[
                'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                'orderId' => 'ORD123',
                'validUntil' => '2023-01-01T00:00:00Z',
                'payCurrencyCode' => null,
                'payNetworkCode' => null,
                'receiveCurrencyCode' => 'SOL',
                'payAmount' => null,
                'receiveAmount' => '10',
                'depositAddress' => null,
                'memo' => null,
                'redirectUrl' => 'https://spectrocoin..com/en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
            ]],
            'redirectUrl with invalid domain label' => [[
                'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                'orderId' => 'ORD123',
                'validUntil' => '2023-01-01T00:00:00Z',
                'payCurrencyCode' => null,
                'payNetworkCode' => null,
                'receiveCurrencyCode' => 'SOL',
                'payAmount' => null,
                'receiveAmount' => '10',
                'depositAddress' => null,
                'memo' => null,
                'redirectUrl' => 'https://-spectrocoin.com/en/payment/preorder/07d9469e-aa78-4263-a12b-25da82390de3',
            ]],
            'Empty redirectUrl' => [[
                'preOrderId' => '07d9469e-aa78-4263-a12b-25da82390de3',
                'orderId' => 'ORD123',
                'validUntil' => '2023-01-01T00:00:00Z',
                'payCurrencyCode' => null,
                'payNetworkCode' => null,
                'receiveCurrencyCode' => 'SOL',
                'payAmount' => null,
                'receiveAmount' => '10',
                'depositAddress' => null,
                'memo' => null,
                'redirectUrl' => '',
            ]],
        ];
    }
}
