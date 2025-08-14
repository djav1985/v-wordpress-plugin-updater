<?php

if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require __DIR__ . '/vendor/autoload.php';
$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/public';
require __DIR__ . '/config.php';

use App\Core\DatabaseManager;
use Doctrine\DBAL\Schema\Schema;

$conn = DatabaseManager::getConnection();
$schema = new Schema();

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

// import hosts file
$hostsFile = __DIR__ . '/HOSTS';
if (file_exists($hostsFile)) {
    $lines = file($hostsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        list($domain, $key) = explode(' ', $line, 2);
        $conn->executeStatement(
            'INSERT INTO hosts (domain, key) VALUES (?, ?) ON CONFLICT(domain) DO UPDATE SET key = excluded.key',
            [$domain, $key]
        );
    }
    unlink($hostsFile);
}

$logFiles = [
    'plugin.log' => 'plugin',
    'theme.log' => 'theme',
];
foreach ($logFiles as $file => $type) {
    $path = LOG_DIR . '/' . $file;
    if (file_exists($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            list($domain, $date, $status) = explode(' ', $line, 3);
            $conn->executeStatement(
                'INSERT INTO logs (domain, type, date, status) VALUES (?, ?, ?, ?)',
                [$domain, $type, $date, $status]
            );
        }
        unlink($path);
    }
}

unlink(__FILE__);
echo "Installation complete\n";
