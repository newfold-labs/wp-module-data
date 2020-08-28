<?php

namespace Endurance\WP\Module\Data;

/**
 * Event data object
 */
class Event {

	/**
	 * Event category
	 *
	 * @var string
	 */
	public $category;

	/**
	 * Event action
	 *
	 * @var string
	 */
	public $action;

	/**
	 * Array of extra data related to the event
	 *
	 * @var array
	 */
	public $data;

	/**
	 * Array of data about the request that triggered the event
	 *
	 * @var array
	 */
	public $request;

	/**
	 * Array of data about the user triggering the event
	 *
	 * @var array
	 */
	public $user;

	/**
	 * Array of version data about the environment
	 *
	 * @var array
	 */
	public $environment;

	/**
	 * Construct
	 *
	 * @param string $category General category of the event. Should match to a Listener class
	 * @param string $action   The action that occurred
	 * @param array  $data     Additional data specific to the event that occurred
	 */
	public function __construct( $category = 'Admin', $action = '', $data = array() ) {
		global $title, $wpdb, $wp_version;

		// Event details
		$this->category = strtolower( $category );
		$this->action   = $action;
		$this->data     = $data;

		// Request information
		$this->request = array(
			'url'        => get_site_url( null, $_SERVER['REQUEST_URI'] ),
			'page_title' => $title,
			'user_agent' => ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) ? $_SERVER['HTTP_USER_AGENT'] : '',
		);

		// User information
		$user       = get_user_by( 'id', get_current_user_id() );
		$this->user = array(
			'id'     => get_current_user_id(),
			'login'  => $user->user_nicename,
			'role'   => $user->roles[0],
			'locale' => get_user_locale(),
		);

		// Environment Information
		$this->environment = array(
			'siteurl'        => get_site_url(),
			'php_version'    => phpversion(),
			'mysql_version'  => $wpdb->db_version(),
			'wp_version'     => $wp_version,
			'plugin_version' => BLUEHOST_PLUGIN_VERSION,
		);
	}

}
