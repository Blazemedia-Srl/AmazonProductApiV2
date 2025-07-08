<?php

declare(strict_types=1);

namespace Blazemedia\AmazonProductApiV2\Exceptions;

use Exception;

/**
 * Base Amazon API Exception
 * 
 * @package Blazemedia\AmazonProductApiV2\Exceptions
 */
class AmazonApiException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
