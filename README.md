# bliptrace/symfony

Symfony SDK for **g8scope** — auto-captures uncaught exceptions in your Symfony
application and ships them to the g8scope ingest API. It is a **thin adapter**
over the runtime SDK [`bliptrace/php`](../bliptrace-php): it adds a Symfony bundle,
an exception event subscriber, configuration glue, and request-context
enrichment. It **never throws into the host application** — capture is always
best-effort and silently swallows its own failures.

## Installation

```bash
composer require bliptrace/symfony
```

## Register the bundle

If you do not use Symfony Flex, add the bundle to `config/bundles.php`:

```php
return [
    // ...
    G8Scope\Symfony\G8ScopeBundle::class => ['all' => true],
];
```

## Configuration

Create `config/packages/g8scope.yaml`:

```yaml
g8scope:
    enabled: '%env(bool:G8SCOPE_ENABLED)%'
    key: '%env(G8SCOPE_KEY)%'
    endpoint: '%env(G8SCOPE_ENDPOINT)%'
    environment: '%env(APP_ENV)%'
    release: '%env(G8SCOPE_RELEASE)%'
    timeout: 3
    max_frames: 30
    max_message: 2000
    ignore_exceptions:
        - Symfony\Component\HttpKernel\Exception\NotFoundHttpException
        - Symfony\Component\Security\Core\Exception\AccessDeniedException
    scrub_keys:
        - password
        - token
        - secret
        - authorization
        - cookie
```

And in `.env`:

```dotenv
G8SCOPE_ENABLED=true
G8SCOPE_KEY=your-project-ingest-key
G8SCOPE_ENDPOINT=https://ingest.scope.g8suite.com
G8SCOPE_RELEASE=
```

### Configuration keys

| Key | Default | Description |
|---|---|---|
| `enabled` | `true` | Master kill switch — set `false` to disable all capture. |
| `key` | `null` | Project **ingest key** from the g8scope dashboard. |
| `endpoint` | `https://ingest.scope.g8suite.com` | Ingest API base URL (the SDK posts to `{endpoint}/v1/ingest`). |
| `environment` | `production` | Logical environment label. |
| `release` | `null` | Release / version identifier. |
| `timeout` | `3` | HTTP timeout (seconds) for the send. |
| `max_frames` | `30` | Maximum stack frames captured. |
| `max_message` | `2000` | Maximum exception-message length. |
| `ignore_exceptions` | `[]` | Exception classes never reported (noise). |
| `scrub_keys` | sensible defaults | Context keys redacted before sending (client-side scrub). |

> **The project is resolved server-side from the ingest key** — it is never sent
> in the payload. This is an anti-spoofing guarantee: an ingest key maps to
> exactly one project on the g8scope backend.

## Usage

Once registered, capture is automatic: the bundle listens on
`kernel.exception`, builds a scrubbed envelope enriched with the current request
context (method, URL, headers with `authorization`/`cookie` stripped), and ships
it on `kernel.terminate` — **after** the response has been sent, so a slow or
unavailable ingest endpoint never affects the monitored application.

To report an exception manually, fetch the reporter service:

```php
use G8Scope\Symfony\Reporter;

public function __construct(private readonly Reporter $reporter) {}

try {
    // ...
} catch (\Throwable $e) {
    $this->reporter->capture($e);
}
```

## Testing

```bash
composer install
composer test
```

## Licence

MIT. See [LICENSE](LICENSE).
