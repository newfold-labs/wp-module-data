<?php

namespace Endurance\WP\Module\Data;

/**
 * Manages a Hub connection instance and interactions with it
 */
class HubConnection implements SubscriberInterface {

	/**
	 * Hub API url
	 *
	 * @var string
	 */
	private $api;

	/**
	 * Authentication token for data api
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Construct
	 */
	public function __construct() {

		if ( ! defined( 'BH_HUB_URL' ) ) {
			define( 'BH_HUB_URL', 'https://hiive.cloud/api' );
		}

		$this->api = BH_HUB_URL;

		$this->token = $this->get_auth_token();
	}

	/**
	 * Check whether site has established connection to hub
	 *
	 * @return boolean
	 */
	public function is_connected() {
		return (bool) ( $this->get_auth_token() );
	}

	/**
	 * Attempt to connect to hub
	 *
	 * @return void
	 */
	public function connect() {

		if ( ! get_transient( 'bh_data_connection_throttle' ) ) {

			set_transient( 'bh_data_connection_throttle', true, 60 * MINUTE_IN_SECONDS );

			$token = md5( wp_generate_password() );
			set_transient( 'bh_data_verify_token', $token, 5 * MINUTE_IN_SECONDS );

			$data                 = $this->get_core_data();
			$data['verify_token'] = $token;

			$args = array(
				'body'     => wp_json_encode( $data ),
				'headers'  => array(
					'Content-Type' => 'applicaton/json',
					'Accept'       => 'applicaton/json',
				),
				'blocking' => true,
				'timeout'  => 30,
			);

			$response = wp_remote_post( $this->api . '/connect', $args );
			$status   = wp_remote_retrieve_response_code( $response );

			// Created = 201; Updated = 200
			if ( 201 === $status || 200 === $status ) {
				$body = json_decode( wp_remote_retrieve_body( $response ) );
				if ( ! empty( $body->token ) ) {
					$encryption      = new Encryption();
					$encrypted_token = $encryption->encrypt( $body->token );
					update_option( 'bh_data_token', $encrypted_token );
				}
			}
		}
	}

	/**
	 * Post event data payload to the hub
	 *
	 * @param Event $event Event object representing the action that occurred
	 *
	 * @return void
	 */
	public function notify( Event $event ) {

		// If for some reason we are not connected, bail out now.
		if ( ! $this->is_connected() ) {
			return;
		}

		$args = array(
			'body'     => wp_json_encode( $event ),
			'headers'  => array(
				'Content-Type'  => 'applicaton/json',
				'Accept'        => 'applicaton/json',
				'Authorization' => "Bearer $this->token",
			),
			'blocking' => false,
			'timeout'  => .5,
		);

		wp_remote_post( $this->api . '/event', $args );
	}

	/**
	 * Try to return the auth token
	 *
	 * @return string|null The decrypted token if it's set
	 */
	public function get_auth_token() {
		if ( empty( $this->token ) ) {
			$encrypted_token = get_option( 'bh_data_token' );
			if ( false !== $encrypted_token ) {
				$encryption  = new Encryption();
				$this->token = $encryption->decrypt( $encrypted_token );
			}
		}

		return $this->token;
	}

	/**
	 * Get core site data for initial connection
	 *
	 * @return array
	 */
	public function get_core_data() {
		global $wpdb, $wp_version;

		return array(
			'url'    => get_site_url(),
			'php'    => phpversion(),
			'mysql'  => $wpdb->db_version(),
			'wp'     => $wp_version,
			'plugin' => BLUEHOST_PLUGIN_VERSION,
		);
	}
}
