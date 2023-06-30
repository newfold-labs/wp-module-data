<?php

namespace NewfoldLabs\WP\Module\Data\WonderBlocks\Requests;

/**
 * Class Fetch
 *
 * Defines the structure of a WonderBlock fetch request.
 */
class Fetch extends Request {
	/**
	 * The endpoint to fetch the data from.
	 *
	 * @var string
	 */
	private $endpoint;

	/**
	 * The type of data to fetch.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * A particular slug of data to fetch.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * The primary type query parameter. See SiteClassification/PrimaryType for information on primary types.
	 *
	 * @var string
	 */
	private $primary_type;

	/**
	 * The secondary type query parameter. See SiteClassification/SecondaryType for information on secondary types.
	 *
	 * @var string
	 */
	private $secondary_type;

	/**
	 * The category of data to fetch.
	 *
	 * @var string
	 */
	private $category;

	/**
	 * Defines whether or not the handler should cache the response data.
	 *
	 * @var boolean
	 */
	private $should_cache = true;

	/**
	 * Defines the timeout for the cache.
	 *
	 * @var integer
	 */
	private $cache_timeout = DAY_IN_SECONDS;

	/**
	 * Constructor for the Fetch class.
	 *
	 * @param array $args An array of arguments that map to the class variables.
	 */
	public function __construct( $args = array() ) {
		foreach ( $args as $arg => $value ) {
			$this->$arg = $value;
		}
	}

	/**
	 * Fetch the correct API URL.
	 *
	 * @return string
	 */
	public function get_url() {
		$url = '';
		if ( isset( $this->endpoint ) ) {
			$url = self::$base_url . "/{$this->endpoint}";
			if ( isset( $this->slug ) ) {
				$url .= "/{$this->slug}";
			}
		}

		return $url;
	}

	/**
	 * Fetch all the valid arguments.
	 *
	 * @return array
	 */
	public function get_args() {
		return array(
			'type'           => $this->type,
			'primary_type'   => $this->primary_type,
			'secondary_type' => $this->secondary_type,
			'category'       => $this->category,
		);
	}

	/**
	 * Determines whether the response for this request should be cached.
	 *
	 * @return boolean
	 */
	public function should_cache() {
		return $this->should_cache;
	}

	/**
	 * Get the cache timeout.
	 *
	 * @return integer
	 */
	public function get_cache_timeout() {
		return $this->cache_timeout;
	}

}
