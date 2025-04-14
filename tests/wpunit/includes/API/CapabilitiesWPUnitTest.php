<?php
/**
 * This is currently just intended for pushing a list of capabilities from Hiive, but it is written agnostic
 * of read/write.
 */

namespace NewfoldLabs\WP\Module\Data\API;

use NewfoldLabs\WP\Module\Data\EventManager;
use NewfoldLabs\WP\Module\Data\HiiveConnection;
use NewfoldLabs\WP\Module\Data\SiteCapabilities;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\API\Capabilities
 */
class CapabilitiesWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Sending a POST request should overwrite the existing capabilities and return 201.
	 *
	 * @covers ::update
	 */
	public function test_post_capabilities(): void {

		$request_capabilities = array(
			'hasAISiteGen' => true,
		);

		$site_capabilities = \Mockery::mock( SiteCapabilities::class );

		$site_capabilities->shouldReceive( 'all' )
			->once()
			->andReturnValues(
				array(
					array(
						'hasAISiteGen'        => false,
						'canAccessHelpCenter' => true,
					),
					array(
						'hasAISiteGen' => false,
					),
				)
			);

		$site_capabilities->shouldReceive( 'set' )->once()->with( $request_capabilities )->andReturn( true );

		$sut = new Capabilities( $site_capabilities );

		$request = new \WP_REST_Request( 'POST', 'newfold-data/v1/capabilities' );
		$request->set_body_params( $request_capabilities );

		$response      = $sut->update( $request );
		$response_data = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );

		$this->assertArrayHasKey( 'hasAISiteGen', $response_data['updated'] );
		$this->assertArrayHasKey( 'canAccessHelpCenter', $response_data['removed'] );
	}

	/**
	 * Sending a PATCH request should update the existing capabilities and return 201.
	 *
	 * @covers ::update
	 */
	public function test_patch_capabilities(): void {

		$request_capabilities = array(
			'hasAISiteGen' => true,
		);

		$site_capabilities = \Mockery::mock( SiteCapabilities::class );

		$site_capabilities->shouldReceive( 'all' )
			->once()
			->andReturn(
				array(
					'hasAISiteGen'        => false,
					'canAccessHelpCenter' => true,
				)
			);

		$site_capabilities->shouldReceive( 'update' )->once()->with( $request_capabilities )->andReturn( true );

		$sut = new Capabilities( $site_capabilities );

		$request = new \WP_REST_Request( 'PATCH', 'newfold-data/v1/capabilities' );
		$request->set_body_params( $request_capabilities );

		$response      = $sut->update( $request );
		$response_data = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );

		$this->assertArrayHasKey( 'hasAISiteGen', $response_data['updated'] );
		$this->assertArrayNotHasKey( 'canAccessHelpCenter', $response_data['removed'] );
	}

	/**
	 * Sending a PATCH request with no changes should return 200.
	 *
	 * @covers ::update
	 */
	public function test_patch_no_changes_to_capabilities(): void {

		$request_capabilities = array(
			'hasAISiteGen' => false,
		);

		$site_capabilities = \Mockery::mock( SiteCapabilities::class );

		$site_capabilities->shouldReceive( 'all' )
			->once()
			->andReturn(
				array(
					'hasAISiteGen'        => false,
					'canAccessHelpCenter' => true,
				)
			);

		$site_capabilities->shouldReceive( 'update' )->once()->with( $request_capabilities )->andReturn( true );

		$sut = new Capabilities( $site_capabilities );

		$request = new \WP_REST_Request( 'PATCH', 'newfold-data/v1/capabilities' );
		$request->set_body_params( $request_capabilities );

		$response      = $sut->update( $request );
		$response_data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEmpty( $response_data['added'] );
		$this->assertEmpty( $response_data['updated'] );
		$this->assertEmpty( $response_data['removed'] );
	}

	/**
	 * Sending a PATCH request with a new capability should include it in the response 'added' array.
	 *
	 * @covers ::update
	 */
	public function test_patch_new_item_in_capabilities(): void {

		$request_capabilities = array(
			'hasEcomdash' => true,
		);

		$site_capabilities = \Mockery::mock( SiteCapabilities::class );

		$site_capabilities->shouldReceive( 'all' )
							->once()
							->andReturnValues(
								array(
									array(
										'hasAISiteGen' => false,
										'canAccessHelpCenter' => true,
									),
									array(
										'hasAISiteGen' => false,
										'canAccessHelpCenter' => true,
										'hasEcomdash'  => true,
									),
								)
							);

		$site_capabilities->shouldReceive( 'update' )->once()->with( $request_capabilities )->andReturn( true );

		$sut = new Capabilities( $site_capabilities );

		$request = new \WP_REST_Request( 'PATCH', 'newfold-data/v1/capabilities' );
		$request->set_body_params( $request_capabilities );

		$response      = $sut->update( $request );
		$response_data = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );

		$this->assertArrayHasKey( 'hasEcomdash', $response_data['added'] );
	}

	/**
	 * If {@see SiteCapabilities::update()} fails, we should return a 500 error.
	 *
	 * @covers ::update
	 */
	public function test_error_in_wp_saving_changes(): void {

		$request_capabilities = array(
			'hasEcomdash' => true,
		);

		$site_capabilities = \Mockery::mock( SiteCapabilities::class );

		$site_capabilities->shouldReceive( 'all' )
							->once()
							->andReturnValues(
								array(
									array(
										'hasAISiteGen' => false,
										'canAccessHelpCenter' => true,
									),
								)
							);

		$site_capabilities->shouldReceive( 'update' )
							->once()
							->andReturnFalse();

		$sut = new Capabilities( $site_capabilities );
		do_action( 'rest_api_init' );
		$sut->register_routes();

		wp_set_current_user( 1 );

		$request = new \WP_REST_Request( 'PATCH', '/newfold-data/v1/capabilities' );
		$request->set_body_params( $request_capabilities );

		$rest_server = rest_get_server();

		$response = $rest_server->dispatch( $request );

		$this->assertEquals( 500, $response->get_status() );
	}

	/**
	 * @covers ::register_routes
	 */
	public function test_register_routes(): void {
		$site_capabilities = \Mockery::mock( SiteCapabilities::class );
		$sut = new Capabilities( $site_capabilities );

		do_action('rest_api_init');

		$sut->register_routes();

		/** @var \Spy_REST_Server $rest_server */
		$rest_server = rest_get_server();
		$rest_routes = $rest_server->get_routes();

		$this->assertArrayHasKey('/newfold-data/v1/capabilities', $rest_routes);
	}

	/**
	 * @covers ::check_permission
	 */
	public function test_check_permission_administrator(): void {
		$admin_user_id = register_new_user( 'testadmin', 'admin@newfold.com' );
		/** @var \WP_User $admin_wp_user */
		$admin_wp_user = get_user_by( 'id', $admin_user_id );
		$admin_wp_user->add_role( 'administrator' );

		wp_set_current_user( $admin_user_id );

		$site_capabilities = \Mockery::mock( SiteCapabilities::class );
		$sut               = new Capabilities( $site_capabilities );

		$result = $sut->check_permission();

		$this->assertTrue( $result );
	}

	/**
	 * @covers ::check_permission
	 */
	public function test_check_permission(): void {
		$non_admin_user_id = register_new_user( 'testadmin', 'admin@newfold.com' );
		/** @var \WP_User $non_admin_wp_user */
		$non_admin_wp_user = get_user_by( 'id', $non_admin_user_id );
		$non_admin_wp_user->remove_role( 'administrator' );

		wp_set_current_user( $non_admin_user_id );

		$site_capabilities = \Mockery::mock( SiteCapabilities::class );
		$sut               = new Capabilities( $site_capabilities );

		$result = $sut->check_permission();

		$this->assertWPError( $result );
	}
}
