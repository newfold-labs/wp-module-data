<?php

namespace NewfoldLabs\WP\Module\Data\API;

use NewfoldLabs\WP\Module\Data\HiiveConnection;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;

/**
 * REST API controller for getting users
 */
class Users extends WP_REST_Controller {

	/**
	 * Instance of HiiveConnection class
	 *
	 * @var HiiveConnection
	 */
	public $hiive;

	/**
	 * Constructor.
	 *
	 * @param HiiveConnection $hiive Instance of the hiive connection manager
	 * @since 4.7.0
	 */
	public function __construct( HiiveConnection $hiive ) {
		$this->hiive     = $hiive;
		$this->namespace = 'newfold-data/v1';
		$this->rest_base = 'users';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 4.7.0
	 *
	 * @see register_rest_route()
	 * @see HiiveConnection::rest_api_init()
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'verify_token' ),
				),
			)
		);
	}

	/**
	 * Returns a verification of the supplied connection token
	 *
	 * @since 1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		// Get all users
		$admin_users = get_users([
			'role' => 'administrator', 
		]);
		$users = [];

		// Add administrators to the response
		foreach ($admin_users as $user) {
			$users[] = [
				'id'       => $user->ID,
				'username' => $user->user_login,
				'email'    => $user->user_email,
				'name'     => $user->display_name,
				'roles'    => $user->roles,
				'super_admin' => is_super_admin($user->ID),
			];
		}		

		$response = new WP_REST_Response(
			$users
		);

		return $response;
	}

	/**
	 * Verifys the Hiive token
	 *
	 * @since 1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function verify_token( $request ) {

		$token = $request->get_param('token');
		$valid  = $this->hiive->verify_token( $token );

		if ( $valid ) {
			return true;
		}

		return false;

	}
}
