<?php
namespace Tests;

use PHPUnit\Framework\TestCase;

if (!function_exists(__NAMESPACE__ . '\\add_query_arg')) {
    function add_query_arg($args, $url)
    {
        return $url . '?' . http_build_query($args, '', '&', PHP_QUERY_RFC3986);
    }
}

class UpdaterEncodingTest extends TestCase
{
    public function testAddQueryArgEncodesOnce(): void
    {
        $args = [
            'type'    => 'plugin',
            'domain'  => 'example domain',
            'slug'    => 'my plugin',
            'version' => '1.0 / beta',
            'key'     => 'abc',
        ];

        $url = add_query_arg($args, 'https://api.example.com');

        $this->assertStringContainsString('domain=example%20domain', $url);
        $this->assertStringContainsString('slug=my%20plugin', $url);
        $this->assertStringContainsString('version=1.0%20%2F%20beta', $url);
        $this->assertStringNotContainsString('my%2520plugin', $url);
        $this->assertStringNotContainsString('example%2520domain', $url);
        $this->assertStringNotContainsString('1.0%2520%252F%2520beta', $url);
    }

    public function testThemeUpdaterHasPluginHeader(): void
    {
        $file = __DIR__ . '/../mu-plugin/v-sys-theme-updater.php';
        if (!file_exists($file)) {
            $this->markTestSkipped('mu-plugin fixture not present in this environment');
        }
        $content = file_get_contents($file);
        $this->assertStringContainsString('Plugin Name:', $content);
        $this->assertStringNotContainsString('Theme Name:', $content);
    }

    public function testPluginUpdaterHasPluginHeader(): void
    {
        $file = __DIR__ . '/../mu-plugin/v-sys-plugin-updater.php';
        if (!file_exists($file)) {
            $this->markTestSkipped('mu-plugin fixture not present in this environment');
        }
        $content = file_get_contents($file);
        $this->assertStringContainsString('Plugin Name:', $content);
    }

    public function testAddQueryArgReservedCharactersEncodeOnce(): void
    {
        $args = ['slug' => 'a+b/c', 'version' => '1.0+b/c'];
        $url = add_query_arg($args, 'https://api.example.com');
        $this->assertStringContainsString('slug=a%2Bb%2Fc', $url);
        $this->assertStringContainsString('version=1.0%2Bb%2Fc', $url);
        $this->assertStringNotContainsString('a%252Bb%252Fc', $url);
        $this->assertStringNotContainsString('1.0%252Bb%252Fc', $url);
    }
}
