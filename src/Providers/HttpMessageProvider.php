<?php

declare(strict_types=1);

namespace Phast\Providers;

use Katora\Container;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Sandesh\RequestFactory;
use Sandesh\ResponseFactory;
use Sandesh\ServerRequestFactory;
use Sandesh\StreamFactory;
use Sandesh\UploadedFileFactory;
use Sandesh\UriFactory;

/**
 * HTTP message factory service provider.
 */
class HttpMessageProvider implements ProviderInterface
{
    public function provide(Container $container): void
    {
        // Register HTTP factories
        $container->set('http.request_factory', $container->share(fn () => new RequestFactory));
        $container->set('http.response_factory', $container->share(fn () => new ResponseFactory));
        $container->set('http.server_request_factory', $container->share(fn () => new ServerRequestFactory));
        $container->set('http.stream_factory', $container->share(fn () => new StreamFactory));
        $container->set('http.uploaded_file_factory', $container->share(fn () => new UploadedFileFactory));
        $container->set('http.uri_factory', $container->share(fn () => new UriFactory));

        // Register PSR-17 interfaces
        $container->set(RequestFactoryInterface::class, fn (Container $c) => $c->get('http.request_factory'));
        $container->set(ResponseFactoryInterface::class, fn (Container $c) => $c->get('http.response_factory'));
        $container->set(ServerRequestFactoryInterface::class, fn (Container $c) => $c->get('http.server_request_factory'));
        $container->set(StreamFactoryInterface::class, fn (Container $c) => $c->get('http.stream_factory'));
        $container->set(UploadedFileFactoryInterface::class, fn (Container $c) => $c->get('http.uploaded_file_factory'));
        $container->set(UriFactoryInterface::class, fn (Container $c) => $c->get('http.uri_factory'));
    }

    public function init(Container $container): void
    {
        // No initialization needed
    }
}
