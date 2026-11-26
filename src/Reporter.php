<?php

declare(strict_types=1);

namespace Bliptrace\Symfony;

use Bliptrace\Php\Client;
use Bliptrace\Symfony\Context\RequestContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

/**
 * Symfony reporter: enrich with request context, then defer the send until the
 * kernel terminates so the monitored app is never blocked. If the deferred send
 * cannot run (e.g. no terminate event), it ships immediately. Never throws.
 */
final class Reporter
{
    /** @var array<int,array<string,mixed>> */
    private array $pending = [];

    public function __construct(
        private readonly Client $client,
        private readonly ?RequestStack $requestStack = null,
    ) {}

    public function capture(Throwable $e): void
    {
        try {
            $envelope = $this->client->buildEnvelope($e, RequestContext::capture($this->currentRequest()));
            if ($envelope === null) {
                return;
            }

            // Defer the send until kernel.terminate (see ExceptionSubscriber).
            $this->pending[] = $envelope;
        } catch (Throwable) {
            // Never break the app.
        }
    }

    /** Ship every queued envelope. Called on kernel.terminate. */
    public function flush(): void
    {
        try {
            foreach ($this->pending as $envelope) {
                $this->client->sendEnvelope($envelope);
            }
        } catch (Throwable) {
            // Never break the app.
        } finally {
            $this->pending = [];
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function pending(): array
    {
        return $this->pending;
    }

    private function currentRequest(): ?Request
    {
        try {
            return $this->requestStack?->getCurrentRequest();
        } catch (Throwable) {
            return null;
        }
    }
}
