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

	public function setUp(): void
	{
		parent::setUp();

		WP_Mock::passthruFunction('__');

		WP_Mock::userFunction('wp_json_encode')->andReturn(function($input) {
			return json_encode($input);
		});
	}

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


	/**
	 * @covers ::notify
	 */
	public function test_notify_returns_wperror_when_no_auth_token(): void {
		$sut = new HiiveConnection();

		$event = Mockery::mock(Event::class);

		WP_Mock::userFunction('get_option')
			->with('nfd_data_token')
			->once()
			->andReturnNull();

		$result = $sut->notify(array($event));

		self::assertInstanceOf(\WP_Error::class, $result);
		self::assertEquals('This site is not connected to the hiive.', $result->get_error_message());
	}

	/**
	 * @covers ::notify
	 */
	public function test_notify_success(): void {
		$sut = Mockery::mock(HiiveConnection::class)->makePartial();
		$sut->expects('get_core_data')->once()->andReturn(['brand' => 'etc']);

		$event = Mockery::mock(Event::class);

		WP_Mock::userFunction('get_option')
			->with('nfd_data_token')
			->twice()
			->andReturn('valid_hiive_auth_token');

		WP_Mock::userFunction('wp_remote_post')
			->with('/sites/v1/events', \WP_Mock\Functions::type( 'array' ))
			->once()->andReturn(
			array(
				'response' => array(
					'code' => 200
				),
				'body' => json_encode(array('data' => array('notifications'))),
			)
		);

		$sut->notify(array($event));

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::notify
	 * @covers ::reconnect
	 * @covers ::connect
	 * @see EnsureSiteUrl::verifyUrl() in Hiive.
	 */
	public function test_notify_bad_token(): void {
		$sut = Mockery::mock(HiiveConnection::class)->makePartial();

		$event = Mockery::mock(Event::class);

		WP_Mock::userFunction('get_option')
			->with('nfd_data_token')
			->times(5)
			->andReturn('valid_hiive_auth_token');

		// Is called in the initial notify(), the connect(), and the second notify().
		$sut->expects('get_core_data')->times(3)->andReturn(['brand' => 'etc']);

		WP_Mock::userFunction('wp_remote_post')
			->with('/sites/v1/events', \WP_Mock\Functions::type( 'array' ))
			->twice()->andReturnValues(
				array(
					array(
						'response' => array(
							'code' => 403
						),
						'body' => json_encode(array('message' => 'Invalid token for url')),
					),
					array(
						'response' => array(
							'code' => 200
						),
						'body' => json_encode(array('data' => array('notifications'))),
					)
				)
			);

		// Calls ::rename()

		// Calls ::connect()

		// Calls ::is_throttled()
		$sut->expects('is_throttled')->once()->andReturn(false);

		// Calls ::throttle()
		$sut->expects('throttle')->once()->andReturns();

		WP_Mock::userFunction('wp_generate_password')
			->once()
			->andReturn('password');

		$temp_dir = sys_get_temp_dir();

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) use ( $temp_dir ) {
				switch ($constant_name) {
					case 'MINUTE_IN_SECONDS':
						return 60;
					case 'ABSPATH':
						return $temp_dir;
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		// Calls Transient::set()

		// Calls Transient::should_use_transients()
		@mkdir($temp_dir . '/wp-admin/includes', 0777, true);
		file_put_contents( $temp_dir .  '/wp-admin/includes/plugin.php', '<?php' );

		WP_Mock::userFunction('get_dropins')
			->once()
			->andReturn(array());

		WP_Mock::userFunction('set_transient')
			->once()->andReturnTrue();

		// Calls Plugin::collect_installed()

		WP_Mock::userFunction('get_plugins')
			->once()->andReturn(array());

		WP_Mock::userFunction('get_mu_plugins')
			->once()->andReturn(array());

		WP_Mock::userFunction('get_option')
			->with('nfd_data_connection_attempts', 0)
			->once()
			->andReturn(0);

		WP_Mock::userFunction('update_option')
			->with('nfd_data_connection_attempts', 1)
			->once()->andReturnTrue();

		WP_Mock::userFunction('wp_remote_post')
			->with('/sites/v2/reconnect', \WP_Mock\Functions::type( 'array' ))
			->once()->andReturn(array());

		WP_Mock::userFunction('wp_remote_retrieve_response_code')
			->with(\WP_Mock\Functions::type( 'array' ))
			->once()->andReturn(200);

		WP_Mock::userFunction('wp_remote_retrieve_body')
			->with(\WP_Mock\Functions::type( 'array' ))
			->once()->andReturn(json_encode(array('site' => array(), 'token' => 'hiive_auth_token')));

		WP_Mock::userFunction('update_option')
			->with('nfd_data_token', 'hiive_auth_token')
			->once()->andReturnTrue();

		$sut->notify(array($event));

		$this->assertConditionsMet();
	}

	public function test_fails_to_reconnect()
	{
		$sut = Mockery::mock(HiiveConnection::class)->makePartial();
		$sut->expects('get_core_data')->once()->andReturn(['brand' => 'etc']);

		$event = Mockery::mock(Event::class);

		WP_Mock::userFunction('get_option')
			->with('nfd_data_token')
			->twice()
			->andReturn('valid_hiive_auth_token');

		WP_Mock::userFunction('wp_remote_post')
			->with('/sites/v1/events', \WP_Mock\Functions::type( 'array' ))
			->once()->andReturn(
				array(
					'response' => array(
						'code' => 403
					),
					'body' => json_encode(array('message' => 'Invalid token for url')),
				)
			);

		$sut->expects('reconnect')->once()->andReturnFalse();

		$result = $sut->notify(array($event));

		self::assertInstanceOf(\WP_Error::class, $result);
		self::assertEquals('This site is not connected to the hiive.', $result->get_error_message());
	}
}
