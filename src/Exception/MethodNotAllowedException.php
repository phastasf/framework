<?php

declare(strict_types=1);

namespace Phast\Exception;

/**
 * 405 Method Not Allowed exception.
 */
class MethodNotAllowedException extends HttpException
{
    public function __construct(string $message = 'Method Not Allowed', ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(405, $message, $previous, $headers);
    }
}
