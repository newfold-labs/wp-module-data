<?php

namespace NewfoldLabs\WP\Module\Data\API;

use NewfoldLabs\WP\Module\Data\HiiveConnection;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * REST API controller for retriving the Entitlements of a site from the hiive.
 */
class Entitlement extends WP_REST_Controller {
	/**
	 * Instance of the HiiveConnection class.
	 *
	 * @var HiiveConnection
	 */
	private $hiive;

	/**
	 * Hiive API endpoint for fetching entitlements.
	 */
	const HIIVE_API_ENTITLEMENT_ENDPOINT = 'sites/v1/entitlements';

	/**
	 * Entitlement constructor.
	 *
	 * @param HiiveConnection $hiive           Instance of the HiiveConnection class.
	 */
	public function __construct( HiiveConnection $hiive ) {
		$this->hiive     = $hiive;
		$this->namespace = 'newfold-data/v1';
		$this->rest_base = 'entitlements';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @see   register_rest_route()
	 */
	public function register_routes() {

		\register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'read_item_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Get entitlements of a site.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {

		$args = array(
			'method' => 'GET'
		);

		$hiive_response = $this->hiive->hiive_request( self::HIIVE_API_ENTITLEMENT_ENDPOINT, array(), $args );

		if ( is_wp_error( $hiive_response ) ) {
			return new \WP_REST_Response( $hiive_response->get_error_message(), 401 );
		}

		$status_code = wp_remote_retrieve_response_code( $hiive_response );

		if ( 200 !== $status_code ) {
			return new \WP_REST_Response( wp_remote_retrieve_response_message( $hiive_response ), $status_code );
		}

		$entitlements = json_decode( wp_remote_retrieve_body( $hiive_response ) );

		return new \WP_REST_Response( $entitlements, 201 );
	}

	/**
	 * User is required to be logged in.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return true|\WP_Error
	 *
	 * @since 1.0
	 */
	public function read_item_permissions_check( $request ) {
		if ( ! current_user_can( 'read' ) ) {
			return new \WP_Error(
				'rest_cannot_log_event',
				__( 'Sorry, you are not allowed to use this endpoint.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}
}
