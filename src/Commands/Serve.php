<?php

declare(strict_types=1);

namespace Phast\Commands;

use Clip\Command;
use Clip\Stdio;
use Kunfig\ConfigInterface;

/**
 * Command to start the development server.
 */
class Serve extends Command
{
    public function __construct(
        private readonly ConfigInterface $config
    ) {}

    public function getName(): string
    {
        return 'serve';
    }

    public function getDescription(): string
    {
        return 'Start the PHP development server';
    }

    public function execute(Stdio $stdio): int
    {
        $host = $stdio->getOption('host', '127.0.0.1');
        $port = (int) $stdio->getOption('port', '8000');

        // Determine the public directory and router file from config
        $publicPath = $this->config->get('app.public.path');
        $routerFile = $this->config->get('app.public.index');

        // If not configured, fall back to defaults
        if (empty($publicPath)) {
            $appBasePath = $this->config->get('app.base_path');
            $publicPath = $appBasePath.'/public';
        }
        if (empty($routerFile)) {
            $routerFile = $publicPath.'/index.php';
        }

        // Handle relative paths
        if (! str_starts_with($publicPath, '/')) {
            $appBasePath = $this->config->get('app.base_path');
            $publicPath = $appBasePath.'/'.ltrim($publicPath, '/');
        }
        if (! str_starts_with($routerFile, '/')) {
            $appBasePath = $this->config->get('app.base_path');
            $routerFile = $appBasePath.'/'.ltrim($routerFile, '/');
        }

        if (! file_exists($routerFile)) {
            $stdio->error("Public entrypoint not found: {$routerFile}");

            return 1;
        }

        if (! is_dir($publicPath)) {
            $stdio->error("Public directory not found: {$publicPath}");

            return 1;
        }

        $address = "{$host}:{$port}";
        $url = "http://{$address}";

        $stdio->writeln();
        $stdio->info('Phast development server started');
        $stdio->writeln(" Server: {$url}");
        $stdio->writeln(" Document root: {$publicPath}");
        $stdio->writeln();
        $stdio->writeln('Press Ctrl+C to stop the server');
        $stdio->writeln();

        // Start the PHP built-in server
        $command = sprintf(
            'php -S %s -t %s %s',
            escapeshellarg($address),
            escapeshellarg($publicPath),
            escapeshellarg($routerFile)
        );

        passthru($command, $exitCode);

        return $exitCode;
    }
}
