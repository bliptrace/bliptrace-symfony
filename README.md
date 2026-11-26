# bliptrace/symfony

Symfony SDK for **Bliptrace** — auto-captures uncaught exceptions in your Symfony
application and ships them to the Bliptrace ingest API. It is a **thin adapter**
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
    Bliptrace\Symfony\BliptraceBundle::class => ['all' => true],
];
```

## Configuration

Create `config/packages/bliptrace.yaml`:

```yaml
bliptrace:
    enabled: '%env(bool:BLIPTRACE_ENABLED)%'
    key: '%env(BLIPTRACE_KEY)%'
    endpoint: '%env(BLIPTRACE_ENDPOINT)%'
    environment: '%env(APP_ENV)%'
    release: '%env(BLIPTRACE_RELEASE)%'
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
BLIPTRACE_ENABLED=true
BLIPTRACE_KEY=your-project-ingest-key
BLIPTRACE_ENDPOINT=https://ingest.bliptrace.com/v1/ingest
BLIPTRACE_RELEASE=
```

### Configuration keys

| Key | Default | Description |
|---|---|---|
| `enabled` | `true` | Master kill switch — set `false` to disable all capture. |
| `key` | `null` | Project **ingest key** from the Bliptrace dashboard. |
| `endpoint` | `https://ingest.bliptrace.com/v1/ingest` | Ingest URL. A bare base URL also works (`/v1/ingest` is appended when missing). |
| `environment` | `production` | Logical environment label. |
| `release` | `null` | Release / version identifier. |
| `timeout` | `3` | HTTP timeout (seconds) for the send. |
| `max_frames` | `30` | Maximum stack frames captured. |
| `max_message` | `2000` | Maximum exception-message length. |
| `ignore_exceptions` | `[]` | Exception classes never reported (noise). |
| `scrub_keys` | sensible defaults | Context keys redacted before sending (client-side scrub). |

> **The project is resolved server-side from the ingest key** — it is never sent
> in the payload. This is an anti-spoofing guarantee: an ingest key maps to
> exactly one project on the Bliptrace backend.

## Usage

Once registered, capture is automatic: the bundle listens on
`kernel.exception`, builds a scrubbed envelope enriched with the current request
context (method, URL, headers with `authorization`/`cookie` stripped), and ships
it on `kernel.terminate` — **after** the response has been sent, so a slow or
unavailable ingest endpoint never affects the monitored application.

To report an exception manually, fetch the reporter service:

```php
use Bliptrace\Symfony\Reporter;

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
