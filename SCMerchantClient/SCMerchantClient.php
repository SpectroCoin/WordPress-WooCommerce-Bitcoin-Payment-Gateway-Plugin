<?php declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient;

use SpectroCoin\SCMerchantClient\Config;
use SpectroCoin\SCMerchantClient\Utils;
use SpectroCoin\SCMerchantClient\Exception\ApiError;
use SpectroCoin\SCMerchantClient\Exception\GenericError;
use SpectroCoin\SCMerchantClient\Http\CreateOrderRequest;
use SpectroCoin\SCMerchantClient\Http\CreateOrderResponse;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

use InvalidArgumentException;
use Exception;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;

// @codeCoverageIgnoreStart
if (!defined('ABSPATH')) {
    die('Access denied.');
}

require_once __DIR__ . '/../vendor/autoload.php';
// @codeCoverageIgnoreEnd
class SCMerchantClient
{
    private string $project_id;
    private string $client_id;
    private string $client_secret;

    protected Client $http_client;


    /**
     * SCMerchantClient constructor.
     *
     * Initializes the merchant client with the necessary project identifier and client credentials.
     *
     * @param string $project_id    The unique identifier for the project.
     * @param string $client_id     The client identifier used for authentication.
     * @param string $client_secret The secret key associated with the client.
     */
    public function __construct(string $project_id, string $client_id, string $client_secret)
    {
        $this->project_id = $project_id;
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;

        $this->http_client = new Client();
    }

    /**
     * Creates a new order.
     *
     * This method builds an order payload using the provided order data and the project identifier,
     * then sends a POST request to the merchant API to create the order. It handles JSON encoding,
     * response decoding, and error handling. Depending on the outcome, it returns a CreateOrderResponse,
     * an ApiError, or a GenericError.
     *
     * @param array $order_data         The data required for creating the order.
     * @param array $access_token_data  The access token data (must include the 'access_token' key) used for authorization.
     * 
     * @return CreateOrderResponse|ApiError|GenericError|null The response object containing order details or an error object if an error occurs.
     */
    public function createOrder(array $order_data, array $access_token_data)
    {
        try {
            $create_order_request = new CreateOrderRequest($order_data);
        } catch (InvalidArgumentException $e) {
            return new GenericError($e->getMessage(), $e->getCode());
        }

        $order_payload = $create_order_request->toArray();
        $order_payload['projectId'] = $this->project_id;

        try {
            $response = $this->http_client->request('POST', Config::MERCHANT_API_URL . '/merchants/orders/create', [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer ' . $access_token_data['access_token'],
                    'Content-Type' => 'application/json'
                ],
                RequestOptions::BODY => json_encode($order_payload)
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Failed to parse JSON response: ' . json_last_error_msg());
            }

            $responseData = [
                'preOrderId' => $body['preOrderId'] ?? null,
                'orderId' => $body['orderId'] ?? null,
                'validUntil' => $body['validUntil'] ?? null,
                'payCurrencyCode' => $body['payCurrencyCode'] ?? null,
                'payNetworkCode' => $body['payNetworkCode'] ?? null,
                'receiveCurrencyCode' => $body['receiveCurrencyCode'] ?? null,
                'payAmount' => $body['payAmount'] ?? null,
                'receiveAmount' => $body['receiveAmount'] ?? null,
                'depositAddress' => $body['depositAddress'] ?? null,
                'memo' => $body['memo'] ?? null,
                'redirectUrl' => $body['redirectUrl'] ?? null
            ];

            return new CreateOrderResponse($responseData);
        } catch (InvalidArgumentException $e) {
            return new GenericError($e->getMessage(), $e->getCode());
        } catch (RequestException $e) {
            return new ApiError($e->getMessage(), $e->getCode());
        } catch (Exception $e) {
            return new GenericError($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Retrieves the current access token data.
     *
     * This method performs a POST request to the authentication endpoint using the client credentials.
     * It decodes the JSON response to extract the access token and expiration information. If the response
     * is invalid or an error occurs, an ApiError is returned.
     *
     * @return array|ApiError|null An associative array containing the access token and expiration info if successful,
     *                             or an ApiError object if the request fails.
     */
    public function getAccessToken()
    {
        try {
            $response = $this->http_client->post(Config::AUTH_URL, [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->client_id,
                    'client_secret' => $this->client_secret,
                ],
            ]);

            $access_token_data = json_decode((string) $response->getBody(), true);

            if (!isset($access_token_data['access_token'], $access_token_data['expires_in'])) {
                return new ApiError('Invalid access token response');
            }
            return $access_token_data;
        }
        catch (RequestException $e) {
            return new ApiError($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Checks if the current access token is valid.
     *
     * This method determines whether the provided access token has expired by checking if an 'expires_at'
     * timestamp exists and comparing it with the current time.
     *
     * @param array $access_token_data An associative array containing access token details, including 'expires_at'.
     * @param int   $current_time      The current time as a Unix timestamp.
     * 
     * @return bool True if the token is valid (i.e., the current time is less than the 'expires_at' timestamp), false otherwise.
     */
    public function isTokenValid(array $access_token_data, int $current_time): bool
    {
        return isset($access_token_data['expires_at']) && $current_time < $access_token_data['expires_at'];
    }
}
