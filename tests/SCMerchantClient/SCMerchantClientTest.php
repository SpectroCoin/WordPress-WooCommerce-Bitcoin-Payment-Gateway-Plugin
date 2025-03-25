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

    // createOrder()
    #[TestDox('Test createOrder() with valid data')]
    


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
}
