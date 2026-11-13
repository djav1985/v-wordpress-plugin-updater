<?php

declare(strict_types=1);

use App\Models\PluginModel;
use App\Models\ThemeModel;
use App\Models\HostsModel;
use App\Core\DatabaseManager;
use App\Core\Request;
use App\Core\SessionManager;
use App\Controllers\PluginsController;
use App\Controllers\ThemesController;
use App\Controllers\ApiController;
use PHPUnit\Framework\TestCase;

final class PackageFileModelTest extends TestCase
{
    private static string $fixtureRoot;
    private static string $pluginDirectory;
    private static string $themeDirectory;

    public static function setUpBeforeClass(): void
    {
        self::$fixtureRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'v-update-api-' . bin2hex(random_bytes(8));
        self::$pluginDirectory = self::$fixtureRoot . DIRECTORY_SEPARATOR . 'plugins';
        self::$themeDirectory = self::$fixtureRoot . DIRECTORY_SEPARATOR . 'themes';

        mkdir(self::$pluginDirectory, 0777, true);
        mkdir(self::$themeDirectory, 0777, true);
        define('DB_FILE', ':memory:');
        define('ENCRYPTION_KEY', str_repeat('a', 64));
        define('LOG_FILE', self::$fixtureRoot . DIRECTORY_SEPARATOR . 'api.log');
        define('PLUGINS_DIR', self::$pluginDirectory);
        define('THEMES_DIR', self::$themeDirectory);
        $fixtureRoot = self::$fixtureRoot;
        $logFile = LOG_FILE;
        register_shutdown_function(static function () use ($fixtureRoot, $logFile): void {
            if (file_exists($logFile)) {
                unlink($logFile);
            }
            if (is_dir($fixtureRoot)) {
                rmdir($fixtureRoot);
            }
        });

        $connection = DatabaseManager::getInstance()->getConnection();
        $connection->executeStatement('CREATE TABLE hosts (domain TEXT PRIMARY KEY, key TEXT NOT NULL)');
        $connection->executeStatement('CREATE TABLE logs (domain TEXT, type TEXT, date TEXT, status TEXT)');
        $connection->executeStatement('CREATE TABLE blacklist (ip TEXT PRIMARY KEY, login_attempts INTEGER, blacklisted INTEGER, timestamp INTEGER)');
    }

    public static function tearDownAfterClass(): void
    {
        self::clearDirectory(self::$pluginDirectory);
        self::clearDirectory(self::$themeDirectory);
        DatabaseManager::getInstance()->getConnection()->close();
        rmdir(self::$pluginDirectory);
        rmdir(self::$themeDirectory);
    }

    public function testPluginListingAndLookupReadVersionsFromZipFilenames(): void
    {
        self::writePackage(self::$pluginDirectory, 'z-plugin_1.0.zip');
        self::writePackage(self::$pluginDirectory, 'a-plugin_1.5.zip');
        self::writePackage(self::$pluginDirectory, 'a-plugin_2.0.zip');
        self::writePackage(self::$pluginDirectory, 'invalid filename.zip');

        self::assertSame(
            [
                ['slug' => 'a-plugin', 'version' => '2.0'],
                ['slug' => 'z-plugin', 'version' => '1.0'],
            ],
            PluginModel::getPlugins()
        );
        self::assertSame('2.0', PluginModel::getVersionBySlug('a-plugin'));
        self::assertNull(PluginModel::getVersionBySlug('missing-plugin'));
    }

    public function testThemeListingAndLookupReadVersionsFromZipFilenames(): void
    {
        self::writePackage(self::$themeDirectory, 'sample-theme_1.8.zip');
        self::writePackage(self::$themeDirectory, 'readme.txt');

        self::assertSame(
            [['slug' => 'sample-theme', 'version' => '1.8']],
            ThemeModel::getThemes()
        );
        self::assertSame('1.8', ThemeModel::getVersionBySlug('sample-theme'));
        self::assertNull(ThemeModel::getVersionBySlug('missing-theme'));
    }

    public function testPluginUploadPersistenceRemovesPriorVersionForSameSlug(): void
    {
        self::writePackage(self::$pluginDirectory, 'replace-me_1.0.zip');
        self::writePackage(self::$pluginDirectory, 'replace-me-pro_1.0.zip');
        $temporaryPath = self::$pluginDirectory . DIRECTORY_SEPARATOR . 'tmp_upload_test.zip';
        $newPath = self::$pluginDirectory . DIRECTORY_SEPARATOR . 'replace-me_2.0.zip';
        file_put_contents($temporaryPath, 'new plugin package');

        $result = self::invokeUploadPersistence(PluginModel::class, 'replace-me', $temporaryPath, $newPath);

        self::assertTrue($result['success']);
        self::assertFileDoesNotExist(self::$pluginDirectory . DIRECTORY_SEPARATOR . 'replace-me_1.0.zip');
        self::assertSame('new plugin package', file_get_contents($newPath));
        self::assertSame('2.0', PluginModel::getVersionBySlug('replace-me'));
        self::assertFileExists(self::$pluginDirectory . DIRECTORY_SEPARATOR . 'replace-me-pro_1.0.zip');
    }

    public function testThemeUploadPersistenceRemovesPriorVersionForSameSlug(): void
    {
        self::writePackage(self::$themeDirectory, 'replace-me_1.0.zip');
        self::writePackage(self::$themeDirectory, 'replace-me-pro_1.0.zip');
        $temporaryPath = self::$themeDirectory . DIRECTORY_SEPARATOR . 'tmp_upload_test.zip';
        $newPath = self::$themeDirectory . DIRECTORY_SEPARATOR . 'replace-me_2.0.zip';
        file_put_contents($temporaryPath, 'new theme package');

        $result = self::invokeUploadPersistence(ThemeModel::class, 'replace-me', $temporaryPath, $newPath);

        self::assertTrue($result['success']);
        self::assertFileDoesNotExist(self::$themeDirectory . DIRECTORY_SEPARATOR . 'replace-me_1.0.zip');
        self::assertSame('new theme package', file_get_contents($newPath));
        self::assertSame('2.0', ThemeModel::getVersionBySlug('replace-me'));
        self::assertFileExists(self::$themeDirectory . DIRECTORY_SEPARATOR . 'replace-me-pro_1.0.zip');
    }

    public function testDeletingPackageRemovesItFromFileBasedListing(): void
    {
        self::writePackage(self::$pluginDirectory, 'delete-me_1.0.zip');

        self::assertTrue(PluginModel::deletePlugin('delete-me_1.0.zip'));

        self::assertFileDoesNotExist(self::$pluginDirectory . DIRECTORY_SEPARATOR . 'delete-me_1.0.zip');
        self::assertNull(PluginModel::getVersionBySlug('delete-me'));
    }

    public function testDeletingThemeRemovesItFromFileBasedListing(): void
    {
        self::writePackage(self::$themeDirectory, 'delete-me_1.0.zip');

        self::assertTrue(ThemeModel::deleteTheme('delete-me_1.0.zip'));

        self::assertFileDoesNotExist(self::$themeDirectory . DIRECTORY_SEPARATOR . 'delete-me_1.0.zip');
        self::assertNull(ThemeModel::getVersionBySlug('delete-me'));
    }

    public function testPluginDeleteHandlerAcceptsSubmittedPackageFilename(): void
    {
        self::writePackage(self::$pluginDirectory, 'controller-delete_1.0.zip');
        SessionManager::getInstance()->set('csrf_token', 'delete-test-token');
        $request = Request::create('POST', '/plupdate', [], [
            'csrf_token' => 'delete-test-token',
            'delete_plugin' => '1',
            'plugin_name' => 'controller-delete_1.0.zip',
        ]);

        $response = (new PluginsController())->handleSubmission($request);

        self::assertFileDoesNotExist(self::$pluginDirectory . DIRECTORY_SEPARATOR . 'controller-delete_1.0.zip');
        self::assertSame('/plupdate', $response->getHeaderLine('Location'));
    }

    public function testThemeDeleteHandlerAcceptsSubmittedPackageFilename(): void
    {
        self::writePackage(self::$themeDirectory, 'controller-delete_1.0.zip');
        SessionManager::getInstance()->set('csrf_token', 'delete-test-token');
        $request = Request::create('POST', '/thupdate', [], [
            'csrf_token' => 'delete-test-token',
            'delete_theme' => '1',
            'theme_name' => 'controller-delete_1.0.zip',
        ]);

        $response = (new ThemesController())->handleSubmission($request);

        self::assertFileDoesNotExist(self::$themeDirectory . DIRECTORY_SEPARATOR . 'controller-delete_1.0.zip');
        self::assertSame('/thupdate', $response->getHeaderLine('Location'));
    }

    public function testDeleteHandlersRejectArrayFilenamesWithoutThrowing(): void
    {
        self::writePackage(self::$pluginDirectory, 'protected-delete_1.0.zip');
        SessionManager::getInstance()->set('csrf_token', 'delete-test-token');
        $request = Request::create('POST', '/plupdate', [], [
            'csrf_token' => 'delete-test-token',
            'delete_plugin' => '1',
            'plugin_name' => ['protected-delete_1.0.zip'],
        ]);

        (new PluginsController())->handleSubmission($request);

        self::assertFileExists(self::$pluginDirectory . DIRECTORY_SEPARATOR . 'protected-delete_1.0.zip');
    }

    public function testPluginUploadHandlerAcceptsSinglePsrUploadedFile(): void
    {
        SessionManager::getInstance()->set('csrf_token', 'upload-test-token');
        $uploadedFile = new \GuzzleHttp\Psr7\UploadedFile(
            '',
            0,
            UPLOAD_ERR_NO_FILE,
            'plugin_1.0.zip'
        );
        $request = Request::create('POST', '/plupdate', [], [
            'csrf_token' => 'upload-test-token',
        ], [], [], ['plugin_file' => $uploadedFile]);

        $response = (new PluginsController())->handleSubmission($request);

        self::assertSame('/plupdate', $response->getHeaderLine('Location'));
    }

    public function testUploadRejectsEqualOrOlderPluginVersionsAndKeepsCurrentPackage(): void
    {
        self::writePackage(self::$pluginDirectory, 'versioned-plugin_2.0.zip');

        foreach (['2.0', '1.9'] as $version) {
            $messages = PluginModel::uploadFiles([
                'name' => 'versioned-plugin_' . $version . '.zip',
                'tmp_name' => 'unused-upload.tmp',
                'error' => UPLOAD_ERR_OK,
                'size' => 1,
            ]);

            self::assertStringContainsString('not newer than current version (2.0)', $messages[0]);
        }

        self::assertSame('2.0', PluginModel::getVersionBySlug('versioned-plugin'));
        self::assertFileExists(self::$pluginDirectory . DIRECTORY_SEPARATOR . 'versioned-plugin_2.0.zip');
        self::assertFileDoesNotExist(self::$pluginDirectory . DIRECTORY_SEPARATOR . 'versioned-plugin_1.9.zip');
    }

    public function testUploadRejectsEqualOrOlderThemeVersionsAndKeepsCurrentPackage(): void
    {
        self::writePackage(self::$themeDirectory, 'versioned-theme_2.0.zip');

        foreach (['2.0', '1.9'] as $version) {
            $messages = ThemeModel::uploadFiles([
                'name' => 'versioned-theme_' . $version . '.zip',
                'tmp_name' => 'unused-upload.tmp',
                'error' => UPLOAD_ERR_OK,
                'size' => 1,
            ]);

            self::assertStringContainsString('not newer than current version (2.0)', $messages[0]);
        }

        self::assertSame('2.0', ThemeModel::getVersionBySlug('versioned-theme'));
        self::assertFileExists(self::$themeDirectory . DIRECTORY_SEPARATOR . 'versioned-theme_2.0.zip');
        self::assertFileDoesNotExist(self::$themeDirectory . DIRECTORY_SEPARATOR . 'versioned-theme_1.9.zip');
    }

    public function testUnavailableUploadDestinationPreservesCurrentPluginPackage(): void
    {
        self::writePackage(self::$pluginDirectory, 'protected-plugin_1.0.zip');
        $temporaryPath = self::$pluginDirectory . DIRECTORY_SEPARATOR . 'tmp_upload_failure.zip';
        $unavailablePath = self::$pluginDirectory . DIRECTORY_SEPARATOR . 'missing' . DIRECTORY_SEPARATOR . 'protected-plugin_2.0.zip';
        file_put_contents($temporaryPath, 'new plugin package');

        $result = self::invokeUploadPersistence(PluginModel::class, 'protected-plugin', $temporaryPath, $unavailablePath);

        self::assertFalse($result['success']);
        self::assertFileExists(self::$pluginDirectory . DIRECTORY_SEPARATOR . 'protected-plugin_1.0.zip');
        self::assertSame('1.0', PluginModel::getVersionBySlug('protected-plugin'));
        self::assertFileDoesNotExist($temporaryPath);
    }

    public function testUnavailableUploadDestinationPreservesCurrentThemePackage(): void
    {
        self::writePackage(self::$themeDirectory, 'protected-theme_1.0.zip');
        $temporaryPath = self::$themeDirectory . DIRECTORY_SEPARATOR . 'tmp_upload_failure.zip';
        $unavailablePath = self::$themeDirectory . DIRECTORY_SEPARATOR . 'missing' . DIRECTORY_SEPARATOR . 'protected-theme_2.0.zip';
        file_put_contents($temporaryPath, 'new theme package');

        $result = self::invokeUploadPersistence(ThemeModel::class, 'protected-theme', $temporaryPath, $unavailablePath);

        self::assertFalse($result['success']);
        self::assertFileExists(self::$themeDirectory . DIRECTORY_SEPARATOR . 'protected-theme_1.0.zip');
        self::assertSame('1.0', ThemeModel::getVersionBySlug('protected-theme'));
        self::assertFileDoesNotExist($temporaryPath);
    }

    public function testMissingPackageDirectoriesReturnEmptyListings(): void
    {
        $hiddenPluginDirectory = self::$pluginDirectory . '-temporarily-hidden';
        $hiddenThemeDirectory = self::$themeDirectory . '-temporarily-hidden';
        self::assertTrue(rename(self::$pluginDirectory, $hiddenPluginDirectory));
        self::assertTrue(rename(self::$themeDirectory, $hiddenThemeDirectory));

        try {
            self::assertSame([], PluginModel::getPlugins());
            self::assertSame([], ThemeModel::getThemes());
        } finally {
            self::assertTrue(rename($hiddenPluginDirectory, self::$pluginDirectory));
            self::assertTrue(rename($hiddenThemeDirectory, self::$themeDirectory));
        }
    }

    public function testApiServesPackageFileForNewerVersionAndReturnsNoContentOtherwise(): void
    {
        $packagePath = self::$pluginDirectory . DIRECTORY_SEPARATOR . 'api-plugin_2.0.zip';
        file_put_contents($packagePath, 'temporary package bytes');

        $update = self::apiRequest('api-plugin', '1.0');

        self::assertSame(200, $update->getStatusCode());
        self::assertSame(realpath($packagePath), realpath((string) $update->getFile()));
        self::assertSame('attachment; filename="api-plugin_2.0.zip"', $update->getHeaderLine('Content-Disposition'));
        self::assertSame((string) filesize($packagePath), $update->getHeaderLine('Content-Length'));
        self::assertSame('temporary package bytes', file_get_contents((string) $update->getFile()));

        $noUpdate = self::apiRequest('api-plugin', '2.0');
        self::assertSame(204, $noUpdate->getStatusCode());
    }

    public function testApiServesThemePackageFromItsFilename(): void
    {
        $packagePath = self::$themeDirectory . DIRECTORY_SEPARATOR . 'api-theme_1.4.zip';
        file_put_contents($packagePath, 'temporary theme bytes');

        $response = self::apiRequest('api-theme', '1.0', 'theme');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(realpath($packagePath), realpath((string) $response->getFile()));
        self::assertSame('temporary theme bytes', file_get_contents((string) $response->getFile()));
    }

    public function testApiReturnsNotFoundForUnknownFileBasedPackageSlug(): void
    {
        self::assertSame(404, self::apiRequest('unknown-plugin', '1.0')->getStatusCode());
    }

    public function testApiRejectsArrayQueryParametersWithBadRequest(): void
    {
        $request = Request::create('GET', '/api', [
            'type' => 'plugin',
            'domain' => ['example.com'],
            'key' => 'fixture-api-key',
            'slug' => 'sample',
            'version' => '1.0',
        ], [], ['REMOTE_ADDR' => '127.0.0.1']);

        self::assertSame(400, (new ApiController())->handleRequest($request)->getStatusCode());
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::clearDirectory(self::$pluginDirectory);
        self::clearDirectory(self::$themeDirectory);
        $connection = DatabaseManager::getInstance()->getConnection();
        $connection->executeStatement('DELETE FROM hosts');
        $connection->executeStatement('DELETE FROM logs');
        $connection->executeStatement('DELETE FROM blacklist');
        $_GET = [];
        unset($_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_METHOD']);
    }

    /** @return array{success: bool, error: string} */
    private static function invokeUploadPersistence(string $class, string $slug, string $temporaryPath, string $finalPath): array
    {
        $method = new ReflectionMethod($class, 'persistUploadedArtifact');
        return $method->invoke(null, $slug, $temporaryPath, $finalPath);
    }

    private static function writePackage(string $directory, string $filename): void
    {
        file_put_contents($directory . DIRECTORY_SEPARATOR . $filename, 'package fixture');
    }

    private static function apiRequest(string $slug, string $version, string $type = 'plugin'): \App\Core\Response
    {
        if (HostsModel::getEncryptedKeyByDomain('example.com') === null) {
            HostsModel::addEntry('example.com', 'fixture-api-key');
        }
        $request = Request::create('GET', '/api', [
            'type' => $type,
            'domain' => 'example.com',
            'key' => 'fixture-api-key',
            'slug' => $slug,
            'version' => $version,
        ], [], ['REMOTE_ADDR' => '127.0.0.1']);

        return (new ApiController())->handleRequest($request);
    }

    private static function clearDirectory(string $directory): void
    {
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
