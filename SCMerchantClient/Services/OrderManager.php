<?php

declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient\Services;

use SpectroCoin\SCMerchantClient\Config;
use SpectroCoin\SCMerchantClient\Http\CreateOrderRequest;
use SpectroCoin\SCMerchantClient\Http\CreateOrderResponse;
use SpectroCoin\SCMerchantClient\Http\HttpClient;
use SpectroCoin\SCMerchantClient\Exceptions\ApiError;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

use InvalidArgumentException;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class OrderManager{

    private $project_id;
    private $token_manager;
    private $http_client;

    public function construct($project_id, $token_manager){
        $this->project_id = $project_id;
        $this->token_manager = $token_manager;

        $this->http_client = new Client();
    }

    public function createOrder($order_data)
    {
        $access_token_data = $this->token_manager->getAccessTokenData();

        if (!$access_token_data || $access_token_data instanceof ApiError) {
            return $access_token_data;
        }

        try {
            $create_order_request = new CreateOrderRequest(
                $order_data['orderId'],
                $order_data['description'],
                $order_data['payAmount'],
                $order_data['payCurrencyCode'],
                $order_data['receiveAmount'],
                $order_data['receiveCurrencyCode'],
                $order_data['callbackUrl'],
                $order_data['successUrl'],
                $order_data['failureUrl']
            );
        } catch (InvalidArgumentException $e) {
            return new ApiError(-1, $e->getMessage());
        }

        $order_payload = [
            "orderId" => $create_order_request->getOrderId(),
            "projectId" => $this->project_id,
            "description" => $create_order_request->getDescription(),
            "payAmount" => $create_order_request->getPayAmount(),
            "payCurrencyCode" => $create_order_request->getPayCurrencyCode(),
            "receiveAmount" => $create_order_request->getReceiveAmount(),
            "receiveCurrencyCode" => $create_order_request->getReceiveCurrencyCode(),
            'callbackUrl' => $create_order_request->getCallbackUrl(),
            'successUrl' => $create_order_request->getSuccessUrl(),
            'failureUrl' => $create_order_request->getFailureUrl()
        ];
        
        return $this->sendCreateOrderRequest(json_encode($order_payload));
    }

    private function sendCreateOrderRequest($order_payload)
    {
        try {
            $response = $this->http_client->request('POST', Config::MERCHANT_API_URL . '/merchants/orders/create', [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer ' . $this->token_manager->getAccessTokenData()['access_token'],
                    'Content-Type' => 'application/json'
                ],
                RequestOptions::BODY => $order_payload
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return new CreateOrderResponse(
                $body['preOrderId'],
                $body['orderId'],
                $body['validUntil'],
                $body['payCurrencyCode'],
                $body['payNetworkCode'],
                $body['receiveCurrencyCode'],
                $body['payAmount'],
                $body['receiveAmount'],
                $body['depositAddress'],
                $body['memo'],
                $body['redirectUrl']
            );

        } 
        catch (GuzzleException $e) {
            return new ApiError($e->getCode(), $e->getMessage());
        }
        return new ApiError('UnknownError', 'An unknown error occurred during order creation');
    }

}