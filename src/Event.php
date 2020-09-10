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
	 * Key representing the event action that occurred
	 *
	 * @var string
	 */
	public $key;

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
	 * @param string $key      Key representing the action that occurred
	 * @param array  $data     Additional data specific to the event that occurred
	 */
	public function __construct( $category = 'Admin', $key = '', $data = array() ) {
		global $title, $wpdb, $wp_version;

		// Event details
		$this->category = strtolower( $category );
		$this->key      = $key;
		$this->data     = $data;

		// Try to grab user request IP and account for any proxies
		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// In case there are multiple proxies, just use the first one
			$ip_list = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
			$ip      = $ip_list[0];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		// Request information
		$this->request = array(
			'url'        => get_site_url( null, $_SERVER['REQUEST_URI'] ),
			'page_title' => $title,
			'user_agent' => ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) ? $_SERVER['HTTP_USER_AGENT'] : '',
			'ip'         => $ip,
		);

		// User information
		$user       = get_user_by( 'id', get_current_user_id() );
		$this->user = array(
			'id'     => get_current_user_id(),
			'login'  => ( ! empty( $user->user_nicename ) ) ? $user->user_nicename : '',
			'role'   => ( ! empty( $user->roles[0] ) ) ? $user->roles[0] : '',
			'locale' => get_user_locale(),
		);

		// Environment Information
		$this->environment = array(
			'url'    => get_site_url(),
			'php'    => phpversion(),
			'mysql'  => $wpdb->db_version(),
			'wp'     => $wp_version,
			'plugin' => BLUEHOST_PLUGIN_VERSION,
		);
	}

}
