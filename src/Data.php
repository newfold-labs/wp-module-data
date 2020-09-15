<?php

namespace Endurance\WP\Module\Data;

/**
 * Main class for the data plugin module
 */
class Data {

	/**
	 * Hub Connection instance
	 *
	 * @var HubConnection
	 */
	public $hub;

	/**
	 * Start up the plugin module
	 *
	 * Do this separately so it isn't tied to class creation
	 *
	 * @return void
	 */
	public function start() {

		// Delays our primary module setup until init
		add_action( 'init', array( $this, 'init' ) );

	}

	/**
	 * Initialize all other module functionality
	 *
	 * @return void
	 */
	public function init() {

		$this->hub = new HubConnection();

		// If not connected, attempt to connect and
		// bail before registering the subscribers/listeners
		if ( ! $this->hub->is_connected() ) {

			// Initialize the required verification endpoints
			$this->hub->register_verification_hooks();

			// Attempt to connect
			if ( ! $this->hub->is_throttled() ) {
				$this->hub->connect();
			}

			return;
		}

		$manager = new EventManager();
		$manager->initialize_listeners();

		$manager->add_subscriber( $this->hub );

		if ( defined( 'BH_DATA_DEBUG' ) && BH_DATA_DEBUG ) {
			$this->logger = new Logger();
			$manager->add_subscriber( $this->logger );
		}

	}

}
