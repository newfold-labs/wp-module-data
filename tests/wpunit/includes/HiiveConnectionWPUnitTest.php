<?php

namespace NewfoldLabs\WP\Module\Data;

use Mockery;
use function NewfoldLabs\WP\ModuleLoader\container;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\HiiveConnection
 */
class HiiveConnectionWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * @covers ::notify
	 */
	public function test_notify_non_blocking(): void {

		$sut = new HiiveConnection();

		$event = Mockery::mock( Event::class );

		update_option( 'nfd_data_token', 'appear-connected' );

		$container = new \NewfoldLabs\WP\ModuleLoader\Container();
		container( $container );

		$plugin = Mockery::mock( \NewfoldLabs\WP\ModuleLoader\Plugin::class );
		$plugin->shouldReceive( 'get' )->andReturnUsing(
			function () {
				$args = func_get_args();
				return $args[1] ?? $args[0];
			}
		);
		$container->set( 'plugin', $plugin );

		/**
		 * @see WP_Http::request()
		 */
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'headers'       => array(),
					'body'          => '',
					'response'      => array(
						'code'    => false,
						'message' => false,
					),
					'cookies'       => array(),
					'http_response' => null,
				);
			}
		);

		$result = $sut->notify( array( $event ), false );

		$this->assertFalse($result['response']['code']);
	}

	/**
	 * @covers ::notify
	 */
	public function test_notify_wp_error(): void {

		$sut = new HiiveConnection();

		$event = Mockery::mock( Event::class );

		update_option( 'nfd_data_token', 'appear-connected' );

		$container = new \NewfoldLabs\WP\ModuleLoader\Container();
		container( $container );

		$plugin = Mockery::mock( \NewfoldLabs\WP\ModuleLoader\Plugin::class );
		$plugin->shouldReceive( 'get' )->andReturnUsing(
			function () {
				$args = func_get_args();
				return $args[1] ?? $args[0];
			}
		);
		$container->set( 'plugin', $plugin );

		/**
		 * @see WP_Http::request()
		 */
		add_filter(
			'pre_http_request',
			function () {
				return new \WP_Error( 'http_request_not_executed', 'User has blocked requests through HTTP.' );
			}
		);

		try {
			$sut->notify( array( $event ), false );
		} catch ( \Error $error ) {
			$this->fail( $error->getMessage() . PHP_EOL . $error->getTraceAsString() );
		}

		$this->assertTrue( true );
	}
}
