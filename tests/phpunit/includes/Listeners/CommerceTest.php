<?php

namespace NewfoldLabs\WP\Module\Data\Listeners;

use Mockery;
use NewfoldLabs\WP\Module\Data\Event;
use NewfoldLabs\WP\Module\Data\EventManager;
use NewfoldLabs\WP\Module\Data\UnitTestCase;
use WP_Mock;
use WP_User;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\Listeners\Commerce
 */
class CommerceTest extends UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		WP_Mock::setUp();
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

	}

	public function tearDown(): void {
		parent::tearDown();
		WP_Mock::tearDown();

		unset( $_SERVER['HTTP_HOST'] );
		unset( $_SERVER['REQUEST_URI'] );
		unset( $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * @covers ::woocommerce_hpos_enabled
	 */
	public function test_woocommerce_hpos_enabled(): void {

		$event_manager = Mockery::mock( EventManager::class );
		$event_manager->expects( 'push' )->once()
			->withArgs(
				function ( Event $event ) {
					return $event->data['label_key'] === 'type' && $event->data['type'] === 'hpos';
				}
			);

		$sut = new Commerce( $event_manager );

		WP_Mock::userFunction( 'is_ssl' )->once()->andReturnTrue();
		$_SERVER['HTTP_HOST']   = '';
		$_SERVER['REQUEST_URI'] = '';

		/**
		 * @see Event::__construct
		 */
		$_SERVER['REMOTE_ADDR'] = '';
		$wp_user                = Mockery::mock( WP_User::class )->makePartial();
		$wp_user->data          = new \stdClass();
		$wp_user->user_nicename = 'admin';
		WP_Mock::userFunction( 'get_current_user_id' )->twice()->andReturn( 1 );
		WP_Mock::userFunction( 'get_user_by' )->once()->andReturn( $wp_user );
		WP_Mock::userFunction( 'get_user_locale' )->once()->andReturn( 'en_US' );

		$sut->woocommerce_hpos_enabled( '', 'yes', 'woocommerce_custom_orders_table_enabled' );

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::woopay_connection
	 */
	public function test_woopay_connection(): void {

		WP_Mock::userFunction( 'is_ssl' )->once()->andReturnTrue();
		$_SERVER['HTTP_HOST']   = 'example.com/';
		$_SERVER['REQUEST_URI'] = 'subdir';

		$expected_data_array = array(
			'label_key' => 'provider',
			'provider'  => 'woopay',
			'status'    => 'connected',
			'page'      => 'https://example.com/subdir',
		);

		$sut = Mockery::mock( Commerce::class )->makePartial();
		$sut->shouldAllowMockingProtectedMethods();
		$sut->expects( 'push' )->once()
			->with( 'payment_connected', $expected_data_array );

		$wcpay_account_data = array(
			'data' => array(
				'account_id'   => 'acc_123456789',
				'status'       => 'connected',
				'last_updated' => '2025-01-08T12:34:56Z',
			),
		);

		$sut->woopay_connection( $wcpay_account_data, '' );

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::site_cart_views
	 */
	public function test_site_cart_views_woocommerce_inactive(): void {

		\Patchwork\redefine(
			'function_exists',
			function ( string $function_name ) {
				switch ($function_name) {
					case 'WC':
						return false;
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		$event_manager = Mockery::mock( EventManager::class );

		$sut = new Commerce( $event_manager );

		$event_manager->expects( 'push' )->never();

		$sut->site_cart_views();

		$this->assertConditionsMet();
	}
}
