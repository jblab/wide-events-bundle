# Wide Events Bundle Documentation

The Wide Events Bundle emits one structured, canonical event for each completed HTTP request or handled Messenger message. Applications enrich events under `context`; the bundle owns lifecycle fields, correlation, normalization, redaction, sampling, and reset behavior.

Start with the [README](../README.md) for installation and a quick-start configuration.

- [Configuration](configuration.md): enable the bundle, choose an emitter, and configure limits, redaction, sampling, and OpenTelemetry.
- [Events and enrichment](events.md): canonical event schema and the `WideEventContext` API.
- [Integrations](integrations.md): HTTP, Messenger, Monolog, and OpenTelemetry behavior.
- [Privacy and limits](privacy-and-limits.md): redaction, payload limits, and safe telemetry practices.
- [Migration](migration.md): adopt wide events alongside ordinary Monolog logs.
- [Architecture](architecture.md): component boundaries and dependency direction.
