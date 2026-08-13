<?php

/**
 * Invariant tests for order-status coverage.
 *
 * The API reports more statuses than a payment simply succeeding or failing:
 * partial and late payments, and the refund lifecycle. Every status it can send
 * must be understood here, otherwise the callback is rejected and the merchant
 * is never told what happened to the payment.
 *
 * Statuses are classified three ways:
 *   - a completed or terminal outcome, which moves the order;
 *   - a cancellation, which ends the order without payment;
 *   - informational, which is recorded and leaves the order untouched, because
 *     transitioning automatically would either fulfil an order that was not
 *     paid in full or reverse one the merchant may already have settled.
 *
 * Standalone by design: this plugin ships no PHPUnit setup, and a WooCommerce
 * bootstrap is not needed to check a status table.
 *
 * Run:  php tests/check-order-status-coverage.php
 */

define('ABSPATH', true);
require_once __DIR__ . '/../SCMerchantClient/Enum/OrderStatus.php';

use SpectroCoin\SCMerchantClient\Enum\OrderStatus;

/** Every status the API can put on the wire, with its legacy numeric code. */
const WIRE_STATUSES = [
    'NEW' => 1, 'PENDING' => 2, 'PAID' => 3, 'FAILED' => 4, 'EXPIRED' => 5,
    'LATE_CRYPTO_PAYMENT' => 10, 'PARTIAL_PAYMENT' => 11, 'UNDERPAID' => 12,
    'CANCELLED' => 13, 'INVALID_PAYMENT' => 14, 'PROCESSING_REFUND' => 17,
    'REFUNDED' => 18, 'REJECTED_REFUND' => 19,
    'PENDING_LATE_CRYPTO_PAYMENT' => 20, 'REJECTED' => 21,
];

const CANCELLATIONS  = ['FAILED', 'CANCELLED', 'REJECTED', 'INVALID_PAYMENT'];
const INFORMATIONAL  = ['PARTIAL_PAYMENT', 'UNDERPAID', 'LATE_CRYPTO_PAYMENT',
                        'PENDING_LATE_CRYPTO_PAYMENT', 'PROCESSING_REFUND',
                        'REFUNDED', 'REJECTED_REFUND'];

class TestRunner
{
    private $failures = [];
    private $passed = 0;
    private $failed = 0;

    public function assertTrue($cond, $message)
    {
        if (!$cond) { $this->failures[] = $message; }
    }

    public function assertSame($expected, $actual, $message)
    {
        if ($expected !== $actual) {
            $this->failures[] = $message . ' (expected ' . var_export($expected, true)
                . ', got ' . var_export($actual, true) . ')';
        }
    }

    public function run($name, callable $test)
    {
        $this->failures = [];
        try { $test($this); }
        catch (\Throwable $e) { $this->failures[] = 'threw ' . get_class($e) . ': ' . $e->getMessage(); }
        if (empty($this->failures)) { $this->passed++; echo "  PASS  {$name}\n"; }
        else {
            $this->failed++;
            echo "  FAIL  {$name}\n";
            foreach ($this->failures as $f) { echo "          {$f}\n"; }
        }
    }

    public function summary()
    {
        echo "\n{$this->passed} passed, {$this->failed} failed\n";
        return $this->failed === 0 ? 0 : 1;
    }
}

$callbackSource = file_get_contents(__DIR__ . '/../includes/SpectroCoinGateway.php');

$t = new TestRunner();
echo "SpectroCoin WooCommerce — order-status coverage\n\n";

$t->run('every status the API can send is accepted', function ($t) {
    foreach (WIRE_STATUSES as $name => $code) {
        $t->assertSame($name, OrderStatus::normalize($name)->value,
            "normalize() must accept the {$name} status");
    }
});

$t->run('legacy numeric codes map to the same statuses', function ($t) {
    foreach (WIRE_STATUSES as $name => $code) {
        $t->assertSame($name, OrderStatus::normalize($code)->value,
            "normalize() must map legacy code {$code} to {$name}");
    }
});

$t->run('cancellations are classified exactly', function ($t) {
    foreach (WIRE_STATUSES as $name => $code) {
        $expected = in_array($name, CANCELLATIONS, true);
        $t->assertSame($expected, OrderStatus::normalize($name)->isCancellation(),
            "{$name}: isCancellation() classification");
    }
});

$t->run('informational statuses are classified exactly', function ($t) {
    foreach (WIRE_STATUSES as $name => $code) {
        $expected = in_array($name, INFORMATIONAL, true);
        $t->assertSame($expected, OrderStatus::normalize($name)->isInformational(),
            "{$name}: isInformational() classification");
    }
});

$t->run('no status is both a cancellation and informational', function ($t) {
    foreach (WIRE_STATUSES as $name => $code) {
        $s = OrderStatus::normalize($name);
        $t->assertTrue(!($s->isCancellation() && $s->isInformational()),
            "{$name} must not be classified both ways");
    }
});

$t->run('a status outside the contract is still rejected', function ($t) {
    foreach (['SOMETHING_NEW', '', '999'] as $bogus) {
        $threw = false;
        try { OrderStatus::normalize($bogus); }
        catch (\InvalidArgumentException $e) { $threw = true; }
        $t->assertTrue($threw, "normalize() must reject '{$bogus}' so protocol drift fails loudly");
    }
});

$t->run('the callback consults the informational classification', function ($t) use ($callbackSource) {
    $t->assertTrue(strpos($callbackSource, 'isInformational()') !== false,
        'the callback must skip shop-side changes for informational statuses');
});

$t->run('the callback routes every cancellation status', function ($t) use ($callbackSource) {
    foreach (CANCELLATIONS as $name) {
        $t->assertTrue(strpos($callbackSource, $name) !== false,
            "the callback must handle the {$name} status");
    }
});

exit($t->summary());
