<?php

declare(strict_types=1);

namespace Phast\Exception;

/**
 * 401 Unauthorized exception.
 */
class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Unauthorized', ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(401, $message, $previous, $headers);
    }
}
