# Architecture And Component Boundaries

This package groups several related concerns, but keeps them behind explicit boundaries. The goal is to make each component understandable, testable, and independently usable without coupling integrations to one another.

The main components are:

- `Core`: framework-independent event model, context, normalization, redaction, sampling, and emitter contracts.
- `Messenger`: message middleware and correlation stamps.
- `Monolog`: the Monolog emitter adapter.
- `OpenTelemetry`: optional trace and span correlation.
- Symfony bundle wiring: configuration, service composition, and HTTP lifecycle integration.

## Dependency Direction

```text
Symfony bundle wiring ───┐
HTTP integration ────────┼──> Core
Messenger integration ───┤
Monolog integration ─────┤
OpenTelemetry adapter ───┘
```

- `Core` must not import Symfony, Messenger, Monolog, or OpenTelemetry.
- Each integration may depend on `Core` and its own external library contracts.
- Integrations must not import one another.
- Shared behavior must use explicit `Core` contracts instead of implementation classes from another component.
- The bundle is the composition root and wires implementations together; Core classes must not access the Symfony container.

These rules prevent optional integrations from becoming mandatory dependencies and keep changes localized to the component that owns the responsibility.
