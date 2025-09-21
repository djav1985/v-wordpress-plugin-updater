<?php

/**
 * V-WordPress-Plugin-Updater Installer
 * Runs in browser and provides HTML status updates.
 */

use App\Core\DatabaseManager;
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

    $conn = DatabaseManager::getConnection();
    $schema = new Schema();

    echo "Creating tables...<br>"; flush();
    $plugins = $schema->createTable('plugins');
    $plugins->addColumn('slug', 'text');
    $plugins->addColumn('version', 'text');
    $plugins->setPrimaryKey(['slug']);

    $themes = $schema->createTable('themes');
    $themes->addColumn('slug', 'text');
    $themes->addColumn('version', 'text');
    $themes->setPrimaryKey(['slug']);

    $hosts = $schema->createTable('hosts');
    $hosts->addColumn('domain', 'text');
    $hosts->addColumn('key', 'text');
    $hosts->addColumn('old_key', 'text', ['notnull' => false]);
    $hosts->addColumn('send_auth', 'boolean', ['default' => 0]);
    $hosts->setPrimaryKey(['domain']);

    $logs = $schema->createTable('logs');
    $logs->addColumn('domain', 'text');
    $logs->addColumn('type', 'text');
    $logs->addColumn('date', 'text');
    $logs->addColumn('status', 'text');

    $blacklist = $schema->createTable('blacklist');
    $blacklist->addColumn('ip', 'text');
    $blacklist->addColumn('login_attempts', 'integer');
    $blacklist->addColumn('blacklisted', 'integer');
    $blacklist->addColumn('timestamp', 'integer');
    $blacklist->setPrimaryKey(['ip']);

    foreach ($schema->toSql($conn->getDatabasePlatform()) as $sql) {
        $conn->executeStatement($sql);
    }
    echo "<span class='success'>Database tables created.</span><br>"; flush();

    // Import hosts file if it exists
    $hostsFile = __DIR__ . '/HOSTS';
    if (file_exists($hostsFile)) {
        echo "Importing HOSTS file...<br>"; flush();
        $lines = file($hostsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            list($domain, $key) = explode(' ', $line, 2);
            $conn->executeStatement(
                'INSERT INTO hosts (domain, key, send_auth) VALUES (?, ?, 0) ' .
                'ON CONFLICT(domain) DO UPDATE SET key = excluded.key, send_auth = 0',
                [$domain, $key]
            );
        }
        unlink($hostsFile);
        echo "<span class='success'>HOSTS imported.</span><br>"; flush();
    }

    // Import log files if they exist
    $logFiles = [
        'plugin.log' => 'plugin',
        'theme.log' => 'theme',
    ];
    foreach ($logFiles as $file => $type) {
        $path = defined('LOG_DIR') ? LOG_DIR . '/' . $file : __DIR__ . '/storage/logs/' . $file;
        if (file_exists($path)) {
            echo "Importing $file...<br>"; flush();
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                list($domain, $date, $status) = explode(' ', $line, 3);
                $conn->executeStatement(
                    'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
                    [$domain, $type, $date, $status]
                );
            }
            unlink($path);
            echo "<span class='success'>$file imported.</span><br>"; flush();
        }
    }

    // Import plugins from storage/plugins
    $pluginsDir = dirname(__DIR__) . '/storage/plugins';
    if (is_dir($pluginsDir)) {
        echo "Importing plugins from storage...<br>"; flush();
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
        echo "<span class='success'>Plugins imported from storage.</span><br>"; flush();
    }

    // Import themes from storage/themes
    $themesDir = dirname(__DIR__) . '/storage/themes';
    if (is_dir($themesDir)) {
        echo "Importing themes from storage...<br>"; flush();
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
        echo "<span class='success'>Themes imported from storage.</span><br>"; flush();
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
