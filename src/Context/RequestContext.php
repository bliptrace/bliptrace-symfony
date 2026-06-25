<?php

declare(strict_types=1);

namespace G8Scope\Symfony\Context;

use Symfony\Component\HttpFoundation\Request;

/** Builds the envelope context from the current Symfony request (if any). */
final class RequestContext
{
    /**
     * @return array<string,mixed>
     */
    public static function capture(?Request $request): array
    {
        $context = ['custom' => []];

        if ($request === null) {
            return $context;
        }

        $context['request'] = [
            'method' => $request->getMethod(),
            'url' => $request->getUri(),
            'headers' => self::headers($request),
        ];

        $user = $request->attributes->get('_g8scope_user');
        if ($user !== null) {
            $context['user'] = ['id' => (string) $user];
        }

        return $context;
    }

    /**
     * @return array<string,string>
     */
    private static function headers(Request $request): array
    {
        $headers = [];

        foreach ($request->headers->all() as $name => $values) {
            if (in_array(strtolower((string) $name), ['authorization', 'cookie'], true)) {
                continue;
            }

            $headers[$name] = is_array($values) ? implode(',', array_map('strval', $values)) : (string) $values;
        }

        return $headers;
    }
}
