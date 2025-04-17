<?php

namespace NewfoldLabs\WP\Module\Data;

use Mockery;
use NewfoldLabs\WP\Module\Data\API\Capabilities;
use NewfoldLabs\WP\Module\Data\Helpers\Transient;
use WP_Mock;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\Data
 */
class DataTest extends UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		WP_Mock::userFunction( 'wp_json_encode' )
				->andReturnUsing(
					function ( $input ) {
						return json_encode( $input );
					}
				);

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) {
				switch ( $constant_name ) {
					case 'ABSPATH':
						return $this->temp_dir;
					default:
						return \Patchwork\relay( func_get_args() );
				}
			}
		);

		/**
		 * Create an empty file for `/wp-admin/includes/plugin.php` so when it is included, it doesn't load anything.
		 *
		 * @see Transient::should_use_transients()
		 */
		@mkdir( $this->temp_dir . '/wp-admin/includes', 0777, true );
		file_put_contents( $this->temp_dir . '/wp-admin/includes/plugin.php', '<?php' );
	}

	public function tearDown(): void {
		parent::tearDown();

		/**
		 * @see DataTest::test_authenticate()
		 */
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
	}

	/**
	 * @covers ::authenticate
	 */
	public function test_authenticate()
	{
		$sut = new Data();

		\Patchwork\redefine(
			'defined',
			function ( string $constant_name ) {
				switch ($constant_name) {
					case 'REST_REQUEST':
						return true;
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) {
				switch ($constant_name) {
					case 'REST_REQUEST':
						return true;
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		$_SERVER['REQUEST_METHOD'] = 'GET';

		\Patchwork\redefine(
			'file_get_contents',
			function ( string $filename ) {
				switch ($filename) {
					case 'php://input':
						return '';
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		$_SERVER['HTTP_X-Timestamp'] = time();

		$hiive_site_auth_token = 'abc123';

		$hash = hash( 'sha256', json_encode(array(
			'method'    => 'GET',
			'url'       => 'http://', /** @see Url::getCurrentUrl() */
			'body'      => '',
			'timestamp' => null,
//			'timestamp' => $_SERVER['HTTP_X-Timestamp'],
		)));
		$salt = hash( 'sha256', strrev( $hiive_site_auth_token ) );
		$token = hash( 'sha256', $hash . $salt );

		$_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";

		WP_Mock::userFunction('get_option')
			->with('nfd_data_token')
			->andReturn($hiive_site_auth_token);

		$user = Mockery::mock(\WP_User::class);
		$user->ID = 123;

		WP_Mock::userFunction('get_users')
			->with(\WP_Mock\Functions::type('array'))
			->andReturn(array($user));

		WP_Mock::userFunction('wp_set_current_user')
			->once()
			->with(123);

		$result = $sut->authenticate(null);

		self::assertTrue($result);
	}

	/**
	 * @covers ::authenticate
	 */
	public function test_authenticate_returns_early_when_no_auth_header()
	{

		$sut = new Data();

		\Patchwork\redefine(
			'defined',
			function ( string $constant_name ) {
				switch ($constant_name) {
					case 'REST_REQUEST':
						return true;
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) {
				switch ($constant_name) {
					case 'REST_REQUEST':
						return true;
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);


		$result = $sut->authenticate(null);

		self::assertNull($result);

	}

	/**
	 * @covers ::authenticate
	 */
	public function test_authenticate_returns_early_when_not_a_rest_request()
	{
		$sut = new Data();

		\Patchwork\redefine(
			'defined',
			function ( string $constant_name ) {
				switch ($constant_name) {
					case 'REST_REQUEST':
						return false;
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		$result = $sut->authenticate(null);

		self::assertNull($result);
	}

	/**
	 * @covers ::authenticate
	 */
	public function test_authenticate_returns_early_when_already_authenticated()
	{
		$sut = new Data();

		$result = $sut->authenticate(true);

		self::assertTrue($result);
	}

	/**
	 * @covers ::start
	 */
	public function test_delete_token_on_401_response_is_added(): void {
		forceWpMockStrictModeOff();

		$sut = new Data();

		WP_Mock::expectFilterAdded(
			'http_response',
			array( $sut, 'delete_token_on_401_response' ),
			10,
			3
		);

		$sut->start();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::delete_token_on_401_response
	 */
	public function test_deletes_hiive_token_on_401(): void {

		$sut = new Data();

		$request_response = array(
			'response' => array(
				'code' => 401,
			),
		);

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) {
				switch ($constant_name) {
					case 'NFD_HIIVE_URL':
						return 'https://hiive.cloud/api';
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		WP_Mock::userFunction('wp_remote_retrieve_response_code')
		       ->once()
				->with(\WP_Mock\Functions::type('array'))
				->andReturn($request_response['response']['code']);

		WP_Mock::userFunction('absint')
		       ->once()
		       ->with(401)
				->andReturn( 401);

		WP_Mock::userFunction('delete_option')
		       ->once()
		       ->with('nfd_data_token');

		$sut->delete_token_on_401_response($request_response, array(), 'https://hiive.cloud/api/sites/v1/events' );

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::delete_token_on_401_response
	 */
	public function test_does_not_delete_hiive_token_on_hiive_200(): void {

		$sut = new Data();

		$request_response = array(
			'response' => array(
				'code' => 200,
			),
		);

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) {
				switch ($constant_name) {
					case 'NFD_HIIVE_URL':
						return 'https://hiive.cloud/api';
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		WP_Mock::userFunction('wp_remote_retrieve_response_code')
		       ->once()
				->with(\WP_Mock\Functions::type('array'))
				->andReturn($request_response['response']['code']);

		WP_Mock::userFunction('absint')
		       ->once()
		       ->with(200)
		       ->andReturn( 200);

		WP_Mock::userFunction('delete_option')
		       ->never();

		$sut->delete_token_on_401_response($request_response, array(), 'https://hiive.cloud/api/sites/v1/events' );

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::delete_token_on_401_response
	 */
	public function test_does_not_delete_hiive_token_on_401_other_domain(): void {

		$sut = new Data();

		$request_response = array(
			'response' => array(
				'code' => 401,
			),
		);

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) {
				switch ($constant_name) {
					case 'NFD_HIIVE_URL':
						return 'https://hiive.cloud/api';
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		WP_Mock::userFunction('wp_remote_retrieve_response_code')
		       ->never();

		WP_Mock::userFunction('absint')
		       ->never();

		WP_Mock::userFunction('delete_option')
		       ->never();

		$sut->delete_token_on_401_response($request_response, array(), 'https://example.org' );
		$this->assertConditionsMet();
	}

	public function test_registers_capabilities_endpoint(): void {

		forceWpMockStrictModeOff();

		WP_Mock::userFunction( 'get_option' )
		       ->with( 'nfd_data_token' )
		       ->once()
		       ->andReturn( 'valid_token' );

		WP_Mock::userFunction( 'wp_next_scheduled' )
		       ->with( 'nfd_data_cron' )
		       ->once()
		       ->andReturnTrue();

		\WP_Mock::userFunction( 'get_dropins' )
		        ->once()
		        ->andReturn( array() );

		WP_Mock::userFunction( 'get_transient' )
		       ->with( 'nfd_plugin_activated' )
		       ->once()
		       ->andReturnFalse();

		WP_Mock::userFunction( 'is_plugin_active' )
		       ->with( 'woocommerce/woocommerce.php' )
		       ->once()
		       ->andReturnFalse();

		WP_Mock::userFunction( 'wp_next_scheduled' )
		       ->with( 'nfd_data_sync_cron' )
		       ->once()
		       ->andReturnTrue();

		WP_Mock::expectActionAdded( 'rest_api_init', array( new WP_Mock\Matcher\AnyInstance( Capabilities::class ), 'register_routes' ) );

		$sut = new Data();
		$sut->init();

		$this->assertConditionsMet();
	}
}
