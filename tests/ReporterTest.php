<?php

declare(strict_types=1);

use G8Scope\Php\Client;
use G8Scope\Php\Config;
use G8Scope\Symfony\Reporter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

function reporterWith(FakeTransport $t, ?Config $config = null, ?RequestStack $stack = null): Reporter
{
    $config ??= new Config(key: 'k', endpoint: 'http://api');
    $client = new Client($config, $t);

    return new Reporter($client, $stack);
}

it('builds and ships a valid envelope for a thrown exception', function (): void {
    $t = new FakeTransport;
    $reporter = reporterWith($t);

    $reporter->capture(new RuntimeException('boom'));

    // Deferred until flush().
    expect($t->sent)->toBeEmpty();

    $reporter->flush();

    expect($t->sent)->toHaveCount(1)
        ->and($t->sent[0]['endpoint'])->toBe('http://api')
        ->and($t->sent[0]['key'])->toBe('k');

    $envelope = $t->sent[0]['envelope'];

    expect($envelope['runtime']['name'])->toBe('php')
        ->and($envelope['sdk'])->toHaveKeys(['name', 'version'])
        ->and($envelope['exception']['type'])->toBe('RuntimeException')
        ->and($envelope['exception']['message'])->toBe('boom');
});

it('enriches the envelope with request context from the request stack', function (): void {
    $t = new FakeTransport;
    $stack = new RequestStack;
    $stack->push(Request::create('https://example.test/orders?x=1', 'POST', [], [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer secret',
    ]));

    $reporter = reporterWith($t, stack: $stack);
    $reporter->capture(new RuntimeException('boom'));
    $reporter->flush();

    $request = $t->sent[0]['envelope']['context']['request'];

    expect($request['method'])->toBe('POST')
        ->and($request['url'])->toContain('https://example.test/orders')
        ->and(array_change_key_case($request['headers']))->not->toHaveKey('authorization');
});

it('skips ignored exception types', function (): void {
    $t = new FakeTransport;
    $config = new Config(key: 'k', endpoint: 'http://api', ignoreExceptions: [LogicException::class]);
    $reporter = reporterWith($t, $config);

    $reporter->capture(new LogicException('ignored'));
    $reporter->flush();

    expect($t->sent)->toBeEmpty();
});

it('respects the master kill switch', function (): void {
    $t = new FakeTransport;
    $config = new Config(key: 'k', endpoint: 'http://api', enabled: false);
    $reporter = reporterWith($t, $config);

    $reporter->capture(new Exception('x'));
    $reporter->flush();

    expect($t->sent)->toBeEmpty();
});

it('never throws on weird input', function (): void {
    $t = new FakeTransport;
    $reporter = reporterWith($t);

    $reporter->capture(new Error('fatal-ish'));
    $reporter->capture(new RuntimeException(str_repeat('x', 10000)));
    $reporter->flush();
    $reporter->flush(); // double-flush is safe (queue cleared)

    expect($t->sent)->toHaveCount(2);
});
