<?php

namespace SpectroCoin\SCMerchantClient\Exception;
// @codeCoverageIgnoreStart
if (!defined('ABSPATH')) {
	die('Access denied.');
}
// @codeCoverageIgnoreEnd
class ApiError extends GenericError
{
    /**
     * @param string $message
     * @param int $code
     */
    function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }
}