# Privacy And Limits

Redaction happens before normalization, sampling, and emission. The default redaction keys include `password`, `token`, `access_token`, `refresh_token`, `authorization`, `cookie`, `client_secret`, `private_key`, `credit_card`, and `ssn`. Matching is case-insensitive and treats punctuation as separators.

Add application-specific keys:

```yaml
jblab_wide_events:
    redaction:
        keys: [national_id, bank_account]
```

Strict allow-list mode drops every key that is not explicitly allowed. It requires at least one allowed key:

```yaml
jblab_wide_events:
    redaction:
        strict_allow_list: true
        allowed_keys: [schema_version, event, timestamp, service, request, message, outcome, error, context, meta]
```

The allow-list is applied recursively, so nested application keys must also be listed when they are expected to remain in the event.

Treat event context as production telemetry, not as a private scratchpad. Do not put secrets, credentials, authorization headers, full payment data, or raw exception messages into it.

## Bounds

The normalizer supports dates, enums, JSON-serializable values, stringable values, scalars, and arrays. Defaults are:

| Limit                      |      Default |
|----------------------------|-------------:|
| Maximum encoded event size | 65,536 bytes |
| Maximum fields             |          200 |
| Maximum nesting depth      |            8 |
| Maximum string size        |  4,096 bytes |

When `oversized_value_strategy` is `truncate`, strings are shortened safely and `meta.truncated` is added. `drop` removes oversized values and records `meta.dropped_fields`. `reject` throws during finalization; the integration catches telemetry failures so they do not break application flow.

## Sampling

The default sample rate for routine traffic is `0.1`. Errors, events with `context.retain: true`, and events at or above `slow_event_threshold_ms` are retained regardless of the routine rate. Set `sample_rate: 1.0` while validating an installation or investigating an issue.
