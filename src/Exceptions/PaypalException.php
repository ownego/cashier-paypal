<?php

namespace Ownego\Cashier\Exceptions;

class PaypalException extends \Exception
{
    private int $statusCode;

    private string $errorCode;

    private string $errorDescription;

    public function __construct(
        int $statusCode,
        string $errorCode,
        string $errorDescription = '',
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->errorDescription = $errorDescription;

        parent::__construct($this->generateMessage(), $code, $previous);
    }

    public function generateMessage()
    {
        return "Paypal API error: {$this->statusCode} - {$this->errorCode} - {$this->errorDescription}";
    }
}
