<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: index.php
 * Description: WordPress Update API
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Container;
use App\Core\ErrorManager;
use App\Core\SessionManager;
use App\Core\DatabaseManager;
use App\Models\PluginModel;
use App\Models\ThemeModel;
use App\Models\HostsModel;
use App\Models\LogModel;
use App\Models\BlacklistModel;
use App\Helpers\EncryptionHelper;
use Doctrine\DBAL\Connection;

// Initialize service container
$container = new Container();

// Register SessionManager as a singleton service
$container->singleton(SessionManager::class, function () {
    return new SessionManager();
});

// Register Connection as a singleton service
$container->singleton(Connection::class, function () {
    return (new DatabaseManager())->getConnection();
});

$container->singleton(PluginModel::class, function () use ($container) {
    return new PluginModel($container->get(Connection::class));
});

$container->singleton(ThemeModel::class, function () use ($container) {
    return new ThemeModel($container->get(Connection::class));
});

$container->singleton(HostsModel::class, function () use ($container) {
    return new HostsModel($container->get(Connection::class));
});

$container->singleton(LogModel::class, function () use ($container) {
    return new LogModel($container->get(Connection::class));
});

$container->singleton(BlacklistModel::class, function () use ($container) {
    return new BlacklistModel($container->get(Connection::class));
});

// Get session instance from container
$session = $container->get(SessionManager::class);

ErrorManager::handle(function () use ($session, $container): void {
    if (!$session->get('csrf_token')) {
        $session->set('csrf_token', bin2hex(EncryptionHelper::bytes(32)));
    }

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    (new Router($container))->dispatch($_SERVER['REQUEST_METHOD'], $uri);
});
