<?php

namespace NewfoldLabs\WP\Module\Data;

use Mockery;
use NewfoldLabs\WP\Module\Data\Listeners\Commerce;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\Listeners\Commerce
 */
class CommerceWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	public function tear_down() {
		Mockery::close();
		WC()->cart = null;
		WC()->initialize_cart();
		parent::tear_down();
	}

	/**
	 * @covers ::site_cart_views
	 */
	public function test_site_cart_views_happy_path(): void {

		$product = new \WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( '9.99' );
		$product->set_status( 'publish' );
		$product_id = $product->save();

		WC()->cart->add_to_cart( $product_id, 2 );

		$event_manager = Mockery::mock( EventManager::class );

		$sut = new Commerce( $event_manager );

		$event_manager->expects( 'push' )->once()->withArgs(
			function ( $event ) {
				$this->assertInstanceOf( \NewfoldLabs\WP\Module\Data\Event::class, $event );
				$this->assertEquals( 'commerce', $event->category );
				$this->assertEquals( 'site_cart_view', $event->key );
				$this->assertEquals( 2, $event->data['product_count'] );
				$this->assertEquals( '19.98', $event->data['cart_total'] );
				$this->assertEquals( 'USD', $event->data['currency'] );
				return true;
			}
		);

		$sut->site_cart_views();
	}

	/**
	 * @covers ::site_cart_views
	 */
	public function test_site_cart_views_nothing_added(): void {

		$event_manager = Mockery::mock( EventManager::class );

		$sut = new Commerce( $event_manager );

		$event_manager->expects( 'push' )->never();

		$sut->site_cart_views();
	}

	/**
	 * @covers ::site_cart_views
	 */
	public function test_site_cart_views_uninitialized_cart(): void {

		$wc       = WC();
		$wc->cart = null;

		$event_manager = Mockery::mock( EventManager::class );

		$sut = new Commerce( $event_manager );

		$event_manager->expects( 'push' )->never();

		$sut->site_cart_views();
	}
}
