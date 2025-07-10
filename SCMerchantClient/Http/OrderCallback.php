<?php

declare(strict_types=1);

namespace SpectroCoin\SCMerchantClient\Http;

use SpectroCoin\SCMerchantClient\Utils;
use SpectroCoin\SCMerchantClient\Config;
use Exception;
use InvalidArgumentException;
// @codeCoverageIgnoreStart
if (!defined('ABSPATH')) {
    die('Access denied.');
}
// @codeCoverageIgnoreEnd

class OrderCallback
{
    private ?string $id;
    private ?string $merchantApiId;

    public function __construct(?string $id, ?string $merchantApiId)
    {
        $this->id = isset($id) ? Utils::sanitize_text_field((string)$id) : null;
        $this->merchantApiId = isset($merchantApiId) ? Utils::sanitize_text_field((string)$merchantApiId) : null;

        $validation_result = $this->validate();
        if (is_array($validation_result)) {
            $errorMessage = 'Invalid order callback. Failed fields: ' . implode(', ', $validation_result);
            throw new InvalidArgumentException($errorMessage);
        }
    }


    /**
     * Validate the input data.
     *
     * @return bool|array True if validation passes, otherwise an array of error messages.
     */
    private function validate(): bool|array
    {
        $errors = [];

        if (empty($this->getId())) {
            $errors[] = 'Id is empty';
        }

        if (empty($this->getmerchantApiId())) {
            $errors[] = 'merchantApiId is empty';
        }

        return empty($errors) ? true : $errors;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getmerchantApiId()
    {
        return $this->merchantApiId;
    }
}
