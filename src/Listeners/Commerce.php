<?php

namespace NewfoldLabs\WP\Module\Data\Listeners;

/**
 * Monitors Yith events
 */
class Commerce extends Listener {

	/**
	 * Register the hooks for the listener
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'woocommerce_order_status_processing', array( $this, 'on_payment' ), 10, 2 );
		add_filter( 'newfold_wp_data_module_cron_data_filter', array( $this, 'products_count' ) );
		add_filter( 'newfold_wp_data_module_cron_data_filter', array( $this, 'orders_count' ) );
	}

	/**
	 * On Payment, send data to Hiive
	 *
	 * @param  int  $order_id
	 * @param  \WC_Order  $order
	 *
	 * @return void
	 */
	public function on_payment( $order_id, \WC_Order $order ) {

		$data = array(
			'order_currency'       => $order->get_currency(),
			'order_total'          => $order->get_total(),
			'payment_method'       => $order->get_payment_method(),
			'payment_method_title' => $order->get_payment_method_title(),
		);

		$this->push( 'woocommerce_order_status_processing', $data );

	}

	/**
	 * Products Count
	 *
	 * @param  string  $data  Array of data to be sent to Hiive
	 *
	 * @return string Array of data
	 */
	public function products_count( $data ) {
		if ( ! isset( $data['meta'] ) ) {
			$data['meta'] = array();
		}
		$data['meta']['products_count'] = (int) wp_count_posts( 'product' )->publish;

		return $data;
	}

	/**
	 * Orders Count
	 *
	 * @param  string  $data  Array of data to be sent to Hiive
	 *
	 * @return string Array of data
	 */
	public function orders_count( $data ) {
		if ( ! isset( $data['meta'] ) ) {
			$data['meta'] = array();
		}
		$data['meta']['orders_count'] = (int) wp_count_posts( 'shop_order' )->publish;

		return $data;
	}
}
