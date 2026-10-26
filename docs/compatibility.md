# Compatibility Policy

The library follows semantic versioning after its first stable 2.x release.

## Patch releases

Patch releases may fix defects, improve documentation, add tests, and improve performance without changing public behavior.

## Minor releases

Minor releases may add optional public methods, interfaces, configuration fields, and extension capabilities. Existing supported usage must retain its behavior.

## Major releases

Major releases may change public method signatures, state semantics, exception types, PHP requirements, extension ordering, or configuration defaults.

## Public API

The public API is the `tommyknocker\chain` namespace, documented method contracts, exception types, and Composer requirements. Files under `tests/`, `examples/`, `benchmarks/`, and members marked `@internal` are not compatibility commitments.

## Deprecation

When a supported public API needs replacement, a minor release should add the replacement and mark the old API deprecated. The old API remains available until the next major release unless retaining it would cause a correctness or security defect.
