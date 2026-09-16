# Wide Events Bundle <img src="https://assets.jblab.info/2024/03/17/jblab-logo-with-text.26da23672fc44c17078dc8ce2ff8495ddb190163.webp" alt="jblab logo" width="120" align="right" style="max-width: 100%">

[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg?style=flat-square)](LICENSE) [![Latest Release](https://img.shields.io/github/release/jblab/wide-events-bundle.svg?style=flat-square)](https://github.com/jblab/wide-events-bundle/releases/latest) [![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/jblab/wide-events-bundle/ci.yml?style=flat-square)](https://github.com/jblab/wide-events-bundle/actions/workflows/ci.yml)

**Wide Events Bundle** is a Symfony bundle for emitting one structured, canonical event for each completed HTTP request or handled Messenger message. Applications enrich the event under `context`; the bundle owns lifecycle fields, correlation, normalization, redaction, sampling, and reset behavior.

## Installation

### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```bash
composer require jblab/wide-events-bundle
```

### Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute:

```bash
composer require jblab/wide-events-bundle
```

#### Step 2: Enable the Bundle

Add the bundle to `config/bundles.php`:

```php
return [
    // ...
    Jblab\WideEvents\JblabWideEventsBundle::class => ['all' => true],
];
```

The bundle is disabled until it is explicitly configured with `enabled: true` and an emitter service.

> [!TIP]
> Monolog is an optional emitter integration, not a bundle requirement. To use the built-in `MonologEventEmitter`, install Monolog and the Symfony Monolog bundle separately:
>
> ```bash
> composer require monolog/monolog symfony/monolog-bundle
> ```
>
> You can use any service implementing `EventEmitterInterface` when Monolog is not the desired destination.

## Usage

Configure a dedicated Monolog channel and use the built-in structured emitter:

```yaml
# config/packages/monolog.yaml
monolog:
    channels: [wide_events]
    handlers:
        wide_events:
            type: stream
            path: '%kernel.logs_dir%/wide-events.log'
            level: info
            channels: [wide_events]
            formatter: monolog.formatter.json
```

```yaml
# config/services.yaml
services:
    Jblab\WideEvents\Monolog\MonologEventEmitter:
        arguments:
            $logger: '@monolog.logger.wide_events'
```

Enable the bundle and select the emitter:

```yaml
# config/packages/jblab_wide_events.yaml
jblab_wide_events:
    enabled: true
    emitter: Jblab\WideEvents\Monolog\MonologEventEmitter
    service:
        name: orders-api
        environment: '%kernel.environment%'
```

Enrich the current event from application code by injecting `WideEventContext`:

```php
use Jblab\WideEvents\Core\Event\WideEventContext;

final class OrderController
{
    public function __invoke(WideEventContext $wideEvent): Response
    {
        $wideEvent->set('order.id', 'order-42');
        $wideEvent->set('order.total_cents', 1299);
        $wideEvent->addTiming('inventory_ms', 7.4);

        return new Response('accepted');
    }
}
```

The HTTP integration emits `http.request.completed` once for the main request. It includes the request ID, method, path, route when available, outcome, status, duration, configured service metadata, and safe error identity.

## Configuration

The following example shows the available options and their defaults:

```yaml
jblab_wide_events:
    enabled: false
    emitter: null
    service: {}
    request_id:
        propagate_response: true
    limits:
        max_event_bytes: 65536
        max_fields: 200
        max_depth: 8
        max_string_bytes: 4096
        oversized_value_strategy: truncate # truncate | drop | reject
    redaction:
        keys: []
        allowed_keys: []
        strict_allow_list: false
    sampling:
        sample_rate: 0.1
        slow_event_threshold_ms: 1000
    opentelemetry:
        enabled: false
```

When enabled, `emitter` must reference an `EventEmitterInterface` service. OpenTelemetry correlation additionally requires `open-telemetry/api`:

```bash
composer require open-telemetry/api
```

See the detailed [configuration documentation](docs/configuration.md) for redaction, request IDs, limits, sampling, and optional integrations.

## Documentation

- [Event schema and enrichment](docs/events.md)
- [Privacy, redaction, and limits](docs/privacy-and-limits.md)
- [HTTP, Messenger, Monolog, and OpenTelemetry integrations](docs/integrations.md)
- [Migration from ordinary Monolog logs](docs/migration.md)
- [Architecture and component boundaries](docs/architecture.md)

## Development

Development commands use Docker so the host PHP installation is not required. Install [Just](https://github.com/casey/just) and Docker first.

The `justfile` uses PHP 8.2 for local commands and runs the test suite across PHP 8.2, 8.3, 8.4, and 8.5:

```bash
just help
```

Use the individual recipes when needed:

```bash
just lint       # PHP syntax linting
just stan       # PHPStan
just cs         # PHP CS Fixer check
just cs-fix     # PHP CS Fixer fix
just test       # PHPUnit tests across supported PHP versions
```

`just test` builds a separate Docker image for each supported PHP version. To run a command against the default PHP version, use `just composer install` or `just php --version`. The default can be changed through `default_version` in the `justfile`.

The named PHPUnit suites are `unit` and `integration`; run them inside the default container with `just php vendor/bin/phpunit --testsuite unit` or `just php vendor/bin/phpunit --testsuite integration`.

## Contributing

We welcome contributions, bug reports, and documentation improvements. See the [contribution guidelines](CONTRIBUTING.md) before opening a pull request.

## License

This bundle is released under the [Apache 2.0 License](LICENSE).
