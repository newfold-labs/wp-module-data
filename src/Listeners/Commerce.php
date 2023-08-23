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
		add_filter('woocommerce_before_cart', array( $this, 'site_cart_views'));
		add_filter('woocommerce_before_checkout_form', array( $this, 'checkout_views'));
		add_filter('woocommerce_thankyou', array( $this, 'thank_you_page'));
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

	/**
	 * Site Cart View, send data to Hiive
	 *
	 * @return void
	 */
	public function site_cart_views() { 
		$data = array(
			"action" 	=> "site_cart_view", 
			"category" 	=> "commerce", 
			"data" 		=> array( 
				"product_count" => WC()->cart->get_cart_contents_count(),
				"cart_total" 	=> floatval(WC()->cart->get_cart_contents_total()),
				"currency" 		=> get_woocommerce_currency(),
			), 
		);
		echo '
		<script type="text/javascript">
			var carnr;        
			carnr = '.json_encode($data).'
			console.log({carnr});
		</script>';
		$this->push(
			$data
		);
	} 

	
	/**
	 * Checkout view, send data to Hiive
	 *
	 * @return void
	 */
	public function checkout_views() { 
		$data = array(
			"action" 	=> "site_checkout_view", 
			"category" 	=> "commerce", 
			"data" 		=> array( 
				"product_count" 	=> WC()->cart->get_cart_contents_count(),
				"cart_total" 		=> floatval(WC()->cart->get_cart_contents_total()),
				"currency" 			=> get_woocommerce_currency(),
				"payment_method" 	=> WC()->payment_gateways()->get_available_payment_gateways()
			), 
		);
		echo '
		<script type="text/javascript">
			var carnr;        
			carnr = '.json_encode($data).'
			console.log({carnr});
		</script>';
		$this->push(
			$data
		);
	}

	/**
	 * Thank you page, send data to Hiive
	 *
	 * @param  int  $order_id
	 * 
	 * @return void
	 */
	public function thank_you_page($order_id ) { 
		$order = wc_get_order( $order_id );
		$line_items = $order->get_items();

		// This loops over line items
		foreach ( $line_items as $item ) {
			$qty = $item['qty'];
		}
		$data = array(
			"action" 	=> "site_thank_you_view", 
			"category" 	=> "commerce", 
			"data" 		=> array( 
				"product_count" => $qty,
				"order_total" 	=> floatval($order->get_total()),
				"currency" 		=> get_woocommerce_currency(),
			), 
		);
		echo '
		<script type="text/javascript">
			var carnr;        
			carnr = '.json_encode($data).'
			console.log({carnr});
		</script>';
		$this->push(
			$data
		);
	}
}
