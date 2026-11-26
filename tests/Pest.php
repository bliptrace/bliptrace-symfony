<?php

declare(strict_types=1);

use Bliptrace\Php\Transport\Transport;

uses()->in(__DIR__);

/** Records every envelope handed to the transport instead of shipping it. */
final class FakeTransport implements Transport
{
    /** @var array<int,array<string,mixed>> */
    public array $sent = [];

    /** @param array<string,mixed> $envelope */
    public function send(string $endpoint, string $key, array $envelope, int $timeout): void
    {
        $this->sent[] = compact('endpoint', 'key', 'envelope');
    }
}
