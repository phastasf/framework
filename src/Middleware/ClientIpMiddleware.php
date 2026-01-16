<?php

declare(strict_types=1);

namespace Phast\Middleware;

use Kunfig\ConfigInterface;
use Middlewares\ClientIp;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ClientIpMiddleware implements MiddlewareInterface
{
    private readonly ClientIp $middleware;

    public function __construct(
        private readonly ConfigInterface $config
    ) {
        // Get trusted proxies from config
        $trustedProxies = $this->config->get('proxies.trusted', []);
        $headers = $this->config->get('proxies.headers', []);

        // Convert ConfigInterface to array if needed
        if ($trustedProxies instanceof ConfigInterface) {
            $trustedProxies = $trustedProxies->all();
        }
        if ($headers instanceof ConfigInterface) {
            $headers = $headers->all();
        }

        // Ensure arrays
        $trustedProxies = is_array($trustedProxies) ? $trustedProxies : [];
        $headers = is_array($headers) ? $headers : [];

        // Configure ClientIp middleware with trusted proxies
        $this->middleware = (new ClientIp)
            ->proxy($trustedProxies, $headers);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->middleware->process($request, $handler);
    }
}
