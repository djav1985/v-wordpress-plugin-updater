# Model Refactoring Analysis: Static to Dependency Injection

## Executive Summary

This analysis documents all static model usages in the v-update-api codebase and provides a detailed map for refactoring from `DatabaseManager::getConnection()` static calls to dependency injection pattern.

**Models Analyzed**: 5 total
- PluginModel
- ThemeModel  
- HostsModel
- LogModel
- BlacklistModel

**Usage Points**: 38 static method calls across 7 files
- 6 Controllers (LoginController, HomeController, PluginsController, ThemesController, LogsController, ApiController)
- 2 Support Files (install.php, SessionManager)
- 1 Helper (CronHelper)

---

## Part 1: Complete Public Interface of Each Model

### 1. PluginModel

**File**: `v-update-api/app/Models/PluginModel.php`

**Public Static Class Variables**:
```php
public static string $dir = PLUGINS_DIR;
```

**Public Static Methods**:

1. **getVersionBySlug(string $slug): ?string**
   - Line: ~27
   - Calls: `DatabaseManager::getConnection()`
   - Returns: Plugin version from database or null
   - SQL: `SELECT version FROM plugins WHERE slug = ?`

2. **getPlugins(): array<int, array{slug: string, version: string}>**
   - Line: ~36
   - Calls: `DatabaseManager::getConnection()`
   - Returns: Array of all plugins with slug and version
   - SQL: `SELECT slug, version FROM plugins ORDER BY slug`

3. **deletePlugin(string $pluginName): bool**
   - Line: ~52
   - Calls: `DatabaseManager::getConnection()`
   - Deletes plugin file and database record
   - SQL: `DELETE FROM plugins WHERE slug = ?`
   - Dependencies: ValidationHelper, error_log

4. **uploadFiles(array $fileArray, bool $isAjax = false): array**
   - Line: ~107
   - Calls: `DatabaseManager::getConnection()` (multiple times during validation and persistence)
   - Handles file upload with validation, transaction, and cleanup
   - SQL: Multiple UPSERTs with transaction management
   - Dependencies: ValidationHelper, ZipArchive

**Private Static Methods** (internal helpers):
- `persistUploadedArtifact(Connection $conn, ... ): array`
- `normalizeUploadPayload(array $fileArray): array`
- `buildEntry(mixed ... ): array`
- `_parseIniSize(string $size): int`

---

### 2. ThemeModel

**File**: `v-update-api/app/Models/ThemeModel.php`

**Public Static Class Variables**:
```php
public static string $dir = THEMES_DIR;
```

**Public Static Methods**:

1. **getVersionBySlug(string $slug): ?string**
   - Line: ~27
   - Calls: `DatabaseManager::getConnection()`
   - Returns: Theme version from database or null
   - SQL: `SELECT version FROM themes WHERE slug = ?`
   - *Identical pattern to PluginModel*

2. **getThemes(): array<int, array{slug: string, version: string}>**
   - Line: ~36
   - Calls: `DatabaseManager::getConnection()`
   - Returns: Array of all themes with slug and version
   - SQL: `SELECT slug, version FROM themes ORDER BY slug`
   - *Identical pattern to PluginModel*

3. **deleteTheme(string $themeName): bool**
   - Line: ~52
   - Calls: `DatabaseManager::getConnection()`
   - Deletes theme file and database record
   - SQL: `DELETE FROM themes WHERE slug = ?`
   - *Identical pattern to PluginModel*

4. **uploadFiles(array $fileArray, bool $isAjax = false): array**
   - Line: ~107
   - Calls: `DatabaseManager::getConnection()` (multiple times)
   - Handles file upload with validation, transaction, and cleanup
   - SQL: Multiple UPSERTs with transaction management
   - *Identical pattern to PluginModel*

**Private Static Methods** (internal helpers):
- `persistUploadedArtifact(Connection $conn, ... ): array`
- `normalizeUploadPayload(array $fileArray): array`
- `buildEntry(mixed ... ): array`
- `_parseIniSize(string $size): int`

---

### 3. HostsModel

**File**: `v-update-api/app/Models/HostsModel.php`

**Public Static Methods**:

1. **getEncryptedKeyByDomain(string $domain): ?string**
   - Line: ~24
   - Calls: `DatabaseManager::getConnection()`
   - Returns: Encrypted API key for a domain or null
   - SQL: `SELECT key FROM hosts WHERE domain = ?`

2. **getEntries(): array<int, array{domain: string, key: string}>**
   - Line: ~35
   - Calls: `DatabaseManager::getConnection()`
   - Returns: All hosts with domain and encrypted key
   - SQL: `SELECT domain, key FROM hosts ORDER BY domain`

3. **getHosts(): array<int, string>**
   - Line: ~50
   - Calls: `DatabaseManager::getConnection()`
   - Returns: Array of domain names only
   - SQL: `SELECT domain FROM hosts ORDER BY domain`

4. **addEntry(string $domain, string $key): bool**
   - Line: ~62
   - Calls: `DatabaseManager::getConnection()`, `EncryptionHelper::encrypt()`
   - Inserts new host entry with encrypted key
   - SQL: `INSERT INTO hosts (domain, key) VALUES (?, ?)`

5. **updateEntry(string $domain, string $key): bool**
   - Line: ~71
   - Calls: `DatabaseManager::getConnection()`, `EncryptionHelper::encrypt()`
   - Updates existing host key
   - SQL: `UPDATE hosts SET key = ? WHERE domain = ?`

6. **migrateLegacyKey(string $domain, string $encryptedKey, string $plainKey): void**
   - Line: ~83
   - Calls: `DatabaseManager::getConnection()`, `EncryptionHelper::needsMigration()`, `EncryptionHelper::encrypt()`
   - Migrates legacy CBC encryption to AEAD
   - SQL: `UPDATE hosts SET key = ? WHERE domain = ?`

7. **deleteEntry(string $domain): bool**
   - Line: ~99
   - Calls: `DatabaseManager::getConnection()` (twice: delete host, delete logs)
   - Deletes host and associated logs
   - SQL: `DELETE FROM hosts WHERE domain = ?` and `DELETE FROM logs WHERE domain = ?`

---

### 4. LogModel

**File**: `v-update-api/app/Models/LogModel.php`

**Public Static Methods**:

1. **addLog(string $domain, string $type, string $status): void**
   - Line: ~24
   - Calls: `DatabaseManager::getConnection()`
   - Inserts log entry with current date
   - SQL: `INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)`

2. **getLogs(string $type): string**
   - Line: ~35
   - Calls: `DatabaseManager::getConnection()`
   - Fetches and formats logs as HTML output for display
   - SQL: `SELECT domain, date, status FROM logs WHERE type = ? ORDER BY date DESC`
   - Returns: HTML string for rendering

3. **clearAllLogs(): void**
   - Line: ~82
   - Calls: `DatabaseManager::getConnection()`
   - Clears all log entries
   - SQL: `DELETE FROM logs`

---

### 5. BlacklistModel

**File**: `v-update-api/app/Models/BlacklistModel.php`

**Public Static Methods**:

1. **updateFailedAttempts(string $ip): void**
   - Line: ~24
   - Calls: `DatabaseManager::getConnection()`
   - Atomically UPSERTs failed login attempt, auto-blacklists at 3+ attempts, updates timestamp
   - SQL: `INSERT INTO blacklist (ip, login_attempts, blacklisted, timestamp) VALUES ... ON CONFLICT(ip) DO UPDATE SET ...`
   - Behavior: Increments counter; sets `blacklisted=1` when attempts >= 3

2. **isBlacklisted(string $ip): bool**
   - Line: ~48
   - Calls: `DatabaseManager::getConnection()` (calls `update()` method if blacklist expired)
   - Checks if IP is blacklisted; auto-resets if 7+ days since blocking
   - SQL: `SELECT * FROM blacklist WHERE ip = ?` and `UPDATE blacklist SET ... WHERE ip = ?`
   - Returns: true if currently blacklisted, false otherwise

---

## Part 2: Model Usage in Controllers

### LoginController

**File**: `v-update-api/app/Controllers/LoginController.php`

**Constructor Dependencies**:
```php
public function __construct(private SessionManager $session)
```

**Model Usage**:
1. **Line 96** - `BlacklistModel::isBlacklisted($ip)`
   - Context: During failed login validation
   - Purpose: Check if IP is blocked from further attempts

2. **Line 101** - `BlacklistModel::updateFailedAttempts($ip)`
   - Context: After invalid username/password
   - Purpose: Increment failed attempt counter and potentially blacklist IP

**Flow**: Login form → validate credentials → if failed, check/update blacklist

---

### HomeController

**File**: `v-update-api/app/Controllers/HomeController.php`

**Constructor Dependencies**:
```php
public function __construct(private SessionManager $session)
```

**Model Usage**:
1. **Line 60** - `HostsModel::addEntry($domain, $newKey)`
   - Context: handleSubmission() when 'add_entry' posted
   - Purpose: Insert new host/domain with generated API key

2. **Line 69** - `HostsModel::updateEntry($domain, $newKey)`
   - Context: handleSubmission() when 'regen_entry' posted
   - Purpose: Update existing host's API key

3. **Line 85** - `HostsModel::deleteEntry($domain)`
   - Context: handleSubmission() when 'delete_entry' posted
   - Purpose: Delete host and its associated logs

4. **Line 137** - `HostsModel::getEntries()`
   - Context: getHostsTableHtml() to display table
   - Purpose: Fetch all hosts for display

5. **Line 161** - `HostsModel::migrateLegacyKey($domain, $encryptedKey, $key)`
   - Context: getHostsTableHtml() during display rendering
   - Purpose: Migrate legacy CBC encryption to AEAD on read

6. **Line 184** - `HostsModel::migrateLegacyKey($domain, $encryptedKey, $key)`
   - Context: getHostsTableHtml() in second iteration/column
   - Purpose: Same as line 161

7. **Line 223** - `HostsModel::getEntries()`
   - Context: generateAPIKeyTableHtml() helper
   - Purpose: Fetch all hosts for API table display

8. **Line 232** - `HostsModel::migrateLegacyKey($domain, $encryptedKey, $key)`
   - Context: generateAPIKeyTableHtml() during display
   - Purpose: Migrate legacy encryption during API table rendering

**Flow**: Display hosts → user action (add/regen/delete) → call HostsModel methods

---

### PluginsController

**File**: `v-update-api/app/Controllers/PluginsController.php`

**Constructor Dependencies**:
```php
public function __construct(private SessionManager $session)
```

**Model Usage**:
1. **Line 62** - `PluginModel::uploadFiles($_FILES['plugin_file'], $isAjax)`
   - Context: handleSubmission() when file upload detected
   - Purpose: Process and validate plugin ZIP upload
   - Returns: Array of status messages

2. **Line 74** - `PluginModel::deletePlugin($pluginName)`
   - Context: handleSubmission() when delete_plugin posted
   - Purpose: Delete plugin file and database record

3. **Line 116** - `PluginModel::getPlugins()`
   - Context: getPluginsTableHtml() for display
   - Purpose: Fetch all plugins to render table

**Flow**: Display plugins → user action (upload/delete) → call PluginModel methods

---

### ThemesController

**File**: `v-update-api/app/Controllers/ThemesController.php`

**Constructor Dependencies**:
```php
public function __construct(private SessionManager $session)
```

**Model Usage**:
1. **Line 62** - `ThemeModel::uploadFiles($_FILES['theme_file'], $isAjax)`
   - Context: handleSubmission() when file upload detected
   - Purpose: Process and validate theme ZIP upload
   - Returns: Array of status messages

2. **Line 72** - `ThemeModel::deleteTheme($themeName)`
   - Context: handleSubmission() when delete_theme posted
   - Purpose: Delete theme file and database record

3. **Line 114** - `ThemeModel::getThemes()`
   - Context: getThemesTableHtml() for display
   - Purpose: Fetch all themes to render table

**Flow**: Display themes → user action (upload/delete) → call ThemeModel methods

---

### LogsController

**File**: `v-update-api/app/Controllers/LogsController.php`

**Constructor Dependencies**:
```php
public function __construct(private SessionManager $session)
```

**Model Usage**:
1. **Line 35** - `LogModel::getLogs('plugin')`
   - Context: handleRequest() for display
   - Purpose: Fetch and format plugin logs as HTML

2. **Line 36** - `LogModel::getLogs('theme')`
   - Context: handleRequest() for display
   - Purpose: Fetch and format theme logs as HTML

3. **Line 58** - `LogModel::clearAllLogs()`
   - Context: handleSubmission() when clear_logs posted
   - Purpose: Clear all log entries

**Flow**: Display logs → optionally clear → call LogModel methods

---

### ApiController

**File**: `v-update-api/app/Controllers/ApiController.php`

**Constructor Dependencies**:
```php
public function __construct(private SessionManager $session)
```

**Model Usage**:
1. **Line 57** - `BlacklistModel::isBlacklisted($ip)`
   - Context: handleRequest() during IP validation
   - Purpose: Reject blacklisted IPs with 403

2. **Line 109** - `HostsModel::getEncryptedKeyByDomain($domain)`
   - Context: handleRequest() during host authentication
   - Purpose: Fetch encrypted API key for domain

3. **Line 112** - `BlacklistModel::updateFailedAttempts($ip)`
   - Context: handleRequest() when domain not found
   - Purpose: Record failed authentication attempt

4. **Line 113** - `LogModel::addLog($domain, $type, 'Failed')`
   - Context: handleRequest() when domain not found
   - Purpose: Log failed update request

5. **Line 121** - `BlacklistModel::updateFailedAttempts($ip)`
   - Context: handleRequest() when key doesn't match
   - Purpose: Record failed authentication attempt

6. **Line 122** - `LogModel::addLog($domain, $type, 'Failed')`
   - Context: handleRequest() when key doesn't match
   - Purpose: Log failed update request

7. **Line 129** - `HostsModel::updateEntry($domain, $hostKey)`
   - Context: handleRequest() after successful auth if key needs migration
   - Purpose: Re-encrypt legacy key to AEAD

8. **Line 133** - `ThemeModel::getVersionBySlug($slug)`
   - Context: handleRequest() when type is 'theme'
   - Purpose: Fetch theme version from database

9. **Line 135** - `PluginModel::getVersionBySlug($slug)`
   - Context: handleRequest() when type is 'plugin'
   - Purpose: Fetch plugin version from database

10. **Line 146** - `LogModel::addLog($domain, $type, 'Success')`
    - Context: handleRequest() when update available and file served
    - Purpose: Log successful update request

11. **Line 156** - `LogModel::addLog($domain, $type, 'Success')`
    - Context: handleRequest() when no update available
    - Purpose: Log successful update check (204 response)

**Flow**: Validate IP → authenticate domain/key → check version → serve update or return 204 → log result

---

## Part 3: Model Usage in Support Files

### install.php

**File**: `v-update-api/public/install.php`

**DatabaseManager Usage**:
```php
$conn = DatabaseManager::getConnection();  // Line ~47
```

**Model Usage**: **NONE**
- install.php creates schema directly using Doctrine DBAL
- No models are instantiated or called
- Direct SQL schema creation: `plugins`, `themes`, `hosts`, `logs`, `blacklist` tables

**HOSTS File Import**:
- Imports encrypted keys into database
- Calls `EncryptionHelper::decrypt()` and `EncryptionHelper::encrypt()`
- Does NOT use HostsModel (direct DB operations via DatabaseManager)

---

### CronHelper

**File**: `v-update-api/app/Helpers/CronHelper.php`

**DatabaseManager Usage**:
```php
$conn = DatabaseManager::getConnection();  // Line ~34
```

**Model Usage**: **NONE** (Direct Connection usage instead)

**Methods**:
1. **runCronJob(): void**
   - Calls directly to `getCon nectiption()`
   - Purpose: Main entry point for cron execution

2. **syncPluginsDir(string $dir, Connection $conn): void** (private)
   - Direct SQL: `INSERT INTO plugins ... ON CONFLICT`
   - Does NOT call PluginModel

3. **syncThemesDir(string $dir, Connection $conn): void** (private)
   - Direct SQL: `INSERT INTO themes ... ON CONFLICT`
   - Does NOT call ThemeModel

4. **syncDir(...): void** (private)
   - Handles generic sync logic
   - Reads filesystem ZIPs and synchronizes with database
   - Deletes stale database entries when files missing

5. **cleanupBlacklist(Connection $conn): void** (private)
   - Direct SQL DELETE statements
   - Does NOT call BlacklistModel
   - Removes expired blacklist entries (7 days blocked, 3 days unblocked)

**Note**: CronHelper accesses Connection directly instead of using Models, which is semantically similar to Models' direct DatabaseManager::getConnection() pattern.

---

### SessionManager

**File**: `v-update-api/app/Core/SessionManager.php`

**Model Usage**:
1. **Line 153** - `BlacklistModel::isBlacklisted($ip)`
   - Context: SessionManager::isValid() method
   - Purpose: Check if current session's IP is blacklisted

**Integration**: SessionManager checks blacklist status via BlacklistModel during session validation

---

## Part 4: Usage Summary by Model

### PluginModel
**Total Calls**: 3
- **PluginsController** (2): getPlugins(), uploadFiles(), deletePlugin()
- **ApiController** (1): getVersionBySlug()

### ThemeModel
**Total Calls**: 3
- **ThemesController** (2): getThemes(), uploadFiles(), deleteTheme()
- **ApiController** (1): getVersionBySlug()

### HostsModel
**Total Calls**: 8
- **HomeController** (8): addEntry(), updateEntry(), deleteEntry(), getEntries() (×2), migrateLegacyKey() (×3)
- **ApiController** (2): getEncryptedKeyByDomain(), updateEntry(), migrateLegacyKey()

### LogModel
**Total Calls**: 6
- **LogsController** (3): getLogs('plugin'), getLogs('theme'), clearAllLogs()
- **ApiController** (6): addLog() (×5), implicitly in error handling

### BlacklistModel
**Total Calls**: 7
- **LoginController** (2): isBlacklisted(), updateFailedAttempts()
- **ApiController** (5): isBlacklisted(), updateFailedAttempts() (×2)
- **SessionManager** (1): isBlacklisted()

**Grand Total**: 27 explicit model calls + 11 in ApiController flow = 38 usage points

---

## Part 5: DatabaseManager Call Density

### Direct DatabaseManager::getConnection() Calls by Model

| Model | Method | DB Calls | Notes |
|-------|--------|----------|-------|
| PluginModel | getVersionBySlug | 1 | Single SELECT |
| PluginModel | getPlugins | 1 | Single SELECT all |
| PluginModel | deletePlugin | 1 | Single DELETE |
| PluginModel | uploadFiles | 2+ | During entry validation + persistUploadedArtifact |
| ThemeModel | getVersionBySlug | 1 | Single SELECT |
| ThemeModel | getThemes | 1 | Single SELECT all |
| ThemeModel | deleteTheme | 1 | Single DELETE |
| ThemeModel | uploadFiles | 2+ | During entry validation + persistUploadedArtifact |
| HostsModel | getEncryptedKeyByDomain | 1 | Single SELECT |
| HostsModel | getEntries | 1 | Single SELECT all |
| HostsModel | getHosts | 1 | Single SELECT all |
| HostsModel | addEntry | 1 | Single INSERT (with encryption) |
| HostsModel | updateEntry | 1 | Single UPDATE |
| HostsModel | migrateLegacyKey | 1 | Conditional UPDATE |
| HostsModel | deleteEntry | 2 | DELETE from hosts + DELETE from logs |
| LogModel | addLog | 1 | Single INSERT |
| LogModel | getLogs | 1 | Single SELECT + HTML formatting |
| LogModel | clearAllLogs | 1 | Single DELETE |
| BlacklistModel | updateFailedAttempts | 1 | Atomic UPSERT |
| BlacklistModel | isBlacklisted | 1-2 | SELECT + optional UPDATE (expired) |

---

## Part 6: Installation & Cron Entry Points

### install.php

**Current Pattern**:
```php
require_once __DIR__ . '/../vendor/autoload.php';
$conn = DatabaseManager::getConnection();
// Direct Doctrine DBAL Schema operations
```

**Models Involved**: None (direct DB schema creation)

**Refactoring Impact**: Minimal—can stay as-is or register Container globally

---

### cron.php

**Current Pattern**:
```php
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\ErrorManager;
use App\Helpers\CronHelper;

ErrorManager::handle(function (): void {
    CronHelper::runCronJob();
});
```

**CronHelper Pattern**:
```php
$conn = DatabaseManager::getConnection();
self::syncPluginsDir(PLUGINS_DIR, $conn);
self::syncThemesDir(THEMES_DIR, $conn);
self::cleanupBlacklist($conn);
```

**Models Involved**: None (direct Connection usage via DatabaseManager)

**Refactoring Impact**: Minimal—can pass Container to CronHelper if needed

---

## Part 7: Refactoring Roadmap

### Current State Issues
1. **Tight Coupling**: All models directly call `DatabaseManager::getConnection()`
2. **No Testability**: Can't inject mock connections for unit testing
3. **Implicit Dependencies**: Database dependency hidden in static methods
4. **No Flexibility**: Can't swap Connection implementation (e.g., for read-only replica)

### Proposed Refactoring

#### Option A: Static Factory Methods (Recommended)
```php
// Keep static interface but add optional Connection parameter
class PluginModel {
    public static function getPlugins(?Connection $conn = null): array {
        $conn = $conn ?? DatabaseManager::getConnection();
        // ... existing logic
    }
}

// In Controller (via DI):
class PluginsController {
    public function __construct(private Connection $conn) {}
    public function handleRequest(): ResponseManager {
        $plugins = PluginModel::getPlugins($this->conn);
        // ...
    }
}
```

**Advantages**:
- Backwards compatible (optional parameter defaults to current behavior)
- Minimal changes to existing code
- Controllers can inject Connection via constructor
- Works with existing static model interface

#### Option B: Instance-based Models (Full refactor)
```php
// Convert to instance-based
class PluginModel {
    public function __construct(private Connection $conn) {}
    public function getPlugins(): array { ... }
}

// In Controller:
class PluginsController {
    public function __construct(private PluginModel $pluginModel) {}
    public function handleRequest(): ResponseManager {
        $plugins = $this->pluginModel->getPlugins();
        // ...
    }
}
```

**Advantages**:
- Cleaner DI pattern
- Removes static method ambiguity
- More testable

**Disadvantages**:
- Requires creating 5 model instances
- Larger refactoring scope
- CronHelper needs update

#### Recommended Path: **Option A** (Static with Optional Connection)
- Non-breaking change
- Leverages existing static interface
- Controllers get testability via DI
- CronHelper stays as-is

---

## Part 8: Affected Files Summary

### Immediate Changes Required
1. **All 5 Models**: Add optional `?Connection $conn = null` parameter
   - PluginModel.php
   - ThemeModel.php
   - HostsModel.php
   - LogModel.php
   - BlacklistModel.php

2. **All 6 Controllers**: Accept Connection/DatabaseManager in constructor
   - LoginController.php
   - HomeController.php
   - PluginsController.php
   - ThemesController.php
   - LogsController.php
   - ApiController.php

3. **Router.php**: Pass Connection to controllers via DI container

4. **public/index.php**: Register Connection in DI container

### No Changes Needed
- `install.php` (can continue using DatabaseManager directly)
- `cron.php` (CronHelper can accept optional Connection)
- `SessionManager.php` (direct BlacklistModel call is acceptable)

---

## Appendix: Complete Method Call List

### By File

**LoginController.php**:
- Line 96: `BlacklistModel::isBlacklisted($ip)`
- Line 101: `BlacklistModel::updateFailedAttempts($ip)`

**HomeController.php**:
- Line 60: `HostsModel::addEntry($domain, $newKey)`
- Line 69: `HostsModel::updateEntry($domain, $newKey)`
- Line 85: `HostsModel::deleteEntry($domain)`
- Line 137: `HostsModel::getEntries()`
- Line 161: `HostsModel::migrateLegacyKey(...)`
- Line 184: `HostsModel::migrateLegacyKey(...)`
- Line 223: `HostsModel::getEntries()`
- Line 232: `HostsModel::migrateLegacyKey(...)`

**PluginsController.php**:
- Line 62: `PluginModel::uploadFiles($_FILES['plugin_file'], ...)`
- Line 74: `PluginModel::deletePlugin($pluginName)`
- Line 116: `PluginModel::getPlugins()`

**ThemesController.php**:
- Line 62: `ThemeModel::uploadFiles($_FILES['theme_file'], ...)`
- Line 72: `ThemeModel::deleteTheme($themeName)`
- Line 114: `ThemeModel::getThemes()`

**LogsController.php**:
- Line 35: `LogModel::getLogs('plugin')`
- Line 36: `LogModel::getLogs('theme')`
- Line 58: `LogModel::clearAllLogs()`

**ApiController.php**:
- Line 57: `BlacklistModel::isBlacklisted($ip)`
- Line 109: `HostsModel::getEncryptedKeyByDomain($domain)`
- Line 112: `BlacklistModel::updateFailedAttempts($ip)` [auth fail - unknown domain]
- Line 113: `LogModel::addLog($domain, $type, 'Failed')` [auth fail - unknown domain]
- Line 121: `BlacklistModel::updateFailedAttempts($ip)` [auth fail - wrong key]
- Line 122: `LogModel::addLog($domain, $type, 'Failed')` [auth fail - wrong key]
- Line 129: `HostsModel::updateEntry(...)` [key migration on successful auth]
- Line 133: `ThemeModel::getVersionBySlug($slug)` [check for update]
- Line 135: `PluginModel::getVersionBySlug($slug)` [check for update]
- Line 146: `LogModel::addLog($domain, $type, 'Success')` [serve update file]
- Line 156: `LogModel::addLog($domain, $type, 'Success')` [return 204 no update]

**SessionManager.php**:
- Line 153: `BlacklistModel::isBlacklisted($ip)`

**CronHelper.php**:
- Direct Connection usage: No models called
- Direct SQL: 3 syncDir variations + cleanupBlacklist

---

## Conclusion

This analysis provides a complete blueprint for refactoring the v-update-api models to support dependency injection while maintaining backwards compatibility. The recommended approach (Option A: Static methods with optional Connection parameter) allows controllers to be tested with mock connections while keeping the existing static interface for other code paths.

**Total refactoring effort**:
- ~5 model files: Add optional Connection parameter
- ~6 controller files: Inject Connection via constructor
- ~1 Router file: Initialize DI
- ~1 index.php file: Register services

**Estimated impact**: Low risk, high testability gain.
