<?php

namespace Endurance\WP\Module\Data\API;

use Endurance\WP\Module\Data\Event;
use Endurance\WP\Module\Data\EventManager;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * REST API controller for sending events to the hub.
 */
class Events extends WP_REST_Controller {

	/**
	 * Instance of the EventManager class.
	 *
	 * @var EventManager
	 */
	public $event_manager;

	/**
	 * Constructor.
	 *
	 * @param EventManager $event_manager Instance of the EventManager class.
	 */
	public function __construct( EventManager $event_manager ) {
		$this->event_manager = $event_manager;
		$this->namespace     = 'bluehost/v1/data';
		$this->rest_base     = 'events';
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
						'required'          => true,
						'description'       => __( 'Event action' ),
						'type'              => 'string',
						'sanitize_callback' => function ( $value ) {
							return sanitize_title( $value );
						},
					),
					'category' => array(
						'default'           => 'admin',
						'description'       => __( 'Event category' ),
						'type'              => 'string',
						'sanitize_callback' => function ( $value ) {
							return sanitize_title( $value );
						},
					),
					'data'     => array(
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
		$data     = ! empty( $request['data'] ) ? $request['data'] : array();

		$event = new Event( $category, $action, $data );

		$this->event_manager->push( $event );

		$response = rest_ensure_response(
			array(
				'category' => $category,
				'action'   => $action,
				'data'     => $data,
			)
		);
		$response->set_status( 202 );

		return $response;
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
	public function create_item_permissions_check( $request ) {
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
