<?php
/**
 * This is currently just intended for pushing a list of capabilities from Hiive, but it is written agnostic
 * of read/write.
 */

namespace NewfoldLabs\WP\Module\Data\API;

use NewfoldLabs\WP\Module\Data\SiteCapabilities;
use NewfoldLabs\WP\Module\Data\WPUnitTestCase;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\API\Capabilities
 */
class CapabilitiesWPUnitTest extends WPUnitTestCase {


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
			->twice()
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
		$request->set_body( json_encode( $request_capabilities ) );
		$request->set_header( 'Content-Type', 'application/json' );

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
			->twice()
			->andReturn(
				array(
					'hasAISiteGen'        => false,
					'canAccessHelpCenter' => true,
				)
			);

		$site_capabilities->shouldReceive( 'update' )->once()->with( $request_capabilities )->andReturn( true );

		$sut = new Capabilities( $site_capabilities );

		$request = new \WP_REST_Request( 'PATCH', 'newfold-data/v1/capabilities' );
		$request->set_body( json_encode( $request_capabilities ) );
		$request->set_header( 'Content-Type', 'application/json' );

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
			->twice()
			->andReturn(
				array(
					'hasAISiteGen'        => false,
					'canAccessHelpCenter' => true,
				)
			);

		$site_capabilities->shouldReceive( 'update' )->once()->with( $request_capabilities )->andReturn( true );

		$sut = new Capabilities( $site_capabilities );

		$request = new \WP_REST_Request( 'PATCH', 'newfold-data/v1/capabilities' );
		$request->set_body( json_encode( $request_capabilities ) );
		$request->set_header( 'Content-Type', 'application/json' );

		$response      = $sut->update( $request );
		$response_data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEmpty( $response_data['added'] );
		$this->assertEmpty( $response_data['updated'] );
		$this->assertEmpty( $response_data['removed'] );
		$this->assertCount( 2, $response_data['unchanged'] );
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
							->twice()
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
		$request->set_body( json_encode( $request_capabilities ) );
		$request->set_header( 'Content-Type', 'application/json' );

		$response      = $sut->update( $request );
		$response_data = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );

		$this->assertArrayHasKey( 'hasEcomdash', $response_data['added'] );
	}

	/**
	 * @covers ::register_routes
	 */
	public function test_register_routes(): void {
		$site_capabilities = \Mockery::mock( SiteCapabilities::class );
		$sut               = new Capabilities( $site_capabilities );

		do_action( 'rest_api_init' );

		$sut->register_routes();

		/** @var \Spy_REST_Server $rest_server */
		$rest_server = rest_get_server();
		$rest_routes = $rest_server->get_routes();

		$this->assertArrayHasKey( '/newfold-data/v1/capabilities', $rest_routes );
	}

	/**
	 * @covers ::check_permission
	 */
	public function test_check_permission_administrator(): void {
		$username      = uniqid( 'testadmin' );
		$admin_user_id = register_new_user( $username, $username . '@newfold.com' );
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
		$username          = uniqid( 'testuser' );
		$non_admin_user_id = register_new_user( $username, $username . '@newfold.com' );
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
