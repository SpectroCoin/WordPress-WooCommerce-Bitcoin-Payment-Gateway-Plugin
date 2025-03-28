<?php declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient\Tests;

use PHPUnit\Framework\TestCase;
use SpectroCoin\SCMerchantClient\Exception\GenericError;
use SpectroCoin\SCMerchantClient\Exception\ApiError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;

#[CoversClass(GenericError::class)]
#[CoversClass(ApiError::class)]
class ExceptionTest extends TestCase
{
    #[TestDox('Test error code of and message GenericError::class')]
    public function testGenericErrorProperties(): void
    {
        $exception = new GenericError('An error occurred', 123);
        $this->assertSame('An error occurred', $exception->getMessage());
        $this->assertSame(123, $exception->getCode());
    }

    #[TestDox('Test error code of and message ApiError::class')]
    public function testApiErrorProperties(): void
    {
        $exception = new ApiError('API error occurred', 456);
        $this->assertSame('API error occurred', $exception->getMessage());
        $this->assertSame(456, $exception->getCode());
    }
}
