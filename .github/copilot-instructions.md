# Copilot Instructions for V-WordPress-Plugin-Updater

## Architecture Overview

This repository contains a **dual-component WordPress updater system**:

1. **Update API Server** (`v-update-api/`): standalone PHP web service that hosts and serves plugin/theme ZIP updates.
2. **WordPress Client Plugin** (`v-wp-updater/`): WordPress plugin that checks the API and installs updates.

Both components are independently deployable.

### Namespaces (Do Not Mix)

- API server code uses `App\...`
- WordPress plugin code uses `VWPU\...`

## Current API Server Architecture (`v-update-api`)

### Core Classes

`v-update-api/app/Core/` currently contains:

- `DatabaseManager.php`
- `ErrorManager.php`
- `Request.php`
- `Response.php`
- `Router.php`

Important: there is **no** `SessionManager`, `ResponseManager`, or `Controller` base class in the current architecture.

### Request/Response Pattern

- `App\Core\Request::fromGlobals()` parses the URL path and builds a request object.
- `App\Core\Router::dispatch()` returns an `App\Core\Response`.
- `public/index.php` sends the returned response via `$response->send()`.
- Prefer `Response` static factories:
  - `Response::view()`
  - `Response::redirect()`
  - `Response::text()`
  - `Response::json()`
  - `Response::file()`
  - `Response::html()`

### Session and Security Helpers

Session/auth logic is implemented through `App\Helpers\SessionHelper` (static helper), not a singleton session manager.

- Initialize/access session values with `SessionHelper::get()` / `SessionHelper::set()`.
- Validate auth session with `SessionHelper::isValid()`.
- Regenerate session after successful login with `SessionHelper::regenerate()`.
- CSRF token is initialized in `public/index.php` if missing.

### Error Handling

`App\Core\ErrorManager` is a singleton that registers error/exception/shutdown handlers and provides centralized logging.

## Routing and Controller Conventions

### Router Behavior

`App\Core\Router`:

- Uses FastRoute (`FastRoute::recommendedSettings(...)`).
- Protects all non-login, non-API routes with auth checks.
- Applies CSRF checks to non-GET/HEAD/OPTIONS requests outside `/api`.
- Returns `Response` objects from dispatch.

### Registered Routes

- `/` → redirect to `/home`
- `/login` (`GET`, `POST`)
- `/home` (`GET`, `POST`)
- `/plupdate` (`GET`, `POST`)
- `/thupdate` (`GET`, `POST`)
- `/logs` (`GET`, `POST`)
- `/api` (router accepts multiple methods; `ApiController` enforces `GET`)

### Controller Contract

Controllers return `App\Core\Response` instances.
Do not use raw `echo`, `header()`, or `exit` for normal request flow.

## API Contract and Data Model

### Update API (`/api`)

Expected query parameters:

- `type` (`plugin` or `theme`)
- `domain`
- `key`
- `slug`
- `version`

`ApiController` behavior:

- `200`: newer ZIP available (streams file)
- `204`: no update available
- `400`: missing/invalid params
- `403`: authentication failure, blacklisted IP, or invalid/missing client IP
- `404`: authenticated request references unknown slug
- `405`: non-GET method
- `500`: update file unavailable/unreadable

### Storage and Sync

- SQLite DB: `v-update-api/storage/updater.sqlite`
- Key tables: `plugins`, `themes`, `hosts`, `logs`, `blacklist`
- `cron.php` syncs filesystem ZIP metadata to DB and cleans blacklist records

### Update Package Naming

ZIP filenames must use:

`{slug}_{version}.zip` (example: `my-plugin_1.2.3.zip`)

## Encryption and Auth Notes

- Host API keys are stored encrypted via `App\Helpers\EncryptionHelper`.
- Current implementation uses AES-256-GCM with legacy CBC migration support.
- `ENCRYPTION_KEY` is read from `v-update-api/config.php` constant.
- Blacklist behavior:
  - Blacklisted entries expire after 7 days.
  - Non-blacklisted stale entries are pruned after 3 days.

## WordPress Client Plugin (`v-wp-updater`)

### Options and Defaults

Plugin options are managed through `VWPU\Helpers\Options` with `vwpu_` prefix.

Canonical keys:

- `update_plugins`
- `update_themes`
- `update_key`
- `update_plugin_url`
- `update_theme_url`

Stored option names in WP are `vwpu_<key>` (for example, `vwpu_update_key`).

### Scheduled Hooks

- `vwpu_plugin_updater_check_updates`
- `vwpu_theme_updater_check_updates`

Both are scheduled daily on install; each handler checks enablement flags before running.

### Updater Behavior

`PluginUpdater` and `ThemeUpdater`:

- call the API with `type`, `domain`, `slug`, `version`, `key`
- handle statuses `200`, `204`, `403`, others as error
- prefetch ZIP bodies on `200` and reuse local temp file during install to avoid duplicate downloads

## Setup and Operations

### API Server Setup (Current)

1. Set `v-update-api/public/` as web root.
2. Configure `v-update-api/config.php` constants:
   - `VALID_USERNAME`
   - `VALID_PASSWORD`
   - `ENCRYPTION_KEY`
   - `SESSION_TIMEOUT_LIMIT`
3. Ensure `v-update-api/storage/` is writable.
4. Run `v-update-api/public/install.php` to initialize schema.
5. Schedule cron to run `php v-update-api/cron.php` (CLI only, no worker flag).

### Test and Quality Commands

From repository root:

- `vendor/bin/phpunit`
- `vendor/bin/phpcs`
- `vendor/bin/phpcbf`
- `vendor/bin/phpstan`

## Documentation Sync

When behavior changes, update in this order:

1. Code
2. Tests (`tests/`)
3. `CHANGELOG.md` (notable changes)
4. `README.md` (if setup/usage/API changed)
5. `.github/copilot-instructions.md` (architecture/conventions changes)

Also follow `AGENTS.md` checklist before submission.
