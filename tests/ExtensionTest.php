<?php

declare(strict_types=1);

use G8Scope\Php\Client;
use G8Scope\Symfony\DependencyInjection\G8ScopeExtension;
use G8Scope\Symfony\EventSubscriber\ExceptionSubscriber;
use G8Scope\Symfony\Reporter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelEvents;

function buildContainer(array $config = [], bool $compile = true): ContainerBuilder
{
    $container = new ContainerBuilder;
    $container->setParameter('kernel.project_dir', sys_get_temp_dir());

    (new G8ScopeExtension)->load([$config], $container);

    if ($compile) {
        $container->compile();
    }

    return $container;
}

it('registers the client, reporter and subscriber services', function (): void {
    // Inspect definitions before compilation removes unreferenced private services.
    $container = buildContainer(['key' => 'k', 'endpoint' => 'http://api'], compile: false);

    expect($container->hasDefinition('g8scope.client'))->toBeTrue()
        ->and($container->hasDefinition('g8scope.reporter'))->toBeTrue()
        ->and($container->getDefinition('g8scope.exception_subscriber')->getTag('kernel.event_subscriber'))->not->toBeEmpty();
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
