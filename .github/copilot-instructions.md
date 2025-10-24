# Copilot Instructions for V-WordPress-Plugin-Updater

## Architecture Overview

This is a **dual-component WordPress updater system**:
1. **Update API Server** (`update-api/`): A standalone PHP web service that hosts and serves plugin/theme updates
2. **WordPress Client Plugin** (`v-wp-updater/`): WordPress plugin that checks for and installs updates from the API server

Both components are independently deployable—the API runs on a web server, the client installs in WordPress sites.

### Key Architectural Patterns

**Separate Namespaces**: `App\` (API server) vs `VWPU\` (WordPress plugin)—never mix them.

**Database Strategy**: 
- API uses SQLite via Doctrine DBAL (`storage/updater.sqlite`)
- Schema: `plugins`, `themes`, `hosts`, `logs`, `blacklist` tables
- Managed by `DatabaseManager::getConnection()` (singleton)
- Cron sync (`cron.php`) keeps database in sync with filesystem

**Routing**: FastRoute-based dispatcher in `App\Core\Router` with Response objects (not direct output). All routes require authentication except `/api` (validates domain+key) and `/login`.

**Security**: 
- API keys encrypted with `App\Helpers\Encryption` using `ENCRYPTION_KEY` env var
- IP blacklisting auto-expires (7 days blocked, 3 days unblocked)
- `SessionManager` enforces timeout (1800s) and user agent validation

## Critical Developer Workflows

### Running Tests
```powershell
vendor/bin/phpunit           # All tests
vendor/bin/phpunit tests/RouterTest.php  # Single test
```
**Test Pattern**: Many tests use `runScript()` to execute code in subprocess with namespace mocking (see `RouterTest.php`). Define mock functions in `App\Core` namespace before loading app code.

### Code Quality Checks
```powershell
vendor/bin/phpcs             # Check all (separate rules: PSR12 for update-api/, WordPress-Core for v-wp-updater/)
vendor/bin/phpcbf            # Auto-fix formatting
vendor/bin/phpstan           # Static analysis (level 6)
```
**IMPORTANT**: Always run full suite before committing (see `AGENTS.md` checklist).

### Cron Job (Required)
```powershell
php update-api/cron.php      # Manual sync
php update-api/cron.php --worker  # Background worker mode
```
**Purpose**: Syncs plugin/theme ZIPs from filesystem to database, cleans blacklist. Must run regularly (system cron) or database becomes stale.

### Database Initialization
```powershell
cd update-api/public; php install.php  # Creates schema in storage/updater.sqlite
```
Run after cloning repo or when adding new tables. File must be writable by web server.

## Project-Specific Conventions

### File Naming for Updates
Update packages **must** follow: `{slug}_{version}.zip` (e.g., `my-plugin_1.2.3.zip`)
- Placed in `storage/plugins/` or `storage/themes/`
- Cron syncs to database with slug extraction and version parsing
- API serves newest version (via `version_compare()`) when client requests

### API Response Pattern
Controllers return `Response` objects, never echo directly:
```php
// ✓ Correct
return new Response(200, 'Hello');
return Response::file($path, $headers);
return Response::redirect('/home');

// ✗ Wrong
echo "Hello"; exit;
```

### Validation Flow
All external input goes through `App\Helpers\Validation`:
```php
$domain = Validation::validateDomain($input);  // Returns null if invalid
if ($domain === null) { return new Response(400); }
```
Used for domain, key, slug, version. Whitelist approach—reject early.

### WordPress Plugin Options
Client plugin stores config in `vontmnt_*` options (managed by `VWPU\Helpers\Options`):
- `vontmnt_api_key`: Encrypted API key (never exposed in API responses)
- `vontmnt_update_plugins`: Enable/disable plugin updates
- `vontmnt_update_themes`: Enable/disable theme updates

Check via `Options::is_true('update_plugins')` before running updaters.

### Logging Standards
```php
ErrorManager::getInstance()->log($message, 'info');  // API server
Logger::info($message, $context);                    // WordPress plugin
```
API logs to `LOG_FILE` (default: `storage/logs/app.log`). WordPress uses WP debug log when enabled.

## Integration Points

### Server Setup
1. Set `update-api/public/` as web server document root
2. Configure `update-api/config.php`:
   - `VALID_USERNAME`, `VALID_PASSWORD_HASH` (use `password_hash()`)
   - `ENCRYPTION_KEY` (32-byte hex, load from env: `getenv('ENCRYPTION_KEY')`)
3. Ensure `storage/` writable by web server
4. Run `php install.php` to create database
5. Add cron: `*/15 * * * * php /path/to/update-api/cron.php --worker`

### WordPress Client Setup
1. Copy `v-wp-updater/` to `wp-content/plugins/`
2. Set options (via provisioning or wp-config):
   ```php
   define('VONTMNT_API_URL', 'https://updates.example.com/api');
   ```
3. Store API key: `update_option('vontmnt_api_key', 'your-encrypted-key');`
4. Activate plugin—scheduled checks run automatically

### Adding New Routes
**Update-API Server:**
```php
// In App\Core\Router::__construct()
$r->addRoute('GET', '/newpage', ['\\App\\Controllers\\NewController', 'handleRequest']);
$r->addRoute('POST', '/newpage', ['\\App\\Controllers\\NewController', 'handleSubmission']);
```
**Pattern**: GET shows form/view, POST handles submission. Both methods return `Response` objects.

### Dual-Component Communication Flow
```
WordPress Site                API Server
    ↓                              ↓
PluginUpdater.php  →  API request  →  ApiController.php
(checks version)      (domain+key)      (validates & serves ZIP)
    ↓                              ↓
Downloads ZIP                 Logs request in database
    ↓
Installs via WP upgrader
```

## Common Patterns

### Testing Controllers with Mocks
```php
// Subprocess pattern from RouterTest.php
$code = <<<'PHP'
namespace App\Core { function header($h){ echo $h; } }  // Mock header()
namespace { require 'update-api/vendor/autoload.php'; /* test code */ }
PHP;
exec('php -r ' . escapeshellarg($code), $output);
```
Why: Allows namespace-level mocking without test framework pollution.

### Encryption Helpers
```php
$encrypted = Encryption::encrypt('plaintext');  // Stores in database
$plain = Encryption::decrypt($encrypted);        // Retrieves for comparison
```
Uses `ENCRYPTION_KEY` constant—never commit real keys.

### Version Comparison
```php
if (version_compare($dbVersion, $clientVersion, '>')) {
    // Serve update
}
```
PHP's built-in `version_compare` handles semantic versioning correctly.

## Documentation Sync
When changing functionality, update in order:
1. Code implementation
2. Tests (`tests/` directory)
3. `CHANGELOG.md` (notable changes only)
4. `README.md` (if user-facing or setup changes)
5. This file (if architecture/conventions change)

See `AGENTS.md` for full pre-commit checklist.
