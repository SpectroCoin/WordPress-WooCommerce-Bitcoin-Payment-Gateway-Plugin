<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use SpectroCoin\SCMerchantClient\Http\OrderCallback;
use SpectroCoin\SCMerchantClient\Utils;
use SpectroCoin\SCMerchantClient\Config;

#[CoversClass(OrderCallback::class)]
#[UsesClass(Utils::class)]
class OrderCallbackTest extends TestCase
{
    private static string $testPrivateKey = <<<EOD
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCZ+254UtC8mXk8
VE2WHN68NctO7SsRthRQDVCGl6KNuNYZt4Lcu4DKVwXoRZyNpA/yNUqsDAyZfkoV
e001zGwuR2oPRLUzcSPO2QnCj70HyCXMkFjh5xt42oN80FTimWXCKqBTaKN++uR+
4jIw3YfR490jRE+ycSYFup/tpAgxB/+q1Ub6/me62DzfMjbm7JhsG4OrJ7flk0jx
r5XI0V4M9LDqbsPsov6rfgAXWeRf4tEGolR9INMcllN+06LXU+T46Q8/BGwegux9
q2c5CEN3IsirU29sOD5xw9Ps2FFbL7dVYDynYF+V7Tlo69eqphJONzq+XdnbguDV
dKw4yOXXAgMBAAECggEAR7hJivefy8h8JHJrtNh7khRIPtnOrrAtI+AfuqDUEMif
yFimNiOLaDNCSB5sPbjFyJ2zyxDNqYyd+wV0P/OYC7ItnzD0aSJweD61Ag8rD33U
xUQSch3PuOmmRrNOZkDmmZp7FH5vcIxxcvvuPr5gLY3BiVSe/lEfUZnMNcaqHhOP
OYO/9T6Wcbl5lNGy1mvcllPLf0E1O3m1y/UrNiUdpnh1k9tI1BMZ/L0xF9IB4IeX
UMa87q7TJlTq2n3dH1mUfa4WekPXoXfoFRpoNmwbD4Nu3l4Jj1gCw8+FPbLmu2uR
wPl8jIvV74hu1xQPTVo321vHkhPWFNe3DEpwxHzHaQKBgQC4gY+WlBOOeUaylPSX
kUzJLLCNpE6PfyuLULMm1MXRIvMHhFPnsX4/tx1jyw/Hnq5hlT2iDHxkreaEX9m3
IGlyxNWICPfBwfIiLztPvBjj2SutM93MtGlBr8uxwA61H07jYAbL6zhM2n9VO1+/
ZpesJl0WhdqcDJSPYB+0wz8/uwKBgQDVpf01WJA52790P2UADkYbVD8xaWBLffjT
eoiwX9FzNE+BZGud/+sN36Cktap32FkLQyY8fwTYQtuk5CWXxYTx3raC7DHV6zdz
sJk5qAXHQu7tW+TGaHVb8l2ivTyR22WJGozYfCWjFCOonuAzU2qD7nlpUaKqIcZw
5CGSwPqKlQKBgERXYStCT/ge/cvaFrspi8qcbg0ZBixqy2NAEgvZFijADEsFfdq9
SOkq140GQyMKqMbmc7zZaR4Vt+PiaQ9GxyhGtl08DSFMyHZXDl4baxDCeUYfhxFy
5eX5yrZdUFVQcnUQNZRE3UbCTBXy9yU7SATw9NwJ2o6grkppLXVUONJPAoGBAKTM
PNyBS/7VOoD4xXediwZZncUHe3e/28eEpRsnTfCHUSyFwV1GopE5BjkGyE9ZWpYN
XdkcQShvqe0u7rB7c2j+WdnzRx2zKjra5dZLfOmO/62nTHie6qfZ89AsGCSKD3AK
QspaOM3qIvdWVapBlc/ei1hp2AdTtjYuQpdos9lhAoGAVkVikqJNa61nbD/HyiIX
I2EMOl8vkIzuUYnNz/RWcxOojO7ZBKLb3NZ2JxBTAgJiYaUO9BDRcUoL1NwbBwvL
9/vIFE+C5TWY6YGfnfiV62pOzaFGcQCxnXLpx4ahiTnkAx671+J/sE5Orvypt4R4
W2uPMMtfmfBooIiRoT6XNdY=
-----END PRIVATE KEY-----
EOD;

    private static string $testPublicKey = <<<EOD
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAmftueFLQvJl5PFRNlhze
vDXLTu0rEbYUUA1QhpeijbjWGbeC3LuAylcF6EWcjaQP8jVKrAwMmX5KFXtNNcxs
LkdqD0S1M3EjztkJwo+9B8glzJBY4ecbeNqDfNBU4pllwiqgU2ijfvrkfuIyMN2H
0ePdI0RPsnEmBbqf7aQIMQf/qtVG+v5nutg83zI25uyYbBuDqye35ZNI8a+VyNFe
DPSw6m7D7KL+q34AF1nkX+LRBqJUfSDTHJZTftOi11Pk+OkPPwRsHoLsfatnOQhD
dyLIq1NvbDg+ccPT7NhRWy+3VWA8p2Bfle05aOvXqqYSTjc6vl3Z24Lg1XSsOMjl
1wIDAQAB
-----END PUBLIC KEY-----
EOD;

    private static string $tempPublicKeyFile;

    public static function setUpBeforeClass(): void
    {
        self::$tempPublicKeyFile = tempnam(sys_get_temp_dir(), 'pubkey_');
        file_put_contents(self::$tempPublicKeyFile, self::$testPublicKey);
    }

    public static function tearDownAfterClass(): void
    {
        if (file_exists(self::$tempPublicKeyFile)) {
            unlink(self::$tempPublicKeyFile);
        }
    }


    #[TestDox('Test valid OrderCallback initialization')]
    public function testValidOrderCallback(): void
    {
        $payload = $this->getValidPayload();
        $payload['sign'] = $this->signPayload($payload);

        $orderCallback = new OrderCallback($payload, self::$tempPublicKeyFile);
        $this->assertInstanceOf(OrderCallback::class, $orderCallback);

        $this->assertEquals($payload['userId'], $orderCallback->getUserId());
        $this->assertEquals($payload['merchantApiId'], $orderCallback->getMerchantApiId());
        $this->assertEquals($payload['merchantId'], $orderCallback->getMerchantId());
        $this->assertEquals($payload['apiId'], $orderCallback->getApiId());
        $this->assertEquals($payload['orderId'], $orderCallback->getOrderId());
        $this->assertEquals($payload['payCurrency'], $orderCallback->getPayCurrency());
        $this->assertEquals(Utils::formatCurrency($payload['payAmount']), $orderCallback->getPayAmount());
        $this->assertEquals($payload['receiveCurrency'], $orderCallback->getReceiveCurrency());
        $this->assertEquals(Utils::formatCurrency($payload['receiveAmount']), $orderCallback->getReceiveAmount());
        $this->assertEquals(Utils::formatCurrency($payload['receivedAmount']), $orderCallback->getReceivedAmount());
        $this->assertEquals($payload['description'], $orderCallback->getDescription());
        $this->assertEquals($payload['orderRequestId'], $orderCallback->getOrderRequestId());
        $this->assertEquals($payload['status'], $orderCallback->getStatus());
        $this->assertEquals($payload['sign'], $orderCallback->getSign());
    }

        private function getValidPayload(): array
    {
        return [
            'userId'         => '1ba2fe21-4f94-4ae1-ba43-ba5e5be11057',
            'merchantApiId'  => 'e8fc7ded-0c54-49bd-b598-cec1bfd42ed6',
            'merchantId'     => '1387551',
            'apiId'          => '101984',
            'orderId'        => '291828',
            'payCurrency'    => 'SOL',
            'payAmount'      => '10',
            'receiveCurrency'=> 'EUR',
            'receiveAmount'  => '100',
            'receivedAmount' => '100',
            'description'    => 'test',
            'orderRequestId' => '14799205',
            'status'         => '3',
        ];
    }

    private function signPayload(array $payload): string
    {
        $data = http_build_query([
            'merchantId'     => $payload['merchantId'],
            'apiId'          => $payload['apiId'],
            'orderId'        => $payload['orderId'],
            'payCurrency'    => $payload['payCurrency'],
            'payAmount'      => Utils::formatCurrency($payload['payAmount']),
            'receiveCurrency'=> $payload['receiveCurrency'],
            'receiveAmount'  => Utils::formatCurrency($payload['receiveAmount']),
            'receivedAmount' => Utils::formatCurrency($payload['receivedAmount']),
            'description'    => $payload['description'],
            'orderRequestId' => $payload['orderRequestId'],
            'status'         => $payload['status'],
        ]);
        $signature = '';
        openssl_sign($data, $signature, self::$testPrivateKey, OPENSSL_ALGO_SHA1);
        return base64_encode($signature);
    }

    #[TestDox('Test OrderCallback initialization with invalid signature')]
    public function testInvalidSignature(): void
    {
        $payload = $this->getValidPayload();
        $payload['sign'] = 'invalidsignature';
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid payload signature.');
        new OrderCallback($payload, self::$tempPublicKeyFile);
    }


    #[DataProvider('invalidPayloadProvider')]
    #[TestDox('Test OrderCallback with invalid payload: {0}')]
    public function testInvalidPayload(array $modifications, string $expectedError): void
    {
        $payload = $this->getValidPayload();
        foreach ($modifications as $key => $value) {
            if ($value === null) {
                unset($payload[$key]);
            } else {
                $payload[$key] = $value;
            }
        }
    
        if (
            array_key_exists('payAmount', $modifications) ||
            array_key_exists('receiveAmount', $modifications) ||
            array_key_exists('receivedAmount', $modifications)
        ) {
            $payload['sign'] = 'dummy-signature';
        } else {
            $payload['sign'] = $this->signPayload($payload);
        }
    
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedError);
        new OrderCallback($payload, self::$tempPublicKeyFile);
    }
    
    public static function invalidPayloadProvider(): array
    {
        return [
            'empty userId' => [
                ['userId' => ''],
                'userId is empty'
            ],
            'empty merchantApiId' => [
                ['merchantApiId' => ''],
                'merchantApiId is empty'
            ],
            'empty merchantId' => [
                ['merchantId' => ''],
                'merchantId is empty'
            ],
            'empty apiId' => [
                ['apiId' => ''],
                'apiId is empty'
            ],
            'empty orderId' => [
                ['orderId' => ''],
                'orderId is empty'
            ],
            'empty status' => [
                ['status' => ''],
                'status is empty'
            ],
            'invalid payCurrency (too short)' => [
                ['payCurrency' => 'SO'],
                'payCurrency is not 3 characters long'
            ],
            'invalid payCurrency (too long)' => [
                ['payCurrency' => 'SOLX'],
                'payCurrency is not 3 characters long'
            ],
            'non-numeric payAmount' => [
                ['payAmount' => 'abc'],
                'The provided amount must be numeric.'
            ],
            'zero payAmount' => [
                ['payAmount' => '0'],
                'payAmount is not a valid positive number'
            ],
            'negative payAmount' => [
                ['payAmount' => '-5'],
                'payAmount is not a valid positive number'
            ],
            'invalid receiveCurrency (too short)' => [
                ['receiveCurrency' => 'EU'],
                'receiveCurrency is not 3 characters long'
            ],
            'invalid receiveCurrency (too long)' => [
                ['receiveCurrency' => 'EURO'],
                'receiveCurrency is not 3 characters long'
            ],
            'non-numeric receiveAmount' => [
                ['receiveAmount' => 'abc'],
                'The provided amount must be numeric.'
            ],
            'zero receiveAmount' => [
                ['receiveAmount' => '0'],
                'receiveAmount is not a valid positive number'
            ],
            'negative receiveAmount' => [
                ['receiveAmount' => '-50'],
                'receiveAmount is not a valid positive number'
            ],
            'missing receivedAmount' => [
                ['receivedAmount' => null],
                'receivedAmount is not set'
            ],
            'non-numeric orderRequestId' => [
                ['orderRequestId' => 'abc'],
                'orderRequestId is not a valid positive number'
            ],
            'zero orderRequestId' => [
                ['orderRequestId' => '0'],
                'orderRequestId is not a valid positive number'
            ],
            'negative orderRequestId' => [
                ['orderRequestId' => '-10'],
                'orderRequestId is not a valid positive number'
            ],
        ];
    }

    #[TestDox('Test OrderCallback with multiple validation errors')]
    public function testMultipleValidationErrors(): void
    {
        $payload = $this->getValidPayload();
        $payload['userId'] = '';
        $payload['payCurrency'] = 'XX';
        $payload['payAmount'] = '-10';
        $payload['orderRequestId'] = '0';
        $payload['status'] = '';
        // Sign the modified payload.
        $payload['sign'] = $this->signPayload($payload);
        $this->expectException(\InvalidArgumentException::class);
        try {
            new OrderCallback($payload, self::$tempPublicKeyFile);
        } catch (\InvalidArgumentException $ex) {
            $message = $ex->getMessage();
            $this->assertStringContainsString('userId is empty', $message);
            $this->assertStringContainsString('status is empty', $message);
            $this->assertStringContainsString('payCurrency is not 3 characters long', $message);
            $this->assertStringContainsString('payAmount is not a valid positive number', $message);
            $this->assertStringContainsString('orderRequestId is not a valid positive number', $message);
            throw $ex;
        }
    }
}
