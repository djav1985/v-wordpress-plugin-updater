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
            'domain'  => 'example.com',
            'slug'    => 'my plugin',
            'version' => '1.0.0',
            'key'     => 'abc',
        ];

        $url = add_query_arg($args, 'https://api.example.com');

        $this->assertStringContainsString('slug=my%20plugin', $url);
        $this->assertStringNotContainsString('my%2520plugin', $url);
    }

    public function testThemeUpdaterHasPluginHeader(): void
    {
        $content = file_get_contents(__DIR__ . '/../mu-plugin/v-sys-theme-updater.php');
        $this->assertStringContainsString('Plugin Name:', $content);
        $this->assertStringNotContainsString('Theme Name:', $content);
    }

    public function testPluginUpdaterHasPluginHeader(): void
    {
        $content = file_get_contents(__DIR__ . '/../mu-plugin/v-sys-plugin-updater.php');
        $this->assertStringContainsString('Plugin Name:', $content);
    }

    public function testAddQueryArgReservedCharactersEncodeOnce(): void
    {
        $args = ['slug' => 'a+b/c'];
        $url = add_query_arg($args, 'https://api.example.com');
        $this->assertStringContainsString('slug=a%2Bb%2Fc', $url);
        $this->assertStringNotContainsString('a%252Bb%252Fc', $url);
    }
}
