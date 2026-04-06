# Changelog

All notable changes to this project will be documented in this file.
See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## Unreleased

- Removed redundant `SessionManager::requireAuth()` wrapper and updated router auth checks to use `SessionManager::isValid()` directly (no behavior change).

## 4.5.0
- Upgraded `nikic/fast-route` to `2.0.0-beta1` and aligned `Router` with the v2 configuration interface.
- Raised minimum PHP requirements to match the current dependency set:
  - `v-update-api` now declares PHP 8.2+ to align with Doctrine DBAL 4.x.
  - README now distinguishes the API server PHP 8.2+ requirement from the WordPress plugin PHP 8.0+ requirement.
- Refactored cron execution flow:
  - Moved cron orchestration into `CronHelper::runCronJob()` and made internal sync/cleanup methods private.
  - Simplified `v-update-api/cron.php` to CLI-only execution that delegates directly to `CronHelper::runCronJob()`.
  - Removed obsolete lock/worker helper usage and deleted `WorkerHelper`.
- Consolidated secure random-byte generation into `EncryptionHelper::bytes()`:
  - Deleted `SecureRandomHelper` and updated consumers (`EncryptionHelper`, `LoginController`, `public/index.php`) to use the centralized helper.
- Removed `RouteDispatcherFactory`; `Router` now builds its FastRoute dispatcher directly via `simpleDispatcher`.
- Strengthened model/controller consistency and failure-safe update flows:
  - `PluginModel` and `ThemeModel` uploads now stage files, perform DB upserts in transactions, and apply rollback/compensation when filesystem or DB steps fail.
  - `deletePlugin`/`deleteTheme` now validate `unlink()` success, skip DB deletion on file-delete failures, and emit structured log entries for invalid path/race/permission cases.
  - `HostsModel::getEntries()` now returns structured `{domain, key}` rows; host update/delete APIs are domain-keyed (removed misleading unused line parameters), and `HomeController` no longer parses concatenated host strings.
  - `HomeController::handleSubmission()` now reports delete failures with explicit log+message behavior, aligned with add/regenerate flows.
  - `ApiController` key comparison now uses `hash_equals` after decryption for constant-time credential checks.
  - Added regression coverage for delete failure handling and upload rollback paths in plugin/theme model tests, and updated host/home tests for structured host rows and domain-keyed mutations.
- Tightened session, lockout, and key-display security controls:
  - `SessionManager::destroy` now expires the active session cookie with matching cookie attributes before `session_destroy()`.
  - `BlacklistModel::updateFailedAttempts` now refreshes `timestamp` on every failed attempt while preserving atomic UPSERT behavior.
  - `HomeController` host listing now masks keys by default, adds explicit reveal/copy controls, and limits plaintext display to a short-lived (30s) session-backed reveal window; UI toasts/messages never include full keys.
  - Added regression coverage in `SessionDestroyCookieTest`, `BlacklistModelTest`, and new `HomeControllerTest` for these behaviors.
- Hardened file-ingest edge cases across upload and cron sync flows:
  - `PluginModel::uploadFiles` and `ThemeModel::uploadFiles` now normalize both single-file and multi-file upload payload shapes, validate required keys/types before indexing, and emit explicit malformed-entry errors instead of runtime warnings.
  - `CronHelper::syncDir` now guards unreadable/missing directories and `glob()` failures, logs sync-read failures, and exits safely without deleting existing records when scanning cannot proceed.
  - Added regression tests for upload payload normalization in `PluginModelDbTest`/`ThemeModelDbTest` and sync-read failure behavior in `CronHelperTest`.
- Hardened host onboarding and key storage paths:
  - `v-update-api/public/install.php` now defensively parses `HOSTS` imports, skips/records malformed records, validates domains, normalizes plaintext/legacy keys into the expected encrypted storage format, and logs normalization summaries for traceability.
  - `HostsModel::updateEntry` now uses `UPDATE` instead of `REPLACE INTO` to avoid delete+insert side effects while preserving encrypted key storage behavior.
  - Added `tests/HostsModelTest.php` regression coverage for update-in-place behavior, missing-domain failures, and foreign-key-safe host updates.
- Removed the unused `Controller::render()` helper from `v-update-api/app/Core/Controller.php` to reduce view-render attack surface and keep rendering centralized in `Router::sendResponse`.
- Refined Update API failure semantics and lockout accounting:
  - `ApiController` now returns `400` for malformed input, `403` only for authentication failures, `204` for no-update responses, and `404` for authenticated unknown slugs.
  - Blacklist increments now occur only for credential/auth failures (e.g., unknown domain or wrong key), not malformed requests or authenticated unknown slugs.
- Unified canonical slug handling across validation and model workflows:
  - Added shared slug/filename parsing in `ValidationHelper` (`validateSlug`, `validateFilename`, `parsePackageFilename`).
  - Updated plugin/theme upload and delete flows to use the canonical parser, including dotted slugs and underscored slugs.
- Added regression coverage for slug and lockout edge cases:
  - `ApiControllerTest` now verifies malformed requests do not consume blacklist budget, wrong keys do consume budget, and authenticated unknown slugs return `404` without budget impact.
  - Model/validation tests now cover dotted slugs, underscored slugs, and single-file plugin slug filename handling end-to-end.
  - Added `PluginUpdaterStatusTest` for single-file plugin enumeration (`single-file-plugin.php` → `single-file-plugin`) and directory plugin slug behavior.
- Replaced remaining obsolete `mu-plugin` test/tooling references with active `v-wp-updater` paths in `ApiKeyHelperTest`, `UpdaterEncodingTest`, and `phpstan.neon`.
- Unified updater status options to canonical `vwpu_*` keys (`vwpu_plugin_update_status`, `vwpu_theme_update_status`) across updater services, activation defaults, and uninstall cleanup.
- Added activation-time migration from legacy status keys (`vontmnt-*`, `vwpu-*` hyphen variants) to canonical status keys so existing installs retain prior status data.
- Hardened deactivation/uninstall execution by validating `uninstall.php` existence, guarding helper calls with `function_exists(...)`, and logging structured errors while safely continuing.
- Renamed `v-update-api/app/Core/Response.php` to `v-update-api/app/Core/ResponseManager.php` and updated all controller/router/test references accordingly.
- Moved CSRF token initialization into the `ErrorManager::handle(...)` request callback in `v-update-api/public/index.php` so token generation exceptions are handled through the centralized error pathway.
- Hardened database bootstrap in `DatabaseManager` by using least-privilege directory permissions (`0750`), validating `mkdir`/`touch` outcomes, and throwing/logging controlled runtime exceptions with context when file-system setup fails.
- Updated `/api` routing semantics so `/api` is always treated as an API route (no auth redirect fallback) and returns consistent API status behavior (`400` validation errors, `403` auth failure, `405` method mismatch).
- Simplified cron execution by removing worker mode (`--worker`/`worker`) and documenting single-run daily cron scheduling.
- Switched admin password configuration from hash-based verification to plain-text `VALID_PASSWORD` comparison in `config.php` and `LoginController`.

- Replaced login-session CSRF token regeneration in `LoginController` with cryptographically secure output using `bin2hex(random_bytes(32))` while preserving existing timeout and session ID regeneration flow.
- Fixed API query argument construction in `PluginUpdater` and `ThemeUpdater` by removing pre-encoding (`rawurlencode`) so `add_query_arg` performs a single RFC3986 encoding pass.
- Hardened package delete parsing in `PluginModel::deletePlugin` and `ThemeModel::deleteTheme` using strict `^([A-Za-z0-9_-]+)_([0-9.]+)\.zip$` extraction, including regression coverage for underscore-containing slugs and non-matching filenames.
- Hardened file response streaming:
  - `ApiController` now validates update package readability and `filesize()` before building a file response.
  - `Router` rejects unreadable file responses and emits deterministic `500` text responses.
  - `Response::send` now suppresses raw `readfile` warnings, logs failures, and follows a controlled `500` fallback path for unreadable files.
- Dependency follow-up (`nikic/fast-route`):
  - Reviewed upgrade path from `v1.3.0` to `2.0.0-beta1` (composer dry-run succeeds but targets a beta line and introduces `psr/simple-cache`).
  - Deferred immediate upgrade and added explicit migration tracking in `README.md` and `v-update-api/docs/dependency-tracking.md` (ticket `DEP-001`).
  - Constrained FastRoute usage behind `App\Core\RouteDispatcherFactory` to reduce migration surface for the eventual v2 adoption.
- **Aligned update-check contract (Option A):** Unified the request/response protocol used between
  the WordPress client plugin and the Update API server.
  - `PluginUpdater::fetch_package` now sends `type=plugin&slug=<slug>` instead of `plugin=<slug>`.
  - `ThemeUpdater::fetch_package` now sends `type=theme&slug=<slug>` instead of `theme=<slug>`.
  - Both updaters now treat HTTP `403` as the auth-failure signal (replacing the previous incorrect
    `401` check) to match the status code returned by `ApiController`.
  - Both updaters now handle the direct binary ZIP response on HTTP `200` (no JSON `zip_url`
    parsing) and return the authenticated API URL as `download_url` for `AbstractRemoteUpdater`.
  - Added `tests/ApiControllerTest.php` covering request validation, auth failure (`403`),
    no-update (`204`), and successful update (`200`) for both `plugin` and `theme` types.
  - Added `tests/UpdaterFetchPackageTest.php` with 22 tests locking parameter names, status-code
    branches, WP_Error handling, and a regression test proving a valid request reaches the
    install path.
  - Updated `v-wp-updater/api/API_SCHEMA.md` with the canonical update-check endpoint contract.
- Updated `v-update-api/cron.php` to accept the positional `worker` argument, reject unknown CLI options, and propagate non-zero exit codes when cron work fails via `ErrorManager`.
- Added integration tests covering worker invocation, argument validation, and CLI error handling, plus lightweight mu-plugin fixtures required for the suite.
- **Updated README.md to match current codebase**: Replaced all references to obsolete `mu-plugin/` directory with `v-wp-updater/`. Updated project structure documentation to reflect dual-component architecture (Update API Server + WordPress Client Plugin). Removed references to non-existent files (HOSTS, autoload.php) and controllers (AccountsController, InfoController, UsersController). Added documentation for SiteLogsController and cron.php with worker mode. Updated installation and usage sections with accurate paths and separate setup procedures for API server and client plugin.
- Removed legacy key-exchange workflow; clients now use a stored API key.
- Updated installation to use `VONTMNT_UPDATE_KEYREGEN` instead of `VONTMENT_KEY`.
- Consolidated `VONTMENT_PLUGINS` and `VONTMENT_THEMES` into a single `VONTMNT_API_URL` constant.
- **Split update loops into single-item tasks**: Refactored plugin and theme updaters to use asynchronous per-item processing. Daily update checks now schedule individual `wp_schedule_single_event()` tasks for each plugin/theme instead of processing all items synchronously. Added `vontmnt_plugin_update_single()` and `vontmnt_theme_update_single()` callback functions.
- **Expanded test coverage to reflect current codebase**: Added comprehensive tests for ThemeModel, Encryption, Blacklist, CronWorker, Validation, Response, Csrf, and MessageHelper classes. Test suite expanded from 37 to 105 tests with 241 assertions, providing coverage for all major components including models, helpers, and core classes.
- Stored admin password as a hash and verified with `password_verify` during login.
- Controllers now return structured `Response` objects; router and session handling updated accordingly.
- Expanded filename validation to allow digits and underscores in slugs and updated tests.
- Introduced configurable `LOG_FILE` and centralized logging through `ErrorManager`.
- Made `SessionManager::requireAuth` non-terminating, returning a boolean instead.
- Enhanced `vontmnt_get_api_key` with wp-config backups and validation.
- Streamlined plugin updates using a single streaming `wp_remote_get` call.
- Removed HTML escaping in `HostsModel` in favor of parameterized queries.

## 4.0.0
- Added PHP_CodeSniffer with WordPress Coding Standards for linting.
- Moved validation helpers to `App\Helpers\Validation` and encryption helpers to `App\Helpers\Encryption`.
- Added `App\Models\Blacklist` for IP blacklist management and removed `App\Core\Utility`.
- Introduced centralized `SessionManager` and `Csrf` utilities, refactored controllers and routing to use them, and replaced `AuthController` with `LoginController`.
- Switched router to instantiate controllers, dropped unused account/user/info routes, and added `/api` endpoint.
- Updated `LoginController` to render views through `$this` instead of creating a new instance.
- Converted controllers to instance methods using `$this->render` and removed the feeds controller and route.
- Refined router dispatch to include HTTP method and validate API requests before enforcing authentication.
- Streamlined session validation to check only timeout and user agent, moved IP blacklist enforcement to authentication, and added unit tests for session expiry, user-agent changes, and blacklist handling.
- Refactored router into a singleton and documented root URL redirection to `/home`.
- Restricted table generation helpers in controllers and `SessionManager::isValid` to internal use and updated tests accordingly.
- Fixed PHPStan reported issues by initializing variables, adding explicit type annotations, and excluding vendor code from analysis.
- Introduced SQLite persistence using Doctrine DBAL with install and cron scripts, and migrated models and controllers to use the database.
- Replaced JSON-based blacklist with SQLite table that automatically resets entries after three days.
- Moved blacklist table creation to installer script.
- Removed `rawurlencode` from updater request parameters to prevent double encoding.
- Added `WP_Error` checks after `wp_remote_get` calls to log network failures and skip processing.
- Corrected the header in `v-sys-theme-updater.php` so it loads as a plugin.
- Updated `SessionManager::requireAuth` to return a boolean and halt routing for blacklisted IPs.
- Logged failed package writes in plugin and theme updaters and skipped installation when writes fail.
