<?php

namespace NewfoldLabs\WP\Module\Data\WonderBlocks\Requests;

/**
 * Base class for WonderBlock Requests.
 */
abstract class Request {
	/**
	 * The production base URL.
	 *
	 * @var string
	 */
	protected static $production_base_url = 'https://patterns.hiive.cloud';

	/**
	 * The local base URL.
	 *
	 * @var string
	 */
	protected static $local_base_url = 'http://localhost:8888';

	/**
	 * The endpoint to request.
	 *
	 * @var string
	 */
	protected $endpoint;

	/**
	 * Get the base URL
	 *
	 * @return string
	 */
	public static function get_base_url() {
		if ( defined( 'NFD_DATA_WB_DEV_MODE' ) && NFD_DATA_WB_DEV_MODE ) {
			return self::$local_base_url;
		}

		return self::$production_base_url;
	}

	/**
	 * Get the request endpoint.
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return $this->endpoint;
	}

	/**
	 * This function should return a MD5 hashed string of the request parameters that can uniquely identify it.
	 *
	 * @return void
	 */
	abstract public function get_md5_hash();
}
