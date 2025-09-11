<?php

if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require __DIR__ . '/vendor/autoload.php';
$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/public';
require __DIR__ . '/config.php';

use App\Core\DatabaseManager;

$conn = DatabaseManager::getConnection();

function syncDir(string $dir, string $table, $conn): void
{
    $files = glob($dir . '/*.zip');
    $found = [];
    foreach ($files as $file) {
        $name = basename($file);
        if (preg_match('/^(.+)_([\d\.]+)\.zip$/', $name, $matches)) {
            $slug = $matches[1];
            $version = $matches[2];
            $found[$slug] = true;
            $conn->executeStatement(
                "INSERT INTO $table (slug, version) VALUES (?, ?) ON CONFLICT(slug) DO UPDATE SET version = excluded.version",
                [$slug, $version]
            );
        }
    }
    $rows = $conn->fetchAllAssociative("SELECT slug FROM $table");
    foreach ($rows as $row) {
        if (!isset($found[$row['slug']])) {
            $conn->executeStatement("DELETE FROM $table WHERE slug = ?", [$row['slug']]);
        }
    }
}

syncDir(PLUGINS_DIR, 'plugins', $conn);
syncDir(THEMES_DIR, 'themes', $conn);
