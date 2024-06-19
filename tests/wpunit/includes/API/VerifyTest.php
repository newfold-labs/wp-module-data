<?php

namespace NewfoldLabs\WP\Module\Data\API;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\API\Verify
 */
class VerifyTest extends \lucatume\WPBrowser\TestCase\WPTestCase
{
	protected function tearDown()
	{
		parent::tearDown();

		/**
		 * Otherwise the registered REST route persists between tests.
		 * @see rest_get_server()
		 */
		global $wp_rest_server;
		$wp_rest_server = null;
	}

	/**
	 * @covers ::__construct
	 * @covers ::register_routes
	 */
	public function test_register_routes(): void {

		// REST API routes must be registered on the rest_api_init action.
		do_action('rest_api_init');

		$hiive_connection = \Mockery::mock( \NewfoldLabs\WP\Module\Data\HiiveConnection::class );

		$sut = new Verify( $hiive_connection );

		$sut->register_routes();

		/** @var \Spy_REST_Server $rest_server */
		$rest_server = rest_get_server();
		$rest_routes = $rest_server->get_routes();

		// MD5 hashes are 32 hexadecimals long.
		self::assertArrayHasKey('/newfold-data/v1/verify/(?P<token>[a-f0-9]{32})', $rest_routes);
	}

	/**
	 * @covers ::get_items
	 */
    public function test_get_items() :void
    {
		$hiive_connection = \Mockery::mock( \NewfoldLabs\WP\Module\Data\HiiveConnection::class );

		$hiive_connection->shouldReceive('verify_token')
			->with('a-valid-token')
			->andReturnTrue();

		$sut = new Verify( $hiive_connection );

		$request = new \WP_REST_Request();
		$request->set_param( 'token', 'a-valid-token' );

		$result = $sut->get_items( $request );

		self::assertEquals(200, $result->status);
    }

	/**
	 * @covers ::get_items
	 */
    public function test_get_items_invalid() :void
    {
		$hiive_connection = \Mockery::mock( \NewfoldLabs\WP\Module\Data\HiiveConnection::class );

		$hiive_connection->shouldReceive('verify_token')
			->with('an-invalid-token')
			->andReturnFalse();

		$sut = new Verify( $hiive_connection );

		$request = new \WP_REST_Request();
		$request->set_param( 'token', 'an-invalid-token' );

		$result = $sut->get_items( $request );

		self::assertEquals(401, $result->status);
    }
}
