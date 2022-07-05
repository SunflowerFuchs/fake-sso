<?php

namespace SunflowerFuchs\FakeSso;

use Exception;
use Throwable;

class SsoException extends Exception
{
    /**
     * @inheritDoc
     */
    public function __construct(string $message, int $code, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}