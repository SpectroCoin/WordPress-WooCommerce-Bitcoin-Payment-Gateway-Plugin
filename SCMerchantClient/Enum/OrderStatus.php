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
            default   => throw new \InvalidArgumentException("Unknown status string: $raw"),
        };
    }
}
