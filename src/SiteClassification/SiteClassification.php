<?php

namespace NewfoldLabs\WP\Module\Data\SiteClassification;

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
	 * Name of the site classification data transient.
	 *
	 * @var string
	 */
	public static $transient_name = 'nfd_data_site_classification';

	/**
	 * Retrieves the site classification data.
	 *
	 * @return array
	 */
	public static function get() {
		// Checks the transient for data.
		$classification = get_transient( self::$transient_name );
		if ( false !== $classification ) {
			return $classification;
		}

		// Fetch data from the worker.
		$classification = self::fetch_from_worker();
		if ( ! empty( $classification ) ) {
			set_transient( self::$transient_name, $classification, DAY_IN_SECONDS );
			return $classification;
		}

		// Fetch data from the static JSON file.
		$classification = self::fetch_from_static_file();
		if ( ! empty( $classification ) ) {
			$classification['static'] = true;
			set_transient( self::$transient_name, $classification, HOUR_IN_SECONDS );
			return $classification;
		}

		// Cache an empty array for an hour if no data could be retrieved.
		set_transient( self::$transient_name, array(), HOUR_IN_SECONDS );
		return array();
	}

	/**
	 * Fetch site classification data from the CF worker.
	 *
	 * @return array
	 */
	public static function fetch_from_worker() {
		$worker   = new HiiveWorker( 'site-classification' );
		$response = $worker->request(
			'GET',
			array(
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! $data || ! is_array( $data ) ) {
			return array();
		}

		return $data;
	}

	/**
	 * Fetch site classification data from a static JSON file.
	 *
	 * @return array
	 */
	public static function fetch_from_static_file() {
		$filename = realpath( __DIR__ . '/../Data/Static/site-classification.json' );

		if ( ! file_exists( $filename ) ) {
			return array();
		}

		$data = json_decode( file_get_contents( $filename ), true );
		if ( ! $data ) {
			return array();
		}

		return $data;

	}
}
