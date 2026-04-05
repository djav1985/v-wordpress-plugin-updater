<?php

/**
 * Tests for PluginUpdater::fetch_package and ThemeUpdater::fetch_package.
 *
 * Verifies that:
 *  - request URLs contain the correct parameter names (type, slug, domain, version, key)
 *  - HTTP 204 is mapped to 'no_update'
 *  - HTTP 403 is mapped to 'unauthorized' (not 401)
 *  - HTTP 200 is mapped to 'update' with a non-empty download_url
 *  - WP_Error / unexpected codes are mapped to 'error'
 */

// ---------------------------------------------------------------------------
// Global stubs (WordPress classes and bootstrap-level functions)
// ---------------------------------------------------------------------------
namespace {
    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', sys_get_temp_dir() . '/' );
    }
    if ( ! defined( 'VONTMNT_API_URL' ) ) {
        define( 'VONTMNT_API_URL', 'https://api.example.com' );
    }

    if ( ! class_exists( 'WP_Error' ) ) {
        class WP_Error {
            private string $code;
            private string $message;
            public function __construct( $code = '', $msg = '' ) {
                $this->code    = (string) $code;
                $this->message = (string) $msg;
            }
            public function get_error_messages(): array { return [ $this->message ]; }
            public function get_error_message(): string { return $this->message; }
        }
    }

    // Stub WP_Upgrader_Skin before SilentUpgraderSkin is loaded
    if ( ! class_exists( 'WP_Upgrader_Skin' ) ) {
        class WP_Upgrader_Skin {
            public $errors = [];
            public function __construct( $args = [] ) {}
            public function set_upgrader( &$upgrader ) {}
            public function add_strings() {}
            public function header() {}
            public function footer() {}
            public function error( $errors ) {}
            public function feedback( $string, ...$args ) {}
            public function before() {}
            public function after() {}
            public function bulk_header() {}
            public function bulk_footer() {}
        }
    }

    if ( ! function_exists( 'add_action' ) ) {
        function add_action( ...$args ) {}
    }
    if ( ! function_exists( 'wp_next_scheduled' ) ) {
        function wp_next_scheduled( $hook ) { return false; }
    }
    if ( ! function_exists( 'wp_schedule_event' ) ) {
        function wp_schedule_event( ...$args ) {}
    }
    if ( ! function_exists( 'get_plugins' ) ) {
        function get_plugins() { return []; }
    }
    if ( ! function_exists( 'wp_get_themes' ) ) {
        function wp_get_themes() { return []; }
    }
    if ( ! function_exists( 'get_option' ) ) {
        function get_option( $name ) { return false; }
    }
    if ( ! function_exists( 'update_option' ) ) {
        function update_option( $name, $value ) { return true; }
    }
    if ( ! function_exists( 'set_transient' ) ) {
        function set_transient( ...$args ) {}
    }
    if ( ! function_exists( 'wp_upload_dir' ) ) {
        function wp_upload_dir() { return [ 'path' => sys_get_temp_dir(), 'error' => false ]; }
    }
    if ( ! function_exists( 'wp_delete_file' ) ) {
        function wp_delete_file( $file ) {}
    }
    if ( ! function_exists( 'add_filter' ) ) {
        function add_filter( ...$args ) {}
    }
    if ( ! function_exists( 'remove_filter' ) ) {
        function remove_filter( ...$args ) {}
    }
    if ( ! function_exists( 'esc_url_raw' ) ) {
        function esc_url_raw( $url ) { return $url; }
    }
    if ( ! function_exists( 'sanitize_file_name' ) ) {
        function sanitize_file_name( $name ) { return preg_replace( '/[^a-zA-Z0-9._-]/', '-', $name ); }
    }
    if ( ! function_exists( 'trailingslashit' ) ) {
        function trailingslashit( $string ) { return rtrim( $string, '/' ) . '/'; }
    }
    if ( ! function_exists( 'is_main_site' ) ) {
        function is_main_site() { return true; }
    }
    if ( ! function_exists( '__' ) ) {
        function __( $text, $domain = '' ) { return $text; }
    }

    // Load plugin classes
    require_once __DIR__ . '/../v-wp-updater/helpers/Options.php';
    require_once __DIR__ . '/../v-wp-updater/helpers/SilentUpgraderSkin.php';
    require_once __DIR__ . '/../v-wp-updater/helpers/AbstractRemoteUpdater.php';
    require_once __DIR__ . '/../v-wp-updater/services/PluginUpdater.php';
    require_once __DIR__ . '/../v-wp-updater/services/ThemeUpdater.php';
}

// ---------------------------------------------------------------------------
// Namespace-level stubs for VWPU\Services so they shadow any global stubs.
// PHP resolves unqualified function calls by looking in the current namespace
// first, so these intercept calls made from PluginUpdater / ThemeUpdater.
// ---------------------------------------------------------------------------
namespace VWPU\Services {
    function wp_remote_get( string $url, array $args = [] ): mixed {
        return $GLOBALS['_wp_remote_stub']( $url, $args );
    }

    function wp_remote_retrieve_response_code( mixed $response ): int {
        return is_array( $response ) ? (int) ( $response['code'] ?? 0 ) : 0;
    }

    function wp_remote_retrieve_body( mixed $response ): string {
        return is_array( $response ) ? (string) ( $response['body'] ?? '' ) : '';
    }

    function is_wp_error( mixed $thing ): bool {
        return $thing instanceof \WP_Error;
    }

    function site_url(): string {
        return 'https://example.com';
    }

    function wp_parse_url( string $url, int $component = -1 ): mixed {
        return parse_url( $url, $component );
    }

    function add_query_arg( array $args, string $url ): string {
        return $url . '?' . http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
    }
}

// ---------------------------------------------------------------------------
// Also stub in VWPU\Helpers namespace (used by AbstractRemoteUpdater)
// ---------------------------------------------------------------------------
namespace VWPU\Helpers {
    function is_wp_error( mixed $thing ): bool {
        return $thing instanceof \WP_Error;
    }
    function esc_url_raw( string $url ): string {
        return $url;
    }
    function sanitize_file_name( string $name ): string {
        return preg_replace( '/[^a-zA-Z0-9._-]/', '-', $name );
    }
    function trailingslashit( string $string ): string {
        return rtrim( $string, '/' ) . '/';
    }
    function wp_upload_dir(): array {
        return [ 'path' => sys_get_temp_dir(), 'error' => false ];
    }
    function wp_delete_file( string $file ): void {}
    function download_url( string $url, int $timeout = 300 ): mixed {
        return $GLOBALS['_wp_remote_stub']( $url, [] );
    }
}

// ---------------------------------------------------------------------------
// Test classes and test suite
// ---------------------------------------------------------------------------
namespace Tests {

    use PHPUnit\Framework\TestCase;

    /**
     * Expose the protected fetch_package method for white-box testing.
     */
    class TestablePluginUpdater extends \VWPU\Services\PluginUpdater {
        public function exposedFetchPackage(
            array $item,
            string $version,
            string $key,
            string $url
        ): array {
            return $this->fetch_package( $item, $version, $key, $url );
        }
    }

    class TestableThemeUpdater extends \VWPU\Services\ThemeUpdater {
        public function exposedFetchPackage(
            array $item,
            string $version,
            string $key,
            string $url
        ): array {
            return $this->fetch_package( $item, $version, $key, $url );
        }
    }

    // -----------------------------------------------------------------------

    class UpdaterFetchPackageTest extends TestCase {

        private TestablePluginUpdater $pluginUpdater;
        private TestableThemeUpdater $themeUpdater;

        protected function setUp(): void {
            $this->pluginUpdater        = new TestablePluginUpdater();
            $this->themeUpdater         = new TestableThemeUpdater();
            $GLOBALS['_wp_remote_stub'] = null;
        }

        // -------------------------------------------------------------------
        // Helper: configure the stubbed HTTP response
        // -------------------------------------------------------------------

        private function stubResponse( int $code, string $body = '' ): void {
            $GLOBALS['_wp_remote_stub'] = static function ( string $url, array $args ) use ( $code, $body ): array {
                return [ 'code' => $code, 'body' => $body ];
            };
        }

        // -------------------------------------------------------------------
        // Parameter-name contract
        // -------------------------------------------------------------------

        public function testPluginFetchPackageSendsTypeAndSlugParams(): void {
            $capturedUrl = null;
            $GLOBALS['_wp_remote_stub'] = static function ( string $url ) use ( &$capturedUrl ): array {
                $capturedUrl = $url;
                return [ 'code' => 204, 'body' => '' ];
            };

            $this->pluginUpdater->exposedFetchPackage(
                [ 'slug' => 'my-plugin' ],
                '1.0.0',
                'testkey',
                'https://api.example.com'
            );

            $this->assertNotNull( $capturedUrl, 'wp_remote_get must have been called' );
            parse_str( (string) parse_url( $capturedUrl, PHP_URL_QUERY ), $query );

            $this->assertArrayHasKey( 'type', $query, 'Request must include "type" parameter' );
            $this->assertSame( 'plugin', $query['type'] );
            $this->assertArrayHasKey( 'slug', $query, 'Request must include "slug" parameter' );
            $this->assertArrayNotHasKey( 'plugin', $query, 'Legacy "plugin" parameter must not be sent' );
        }

        public function testPluginFetchPackageSendsCorrectSlugVersionKey(): void {
            $capturedUrl = null;
            $GLOBALS['_wp_remote_stub'] = static function ( string $url ) use ( &$capturedUrl ): array {
                $capturedUrl = $url;
                return [ 'code' => 204, 'body' => '' ];
            };

            $this->pluginUpdater->exposedFetchPackage(
                [ 'slug' => 'awesome-plugin' ],
                '2.3.1',
                'mykey',
                'https://api.example.com'
            );

            parse_str( (string) parse_url( $capturedUrl, PHP_URL_QUERY ), $query );
            $this->assertSame( 'awesome-plugin', $query['slug'] );
            $this->assertSame( '2.3.1', $query['version'] );
            $this->assertSame( 'mykey', $query['key'] );
        }

        public function testThemeFetchPackageSendsTypeAndSlugParams(): void {
            $capturedUrl = null;
            $GLOBALS['_wp_remote_stub'] = static function ( string $url ) use ( &$capturedUrl ): array {
                $capturedUrl = $url;
                return [ 'code' => 204, 'body' => '' ];
            };

            $this->themeUpdater->exposedFetchPackage(
                [ 'slug' => 'my-theme' ],
                '1.0.0',
                'testkey',
                'https://api.example.com'
            );

            $this->assertNotNull( $capturedUrl, 'wp_remote_get must have been called' );
            parse_str( (string) parse_url( $capturedUrl, PHP_URL_QUERY ), $query );

            $this->assertArrayHasKey( 'type', $query, 'Request must include "type" parameter' );
            $this->assertSame( 'theme', $query['type'] );
            $this->assertArrayHasKey( 'slug', $query, 'Request must include "slug" parameter' );
            $this->assertArrayNotHasKey( 'theme', $query, 'Legacy "theme" parameter must not be sent' );
        }

        public function testThemeFetchPackageSendsCorrectSlugVersionKey(): void {
            $capturedUrl = null;
            $GLOBALS['_wp_remote_stub'] = static function ( string $url ) use ( &$capturedUrl ): array {
                $capturedUrl = $url;
                return [ 'code' => 204, 'body' => '' ];
            };

            $this->themeUpdater->exposedFetchPackage(
                [ 'slug' => 'ocean-theme' ],
                '3.0.0',
                'themekey',
                'https://api.example.com'
            );

            parse_str( (string) parse_url( $capturedUrl, PHP_URL_QUERY ), $query );
            $this->assertSame( 'ocean-theme', $query['slug'] );
            $this->assertSame( '3.0.0', $query['version'] );
        }

        // -------------------------------------------------------------------
        // HTTP 204 → no_update
        // -------------------------------------------------------------------

        public function testPlugin204ReturnsNoUpdate(): void {
            $this->stubResponse( 204 );
            $result = $this->pluginUpdater->exposedFetchPackage(
                [ 'slug' => 'p' ], '1.0', 'k', 'https://api.example.com'
            );
            $this->assertSame( 'no_update', $result['status'] );
        }

        public function testTheme204ReturnsNoUpdate(): void {
            $this->stubResponse( 204 );
            $result = $this->themeUpdater->exposedFetchPackage(
                [ 'slug' => 't' ], '1.0', 'k', 'https://api.example.com'
            );
            $this->assertSame( 'no_update', $result['status'] );
        }

        // -------------------------------------------------------------------
        // HTTP 403 → unauthorized (the API server's auth failure code)
        // -------------------------------------------------------------------

        public function testPlugin403ReturnsUnauthorized(): void {
            $this->stubResponse( 403 );
            $result = $this->pluginUpdater->exposedFetchPackage(
                [ 'slug' => 'p' ], '1.0', 'k', 'https://api.example.com'
            );
            $this->assertSame( 'unauthorized', $result['status'] );
        }

        public function testTheme403ReturnsUnauthorized(): void {
            $this->stubResponse( 403 );
            $result = $this->themeUpdater->exposedFetchPackage(
                [ 'slug' => 't' ], '1.0', 'k', 'https://api.example.com'
            );
            $this->assertSame( 'unauthorized', $result['status'] );
        }

        public function testPlugin401ReturnsError(): void {
            // 401 is not the API server's auth code; must not be treated as unauthorized
            $this->stubResponse( 401 );
            $result = $this->pluginUpdater->exposedFetchPackage(
                [ 'slug' => 'p' ], '1.0', 'k', 'https://api.example.com'
            );
            $this->assertSame( 'error', $result['status'] );
        }

        // -------------------------------------------------------------------
        // HTTP 200 → update with non-empty download_url (binary ZIP contract)
        // -------------------------------------------------------------------

        public function testPlugin200ReturnsUpdateWithDownloadUrl(): void {
            $this->stubResponse( 200, 'BINARY_ZIP_DATA' );
            $result = $this->pluginUpdater->exposedFetchPackage(
                [ 'slug' => 'my-plugin' ], '1.0', 'k', 'https://api.example.com'
            );
            $this->assertSame( 'update', $result['status'] );
            $this->assertArrayHasKey( 'download_url', $result );
            $this->assertNotEmpty( $result['download_url'] );
        }

        public function testTheme200ReturnsUpdateWithDownloadUrl(): void {
            $this->stubResponse( 200, 'BINARY_ZIP_DATA' );
            $result = $this->themeUpdater->exposedFetchPackage(
                [ 'slug' => 'my-theme' ], '2.0', 'k', 'https://api.example.com'
            );
            $this->assertSame( 'update', $result['status'] );
            $this->assertArrayHasKey( 'download_url', $result );
            $this->assertNotEmpty( $result['download_url'] );
        }

        public function testPlugin200DownloadUrlContainsCorrectParams(): void {
            $this->stubResponse( 200, '' );
            $result = $this->pluginUpdater->exposedFetchPackage(
                [ 'slug' => 'cool-plugin' ], '1.5.0', 'securekey', 'https://api.example.com'
            );
            $this->assertSame( 'update', $result['status'] );

            parse_str( (string) parse_url( $result['download_url'], PHP_URL_QUERY ), $q );
            $this->assertSame( 'plugin', $q['type'] );
            $this->assertSame( 'cool-plugin', $q['slug'] );
            $this->assertSame( '1.5.0', $q['version'] );
            $this->assertSame( 'securekey', $q['key'] );
        }

        // -------------------------------------------------------------------
        // WP_Error → error
        // -------------------------------------------------------------------

        public function testPluginWpErrorReturnsError(): void {
            $GLOBALS['_wp_remote_stub'] = static function () {
                return new \WP_Error( 'http_request_failed', 'cURL error' );
            };
            $result = $this->pluginUpdater->exposedFetchPackage(
                [ 'slug' => 'p' ], '1.0', 'k', 'https://api.example.com'
            );
            $this->assertSame( 'error', $result['status'] );
        }

        public function testThemeWpErrorReturnsError(): void {
            $GLOBALS['_wp_remote_stub'] = static function () {
                return new \WP_Error( 'http_request_failed', 'timeout' );
            };
            $result = $this->themeUpdater->exposedFetchPackage(
                [ 'slug' => 't' ], '1.0', 'k', 'https://api.example.com'
            );
            $this->assertSame( 'error', $result['status'] );
        }

        // -------------------------------------------------------------------
        // Unexpected HTTP codes → error
        // -------------------------------------------------------------------

        public function testPlugin500ReturnsError(): void {
            $this->stubResponse( 500 );
            $result = $this->pluginUpdater->exposedFetchPackage(
                [ 'slug' => 'p' ], '1.0', 'k', 'https://api.example.com'
            );
            $this->assertSame( 'error', $result['status'] );
        }

        public function testTheme404ReturnsError(): void {
            $this->stubResponse( 404 );
            $result = $this->themeUpdater->exposedFetchPackage(
                [ 'slug' => 't' ], '1.0', 'k', 'https://api.example.com'
            );
            $this->assertSame( 'error', $result['status'] );
        }

        // -------------------------------------------------------------------
        // Regression: a valid request can progress to the install path
        // -------------------------------------------------------------------

        public function testValidPluginRequestProgressesToInstallPath(): void {
            $this->stubResponse( 200, str_repeat( 'Z', 512 ) );

            $result = $this->pluginUpdater->exposedFetchPackage(
                [ 'slug' => 'my-plugin' ],
                '1.0.0',
                'validkey',
                'https://updates.example.com/api'
            );

            $this->assertSame( 'update', $result['status'], 'Status must be "update" to proceed to install' );
            $this->assertArrayHasKey( 'download_url', $result, 'download_url is required by AbstractRemoteUpdater' );
            $this->assertStringStartsWith( 'https://', $result['download_url'] );

            parse_str( (string) parse_url( $result['download_url'], PHP_URL_QUERY ), $q );
            $this->assertSame( 'plugin', $q['type'] );
            $this->assertSame( 'my-plugin', $q['slug'] );
            $this->assertSame( '1.0.0', $q['version'] );
            $this->assertSame( 'validkey', $q['key'] );
        }

        public function testValidThemeRequestProgressesToInstallPath(): void {
            $this->stubResponse( 200, str_repeat( 'Z', 512 ) );

            $result = $this->themeUpdater->exposedFetchPackage(
                [ 'slug' => 'my-theme' ],
                '2.0.0',
                'validkey',
                'https://updates.example.com/api'
            );

            $this->assertSame( 'update', $result['status'] );
            $this->assertArrayHasKey( 'download_url', $result );
            $this->assertStringStartsWith( 'https://', $result['download_url'] );

            parse_str( (string) parse_url( $result['download_url'], PHP_URL_QUERY ), $q );
            $this->assertSame( 'theme', $q['type'] );
            $this->assertSame( 'my-theme', $q['slug'] );
        }
    }
}
