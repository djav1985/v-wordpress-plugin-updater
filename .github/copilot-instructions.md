# Copilot Instructions for V-WordPress-Plugin-Updater

## Architecture Overview

This is a **dual-component WordPress updater system**:
1. **Update API Server** (`update-api/`): A standalone PHP web service that hosts and serves plugin/theme updates
2. **WordPress Client Plugin** (`v-wp-updater/`): WordPress plugin that checks for and installs updates from the API server

Both components are independently deployable—the API runs on a web server, the client installs in WordPress sites.

### Key Architectural Patterns

**Separate Namespaces**: `App\` (API server) vs `VWPU\` (WordPress plugin)—never mix them.

**Unified Framework Architecture**:
Both `update-api/` and `root/` applications share identical core patterns:
- **SessionManager**: Singleton with CSRF management, timeout tracking, IP blacklisting (7 days blocked, 3 days unblocked)
- **Response**: Immutable fluent API with static factories (`Response::view()`, `Response::redirect()`, `Response::text()`)
- **Controller**: Base class with `render()` method; all handlers return Response objects (never echo/exit)
- **Entry Point**: Explicit URL parsing in `public/index.php` before routing (`parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)`)
- **Router**: Receives pre-parsed path; uses FastRoute dispatcher; enforces authentication except `/login` (and `/api` or `/feeds/` in respective apps)

**Architecture Differences** (intentional, domain-specific):
- **update-api**: Minimal framework, no Service layer, SQLite database, lightweight deployment
- **root**: Full-featured application with Service layer (StatusService, CacheService, QueueService), MySQL database, complex domain logic
Both approaches are valid—choose based on application complexity.

**Database Strategy**: 
- API uses SQLite via Doctrine DBAL (`storage/updater.sqlite`)
- Root uses MySQL via custom DatabaseManager with retry logic
- Schema: API has `plugins`, `themes`, `hosts`, `logs`, `blacklist` tables
- Managed by `DatabaseManager::getConnection()` or `DatabaseManager::getInstance()` (singletons)
- Cron sync (`cron.php`) keeps database in sync with filesystem

**Routing**: FastRoute-based dispatcher in `App\Core\Router` with Response objects (not direct output). All routes require authentication except `/api` (validates domain+key) and `/login`.

**Security**: 
- API keys encrypted with `App\Helpers\Encryption` using `ENCRYPTION_KEY` env var
- IP blacklisting auto-expires (7 days blocked, 3 days unblocked)
- `SessionManager` enforces timeout (1800s) and user agent validation
- CSRF tokens initialized in bootstrap after session start
- Session regenerated only after successful login

## Entry Point & Routing Design

### public/index.php - Best Practices
Both `update-api/public/index.php` and `root/public/index.php` follow this pattern:
```php
require_once __DIR__ . '/../config.php';              // Absolute __DIR__ paths (not relative ../)
require_once __DIR__ . '/../vendor/autoload.php';

$session = SessionManager::getInstance();
$session->start();
if (!$session->get('csrf_token')) {
    $session->set('csrf_token', bin2hex(random_bytes(32)));  // CSRF token init (once per session)
}

ErrorManager::handle(function (): void {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);  // CRITICAL: Parse URL path before routing
    Router::getInstance()->dispatch($_SERVER['REQUEST_METHOD'], $uri);
});
```
**Key Points**:
- Use absolute paths with `__DIR__` (not relative `../`)
- Parse URL path in index.php (`parse_url()` separates query string)
- Pass only the path to Router (Router assumes path is pre-parsed)
- CSRF token initialization happens in bootstrap (not in SessionManager::start())
- Session regeneration only happens after successful login (in LoginController)

### Router::dispatch() Contract
- **Receives**: Pre-parsed path (no query string, e.g., `/home` not `/home?foo=bar`)
- **Responsibility**: Match path to route, instantiate controller, call handleRequest or handleSubmission
- **Returns**: Nothing (sends Response via sendResponse() internally)
- **Important**: Router does NOT parse URL—caller (index.php) is responsible

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

### Response Pattern - Static Factories (Preferred)
Controllers return `Response` objects using static factories for clarity:
```php
// ✓ Correct - Use static factories (cleaner, intent-clear)
return Response::view('page', ['key' => 'value'], 200);
return Response::redirect('/home');
return Response::text('Plain text response', 200);
return Response::json(['data' => 'value'], 200);
return Response::file('/path/to/file', 'application/pdf', 200);
return Response::html('<h1>HTML</h1>', 200);

// ✓ Also correct - Fluent API for complex responses
return (new Response(403))->withHeader('X-Custom', 'value');

// ✗ Wrong - Never echo directly
echo "Hello"; 
header('Location: /home');
exit;
```
**Key Pattern**: Use static factory methods (`Response::view()`, `Response::redirect()`) for standard responses. They are cleaner than constructor + fluent method calls.

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
- `vontmnt_updaControllers & Routes

**Controller Pattern**:
```php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Response;

class NewController extends Controller {
    /**
     * Display form or view (GET request).
     * @return Response
     */
    public function handleRequest(): Response {
        return Response::view('newpage', ['data' => 'value']);
    }

    /**
     * Process form submission (POST request).
     * @return Response
     */
    public function handleSubmission(): Response {
        if (!ValidationHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            MessageHelper::addMessage('Invalid CSRF token.');
            return Response::redirect('/newpage');
        }
        // Process data
        return Response::redirect('/newpage');
    }
}
```

**Route Registration**:
```php
// In App\Core\Router::__construct()
$r->addRoute('GET', '/newpage', [NewController::class, 'handleRequest']);
$r->addRoute('POST', '/newpage', [NewController::class, 'handleSubmission']);
```
**Pattern**: GET shows form/view via `handleRequest()`, POST processes via `handleSubmission()`. Both return `Response`. Controllers inherit from `Controller` base class (provides `render()` helper).

## SessionManager Best Practices

### Initialization Flow
```php
// In public/index.php (BOOTSTRAP PHASE)
$session = SessionManager::getInstance();
$session->start();
if (!$session->get('csrf_token')) {
    $session->set('csrf_token', bin2hex(random_bytes(32)));  // Initialize CSRF token once
}

// Later in LoginController::handleSubmission() (AFTER AUTHENTICATION)
SessionManager::getInstance()->regenerate();  // Regenerate session ID for security
$session->set('timeout', time());             // Track session start time
$session->set('is_admin', $userInfo->admin);  // Store user flags if needed
```

### Common Session Operations
```php
// Get value with default
$username = $session->get('username', null);

// Check if session is valid (checks timeout, user agent, not blacklisted)
if (!$session->isValid()) {
    // Session expired or compromised
}

// Check authentication and redirect if needed
if (!$session->requireAuth()) {
    // User not authenticated, redirect to login
}

// Destroy session (logout)
$session->destroy();

// Regenerate session ID (after login)
$session->regenerate();
```

## Common Patterns

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
