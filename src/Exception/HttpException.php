<?php

declare(strict_types=1);

namespace Phast\Exception;

use RuntimeException;
use Throwable;

/**
 * Base HTTP exception class.
 */
class HttpException extends RuntimeException
{
    protected int $statusCode;

    protected array $headers;

    public function __construct(
        int $statusCode,
        string $message = '',
        ?Throwable $previous = null,
        array $headers = []
    ) {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function withHeader(string $name, string $value): self
    {
        $new = clone $this;
        $new->headers[$name] = $value;

        return $new;
    }
}
