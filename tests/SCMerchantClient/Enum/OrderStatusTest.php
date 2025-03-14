<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SpectroCoin\SCMerchantClient\Enum\OrderStatus;

#[CoversClass(OrderStatus::class)]
class OrderStatusTest extends TestCase
{
    #[DataProvider('orderStatusProvider')]
    #[TestDox('Test order statuses')]
    public function testOrderStatusEnums($status, $expected): void
    {
        $this->assertSame($expected, $status);
    }

    public static function orderStatusProvider(): array{

        return [
            '1 is New' => [1, OrderStatus::New->value],
            '2 is Pending' => [2, OrderStatus::Pending->value],
            '3 is Paid' => [3, OrderStatus::Paid->value],
            '4 is Failed' => [4, OrderStatus::Failed->value],
            '5 is Expired' => [5, OrderStatus::Expired->value],
        ];
    }
}
