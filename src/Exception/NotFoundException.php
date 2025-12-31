<?php

declare(strict_types=1);

namespace Phast\Exception;

/**
 * 404 Not Found exception.
 */
class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Not Found', ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(404, $message, $previous, $headers);
    }
}
