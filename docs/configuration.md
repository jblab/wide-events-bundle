# Configuration

The bundle is inert by default. Enable it only in environments where a destination has been configured.

```yaml
# config/packages/jblab_wide_events.yaml
jblab_wide_events:
    enabled: true
    emitter: Jblab\WideEvents\Monolog\MonologEventEmitter
    service:
        name: orders-api
        environment: '%kernel.environment%'
        version: '2026.09'
    request_id:
        propagate_response: true
    limits:
        max_event_bytes: 65536
        max_fields: 200
        max_depth: 8
        max_string_bytes: 4096
        oversized_value_strategy: truncate
    redaction:
        keys: [account_number]
        allowed_keys: []
        strict_allow_list: false
    sampling:
        sample_rate: 0.1
        slow_event_threshold_ms: 1000
    opentelemetry:
        enabled: false
```

## Required settings

`enabled` defaults to `false`. When it is `true`, `emitter` must contain the service ID of an `EventEmitterInterface` implementation. Container compilation fails when the emitter is missing.

`service` is explicit application metadata. The bundle does not inspect the environment or package metadata to invent service identity.

## Emitter services

The built-in `MonologEventEmitter` writes the full event as structured PSR-3 context to the logger injected into it. Configure its logger, channel, formatter, and transport in the host application. A custom service can implement `Jblab\WideEvents\Core\Emission\EventEmitterInterface` when another destination is required.

## Request IDs

The HTTP integration accepts an inbound `X-Request-Id` containing only letters, digits, `.`, `_`, `:`, or `-`, up to 128 bytes. Invalid or missing values are replaced with a generated identifier. `request_id.propagate_response` controls the `X-Request-Id` response header and defaults to `true`.

## Optional OpenTelemetry

Install `open-telemetry/api` and enable the integration explicitly:

```bash
composer require open-telemetry/api
```

```yaml
jblab_wide_events:
    opentelemetry:
        enabled: true
```

Enabling this option without the package is a configuration error. Core and non-OpenTelemetry installations remain dependency-free from the SDK.
