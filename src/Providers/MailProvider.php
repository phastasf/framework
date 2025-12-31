<?php

declare(strict_types=1);

namespace Phast\Providers;

use Envelope\Mailer;
use Envelope\Transports\MailgunTransport;
use Envelope\Transports\ResendTransport;
use Envelope\Transports\SmtpTransport;
use Envelope\Transports\TransportInterface;
use Katora\Container;
use Katora\ServiceProviderInterface;
use Kunfig\ConfigInterface;
use RuntimeException;

/**
 * Mail service provider.
 */
class MailProvider implements ServiceProviderInterface
{
    public function provide(Container $container): void
    {
        $container->set('mail.transport', $container->share(function (Container $c) {
            $config = $c->get('config');
            $driver = $config->get('mail.driver', 'smtp');

            return match ($driver) {
                'smtp' => $this->createSmtpTransport($config),
                'mailgun' => $this->createMailgunTransport($config),
                'resend' => $this->createResendTransport($config),
                default => throw new RuntimeException("Unsupported mail driver: {$driver}"),
            };
        }));

        $container->set('mail', $container->share(function (Container $c) {
            $transport = $c->get('mail.transport');

            return new Mailer($transport);
        }));

        $container->set(TransportInterface::class, fn (Container $c) => $c->get('mail.transport'));
        $container->set(Mailer::class, fn (Container $c) => $c->get('mail'));
    }

    protected function createSmtpTransport(ConfigInterface $config): SmtpTransport
    {
        $host = $config->get('mail.smtp.host', 'localhost');
        $port = (int) $config->get('mail.smtp.port', 25);
        $encryption = $config->get('mail.smtp.encryption', null);
        $username = $config->get('mail.smtp.username', null);
        $password = $config->get('mail.smtp.password', null);
        $timeout = (int) $config->get('mail.smtp.timeout', 30);

        return new SmtpTransport($host, $port, $encryption, $username, $password, $timeout);
    }

    protected function createMailgunTransport(ConfigInterface $config): MailgunTransport
    {
        $domain = $config->get('mail.mailgun.domain', '');
        $apiKey = $config->get('mail.mailgun.api_key', '');
        $region = $config->get('mail.mailgun.region', 'us');

        return new MailgunTransport($domain, $apiKey, $region);
    }

    protected function createResendTransport(ConfigInterface $config): ResendTransport
    {
        $apiKey = $config->get('mail.resend.api_key', '');

        return new ResendTransport($apiKey);
    }
}
