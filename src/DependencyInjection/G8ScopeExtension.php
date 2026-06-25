<?php

declare(strict_types=1);

namespace G8Scope\Symfony\DependencyInjection;

use G8Scope\Php\Client;
use G8Scope\Php\Config;
use G8Scope\Symfony\EventSubscriber\ExceptionSubscriber;
use G8Scope\Symfony\Reporter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Wires the g8scope services: builds an immutable {@see Config} from the resolved
 * configuration, registers the runtime {@see Client}, the {@see Reporter}, and
 * the {@see ExceptionSubscriber} (tagged as an event subscriber).
 */
final class G8ScopeExtension extends Extension
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
            'sdk_name' => 'g8scope-symfony',
        ]]);
        $configDefinition->setPublic(false);
        $container->setDefinition('g8scope.config', $configDefinition);

        $clientDefinition = new Definition(Client::class);
        $clientDefinition->setArguments([new Reference('g8scope.config')]);
        $clientDefinition->setPublic(true);
        $container->setDefinition('g8scope.client', $clientDefinition);
        $container->setAlias(Client::class, 'g8scope.client')->setPublic(true);

        $reporterDefinition = new Definition(Reporter::class);
        $reporterDefinition->setArguments([
            new Reference('g8scope.client'),
            new Reference('request_stack', ContainerBuilder::NULL_ON_INVALID_REFERENCE),
        ]);
        $reporterDefinition->setPublic(true);
        $container->setDefinition('g8scope.reporter', $reporterDefinition);
        $container->setAlias(Reporter::class, 'g8scope.reporter')->setPublic(true);

        $subscriberDefinition = new Definition(ExceptionSubscriber::class);
        $subscriberDefinition->setArguments([new Reference('g8scope.reporter')]);
        $subscriberDefinition->addTag('kernel.event_subscriber');
        $subscriberDefinition->setPublic(false);
        $container->setDefinition('g8scope.exception_subscriber', $subscriberDefinition);
    }

    public function getAlias(): string
    {
        return 'g8scope';
    }
}
