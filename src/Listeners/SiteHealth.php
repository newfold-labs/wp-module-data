<?php

namespace Endurance\WP\Module\Data\Listeners;

use Endurance\WP\Module\Data\Helpers\SiteHealth as SiteHealthHelper;

/**
 * Monitors Site Health events
 */
class SiteHealth extends Listener {

	/**
	 * SiteHealth helper object.
	 *
	 * @var SiteHealthHelper
	 */
	private $siteHealthHelper;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$this->siteHealthHelper = new SiteHealthHelper();
	}

	/**
	 * Register the hooks for the subscriber
	 *
	 * @return void
	 */
	public function register_hooks() {

		add_action( 'set_transient_health-check-site-status-result', array( $this, 'tests_run' ) );
	}

	/**
	 * Site Health tests are run
	 *
	 * @param string $value A JSON string with the results of Site Health tests
	 *
	 * @return void
	 */
	public function tests_run( $value ) {
		$site_health_data = array();

		$site_health_data = array(
			'score' => $this->siteHealthHelper->calculate_score( $value ),
			'debug_data' => wp_json_encode( $this->siteHealthHelper->get_safe_data() ),
		);

		$this->push( 'site_health', $site_health_data );

		update_option( 'jons-test-2', $site_health_data );
	}
}
