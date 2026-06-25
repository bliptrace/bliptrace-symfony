<?php

declare(strict_types=1);

namespace G8Scope\Symfony\EventSubscriber;

use G8Scope\Symfony\Reporter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * Captures kernel exceptions and flushes them after the response is sent.
 * Every callback is guarded so the SDK never throws into the host app.
 */
final class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Reporter $reporter) {}

    /**
     * @return array<string,array{0:string,1:int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
            KernelEvents::TERMINATE => ['onKernelTerminate', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        try {
            $this->reporter->capture($event->getThrowable());
        } catch (Throwable) {
            // Never break the app.
        }
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        try {
            $this->reporter->flush();
        } catch (Throwable) {
            // Never break the app.
        }
    }
}
