# Dependency Tracking Notes

## DEP-001: `nikic/fast-route` migration plan

- **Current state:** `update-api` is pinned to `nikic/fast-route:^1.3` (`v1.3.0` resolved in lockfile).
- **Compatibility review date:** April 6, 2026.
- **What we verified:**
  - `composer show nikic/fast-route --all` confirms `v1.3.0` is the currently installed stable line and that `2.0.0-beta1` exists.
  - `composer require nikic/fast-route:^2.0.0-beta1 --dry-run` resolves successfully but introduces `psr/simple-cache` and upgrades to a **beta** release.
- **Why upgrade is deferred right now:**
  1. Available maintained line is currently a beta channel (`2.0.0-beta1`), which we do not want to promote directly into production without stabilization.
  2. We want to validate runtime behavior against our route setup and dispatch flow before adoption.
- **Risk-reduction step already completed:**
  - Constrained FastRoute integration to `App\Core\RouteDispatcherFactory` so the eventual migration can happen in one adapter boundary.
- **Planned migration ticket:** `DEP-001` (target: move to stable FastRoute v2 once released, then remove legacy v1 constraint).
