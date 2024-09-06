<?php

namespace NewfoldLabs\WP\Module\Data\Listeners;

/**
 * Monitors EcommerceCheckout events
 */
class Ecommerce_Checkout extends Listener {

	/**
	 * Register the hooks for the listener
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'woocommerce_payment_complete', array( $this, 'Ecommerce_checkout_used' ), 10, 1 );
	}

	
	/**
	 * Track Ecommerce_Checkout used in checkout page
	 * Send data to hiive

	 * @param string $order_id complete order details.

	 * @return void
	 */
	public function Ecommerce_checkout_used( $order_id ) {

			$order        = wc_get_order( $order_id );
			$order_id     = $order->get_id();
			$order_status = $order->get_status();

			$data = array(
				'label_key' => 'order_id',
				'order_id'  => $order_id,
				'status'    => $order_status,
			);

      error_log('data to send',print_r($data));
      
			// $this->push(
			// 	'order_status_update',
			// 	$data
			// );
	}
}
