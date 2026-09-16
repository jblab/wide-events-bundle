# Integrations

## HTTP

The bundle subscribes to the main Symfony kernel request, exception, and response events. It starts a fresh context at request start, records safe request metadata, and emits once at response time. Telemetry exceptions are contained and do not replace the application response.

Response request-ID propagation can be disabled:

```yaml
jblab_wide_events:
    request_id:
        propagate_response: false
```

## Messenger

The bundle registers `WideEventMiddleware` as Messenger middleware when enabled. Add it to a bus if the application's Messenger configuration does not automatically use tagged middleware:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        buses:
            messenger.bus.default:
                middleware:
                    - Jblab\WideEvents\Messenger\WideEventMiddleware
```

The bundle's service ID is `Jblab\WideEvents\Messenger\WideEventMiddleware`. The middleware emits on success and failure, captures retry count and handler identity when available, and resets shared context in all cases.

Messages carry only the four correlation identifiers in `WideEventCorrelationStamp`: `request_id`, `trace_id`, `span_id`, and `causation_id`.

## Monolog

`MonologEventEmitter` calls `LoggerInterface::info()` with the event name as the message and the complete event array as structured context. A dedicated channel and JSON formatter are recommended. The bundle does not choose a transport or parse ordinary log messages.

## OpenTelemetry

When enabled, `OpenTelemetryCorrelationProvider` reads the current valid span context and supplies `trace_id` and `span_id` to HTTP and Messenger events. The rest of the bundle works without the optional OpenTelemetry API package.

## JSON stdout deployment

For containers, configure the dedicated Monolog handler to write JSON to `php://stdout` and let the runtime or platform collect the stream. Keep application logs and canonical wide events on separate channels so downstream ingestion can distinguish them without parsing message text.
