# Events And Enrichment

## Canonical event shape

Every finalized event uses schema version `1` and these top-level fields:

```json
{
  "schema_version": 1,
  "event": "http.request.completed",
  "timestamp": "2026-09-16T12:00:00.000Z",
  "service": {},
  "request": {},
  "message": {},
  "outcome": {},
  "error": {},
  "context": {},
  "meta": {}
}
```

Bridge-owned fields are written by the HTTP or Messenger integration. Application-owned values belong under `context` and must not be added as replacement top-level fields.

## Context API

Inject `Jblab\WideEvents\Core\Event\WideEventContext` into application services:

```php
$context->set('customer.id', $customer->id());
$context->merge([
    'cart' => ['items' => 3],
    'checkout.currency' => 'EUR',
]);
$context->addTiming('pricing_ms', 12.5);
$context->recordError($exception, code: 'payment_declined', retryable: false);
$context->markOutcome('success', ['cache' => 'hit']);
```

`set()` and `merge()` use dot-separated paths. Associative arrays merge recursively; scalar conflicts are replaced. Error recording stores the exception class and optional safe domain code and retryability, not the exception message or stack trace.

The context is reset after every HTTP request and Messenger message. A finalized context cannot be mutated until it is reset.

## HTTP events

The event name is `http.request.completed`. The request section contains `method`, `path`, `request_id`, an optional route name, and valid trace/span identifiers. The outcome section contains `status`, `http_status`, and `duration_ms`. Only the main request creates an event; subrequests are ignored.

## Messenger events

The event name is `messenger.message.completed`. The message section contains the message class, transport and handler when available, and retry count. The request section carries only `request_id`, `trace_id`, `span_id`, and `causation_id` through `WideEventCorrelationStamp`.

Errors and slow events are always kept. Other events are deterministically sampled using the configured request or trace identity. The core sampling policy also recognizes an explicit `meta.retain` flag for integrations that construct events directly.
