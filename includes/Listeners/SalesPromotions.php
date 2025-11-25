<?php

namespace NewfoldLabs\WP\Module\Data\Listeners;

use WC_Cart;
use WP_Post;

/**
 * Monitors SalesPromotion events
 */
class SalesPromotions extends Listener {

	/**
	 * Register the hooks for the listener
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'rest_after_insert_bh_sales_campaign', array( $this, 'register_campaign' ), 10 );
		add_action( 'bluehost/sales_promotions/edit_campaign_event_modal_opened', array(
			$this,
			'create_campaign_modal_open',
		),          10, 2 );
		add_action( 'bluehost/sales_promotions/edit_campaign_event_campaign_selected', array(
			$this,
			'campaign_selected',
		),          10, 2 );
		add_action( 'bluehost/sales_promotions/edit_campaign_event_campaign_abandoned', array(
			$this,
			'campaign_abandoned',
		),          10, 2 );
		add_action( 'woocommerce_payment_complete', array( $this, 'checkout_campaigns_used' ) );
	}

	/**
	 * Campaign created
	 *
	 * @param WP_Post $post Campaign data
	 *
	 * @return WP_Post The post value
	 */
	public function register_campaign( $post ) {
		$campaign = function_exists( 'bh_sales_promotions_get_campaign' ) ? bh_sales_promotions_get_campaign( $post->ID ) : false;
		if ( $campaign ) {
			$type = $campaign->get_type();

			$data = array(
				'label_key' => 'type',
				'type'      => $type,
			);

			$this->push(
				'campaign_created',
				$data
			);
		}

		return $post;
	}

	/**
	 * Track sales_promotion create campaign modal window open
	 * Send data to hiive
	 *
	 * @param string $args  A list of details that were involved on the event.
	 * @param string $event The name of the event.
	 *
	 * @return void
	 */
	public function create_campaign_modal_open( $args, $event ) {
		$url = is_ssl() ? 'https://' : 'http://';
		$url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$data = array(
			'label_key' => 'trigger',
			'trigger'   => 'Campaign Modal Open',
			'page'      => $url,
		);

		$this->push(
			'modal_open',
			$data
		);
	}

	/**
	 * Track sales_promotion campaign selection
	 * Send data to hiive
	 *
	 * @param string $args  A list of details that were involved on the event.
	 * @param string $event The name of the event.
	 *
	 * @return void
	 */
	public function campaign_selected( $args, $event ) {
		$data = array(
			'label_key'     => 'campaign_slug',
			'type'          => $args['type'],
			'campaign_slug' => $args['type'],
		);

		$this->push(
			'campaign_selected',
			$data
		);
	}

	/**
	 * Track sales_promotion campaign abondoned
	 * Send data to hiive
	 *
	 * @param string $args  A list of details that were involved on the event.
	 * @param string $event The name of the event.
	 *
	 * @return void
	 */
	public function campaign_abandoned( $args, $event ) {
		$data = array(
			'label_key'     => 'campaign_slug',
			'type'          => $args['type'],
			'campaign_slug' => $args['type'] . '-' . $args['id'],
		);

		$this->push(
			'campaign_abandoned',
			$data
		);
	}

	/**
	 * Track sales_promotion campaigns used in checkout page
	 * Send data to hiive
	 *
	 * @return void
	 */
	public function checkout_campaigns_used() {
		$campaigns      = array();
		$campaign_total = 0;

		$cart = WC()->cart;

		if ( $cart instanceof WC_Cart ) {
			// To track Cart Discount
			foreach ( $cart->get_applied_coupons() as $coupon_item ) {
				if( false !== strpos ( $coupon_item, 'bh_sales_promotions_cart_discount')  ) {
					array_push( $campaigns, 'cart-discount' );
					$campaign_total += $cart->coupon_discount_totals[ $coupon_item ];
				}
			}

			// To track free shipping campaign ( Using reflection to access protected properties)
			$reflection_class          = new \ReflectionClass( $cart );
			$shipping_methods_property = $reflection_class->getProperty( 'shipping_methods' );
			$shipping_methods_property->setAccessible( true );
			$shipping_methods = $shipping_methods_property->getValue( $cart );
			foreach ( $shipping_methods as $shipping_method ) {
				if ( 'bh_sales_promotions_free_shipping' === $shipping_method->id ) {
					array_push( $campaigns, 'free-shipping' );
				}
			}

			// To track rest of the campaigns
			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				if ( isset( $cart_item['bh_sales_promotions'] ) && isset( $cart_item['bh_sales_promotions']['campaigns'] ) ) {
					$campaign_type = $cart_item['bh_sales_promotions_discounts']['type'];
					array_push( $campaigns, $campaign_type );
					$campaign_total += $cart_item['bh_sales_promotions_discounts']['price_base'] - $cart_item['bh_sales_promotions_discounts']['price_adjusted'];
				}
			}
			if ( count( $campaigns ) > 0 ) {
				$data = array(
					'label_key'      => 'type',
					'type'           => array_unique( $campaigns ),
					'campaign_count' => count( $campaigns ),
					'campaign_total' => '$' . $campaign_total,
				);


				$this->push(
					'checkout_campaign_type',
					$data
				);
			}
		}
	}
}
