<?php

declare(strict_types=1);

use Bliptrace\Php\Client;
use Bliptrace\Symfony\DependencyInjection\BliptraceExtension;
use Bliptrace\Symfony\EventSubscriber\ExceptionSubscriber;
use Bliptrace\Symfony\Reporter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelEvents;

function buildContainer(array $config = [], bool $compile = true): ContainerBuilder
{
    $container = new ContainerBuilder;
    $container->setParameter('kernel.project_dir', sys_get_temp_dir());

    (new BliptraceExtension)->load([$config], $container);

    if ($compile) {
        $container->compile();
    }

    return $container;
}

it('registers the client, reporter and subscriber services', function (): void {
    // Inspect definitions before compilation removes unreferenced private services.
    $container = buildContainer(['key' => 'k', 'endpoint' => 'http://api'], compile: false);

    expect($container->hasDefinition('bliptrace.client'))->toBeTrue()
        ->and($container->hasDefinition('bliptrace.reporter'))->toBeTrue()
        ->and($container->getDefinition('bliptrace.exception_subscriber')->getTag('kernel.event_subscriber'))->not->toBeEmpty();
});

it('exposes the reporter via its class alias', function (): void {
    $container = buildContainer(['key' => 'k']);

    expect($container->get(Reporter::class))->toBeInstanceOf(Reporter::class)
        ->and($container->get(Client::class))->toBeInstanceOf(Client::class);
});

it('the subscriber subscribes to the kernel exception event', function (): void {
    expect(ExceptionSubscriber::getSubscribedEvents())
        ->toHaveKey(KernelEvents::EXCEPTION);
});
