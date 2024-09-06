<?php

namespace NewfoldLabs\WP\Module\Data;

/**
 * Class SiteCapabilities
 *
 * Class that handles fetching, caching, and checking of site capabilities.
 *
 * @package NewfoldLabs\WP\Module\Data
 */
class SiteCapabilities {

	/**
	 * Get the value of a capability.
	 *
	 * @used-by \NewfoldLabs\WP\Module\AI\SiteGen\SiteGen::check_capabilities()
	 * @used-by \NewfoldLabs\WP\Module\AI\Utils\AISearchUtil::check_capabilities()
	 * @used-by \NewfoldLabs\WP\Module\AI\Utils\AISearchUtil::check_help_capability()
	 * @used-by \NewfoldLabs\WP\Module\ECommerce\ECommerce::__construct()
	 * @used-by \NewfoldLabs\WP\Module\HelpCenter\CapabilityController::get_capability()
	 * @used-by \NewfoldLabs\WP\Module\Onboarding\Data\Config::get_site_capability()
	 *
	 * @param string $capability Capability name.
	 *
	 * @return bool
	 */
	public function get( $capability ) {
		return $this->exists( $capability ) && $this->all()[ $capability ];
	}

	/**
	 * Get all capabilities.
	 *
	 * @return array
	 */
	protected function all() {
		$capabilities = get_transient( 'nfd_site_capabilities' );
		if ( false === $capabilities ) {
			$capabilities = $this->fetch();
			set_transient( 'nfd_site_capabilities', $capabilities, 4 * HOUR_IN_SECONDS );
		}

		return $capabilities;
	}

	/**
	 * Check if a capability exists.
	 *
	 * @param string $capability Capability name.
	 *
	 * @return bool
	 */
	protected function exists( $capability ) {
		return array_key_exists( $capability, $this->all() );
	}

	/**
	 * Fetch all capabilities from Hiive.
	 *
	 * @return array
	 */
	protected function fetch() {
		$capabilities = array();

		$response = wp_remote_get(
			NFD_HIIVE_URL . '/sites/v1/capabilities',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer ' . HiiveConnection::get_auth_token(),
				),
			)
		);

		if ( wp_remote_retrieve_response_code( $response ) === 200 && ! is_wp_error( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			if ( $data && is_array( $data ) ) {
				$capabilities = $data;
			}
		}

		return $capabilities;
	}

}
