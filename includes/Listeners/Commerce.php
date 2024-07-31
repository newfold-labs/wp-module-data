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
		add_filter( 'woocommerce_before_cart', array( $this, 'site_cart_views' ) );
		add_filter( 'woocommerce_before_checkout_form', array( $this, 'checkout_views' ) );
		add_filter( 'woocommerce_thankyou', array( $this, 'thank_you_page' ) );
		add_filter( 'pre_update_option_nfd-ecommerce-captive-flow-razorpay', array( $this, 'razorpay_connection' ), 10, 2 );
		add_filter( 'pre_update_option_nfd-ecommerce-captive-flow-shippo', array( $this, 'shippo_connection' ), 10, 2 );
		add_filter( 'pre_update_option_nfd-ecommerce-captive-flow-stripe', array( $this, 'stripe_connection' ), 10, 2 );
		// Paypal Connection
		add_filter( 'pre_update_option_yith_ppwc_merchant_data_production', array( $this, 'paypal_connection' ), 10, 2 );
		add_filter( 'update_option_ewc4wp_sso_account_status', array( $this, 'ecomdash_connected' ), 10, 2 );
		add_filter( 'woocommerce_update_product', array( $this, 'product_created_or_updated' ), 100, 2 );
		add_action( 'update_option_woocommerce_custom_orders_table_enabled', array( $this, 'woocommerce_hpos_enabled' ), 10, 3 );
		//Store page events			
		add_action('current_screen', array( $this, 'ecommerce_exclusive_tools_settings_click_tracking' ), 10);
	}

	/**
	 * Store page events
	 *
	 * @param  string $data  Array of data to be sent to Hiive
	 *
	 * 
	 */

	 public function ecommerce_exclusive_tools_settings_click_tracking($data)
	 {
 				
		$screen = get_current_screen();
    	if ($screen) {
			$screen_id = $screen->id;
		}

		$url  = is_ssl() ? 'https://' : 'http://';
		$url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$is_yith_plugin_settings_page = true;

		switch ($screen_id) {
			//Gift Cards
			case 'edit-gift_card':
				$data = array(
					'label_key' => 'manage_nfd_slug_yith_woocommerce_gift_card_clicked',
					'provider'  => 'yith_giftcards',
					'page'      => $url,
				);
				break;
			//Wishlists
			case 'yith-plugins_page_yith_wcwl_panel':
				$data = array(
					'label_key' => 'manage_nfd_slug_yith_woocommerce_wishlist_clicked',
					'provider'  => 'yith_wishlists',
					'page'      => $url,
				);
				break;
			//Booking and Appointments
			case 'edit-yith_booking':
				$data = array(
					'label_key' => 'manage_nfd_slug_yith_woocommerce_booking_clicked',
					'provider'  => 'yith_bookings_and_appointments',
					'page'      => $url,
				);
				break;
			//Product Filters
			case 'yith-plugins_page_yith_wcan_panel':
				$data = array(
					'label_key' => 'manage_nfd_slug_yith_woocommerce_ajax_product_filter_clicked',
					'provider'  => 'yith_product_filters',
					'page'      => $url,
				);
				break;
			//Product Search
			case 'yith-plugins_page_yith_wcas_panel':
				$data = array(
					'label_key' => 'manage_yith-woocommerce-ajax-search_clicked',
					'provider'  => 'yith_product_search',
					'page'      => $url,
				);
				break;
			//Customize My Account Page
			case 'yith-plugins_page_yith_wcmap_panel':
				$data = array(
					'label_key' => 'manage_nfd_slug_yith_woocommerce_customize_myaccount_page_clicked',
					'provider'  => 'yith_customize_my_account_page',
					'page'      => $url,
				);
				break;
			//EcomDash
			case 'toplevel_page_newfold-ecomdash':
				$data = array(
					'label_key' => 'manage_nfd_slug_ecomdash_wordpress_plugin_clicked',
					'provider'  => 'newfold_ecomdash_wordpress_plugin',
					'page'      => $url,
				);
				break;
			//View all analytics	
			case 'woocommerce_page_wc-admin':
				if(strpos($_SERVER['REQUEST_URI'], 'analytics')){
					$data = array(
						'label_key' => 'view_all_analytics_clicked',
						'provider'  => 'woocommerce',
						'page'      => $url,
					);					
				}
				break;			
			default:
				$is_yith_plugin_settings_page = false;
		}
		
		if($is_yith_plugin_settings_page){
			$this->push(
				'ecommerce_exclusive_tools_settings_clicked',
				$data
			);
		}
		
	 }

	/**
	 * On Payment, send data to Hiive
	 *
	 * @param  int       $order_id  the order id
	 * @param  \WC_Order $order  the order
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
	 * @param  string $data  Array of data to be sent to Hiive
	 *
	 * @return string Array of data
	 */
	public function products_count( $data ) {
		if ( ! isset( $data['meta'] ) ) {
			$data['meta'] = array();
		}
		$product_post_counts = wp_count_posts( 'product' );
		if ( $product_post_counts && isset( $product_post_counts->publish ) ) {
			$data['meta']['products_count'] = (int) $product_post_counts->publish;
		}

		return $data;
	}

	/**
	 * Orders Count
	 *
	 * @param  string $data  Array of data to be sent to Hiive
	 *
	 * @return string Array of data
	 */
	public function orders_count( $data ) {
		if ( ! isset( $data['meta'] ) ) {
			$data['meta'] = array();
		}
		$shop_order_post_counts = wp_count_posts( 'shop_order' );
		if ( $shop_order_post_counts && isset( $shop_order_post_counts->publish ) ) {
			$data['meta']['orders_count'] = (int) $shop_order_post_counts->publish;
		}

		return $data;
	}

	/**
	 * Site Cart View, send data to Hiive
	 *
	 * @return void
	 */
	public function site_cart_views() {
		if ( WC()->cart->get_cart_contents_count() !== 0 ) {
			$data = array(
				'product_count' => WC()->cart->get_cart_contents_count(),
				'cart_total'    => floatval( WC()->cart->get_cart_contents_total() ),
				'currency'      => get_woocommerce_currency(),
			);

			$this->push(
				'site_cart_view',
				$data
			);
		}
	}


	/**
	 * Checkout view, send data to Hiive
	 *
	 * @return void
	 */
	public function checkout_views() {
		$data = array(
			'product_count'  => WC()->cart->get_cart_contents_count(),
			'cart_total'     => floatval( WC()->cart->get_cart_contents_total() ),
			'currency'       => get_woocommerce_currency(),
			'payment_method' => array_keys( WC()->payment_gateways()->get_available_payment_gateways() ),
		);

		$this->push(
			'site_checkout_view',
			$data
		);
	}

	/**
	 * Thank you page, send data to Hiive
	 *
	 * @param  int $order_id  the order id
	 *
	 * @return void
	 */
	public function thank_you_page( $order_id ) {
		$order      = wc_get_order( $order_id );
		$line_items = $order->get_items();

		// This loops over line items
		foreach ( $line_items as $item ) {
			$qty = $item['qty'];
		}
		$data = array(
			'product_count' => $qty,
			'order_total'   => floatval( $order->get_total() ),
			'currency'      => get_woocommerce_currency(),
		);

		$this->push(
			'site_thank_you_view',
			$data
		);
	}

	/**
	 * Razorpay connected
	 *
	 * @param string $new_option  New value of the razorpay_data_production option
	 * @param string $old_option  Old value of the razorpay_data_production option
	 *
	 * @return string The new option value
	 */
	public function razorpay_connection( $new_option, $old_option ) {
		$url  = is_ssl() ? 'https://' : 'http://';
		$url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$data = array(
			'label_key' => 'provider',
			'provider'  => 'razorpay',
			'page'      => $url,
		);
		if ( $new_option !== $old_option && ! empty( $new_option ) ) {
			$this->push(
				'payment_connected',
				$data
			);
		}

		return $new_option;
	}

	/**
	 * Shippo connected
	 *
	 * @param string $new_option  New value of the shippo_data option
	 * @param string $old_option  Old value of the shippo_data option
	 *
	 * @return string The new option value
	 */
	public function shippo_connection( $new_option, $old_option ) {
		$url  = is_ssl() ? 'https://' : 'http://';
		$url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$data = array(
			'label_key' => 'provider',
			'provider'  => 'yith_shippo',
			'page'      => $url,
		);
		if ( $new_option !== $old_option && ! empty( $new_option ) ) {
			$this->push(
				'shipping_connected',
				$data
			);
		}

		return $new_option;
	}

	/**
	 * Stripe connected
	 *
	 * @param string $new_option  New value of the stripe_data_production option
	 * @param string $old_option  Old value of the stripe_data_production option
	 *
	 * @return string The new option value
	 */
	public function stripe_connection( $new_option, $old_option ) {
		$url  = is_ssl() ? 'https://' : 'http://';
		$url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$data = array(
			'label_key' => 'provider',
			'provider'  => 'yith_stripe',
			'page'      => $url,
		);
		if ( $new_option !== $old_option && ! empty( $new_option ) ) {
			$this->push(
				'payment_connected',
				$data
			);
		}

		return $new_option;
	}

	/**
	 * PayPal connected
	 *
	 * @param string $new_option  New value of the yith_ppwc_merchant_data_production option
	 * @param string $old_option  Old value of the yith_ppwc_merchant_data_production option
	 *
	 * @return string The new option value
	 */
	public function paypal_connection( $new_option, $old_option ) {
		$url  = is_ssl() ? 'https://' : 'http://';
		$url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$data = array(
			'label_key' => 'provider',
			'provider'  => 'yith_paypal',
			'page'      => $url,
		);
		if ( $new_option !== $old_option && ! empty( $new_option ) ) {
			$this->push(
				'payment_connected',
				$data
			);
		}

		return $new_option;
	}

	/**
	 * Ecomdash connection, send data to Hiive
	 *
	 * @param string $new_option  New value of the update_option_ewc4wp_sso_account_status option
	 * @param string $old_option  Old value of the update_option_ewc4wp_sso_account_status option
	 *
	 * @return string The new option value
	 */
	public function ecomdash_connected( $new_option, $old_option ) {
		if ( $new_option !== $old_option && ! empty( $new_option ) && 'connected' === $new_option ) {
			$url  = is_ssl() ? 'https://' : 'http://';
			$url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$data = array(
				'url' => $url,
			);
			$this->push(
				'ecomdash_connected',
				$data
			);
		}
		return $new_option;
	}

	/**
	 * Product added, send data to Hiive
	 *
	 * @param string  $product_id  id of post which is being savedPost ObjectOld value of the yith_ppwc_merchant_data_production option
	 * @param WP_POST $product  details of the product
	 * @return void
	 */
	public function product_created_or_updated( $product_id, $product ) {
		$data = array(
			'label_key'    => 'product_type',
			'product_type' => $product->product_type,
			'post_id'      => $product_id,
		);

			$this->push(
				'product_created',
				$data
			);
	}

	/**
	 * HPOS (High Performance Order Storage) is enabled
	 * Send data to Hiive containing "hpos" or "legacy", and the page URL.
	 *
	 * @hooked update_option_woocommerce_custom_orders_table_enabled
	 *
	 * @param mixed|string $old_value  Old value of woocommerce_custom_orders_table_enabled.
	 * @param mixed|string $new_value  New value of woocommerce_custom_orders_table_enabled, 'yes'|'no'.
	 * @param string       $option  Name of the option being updated, always 'woocommerce_custom_orders_table_enabled'.
	 */
	public function woocommerce_hpos_enabled( $old_value, $new_value, string $option ): void {
		if ( $new_value !== $old_value && ! empty( $new_value ) ) {
			$url  = is_ssl() ? 'https://' : 'http://';
			$url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$type = ( 'yes' === $new_value ) ? 'hpos' : 'legacy';

			$data = array(
				'label_key' => 'type',
				'type'      => $type,
				'page'      => $url,
			);

			$this->push(
				'changed_woo_order_storage_type',
				$data
			);
		}
	}
}
