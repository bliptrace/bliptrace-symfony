<?php

declare(strict_types=1);

use Bliptrace\Symfony\Context\RequestContext;
use Symfony\Component\HttpFoundation\Request;

it('builds method and url and strips sensitive headers', function (): void {
    $request = Request::create('https://example.test/checkout', 'PUT', [], [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer secret',
        'HTTP_COOKIE' => 'session=abc',
        'HTTP_X_CUSTOM' => 'keep-me',
    ]);

    $context = RequestContext::capture($request);

    expect($context['request']['method'])->toBe('PUT')
        ->and($context['request']['url'])->toContain('https://example.test/checkout')
        ->and($context['custom'])->toBe([]);

    $headers = array_change_key_case($context['request']['headers']);

    expect($headers)->not->toHaveKey('authorization')
        ->and($headers)->not->toHaveKey('cookie')
        ->and($headers)->toHaveKey('x-custom');
});

it('returns just custom when there is no request', function (): void {
    expect(RequestContext::capture(null))->toBe(['custom' => []]);
});
