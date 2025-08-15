# Changelog

All notable changes to this project will be documented in this file.
See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## Unreleased
- Added `vontmnt_get_api_key` helper in mu-plugins to cache API keys and auto-regenerate via the `/api/key` endpoint.
- Introduced `send_auth` flag and `KeyController` so keys are retrievable once per regeneration.
- Updated installation to use `VONTMNT_UPDATE_KEYREGEN` instead of `VONTMENT_KEY`.
- Consolidated `VONTMENT_PLUGINS` and `VONTMENT_THEMES` into a single `VONTMNT_API_URL` constant.
- **Split update loops into single-item tasks**: Refactored plugin and theme updaters to use asynchronous per-item processing. Daily update checks now schedule individual `wp_schedule_single_event()` tasks for each plugin/theme instead of processing all items synchronously. Added `vontmnt_plugin_update_single()` and `vontmnt_theme_update_single()` callback functions.

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
