<?php declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient\Enum;
// @codeCoverageIgnoreStart
if (!defined('ABSPATH')) {
	die('Access denied.');
}
// @codeCoverageIgnoreEnd
enum OrderStatus: string
{
    case NEW     = 'NEW';
    case PENDING = 'PENDING';
    case PAID    = 'PAID';
    case FAILED  = 'FAILED';
    case EXPIRED = 'EXPIRED';
    case LATE_CRYPTO_PAYMENT         = 'LATE_CRYPTO_PAYMENT';
    case PARTIAL_PAYMENT             = 'PARTIAL_PAYMENT';
    case UNDERPAID                   = 'UNDERPAID';
    case CANCELLED                   = 'CANCELLED';
    case INVALID_PAYMENT             = 'INVALID_PAYMENT';
    case PROCESSING_REFUND           = 'PROCESSING_REFUND';
    case REFUNDED                    = 'REFUNDED';
    case REJECTED_REFUND             = 'REJECTED_REFUND';
    case PENDING_LATE_CRYPTO_PAYMENT = 'PENDING_LATE_CRYPTO_PAYMENT';
    case REJECTED                    = 'REJECTED';

    /**
     * Map old numeric codes to new enum.
     */
    public static function fromCode(int $code): self
    {
        return match ($code) {
            1 => self::NEW,
            2 => self::PENDING,
            3 => self::PAID,
            4 => self::FAILED,
            5 => self::EXPIRED,
            10 => self::LATE_CRYPTO_PAYMENT,
            11 => self::PARTIAL_PAYMENT,
            12 => self::UNDERPAID,
            13 => self::CANCELLED,
            14 => self::INVALID_PAYMENT,
            17 => self::PROCESSING_REFUND,
            18 => self::REFUNDED,
            19 => self::REJECTED_REFUND,
            20 => self::PENDING_LATE_CRYPTO_PAYMENT,
            21 => self::REJECTED,
            default => throw new \InvalidArgumentException("Unknown numeric status code: $code"),
        };
    }

    /**
     * Normalize either an integer (legacy) or a string.
     */
    public static function normalize(string|int $raw): self
    {
        if (is_int($raw) || ctype_digit((string)$raw)) {
            return self::fromCode((int)$raw);
        }
        $upper = strtoupper((string)$raw);
        return match ($upper) {
            'NEW'     => self::NEW,
            'PENDING' => self::PENDING,
            'PAID'    => self::PAID,
            'FAILED'  => self::FAILED,
            'EXPIRED' => self::EXPIRED,
            'LATE_CRYPTO_PAYMENT'         => self::LATE_CRYPTO_PAYMENT,
            'PARTIAL_PAYMENT'             => self::PARTIAL_PAYMENT,
            'UNDERPAID'                   => self::UNDERPAID,
            'CANCELLED'                   => self::CANCELLED,
            'INVALID_PAYMENT'             => self::INVALID_PAYMENT,
            'PROCESSING_REFUND'           => self::PROCESSING_REFUND,
            'REFUNDED'                    => self::REFUNDED,
            'REJECTED_REFUND'             => self::REJECTED_REFUND,
            'PENDING_LATE_CRYPTO_PAYMENT' => self::PENDING_LATE_CRYPTO_PAYMENT,
            'REJECTED'                    => self::REJECTED,
            default   => throw new \InvalidArgumentException("Unknown status string: $raw"),
        };
    }

    /**
     * Statuses that end the order without a completed payment.
     */
    public function isCancellation(): bool
    {
        return match ($this) {
            self::FAILED, self::CANCELLED, self::REJECTED, self::INVALID_PAYMENT => true,
            default => false,
        };
    }

    /**
     * Statuses that report on a payment already under way and carry no shop-side
     * transition. The merchant is told; the order is left alone. Transitioning
     * automatically here would either fulfil an order that was not paid in full
     * or reverse one the merchant may already have settled by hand.
     */
    public function isInformational(): bool
    {
        return match ($this) {
            self::PARTIAL_PAYMENT,
            self::UNDERPAID,
            self::LATE_CRYPTO_PAYMENT,
            self::PENDING_LATE_CRYPTO_PAYMENT,
            self::PROCESSING_REFUND,
            self::REFUNDED,
            self::REJECTED_REFUND => true,
            default => false,
        };
    }
}
