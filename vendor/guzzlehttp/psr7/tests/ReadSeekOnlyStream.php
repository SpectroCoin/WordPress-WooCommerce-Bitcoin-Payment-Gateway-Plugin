<?php

declare(strict_types=1);

namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;

final class ReadSeekOnlyStream extends Stream
{
    public function __construct()
    {
        parent::__construct(Utils::tryFopen('php://memory', 'wb'));
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function isReadable(): bool
    {
        return false;
    }
}
