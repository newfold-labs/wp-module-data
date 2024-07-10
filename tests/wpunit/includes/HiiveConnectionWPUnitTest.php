<?php

namespace NewfoldLabs\WP\Module\Data;

use Mockery;
use function NewfoldLabs\WP\ModuleLoader\container;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\HiiveConnection
 */
class HiiveConnectionWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Previously, choosing to use a non-blocking request was an option. Now the test is kept to
	 * ensure an error is returned when the response is invalid.
	 *
	 * @covers ::notify
	 */
	public function test_notify_non_blocking_returns_error(): void {

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

		$result = $sut->notify( array( $event ) );

		$this->assertWPError( $result );
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

		$result = $sut->notify( array( $event ) );

		$this->assertWPError( $result );
	}

	/**
	 * @covers ::send_event
	 * @covers ::hiive_request
	 */
	public function test_plugin_search(): void {

		$sut = new HiiveConnection();

		update_option( 'nfd_data_token', 'appear-connected' );

		/**
		 * For {@see HiiveConnection::get_core_data()} to work, it needs this.
		 */
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
					'body'          => json_encode(
						array(
							'data' => array(
								array(
									'id'         => 'notification123',
									'locations'  => array(),
									'query'      => null,
									'expiration' => time(),
									'content'    => '<p>Some content</p>',
								),
							),
						)
					),
					'response'      => array(
						'code'    => 200,
						'message' => 'OK',
					),
				);
			}
		);

		$event = new Event(
			'admin',
			'plugin_search',
			array(
				'type'  => 'term',
				'query' => 'seo',
			),
		);

		$result = $sut->send_event( $event );

		$this->assertEquals( 'notification123', $result[0]['id'] );
	}
}
