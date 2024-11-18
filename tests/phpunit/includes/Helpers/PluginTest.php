<?php

namespace NewfoldLabs\WP\Module\Data\Helpers;

use WP_Mock;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\Helpers\Plugin
 */
class PluginTest extends \WP_Mock\Tools\TestCase {

	public function tearDown(): void {
		parent::tearDown();

		\Patchwork\restoreAll();
	}

	public function test_includes_plugins_functions(): void {

		$sut = new Plugin();

		$temp_dir = sys_get_temp_dir();

		@mkdir( $temp_dir . '/wp-admin/includes', 0777, true );
		file_put_contents( $temp_dir . '/wp-admin/includes/plugin.php', '<?php' );

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) use ($temp_dir) {
				switch ($constant_name) {
					case 'ABSPATH':
						return $temp_dir;
					case 'WP_PLUGIN_DIR':
						return dirname( __DIR__, 4 ) . '/wp-content/plugins';
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		\Patchwork\redefine(
			'function_exists',
			function ( string $function_name ) {
				switch ($function_name) {
					case 'get_plugin_data':
						return false;
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		WP_Mock::passthruFunction( 'wp_normalize_path' );

		WP_Mock::userFunction( 'get_plugin_data' )
		       ->once()
		       ->andReturn( array(

		       ) );
		WP_Mock::userFunction( 'is_plugin_active' )
			->once()
			->andReturnTrue();

		WP_Mock::userFunction( 'get_site_option' )
			->once()
			->with('auto_update_plugin', 'true')
			->andReturnTrue();

		WP_Mock::userFunction( 'get_site_option' )
			->once()
			->with('auto_update_plugins', \WP_Mock\Functions::type( 'array' ))
			->andReturn(array());

		$sut->collect( 'bluehost-wordpress-plugin/bluehost-wordpress-plugin.php' );

		$this->assertConditionsMet();
	}
}
