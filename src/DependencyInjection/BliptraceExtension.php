<?php

declare(strict_types=1);

namespace Bliptrace\Symfony\DependencyInjection;

use Bliptrace\Php\Client;
use Bliptrace\Php\Config;
use Bliptrace\Symfony\EventSubscriber\ExceptionSubscriber;
use Bliptrace\Symfony\Reporter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Wires the Bliptrace services: builds an immutable {@see Config} from the resolved
 * configuration, registers the runtime {@see Client}, the {@see Reporter}, and
 * the {@see ExceptionSubscriber} (tagged as an event subscriber).
 */
final class BliptraceExtension extends Extension
{
    /**
     * @param  array<int,array<string,mixed>>  $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration, $configs);

        // Build the immutable runtime Config. base_path is the project root so
        // in-app frame detection works; sdk_name marks this as the Symfony SDK.
        $configDefinition = new Definition(Config::class);
        $configDefinition->setFactory([Config::class, 'fromArray']);
        $configDefinition->setArguments([[
            'enabled' => $config['enabled'],
            'key' => $config['key'],
            'endpoint' => $config['endpoint'],
            'environment' => $config['environment'],
            'release' => $config['release'],
            'timeout' => $config['timeout'],
            'max_frames' => $config['max_frames'],
            'max_message' => $config['max_message'],
            'ignore_exceptions' => $config['ignore_exceptions'],
            'scrub_keys' => $config['scrub_keys'],
            'base_path' => $container->getParameter('kernel.project_dir'),
            'sdk_name' => 'bliptrace-symfony',
        ]]);
        $configDefinition->setPublic(false);
        $container->setDefinition('bliptrace.config', $configDefinition);

        $clientDefinition = new Definition(Client::class);
        $clientDefinition->setArguments([new Reference('bliptrace.config')]);
        $clientDefinition->setPublic(true);
        $container->setDefinition('bliptrace.client', $clientDefinition);
        $container->setAlias(Client::class, 'bliptrace.client')->setPublic(true);

        $reporterDefinition = new Definition(Reporter::class);
        $reporterDefinition->setArguments([
            new Reference('bliptrace.client'),
            new Reference('request_stack', ContainerBuilder::NULL_ON_INVALID_REFERENCE),
        ]);
        $reporterDefinition->setPublic(true);
        $container->setDefinition('bliptrace.reporter', $reporterDefinition);
        $container->setAlias(Reporter::class, 'bliptrace.reporter')->setPublic(true);

        $subscriberDefinition = new Definition(ExceptionSubscriber::class);
        $subscriberDefinition->setArguments([new Reference('bliptrace.reporter')]);
        $subscriberDefinition->addTag('kernel.event_subscriber');
        $subscriberDefinition->setPublic(false);
        $container->setDefinition('bliptrace.exception_subscriber', $subscriberDefinition);
    }

    public function getAlias(): string
    {
        return 'bliptrace';
    }
}
