# Migration From Ordinary Monolog Logs

Wide events complement diagnostic logs; they do not replace all logs. A normal log records a point-in-time message, while a wide event records one completed request or message with its outcome and accumulated business context.

## Recommended migration

1. Install the bundle and configure a dedicated `wide_events` Monolog channel.
2. Keep existing application handlers and log levels unchanged.
3. Move stable request or business fields from repeated log context into `WideEventContext` enrichment.
4. Add redaction keys before enabling production emission.
5. Start with `sample_rate: 1.0` while comparing event coverage and payload size.
6. Lower the routine sample rate after validating dashboards and retention rules.

Avoid copying raw log messages, exception messages, request headers, or entire request bodies into `context`. Use stable identifiers and explicit safe domain fields instead.

## Field mapping

| Existing log concern       | Wide event location                                            |
|----------------------------|----------------------------------------------------------------|
| Request ID                 | `request.request_id` (bridge-owned)                            |
| HTTP status                | `outcome.http_status` (bridge-owned)                           |
| Duration                   | `outcome.duration_ms` (bridge-owned)                           |
| Order/customer identifiers | `context.order.*`, `context.customer.*`                        |
| Safe domain error code     | `context.errors[].code`                                        |
| Trace/span IDs             | `request.trace_id`, `request.span_id` (optional bridge fields) |

The event schema is intentionally stable and the package starts at version `0.x`; review release notes before upgrading.
