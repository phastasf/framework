<?php

declare(strict_types=1);

return [
    // Trusted proxy IP addresses or CIDR ranges
    // These are the IPs of reverse proxies/load balancers that can be trusted
    // Only requests from these IPs will have their proxy headers (X-Forwarded-For, etc.) trusted
    'trusted' => [
        // Private network ranges (for Docker/LAN)
        '10.0.0.0/8',      // Private network
        '172.16.0.0/12',  // Docker network
        '192.168.0.0/16', // Private network
        '127.0.0.1',      // Localhost IPv4
        '::1',            // Localhost IPv6
        // Add your production proxy IPs here, e.g.:
        // '203.0.113.0/24', // Your load balancer IP range
    ],

    // Headers to check for client IP (in order of preference)
    'headers' => [
        'Forwarded',
        'X-Forwarded-For',
        'X-Real-Ip',
        'Client-Ip',
    ],
];
