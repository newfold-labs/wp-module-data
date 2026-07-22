<?php
/**
 * SiteCapabilities has only one public method: {@see \NewfoldLabs\WP\Module\Data\SiteCapabilities::get()}
 *
 * @package NewfoldLabs\WP\Module\Data
 */

namespace NewfoldLabs\WP\Module\Data;

use Mockery;
use NewfoldLabs\WP\Module\Data\Helpers\Transient;
use WP_Error;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\SiteCapabilities
 */
class SiteCapabilitiesWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Reset Mockery between tests.
	 */
	protected function tearDown(): void {
		parent::tearDown();
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
			->andReturn(
				array(
					'canAccessAI'     => true,
					'test_capability' => true,
				)
			);

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

		$hiive = $this->mock_hiive_capabilities_request(
			array(
				'body'     => wp_json_encode(
					array(
						'canAccessAI'     => true,
						'test_capability' => true,
					)
				),
				'response' => array( 'code' => 200 ),
			)
		);

		$transient->shouldReceive( 'set' )
					->once()
					->with(
						'nfd_site_capabilities',
						array(
							'canAccessAI'     => true,
							'test_capability' => true,
						),
						14400,
					)
					->andReturnTrue();

		$sut = new SiteCapabilities( $transient, $hiive );

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

		$hiive = $this->mock_hiive_capabilities_request(
			new WP_Error( 'could_not_connect', 'Could not connect to Hiive' )
		);

		$transient->shouldNotReceive( 'set' );

		$sut = new SiteCapabilities( $transient, $hiive );

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

		$hiive = $this->mock_hiive_capabilities_request(
			array(
				'body'     => '',
				'response' => array( 'code' => 401 ),
			)
		);

		$transient->shouldNotReceive( 'set' );

		$sut = new SiteCapabilities( $transient, $hiive );

		$result = $sut->get( 'test_capability' );

		$this->assertFalse( $result );
	}

	/**
	 * @covers ::update
	 */
	public function test_update(): void {

		$transient = Mockery::mock( Transient::class );

		$transient->shouldReceive( 'get' )
					->once()
					->with( 'nfd_site_capabilities' )
					->andReturn(
						array(
							'canAccessAI'         => true,
							'existing_capability' => true,
						),
					);

		$transient->shouldReceive( 'set' )
					->once()
					->with(
						'nfd_site_capabilities',
						array(
							'canAccessAI'         => true,
							'existing_capability' => true,
							'new_capability'      => true,
						),
						14400,
					)
					->andReturnTrue();

		$sut = new SiteCapabilities( $transient );

		$result = $sut->update( array( 'new_capability' => true ) );

		$this->assertTrue( $result );
	}

	/**
	 * @covers ::set
	 */
	public function test_set(): void {

		$transient = Mockery::mock( Transient::class );

		$transient->shouldReceive( 'set' )
					->once()
					->with(
						'nfd_site_capabilities',
						array(
							'canAccessAI'    => true,
							'new_capability' => true,
						),
						14400,
					)
					->andReturnTrue();

		$sut = new SiteCapabilities( $transient );

		$result = $sut->set(
			array(
				'canAccessAI'    => true,
				'new_capability' => true,
			)
		);

		$this->assertTrue( $result );
	}

	/**
	 * @covers ::all
	 * @covers ::fetch
	 */
	public function test_refetches_when_cache_contains_bootstrap_fallback(): void {

		$transient = Mockery::mock( Transient::class );

		$transient->shouldReceive( 'get' )
			->once()
			->with( 'nfd_site_capabilities' )
			->andReturn(
				array(
					'canMigrateSite' => true,
					'hasAISiteGen'   => true,
				)
			);

		$hiive = $this->mock_hiive_capabilities_request(
			array(
				'body'     => wp_json_encode(
					array(
						'canAccessAI'         => true,
						'canAccessHelpCenter' => true,
					)
				),
				'response' => array( 'code' => 200 ),
			)
		);

		$transient->shouldReceive( 'set' )
					->once()
					->with(
						'nfd_site_capabilities',
						array(
							'canAccessAI'         => true,
							'canAccessHelpCenter' => true,
						),
						14400,
					)
					->andReturnTrue();

		$sut = new SiteCapabilities( $transient, $hiive );

		$result = $sut->all();

		$this->assertTrue( $result['canAccessHelpCenter'] );
	}

	/**
	 * @covers ::clear
	 */
	public function test_clear(): void {

		$transient = Mockery::mock( Transient::class );

		$transient->shouldReceive( 'delete' )
					->once()
					->with( 'nfd_site_capabilities' )
					->andReturnTrue();

		$sut = new SiteCapabilities( $transient );

		$this->assertTrue( $sut->clear() );
	}

	/**
	 * @covers ::set
	 */
	public function test_set_rejects_invalid_capabilities(): void {

		$transient = Mockery::mock( Transient::class );

		$transient->shouldNotReceive( 'set' );

		$sut = new SiteCapabilities( $transient );

		$this->assertFalse(
			$sut->set(
				array(
					'canMigrateSite' => true,
					'hasAISiteGen'   => true,
				)
			)
		);
	}

	/**
	 * @covers ::all
	 */
	public function test_invalid_capabilities_data_previously_saved(): void {

		$transient = Mockery::mock( Transient::class );

		$transient->shouldReceive( 'get' )
					->once()
					->with( 'nfd_site_capabilities' )
					->andReturn( 'error_should_be_an_array' );

		$sut = new SiteCapabilities( $transient );

		$result = $sut->all( false );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * @param array<string, mixed>|WP_Error $response Mocked Hiive capabilities API response.
	 *
	 * @return HiiveConnection
	 */
	private function mock_hiive_capabilities_request( $response ): HiiveConnection {
		$hiive = Mockery::mock( HiiveConnection::class );

		$hiive->shouldReceive( 'hiive_request' )
			->once()
			->with(
				'sites/v1/capabilities',
				null,
				array(
					'method' => 'GET',
				)
			)
			->andReturn( $response );

		return $hiive;
	}
}
