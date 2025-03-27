<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use SpectroCoin\SCMerchantClient\SCMerchantClient;
use SpectroCoin\SCMerchantClient\Config;
use SpectroCoin\SCMerchantClient\Utils;
use SpectroCoin\SCMerchantClient\Exception\ApiError;
use SpectroCoin\SCMerchantClient\Exception\GenericError;
use SpectroCoin\SCMerchantClient\Http\CreateOrderRequest;
use SpectroCoin\SCMerchantClient\Http\CreateOrderResponse;
use DotenvVault\DotenvVault;

#[CoversClass(SCMerchantClient::class)]
#[UsesClass(Config::class)]
#[UsesClass(Utils::class)]
#[UsesClass(ApiError::class)]
#[UsesClass(GenericError::class)]
#[UsesClass(CreateOrderRequest::class)]
#[UsesClass(CreateOrderResponse::class)]
class SCMerchantClientTest extends TestCase
{
    private $dotenv;
    private $client_id;
    private $client_secret;
    private $project_id;
    private $sc_client;

    private static $cachedToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dotenv = DotenvVault::createImmutable(__DIR__ . '/..', '.env');
        $this->dotenv->safeLoad();

        $this->project_id = $_SERVER['PROJECT_ID'];
        $this->client_id = $_SERVER['CLIENT_ID'];
        $this->client_secret = $_SERVER['CLIENT_SECRET'];

        $this->sc_client = new SCMerchantClient($this->project_id, $this->client_id, $this->client_secret);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getCachedToken() {
        if (self::$cachedToken && $this->sc_client->isTokenValid(self::$cachedToken, time())) {
            return self::$cachedToken;
        }
        self::$cachedToken = $this->sc_client->getAccessToken();
        if (!isset(self::$cachedToken['expires_at'])) {
            self::$cachedToken['expires_at'] = time() + self::$cachedToken['expires_in'];
        }
        return self::$cachedToken;
    }

    // createOrder()
    #[TestDox('Test createOrder() with valid data')]
    public function testCreateOrderValid(): void{
        $order_data = [
            'orderId' => 'order' . rand(1, 1000),
            'description' => 'Test order',
            'receiveAmount' => '1.00',
            'receiveCurrencyCode' => 'EUR',
            'callbackUrl' => 'https://example.com/callback',
            'successUrl' => 'https://example.com/success',
            'failureUrl' => 'https://example.com/failure',
        ];
        $access_token_data = $this->getCachedToken();

        $response = $this->sc_client->createOrder($order_data, $access_token_data);
        $this->assertInstanceOf(CreateOrderResponse::class, $response, 'Response should be an instance of CreateOrderResponse.');
        $this->assertIsString($response->getOrderId(), 'Order ID should be a string.');
        $this->assertNotEmpty($response->getOrderId(), 'Order ID should not be empty.');
        $this->assertIsString($response->getRedirectUrl(), 'Redirect URL should be a string.');
        $this->assertNotEmpty($response->getRedirectUrl(), 'Redirect URL should not be empty.');
    }

    #[DataProvider('invalidCreateOrderDataProvider')]
    #[TestDox('Test createOrder() with invalid data')]
    public function testCreateOrderWithInvalid(array $order_data, ?array $token_data, string $expectedErrorClass): void {
        // Use the cached token if token_data is not provided by the test case.
        if ($token_data === null) {
            $token_data = $this->getCachedToken();
        }
        $response = $this->sc_client->createOrder($order_data, $token_data);
        $this->assertInstanceOf($expectedErrorClass, $response, "Expected an instance of {$expectedErrorClass}.");
    }
    
    public static function invalidCreateOrderDataProvider(): array {
        return [
            'Empty order data' => [
                'order_data' => [],
                'token_data' => null, // use cached token
                'expectedErrorClass' => \SpectroCoin\SCMerchantClient\Exception\GenericError::class,
            ],
            'Missing orderId' => [
                'order_data' => [
                    // 'orderId' omitted
                    'description' => 'Test order',
                    'receiveAmount' => '1.00',
                    'receiveCurrencyCode' => 'EUR',
                    'callbackUrl' => 'https://example.com/callback',
                    'successUrl' => 'https://example.com/success',
                    'failureUrl' => 'https://example.com/failure',
                ],
                'token_data' => null,
                'expectedErrorClass' => \SpectroCoin\SCMerchantClient\Exception\GenericError::class,
            ],
            'Missing description' => [
                'order_data' => [
                    'orderId' => 'order' . rand(1, 1000),
                    // 'description' omitted
                    'receiveAmount' => '1.00',
                    'receiveCurrencyCode' => 'EUR',
                    'callbackUrl' => 'https://example.com/callback',
                    'successUrl' => 'https://example.com/success',
                    'failureUrl' => 'https://example.com/failure',
                ],
                'token_data' => null,
                'expectedErrorClass' => \SpectroCoin\SCMerchantClient\Exception\GenericError::class,
            ],
            'Missing receiveAmount' => [
                'order_data' => [
                    'orderId' => 'order' . rand(1, 1000),
                    'description' => 'Test order',
                    // 'receiveAmount' omitted
                    'receiveCurrencyCode' => 'EUR',
                    'callbackUrl' => 'https://example.com/callback',
                    'successUrl' => 'https://example.com/success',
                    'failureUrl' => 'https://example.com/failure',
                ],
                'token_data' => null,
                'expectedErrorClass' => \SpectroCoin\SCMerchantClient\Exception\GenericError::class,
            ],
            'Missing receiveCurrencyCode' => [
                'order_data' => [
                    'orderId' => 'order' . rand(1, 1000),
                    'description' => 'Test order',
                    'receiveAmount' => '1.00',
                    // 'receiveCurrencyCode' omitted
                    'callbackUrl' => 'https://example.com/callback',
                    'successUrl' => 'https://example.com/success',
                    'failureUrl' => 'https://example.com/failure',
                ],
                'token_data' => null,
                'expectedErrorClass' => \SpectroCoin\SCMerchantClient\Exception\GenericError::class,
            ],
            'Invalid callbackUrl' => [
                'order_data' => [
                    'orderId' => 'order' . rand(1, 1000),
                    'description' => 'Test order',
                    'receiveAmount' => '1.00',
                    'receiveCurrencyCode' => 'EUR',
                    'callbackUrl' => 'not_a_valid_url',
                    'successUrl' => 'https://example.com/success',
                    'failureUrl' => 'https://example.com/failure',
                ],
                'token_data' => null,
                'expectedErrorClass' => \SpectroCoin\SCMerchantClient\Exception\GenericError::class,
            ],
            'Invalid successUrl' => [
                'order_data' => [
                    'orderId' => 'order' . rand(1, 1000),
                    'description' => 'Test order',
                    'receiveAmount' => '1.00',
                    'receiveCurrencyCode' => 'EUR',
                    'callbackUrl' => 'https://example.com/callback',
                    'successUrl' => 'invalid_url',
                    'failureUrl' => 'https://example.com/failure',
                ],
                'token_data' => null,
                'expectedErrorClass' => \SpectroCoin\SCMerchantClient\Exception\GenericError::class,
            ],
            'Invalid failureUrl' => [
                'order_data' => [
                    'orderId' => 'order' . rand(1, 1000),
                    'description' => 'Test order',
                    'receiveAmount' => '1.00',
                    'receiveCurrencyCode' => 'EUR',
                    'callbackUrl' => 'https://example.com/callback',
                    'successUrl' => 'https://example.com/success',
                    'failureUrl' => 'invalid_url',
                ],
                'token_data' => null,
                'expectedErrorClass' => \SpectroCoin\SCMerchantClient\Exception\GenericError::class,
            ],
        ];
    }
    


    // getAccessToken()
    #[TestDox('Test getAccessToken() with valid data')]
    public function testGetAccessTokenValid()
    {
        $access_token_data = $this->sc_client->getAccessToken();
        $this->assertIsArray($access_token_data, "Response should be array");
        $this->assertArrayHasKey('access_token', $access_token_data, "Has access_token key.");
        $this->assertIsString($access_token_data['access_token'], "access_token should be a string.");
        $this->assertNotEmpty($access_token_data['access_token'], "access_token should not be empty.");
        $this->assertArrayHasKey('expires_in', $access_token_data, "Missing expires_in key.");
        $this->assertIsInt($access_token_data['expires_in'], "expires_in should be an integer.");
        $this->assertGreaterThan(0, $access_token_data['expires_in'], "expires_in should be greater than 0.");
        $this->assertArrayHasKey('token_type', $access_token_data, "Missing token_type key.");
        $this->assertSame('bearer', strtolower($access_token_data['token_type']), "token_type should be 'bearer'.");
    }

    #[DataProvider('invalidClientCredentialsProvider')]
    #[TestDox('Test getAccessToken() with invalid data')]
    public function testGetAccessTokenInvalid($client_id, $client_secret): void {
        $sc_client = new SCMerchantClient("dummy_project", $client_id, $client_secret);
        $response = $sc_client->getAccessToken();
    
        $this->assertIsObject($response, 'Response should be an object.');
    
        $this->assertInstanceOf(ApiError::class, $response, 'Response should be an instance of ApiError.');
    
        $this->assertIsString($response->getMessage(), 'Error message should be a string.');
        $this->assertNotEmpty($response->getMessage(), 'Error message should not be empty.');
    
        $this->assertIsInt($response->getCode(), 'Error code should be an integer.');

        $this->assertSame($response->getCode(), 401);
    }
    

    public static function invalidClientCredentialsProvider(): array
    {
        return [
            'All credentials invalid' => [
                'dummy_client',
                'dummy_secret',
            ],
            'Only client_id is valid' => [
                $_SERVER['CLIENT_ID'],
                'dummy_secret',
            ],
            'Only client_secret is valid' => [
                'dummy_client',
                $_SERVER['CLIENT_SECRET'],
            ],
        ];
    }

    // IsTokenValid
    #[DataProvider('validTokenProvider')]
    #[TestDox('Test IsTokenValid() with return value as true')]
    public function testIsTokenValidReturnsTrue(array $tokenData, int $currentTime): void
    {
        $client = new SCMerchantClient('dummy_project', 'dummy_client', 'dummy_secret');
        $this->assertTrue($client->isTokenValid($tokenData, $currentTime));
    }

    #[DataProvider('invalidTokenProvider')]
    #[TestDox('Test IsTokenValid() with return value as false')]
    public function testIsTokenValidReturnsFalse(array $tokenData, int $currentTime): void
    {
        $client = new SCMerchantClient('dummy_project', 'dummy_client', 'dummy_secret');
        $this->assertFalse($client->isTokenValid($tokenData, $currentTime));
    }

    public static function validTokenProvider(): array
    {
        return [
            'Valid token (expires in future)' => [
                ['expires_at' => time() + 100],
                time(),
            ],
        ];
    }

    public static function invalidTokenProvider(): array
    {
        return [
            'Token expires now' => [
                ['expires_at' => time()],
                time(),
            ],
            'Token expired (past)' => [
                ['expires_at' => time() - 100],
                time(),
            ],
            'Missing expires_at key' => [
                [],
                time(),
            ],
            'Null expires_at' => [
                ['expires_at' => null],
                time(),
            ],
        ];
    }

   /**
     * Inject a custom HTTP client into the SCMerchantClient instance.
     *
     * @param SCMerchantClient $client
     * @param \GuzzleHttp\Client $httpClient
     */
    private function setHttpClient(SCMerchantClient $client, \GuzzleHttp\Client $httpClient): void
    {
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('http_client');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);
    }

    #[TestDox('Test createOrder() handles an InvalidArgumentException by returning a GenericError')]
    public function testCreateOrderHandlesInvalidArgumentException(): void
    {
        $client = new SCMerchantClient('dummy_project', 'dummy_client', 'dummy_secret');
        
        $order_data = [];
        $token_data = ['access_token' => 'dummy_token', 'expires_at' => time() + 100];

        $response = $client->createOrder($order_data, $token_data);
        $this->assertInstanceOf(
            \SpectroCoin\SCMerchantClient\Exception\GenericError::class,
            $response,
            'Expected GenericError on InvalidArgumentException'
        );
        $this->assertStringContainsString("Invalid order creation payload", $response->getMessage());
    }

    #[TestDox('Test createOrder() handles a RequestException by returning an ApiError')]
    public function testCreateOrderHandlesRequestException(): void
    {
        $client = new SCMerchantClient('dummy_project', 'dummy_client', 'dummy_secret');
        
        $mockHttpClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockHttpClient->method('request')->will(
            $this->throwException(new \GuzzleHttp\Exception\RequestException(
                "Request error",
                new \GuzzleHttp\Psr7\Request('POST', 'test')
            ))
        );
        
        $this->setHttpClient($client, $mockHttpClient);

        $order_data = [
            'orderId'             => 'order' . rand(1, 1000),
            'description'         => 'Test order',
            'receiveAmount'       => '1.00',
            'receiveCurrencyCode' => 'EUR',
            'callbackUrl'         => 'https://example.com/callback',
            'successUrl'          => 'https://example.com/success',
            'failureUrl'          => 'https://example.com/failure',
        ];
        $token_data = ['access_token' => 'dummy_token', 'expires_at' => time() + 100];

        $response = $client->createOrder($order_data, $token_data);
        $this->assertInstanceOf(
            \SpectroCoin\SCMerchantClient\Exception\ApiError::class,
            $response,
            'Expected ApiError on RequestException'
        );
        $this->assertStringContainsString("Request error", $response->getMessage());
    }

    #[TestDox('Test createOrder() handles a general Exception by returning a GenericError')]
    public function testCreateOrderHandlesGeneralException(): void
    {
        $client = new SCMerchantClient('dummy_project', 'dummy_client', 'dummy_secret');
        
        $mockHttpClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockHttpClient->method('request')->will(
            $this->throwException(new \Exception("General error"))
        );
        
        $this->setHttpClient($client, $mockHttpClient);

        $order_data = [
            'orderId'             => 'order' . rand(1, 1000),
            'description'         => 'Test order',
            'receiveAmount'       => '1.00',
            'receiveCurrencyCode' => 'EUR',
            'callbackUrl'         => 'https://example.com/callback',
            'successUrl'          => 'https://example.com/success',
            'failureUrl'          => 'https://example.com/failure',
        ];
        $token_data = ['access_token' => 'dummy_token', 'expires_at' => time() + 100];

        $response = $client->createOrder($order_data, $token_data);
        $this->assertInstanceOf(
            \SpectroCoin\SCMerchantClient\Exception\GenericError::class,
            $response,
            'Expected GenericError on general Exception'
        );
        $this->assertStringContainsString("General error", $response->getMessage());
    }

    #[TestDox('Test createOrder() handles invalid JSON response by returning a GenericError')]
    public function testCreateOrderHandlesInvalidJsonResponse(): void
    {
        $client = new SCMerchantClient('dummy_project', 'dummy_client', 'dummy_secret');
        
        $responseMock = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $streamMock = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $streamMock->method('getContents')->willReturn('invalid_json');
        $responseMock->method('getBody')->willReturn($streamMock);
        
        $mockHttpClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockHttpClient->method('request')->willReturn($responseMock);
        
        $this->setHttpClient($client, $mockHttpClient);

        $order_data = [
            'orderId'             => 'order' . rand(1, 1000),
            'description'         => 'Test order',
            'receiveAmount'       => '1.00',
            'receiveCurrencyCode' => 'EUR',
            'callbackUrl'         => 'https://example.com/callback',
            'successUrl'          => 'https://example.com/success',
            'failureUrl'          => 'https://example.com/failure',
        ];
        $token_data = ['access_token' => 'dummy_token', 'expires_at' => time() + 100];

        $response = $client->createOrder($order_data, $token_data);
        $this->assertInstanceOf(
            \SpectroCoin\SCMerchantClient\Exception\GenericError::class,
            $response,
            'Expected GenericError when JSON decoding fails'
        );
        $this->assertStringContainsString("Failed to parse JSON response", $response->getMessage());
    }

    #[TestDox('Test getAccessToken() handles a RequestException by returning an ApiError')]
    public function testGetAccessTokenHandlesRequestException(): void
    {
        $client = new SCMerchantClient('dummy_project', 'dummy_client', 'dummy_secret');
        
        $mockHttpClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockHttpClient->method('post')->will(
            $this->throwException(new \GuzzleHttp\Exception\RequestException(
                "Auth Request error",
                new \GuzzleHttp\Psr7\Request('POST', 'test')
            ))
        );
        
        $this->setHttpClient($client, $mockHttpClient);

        $response = $client->getAccessToken();
        $this->assertInstanceOf(
            \SpectroCoin\SCMerchantClient\Exception\ApiError::class,
            $response,
            'Expected ApiError on RequestException in getAccessToken()'
        );
        $this->assertStringContainsString("Auth Request error", $response->getMessage());
    }

    #[TestDox('Test getAccessToken() handles invalid access token response by returning an ApiError')]
    public function testGetAccessTokenHandlesInvalidAccessTokenResponse(): void
    {
        $client = new SCMerchantClient('dummy_project', 'dummy_client', 'dummy_secret');
        
        $invalidTokenResponse = json_encode([]); // returns "{}"
        
        $responseMock = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $streamMock = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        // getAccessToken() casts the response body to string.
        $streamMock->method('__toString')->willReturn($invalidTokenResponse);
        $responseMock->method('getBody')->willReturn($streamMock);
        
        $mockHttpClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockHttpClient->method('post')->willReturn($responseMock);
        
        $this->setHttpClient($client, $mockHttpClient);
        
        $response = $client->getAccessToken();
        $this->assertInstanceOf(
            \SpectroCoin\SCMerchantClient\Exception\ApiError::class,
            $response,
            'Expected ApiError when access token response is invalid'
        );
        $this->assertEquals('Invalid access token response', $response->getMessage());
    }

    #[TestDox('Test createOrder() handles a general Exception thrown by getContents() as GenericError')]
    public function testCreateOrderHandlesExceptionInGetContents(): void
    {
        $client = new SCMerchantClient('dummy_project', 'dummy_client', 'dummy_secret');

        $streamMock = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $streamMock->method('getContents')->will($this->throwException(new \Exception("Stream error")));

        $responseMock = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $responseMock->method('getBody')->willReturn($streamMock);

        $mockHttpClient = $this->createMock(\GuzzleHttp\Client::class);
        $mockHttpClient->method('request')->willReturn($responseMock);

        $this->setHttpClient($client, $mockHttpClient);

        $order_data = [
            'orderId'             => 'order' . rand(1, 1000),
            'description'         => 'Test order',
            'receiveAmount'       => '1.00',
            'receiveCurrencyCode' => 'EUR',
            'callbackUrl'         => 'https://example.com/callback',
            'successUrl'          => 'https://example.com/success',
            'failureUrl'          => 'https://example.com/failure',
        ];
        $token_data = ['access_token' => 'dummy_token', 'expires_at' => time() + 100];

        $response = $client->createOrder($order_data, $token_data);

        $this->assertInstanceOf(
            \SpectroCoin\SCMerchantClient\Exception\GenericError::class,
            $response,
            'Expected GenericError when an exception is thrown during getContents()'
        );
        $this->assertStringContainsString("Stream error", $response->getMessage());
    }
}
