<?php

namespace NewfoldLabs\WP\Module\Data\WonderBlocks\Requests;

/**
 * Base class for WonderBlock Requests.
 */
class Request {
	/**
	 * The base URL.
	 *
	 * @var string
	 */
	protected static $base_url = 'https://patterns.hiive.cloud';

	/**
	 * Get the base URL
	 *
	 * @return string
	 */
	public static function get_base_url() {
		return self::$base_url;
	}
}
