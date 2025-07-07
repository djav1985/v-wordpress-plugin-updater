# Copilot Instructions for v-wordpress-plugin-updater

## Project Overview
- This project provides a secure, modular API and admin interface for managing WordPress plugin and theme updates, with support for both single and multisite environments.
- The codebase is split between a PHP-based update API (in `update-api/`) and WordPress MU plugins (in `mu-plugin/`).
- Security is a priority: input validation, CSRF protection, session management, and IP blacklisting are enforced throughout.

## Key Components
- `update-api/` contains the API, admin UI, and all supporting logic:
  - `app/Core`: Framework classes such as the base controller, router and utilities.
  - `app/Controllers`: Handle authentication along with plugin, theme, log and host actions.
  - `app/Models`: Provide data access for plugins, themes, hosts and logs.
  - `app/Views`: Admin UI pages for managing plugins, themes, logs and hosts.
  - `app/Lib`: Loader and autoloader used by the public entry points.
  - `public/`: Web entrypoints (API, login, dashboard, etc.), assets, and `.htaccess` for routing.
  - `storage/`: Uploaded plugin/theme packages, logs, and blacklists.
- `mu-plugin/`: WordPress-side updaters that call the API to automate updates.

## Developer Workflows
- **No build step required**; PHP is interpreted directly.
- To run locally, ensure a PHP web server with write access to `update-api/storage`.
- To test plugin/theme update flows, use the admin UI at `/update-api/public/` and upload via the provided forms.
- Logs are written to `update-api/storage/logs/` and can be viewed in the admin UI.
- Use the `BLACKLIST.json` file to block specific plugins/themes from updates.

## Project-Specific Patterns
- All file uploads and deletions are handled by controllers (`PluginsController`, `ThemesController`) with strict validation and error logging.
- CSRF tokens are required for all POST actions in the admin UI; see `handleRequest()` in helpers for the pattern.
- All user-facing messages are stored in `$_SESSION['messages']` and rendered via `ErrorHandler`.
- Routing is managed by `.htaccess` and `app/Lib/Loader.php` to ensure only authenticated users can access admin pages.
- The MU plugins expect specific API endpoints and keys, configured in `wp-config.php`.

## Integration Points
- The API is consumed by the MU plugins in WordPress via HTTP requests.
- Authentication and WAF logic are handled by `AuthController.php` and `app/Lib/Loader.php`.
- All configuration (paths, credentials) is centralized in `update-api/config.php`.

## Examples
- To add a new admin page, create a view in `update-api/app/Views/`, a controller in `update-api/app/Controllers/`, and update routing in `app/Lib/Loader.php`.
- To add a new validation rule, extend `app/Core/UtilityHandler.php`.

## References
- See `README.md` for setup, directory structure, and usage.
- See `update-api/config.php` for all critical constants and credentials.
- See `update-api/public/.htaccess` for routing rules.

---

If you are unsure about a workflow or pattern, check the corresponding helper class or the README for guidance. When in doubt, prefer explicit validation, logging, and session-based messaging as seen throughout the codebase.
