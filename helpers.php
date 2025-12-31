<?php

declare(strict_types=1);

/**
 * Get an environment variable.
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false) {
        return $default;
    }

    // Convert string booleans
    if (strtolower($value) === 'true') {
        return true;
    }

    if (strtolower($value) === 'false') {
        return false;
    }

    // Convert empty string to null
    if ($value === '') {
        return $default;
    }

    return $value;
}
