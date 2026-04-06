<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: install.php
 * Description: WordPress Update API
 */

use App\Core\DatabaseManager;
use App\Helpers\EncryptionHelper;
use App\Helpers\ValidationHelper;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Install Status</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        .status { margin-bottom: 1em; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
<h2>V-WordPress-Plugin-Updater Installation</h2>
<div class="status">
<?php
flush();

try {
    include dirname(__DIR__) . '/vendor/autoload.php';
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__) . '/public';
    include dirname(__DIR__) . '/config.php';

    $installCreateDirectory = static function (string $dir): void {
        if (is_dir($dir)) {
            return;
        }

        $error = null;
        set_error_handler(static function (int $severity, string $message) use (&$error): bool {
            $error = $message;
            return true;
        });

        try {
            $created = mkdir($dir, 0750, true);
        } finally {
            restore_error_handler();
        }

        if ($created !== true && !is_dir($dir)) {
            $context = 'install.php failed to create database directory: ' . $dir
                . ($error !== null ? ' (' . $error . ')' : '');
            error_log($context);
            throw new RuntimeException($context);
        }
    };

    $installCreateDatabaseFile = static function (string $path): void {
        if (file_exists($path)) {
            return;
        }

        $error = null;
        set_error_handler(static function (int $severity, string $message) use (&$error): bool {
            $error = $message;
            return true;
        });

        try {
            $created = touch($path);
        } finally {
            restore_error_handler();
        }

        if ($created !== true || !file_exists($path)) {
            $context = 'install.php failed to initialize database file: ' . $path
                . ($error !== null ? ' (' . $error . ')' : '');
            error_log($context);
            throw new RuntimeException($context);
        }
    };

    $dbDir = dirname(DB_FILE);
    $installCreateDirectory($dbDir);
    $installCreateDatabaseFile(DB_FILE);

    $conn = DatabaseManager::connection();
    $schema = new Schema();
    $schemaManager = $conn->createSchemaManager();
    // phpcs:ignore -- listTableNames is deprecated in Doctrine DBAL but no direct replacement yet
    /** @phpstan-ignore-next-line */
    $existingTables = array_map('strtolower', $schemaManager->listTableNames());
    $existingTableMap = array_fill_keys($existingTables, true);

    echo "Creating tables...<br>";
    flush();
    if (!isset($existingTableMap['plugins'])) {
        $plugins = $schema->createTable('plugins');
        $plugins->addColumn('slug', 'text');
        $plugins->addColumn('version', 'text');
        $plugins->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()->setUnquotedColumnNames('slug')->create()
        );
    }

    if (!isset($existingTableMap['themes'])) {
        $themes = $schema->createTable('themes');
        $themes->addColumn('slug', 'text');
        $themes->addColumn('version', 'text');
        $themes->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()->setUnquotedColumnNames('slug')->create()
        );
    }

    if (!isset($existingTableMap['hosts'])) {
        $hosts = $schema->createTable('hosts');
        $hosts->addColumn('domain', 'text');
        $hosts->addColumn('key', 'text');
        $hosts->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()->setUnquotedColumnNames('domain')->create()
        );
    }

    if (!isset($existingTableMap['logs'])) {
        $logs = $schema->createTable('logs');
        $logs->addColumn('domain', 'text');
        $logs->addColumn('type', 'text');
        $logs->addColumn('date', 'text');
        $logs->addColumn('status', 'text');
    }

    if (!isset($existingTableMap['blacklist'])) {
        $blacklist = $schema->createTable('blacklist');
        $blacklist->addColumn('ip', 'text');
        $blacklist->addColumn('login_attempts', 'integer');
        $blacklist->addColumn('blacklisted', 'integer');
        $blacklist->addColumn('timestamp', 'integer');
        $blacklist->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()->setUnquotedColumnNames('ip')->create()
        );
    }

    foreach ($schema->toSql($conn->getDatabasePlatform()) as $sql) {
        $conn->executeStatement($sql);
    }
    echo "<span class='success'>Database tables ensured.</span><br>";
    flush();

    // Import hosts file if it exists
    $hostsFile = __DIR__ . '/HOSTS';
    if (file_exists($hostsFile)) {
        echo "Importing HOSTS file...<br>";
        flush();

        $lines = file($hostsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $summary = [
            'total' => 0,
            'imported' => 0,
            'already_encrypted' => 0,
            'normalized_plaintext' => 0,
            'normalized_legacy' => 0,
            'malformed' => 0,
            'invalid_domain' => 0,
        ];
        $issues = [];

        foreach ($lines as $lineNumber => $line) {
            $summary['total']++;

            $parts = preg_split('/\s+/', trim($line), 2);
            if (!is_array($parts) || count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
                $summary['malformed']++;
                $issues[] = 'line ' . ($lineNumber + 1) . ': malformed entry';
                continue;
            }

            $domain = ValidationHelper::validateDomain($parts[0]);
            if ($domain === null) {
                $summary['invalid_domain']++;
                $issues[] = 'line ' . ($lineNumber + 1) . ': invalid domain "' . $parts[0] . '"';
                continue;
            }

            $rawKey = trim($parts[1]);
            $normalizedKey = $rawKey;
            $decrypted = EncryptionHelper::decrypt($rawKey);

            if ($decrypted !== null && !EncryptionHelper::needsMigration($rawKey)) {
                $summary['already_encrypted']++;
            } elseif ($decrypted !== null && EncryptionHelper::needsMigration($rawKey)) {
                $normalizedKey = EncryptionHelper::encrypt($decrypted);
                $summary['normalized_legacy']++;
            } else {
                $normalizedKey = EncryptionHelper::encrypt($rawKey);
                $summary['normalized_plaintext']++;
            }

            $conn->executeStatement(
                'INSERT INTO hosts (domain, key) VALUES (?, ?) ' .
                'ON CONFLICT(domain) DO UPDATE SET key = excluded.key',
                [$domain, $normalizedKey]
            );
            $summary['imported']++;
        }

        unlink($hostsFile);

        $summaryMessage = sprintf(
            'HOSTS normalization: total=%d imported=%d encrypted=%d ' .
            'normalized_plaintext=%d normalized_legacy=%d malformed=%d invalid_domain=%d',
            $summary['total'],
            $summary['imported'],
            $summary['already_encrypted'],
            $summary['normalized_plaintext'],
            $summary['normalized_legacy'],
            $summary['malformed'],
            $summary['invalid_domain']
        );
        error_log($summaryMessage);

        if ($issues !== []) {
            error_log('HOSTS normalization issues: ' . implode('; ', $issues));
        }

        echo '<span class="success">HOSTS imported. ' . htmlspecialchars($summaryMessage) . '</span><br>';
        flush();
    }

    // Import log files if they exist
    $logFiles = [
        'plugin.log' => 'plugin',
        'theme.log' => 'theme',
    ];
    foreach ($logFiles as $file => $type) {
        $path = defined('LOG_DIR') ? LOG_DIR . '/' . $file : __DIR__ . '/storage/logs/' . $file;
        if (file_exists($path)) {
            echo "Importing $file...<br>";
            flush();
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $importedCount = 0;
            $malformedCount = 0;
            foreach ($lines as $line) {
                $parts = explode(' ', trim($line), 3);
                if (count($parts) !== 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
                    $malformedCount++;
                    continue;
                }

                [$domain, $date, $status] = $parts;
                $conn->executeStatement(
                    'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
                    [$domain, $type, $date, $status]
                );
                $importedCount++;
            }
            unlink($path);
            echo "<span class='success'>$file imported (rows: "
                . $importedCount
                . ", malformed skipped: "
                . $malformedCount
                . ").</span><br>";
            flush();
        }
    }

    // Import plugins from storage/plugins
    $pluginsDir = dirname(__DIR__) . '/storage/plugins';
    if (is_dir($pluginsDir)) {
        echo "Importing plugins from storage...<br>";
        flush();
        foreach (glob($pluginsDir . '/*.zip') as $pluginFile) {
            if (preg_match('/([A-Za-z0-9._-]+)_([\d.]+)\.zip$/', basename($pluginFile), $matches)) {
                $slug = $matches[1];
                $version = $matches[2];
                $conn->executeStatement(
                    'INSERT INTO plugins (slug, version) VALUES (?, ?) ' .
                    'ON CONFLICT(slug) DO UPDATE SET version = excluded.version',
                    [$slug, $version]
                );
            }
        }
        echo "<span class='success'>Plugins imported from storage.</span><br>";
        flush();
    }

    // Import themes from storage/themes
    $themesDir = dirname(__DIR__) . '/storage/themes';
    if (is_dir($themesDir)) {
        echo "Importing themes from storage...<br>";
        flush();
        foreach (glob($themesDir . '/*.zip') as $themeFile) {
            if (preg_match('/([A-Za-z0-9._-]+)_([\d.]+)\.zip$/', basename($themeFile), $matches)) {
                $slug = $matches[1];
                $version = $matches[2];
                $conn->executeStatement(
                    'INSERT INTO themes (slug, version) VALUES (?, ?) ' .
                    'ON CONFLICT(slug) DO UPDATE SET version = excluded.version',
                    [$slug, $version]
                );
            }
        }
        echo "<span class='success'>Themes imported from storage.</span><br>";
        flush();
    }

    echo "<strong class='success'>Installation complete!</strong>";
} catch (Exception $e) {
    echo "<span class='error'>Error: " .
        htmlspecialchars($e->getMessage()) . "</span>";
}
?>
</div>
</body>
</html>
