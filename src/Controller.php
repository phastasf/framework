<?php

declare(strict_types=1);

namespace Phast;

use Filtr\ResultInterface;
use Filtr\Validator;
use Katora\Container;
use Phew\ViewInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Base controller class with helper methods.
 */
abstract class Controller
{
    protected Container $container;

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Render a view template.
     *
     * @param  string  $template  Template name (e.g., 'welcome' or 'admin:dashboard')
     * @param  array<string, mixed>  $vars  Variables to pass to the template
     */
    protected function render(string $template, array $vars = []): ResponseInterface
    {
        $responseFactory = $this->container->get(ResponseFactoryInterface::class);
        $response = $responseFactory->createResponse(200);
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');

        $view = $this->container->get(ViewInterface::class);

        // Append .phtml extension if not already present
        if (! str_ends_with($template, '.phtml')) {
            $template .= '.phtml';
        }

        $content = $view->fetch($template, $vars);
        $response->getBody()->write($content);

        return $response;
    }

    /**
     * Validate data using Filtr.
     *
     * @param  array<string, mixed>  $data  Data to validate
     * @param  callable  $rules  Callback that receives a Validator instance to define rules
     * @return ResultInterface Validation result
     */
    protected function validate(array $data, callable $rules): ResultInterface
    {
        $validator = new Validator;
        $rules($validator);

        return $validator->validate($data);
    }

    /**
     * Create a JSON response.
     *
     * @param  mixed  $data  Data to encode as JSON
     * @param  int  $statusCode  HTTP status code
     */
    protected function json(mixed $data, int $statusCode = 200): ResponseInterface
    {
        $responseFactory = $this->container->get(ResponseFactoryInterface::class);
        $response = $responseFactory->createResponse($statusCode);
        $response = $response->withHeader('Content-Type', 'application/json; charset=utf-8');

        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($json);

        return $response;
    }

    /**
     * Create a redirect response.
     *
     * @param  string  $url  URL to redirect to
     * @param  int  $statusCode  HTTP status code (default: 302)
     */
    protected function redirect(string $url, int $statusCode = 302): ResponseInterface
    {
        $responseFactory = $this->container->get(ResponseFactoryInterface::class);
        $response = $responseFactory->createResponse($statusCode);
        $response = $response->withHeader('Location', $url);

        return $response;
    }
}
