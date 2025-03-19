<?php declare(strict_types=1);

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

#[CoversClass(SCMerchantClient::class)]
#[UsesClass(Config::class)]
#[UsesClass(Utils::class)]
#[UsesClass(ApiError::class)]
#[UsesClass(GenericError::class)]
#[UsesClass(CreateOrderRequest::class)]
#[UsesClass(CreateOrderResponse::class)]
class SCMerchantClientTest extends TestCase
{

}