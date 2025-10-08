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
	 * Get the local base URL.
	 *
	 * @return string
	 */
	protected static function get_local_base_url() {
		return defined( 'NFD_WB_LOCAL_BASE_URL' ) ? NFD_WB_LOCAL_BASE_URL : self::$local_base_url;
	}

	/**
	 * Get the production base URL.
	 *
	 * @return string
	 */
	protected static function get_production_base_url() {
		return defined( 'NFD_WB_PRODUCTION_BASE_URL' ) ? NFD_WB_PRODUCTION_BASE_URL : self::$production_base_url;
	}

	/**
	 * Get the base URL
	 */
	public function get_base_url(): string {
		if ( defined( 'NFD_DATA_WB_DEV_MODE' ) && constant( 'NFD_DATA_WB_DEV_MODE' ) ) {
			return self::get_local_base_url();
		}

		return self::get_production_base_url();
	}

	/**
	 * Get the request endpoint.
	 */
	public function get_endpoint(): string {
		return $this->endpoint;
	}

	/**
	 * This function should return a MD5 hashed string of the request parameters that can uniquely identify it.
	 */
	abstract public function get_md5_hash(): string;
}
