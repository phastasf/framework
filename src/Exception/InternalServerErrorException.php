<?php

declare(strict_types=1);

namespace Phast\Exception;

/**
 * 500 Internal Server Error exception.
 */
class InternalServerErrorException extends HttpException
{
    public function __construct(string $message = 'Internal Server Error', ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(500, $message, $previous, $headers);
    }
}
