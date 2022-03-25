<?php

namespace NewfoldLabs\WP\Module\Data;

/**
 * Main class for the data plugin module
 */
class Data {

	/**
	 * Hiive Connection instance
	 *
	 * @var HiiveConnection
	 */
	public $hiive;

	/**
	 * Last instantiated instance of this class.
	 *
	 * @var Data
	 */
	public static $instance;

	/**
	 * Data constructor.
	 */
	public function __construct() {
		self::$instance = $this;
	}

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

		$this->hiive = new HiiveConnection();

		$manager = new EventManager();
		$manager->initialize_rest_endpoint();

		// If not connected, attempt to connect and
		// bail before registering the subscribers/listeners
		if ( ! $this->hiive::is_connected() ) {

			// Initialize the required verification endpoints
			$this->hiive->register_verification_hooks();

			// Attempt to connect
			if ( ! $this->hiive->is_throttled() ) {
				$this->hiive->connect();
			}

			return;
		}

		$manager->init();

		$manager->add_subscriber( $this->hiive );

		if ( defined( 'NFD_DATA_DEBUG' ) && NFD_DATA_DEBUG ) {
			$this->logger = new Logger();
			$manager->add_subscriber( $this->logger );
		}

	}

}
