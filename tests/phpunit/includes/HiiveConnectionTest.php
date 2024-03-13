<?php

namespace NewfoldLabs\WP\Module\Data;

use Mockery;
use NewfoldLabs\WP\Module\Data\Listeners\Plugin;
use WP_Mock;
use WP_Mock\Tools\TestCase;
use function NewfoldLabs\WP\ModuleLoader\container;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\HiiveConnection
 */
class HiiveConnectionTest extends TestCase {
    /**
     * @covers ::get_core_data
     */
    public function test_plugin_sends_boxname_to_hiive(): void
    {
//        WP_Mock::expectAction('newfold_container_set');

        $plugin = Mockery::mock(Plugin::class);
        $plugin->brand = 'bluehost';
        $plugin->expects('get')->once()->with('id', 'error')->andReturn('bluehost');
        $plugin->expects('get')->once()->with('version', '0')->andReturn('1.2.3');
        container()->set('plugin', $plugin);

        WP_Mock::passthruFunction('sanitize_title');

        WP_Mock::userFunction('get_option')->once()->with('newfold_cache_level', 2)->andReturn(2);
        WP_Mock::userFunction('get_option')->once()->with('newfold_cloudflare_enabled', false)->andReturn(false);
        WP_Mock::userFunction('get_option')->once()->with('admin_email')->andReturn('admin@example.com');
        WP_Mock::userFunction('get_site_url')->once()->withNoArgs()->andReturn('http://example.com');

//        WP_Mock::expectFilter('newfold_wp_data_module_core_data_filter');

        global $wpdb;
        $wpdb = Mockery::mock();
        $wpdb->expects('db_version')->once()->andReturn('1.2.3');

        $sut = new HiiveConnection();

        $result = $sut->get_core_data();

        self::assertArrayHasKey('hostname', $result);
    }
    /**
     * @covers ::get_core_data
     */
    public function test_plugin_sends_server_path_to_hiive(): void
    {
//        WP_Mock::expectAction('newfold_container_set');

        $plugin = Mockery::mock(Plugin::class);
        $plugin->brand = 'bluehost';
        $plugin->expects('get')->once()->with('id', 'error')->andReturn('bluehost');
        $plugin->expects('get')->once()->with('version', '0')->andReturn('1.2.3');
        container()->set('plugin', $plugin);

        WP_Mock::passthruFunction('sanitize_title');

        WP_Mock::userFunction('get_option')->once()->with('newfold_cache_level', 2)->andReturn(2);
        WP_Mock::userFunction('get_option')->once()->with('newfold_cloudflare_enabled', false)->andReturn(false);
        WP_Mock::userFunction('get_option')->once()->with('admin_email')->andReturn('admin@example.com');
        WP_Mock::userFunction('get_site_url')->once()->withNoArgs()->andReturn('http://example.com');

//        WP_Mock::expectFilter('newfold_wp_data_module_core_data_filter');

        global $wpdb;
        $wpdb = Mockery::mock();
        $wpdb->expects('db_version')->once()->andReturn('1.2.3');

        \Patchwork\redefine(
            'constant',
            function ( string $constant_name ): string {
                return 'ABSPATH' === $constant_name
                    ? '/path/on/server/'
                    : \Patchwork\relay(func_get_args());
            }
        );

        $sut = new HiiveConnection();

        $result = $sut->get_core_data();

        self::assertArrayHasKey('server_path', $result);
        self::assertEquals('/path/on/server/', $result['server_path']);
    }
}