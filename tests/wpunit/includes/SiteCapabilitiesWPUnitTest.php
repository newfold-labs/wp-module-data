<?php
/**
 * SiteCapabilities has only one public method: {@see \NewfoldLabs\WP\Module\Data\SiteCapabilities::get()}
 */

namespace NewfoldLabs\WP\Module\Data;

use Mockery;
use NewfoldLabs\WP\Module\Data\Helpers\Transient;
use WP_Error;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\SiteCapabilities
 */
class SiteCapabilitiesWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	protected function setUp() {
		parent::setUp();

		require_once codecept_root_dir( 'vendor/antecedent/patchwork/Patchwork.php' );

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) {
				switch ( $constant_name ) {
					case 'NFD_HIIVE_URL':
						return 'https://hiive.localhost';
					default:
						return \Patchwork\relay( func_get_args() );
				}
			}
		);
	}

	protected function tearDown() {
		Mockery::resetContainer();
	}

	/**
	 * @covers ::get
	 * @covers ::exists
	 * @covers ::all
	 */
	public function test_get_capability(): void {

		$transient = Mockery::mock( Transient::class );

		$transient->shouldReceive( 'get' )
			->once()
			->with( 'nfd_site_capabilities' )
			->andReturn( array( 'test_capability' => true ) );

		$sut = new SiteCapabilities( $transient );

		$result = $sut->get( 'test_capability' );

		$this->assertTrue( $result );
	}

	/**
	 * @covers ::all
	 * @covers ::fetch
	 */
	public function test_fetch_capabilities(): void {

		$transient = Mockery::mock( Transient::class );

		$transient->shouldReceive( 'get' )
			->once()
			->with( 'nfd_site_capabilities' )
			->andReturnFalse();

		/**
		 * @see \WP_Http::request()
		 */
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'body'     => json_encode( array( 'test_capability' => true ) ),
					'response' => array( 'code' => 200 ),
				);
			}
		);

		$transient->shouldReceive( 'set' )
					->once()
					->with(
						'nfd_site_capabilities',
						array( 'test_capability' => true ),
						14400,
					)
					->andReturnTrue();

		$sut = new SiteCapabilities( $transient );

		$result = $sut->get( 'test_capability' );

		$this->assertTrue( $result );
	}

	/**
	 * @covers ::all
	 * @covers ::fetch
	 */
	public function test_fetch_capabilities_wp_error(): void {

		$transient = Mockery::mock( Transient::class );

		$transient->shouldReceive( 'get' )
			->once()
			->with( 'nfd_site_capabilities' )
			->andReturnFalse();

		/**
		 * @see \WP_Http::request()
		 */
		add_filter(
			'pre_http_request',
			function () {
				return new WP_Error( 'could_not_connect', 'Could not connect to Hiive' );
			}
		);

		$transient->shouldReceive( 'set' )
					->once()
					->with(
						'nfd_site_capabilities',
						array(),
						14400,
					)
					->andReturnTrue();

		$sut = new SiteCapabilities( $transient );

		$result = $sut->get( 'test_capability' );

		$this->assertFalse( $result );
	}

	/**
	 * @covers ::all
	 * @covers ::fetch
	 */
	public function test_fetch_capabilities_401(): void {

		$transient = Mockery::mock( Transient::class );

		$transient->shouldReceive( 'get' )
			->once()
			->with( 'nfd_site_capabilities' )
			->andReturnFalse();

		/**
		 * @see \WP_Http::request()
		 */
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'body'     => '',
					'response' => array( 'code' => 401 ),
				);
			}
		);

		$transient->shouldReceive( 'set' )
					->once()
					->with(
						'nfd_site_capabilities',
						array(),
						14400,
					)
					->andReturnTrue();

		$sut = new SiteCapabilities( $transient );

		$result = $sut->get( 'test_capability' );

		$this->assertFalse( $result );
	}
}
