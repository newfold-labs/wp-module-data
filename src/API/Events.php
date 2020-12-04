<?php

namespace Endurance\WP\Module\Data\API;

use Endurance\WP\Module\Data\Event;
use Endurance\WP\Module\Data\HubConnection;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * REST API controller for sending events to the hub.
 */
class Events extends WP_REST_Controller {

	/**
	 * Instance of HubConnection class
	 *
	 * @var HubConnection
	 */
	public $hub;

	/**
	 * Constructor.
	 *
	 * @param HubConnection $hub Instance of the hub connection manager
	 */
	public function __construct( HubConnection $hub ) {
		$this->hub       = $hub;
		$this->namespace = 'bluehost/v1/data';
		$this->rest_base = 'events';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @see   register_rest_route()
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/',
			array(
				'args' => array(
					'action'   => array(
						'required'    => true,
						'description' => __( 'Event action' ),
						'type'        => 'string',
					),
					'category' => array(
						'default'     => 'Admin',
						'description' => __( 'Event category' ),
						'type'        => 'string',
					),
					'data'     => array(
						'required'    => true,
						'description' => __( 'Event data' ),
						'type'        => 'object',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
			)
		);

	}

	/**
	 * Dispatches a new event.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {

		$category = $request->get_param( 'category' );
		$action   = $request->get_param( 'action' );
		$data     = $request->get_param( 'data' );

		$event = new Event( $category, $action, $data );

		$this->hub->notify( [ $event ] );
		$response = rest_ensure_response(
			[
				'category' => $category,
				'action'   => $action,
				'data'     => $data,
			]
		);
		$response->set_status( 202 );

		return $response;
	}

	/**
	 * No authentication required for this endpoint
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return true|\WP_Error
	 * @since 1.0
	 *
	 */
	public function create_item_permissions_check( $request ) {
		return true;
	}
}
