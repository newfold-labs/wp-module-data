<?php

namespace NewfoldLabs\WP\Module\Data;

use NewfoldLabs\WP\Module\Data\HiiveWorker;

/**
 * Class SiteClassification
 *
 * Class that handles fetching and caching of site classification data.
 *
 * @package NewfoldLabs\WP\Module\Data
 */
class SiteClassification {

	/**
	 * Get site classification data.
	 *
	 * @return array
	 */
	public function get() {
		// Checks the transient for cached data.
		$classification = get_transient( 'nfd_site_classification' );
		if ( false !== $classification ) {
			return $classification;
		}

		// Fetch data from the worker.
		$classification = $this->fetch_from_worker();

		// Cache the data if it is not empty.
		if ( ! empty( $classification ) ) {
			set_transient( 'nfd_site_classification', $classification, DAY_IN_SECONDS );
		}

		return $classification;
	}

	/**
	 * Fetch site classification data from the worker.
	 *
	 * @return array
	 */
	public function fetch_from_worker() {
		$worker   = new HiiveWorker( 'site-classification' );
		$response = $worker->request(
			'GET',
			array(
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== \wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$body = \wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! $data || ! is_array( $data ) ) {
			return array();
		}

		return $data;
	}

}
