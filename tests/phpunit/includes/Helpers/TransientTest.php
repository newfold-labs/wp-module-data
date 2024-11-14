<?php

namespace NewfoldLabs\WP\Module\Helpers;

use NewfoldLabs\WP\Module\Data\Helpers\Transient;
use WP_Mock;
use WP_Mock\Tools\TestCase;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\Helpers\Transient
 */
class TransientTest extends TestCase {

	/**
	 * Create an empty file for `/wp-admin/includes/plugin.php` so when it is included, it doesn't load anything.
	 */
	public function setUp(): void {
		parent::setUp();

		$temp_dir = sys_get_temp_dir();

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) use ( $temp_dir ) {
				switch ( $constant_name ) {
					case 'ABSPATH':
						return $temp_dir;
					default:
						return \Patchwork\relay( func_get_args() );
				}
			}
		);

		@mkdir( $temp_dir . '/wp-admin/includes', 0777, true );
		file_put_contents( $temp_dir . '/wp-admin/includes/plugin.php', '<?php' );
	}

	/**
	 * @covers ::set
	 */
	public function test_set_transient_use_default(): void {

		$test_transient_name = uniqid( __FUNCTION__ );

		\WP_Mock::userFunction( 'get_dropins' )
				->once()
				->andReturn( array( 'not-object-cache.php' => array() ) );

		\WP_Mock::userFunction( 'set_transient' )
				->once()
				->with( $test_transient_name, 'value', 999 )
				->andReturnTrue();

		\WP_Mock::userFunction( 'update_option' )
				->never();

		Transient::set( $test_transient_name, 'value', 999 );

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::set
	 */
	public function test_set_transient_use_options(): void {

		$test_transient_name = uniqid( __FUNCTION__ );

		\WP_Mock::userFunction( 'get_dropins' )
				->once()
				->andReturn( array( 'object-cache.php' => array() ) );

		\WP_Mock::userFunction( 'set_transient' )
				->never();

		\WP_Mock::userFunction( 'update_option' )
				->once()
				->with(
					$test_transient_name,
					\WP_Mock\Functions::type( 'array' ),
					false
				)
				->andReturnTrue();

		Transient::set( $test_transient_name, 'value', 999 );

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::get
	 */
	public function test_get_transient_use_default(): void {

		$test_transient_name = uniqid( __FUNCTION__ );

		\WP_Mock::userFunction( 'get_dropins' )
				->once()
				->andReturn( array( 'not-object-cache.php' => array() ) );

		\WP_Mock::userFunction( 'get_transient' )
				->once()
				->with( $test_transient_name )
				->andReturn( 'value' );

		\WP_Mock::userFunction( 'get_option' )
				->never();

		$result = Transient::get( $test_transient_name );

		$this->assertEquals( 'value', $result );
	}

	/**
	 * @covers ::get
	 */
	public function test_get_transient_use_options(): void {

		$test_transient_name = uniqid( __FUNCTION__ );

		\WP_Mock::userFunction( 'get_dropins' )
				->once()
				->andReturn( array( 'object-cache.php' => array() ) );

		\WP_Mock::userFunction( 'get_transient' )
				->never();

		\WP_Mock::userFunction( 'get_option' )
				->once()
				->with( $test_transient_name, )
				->andReturn(
					array(
						'value'      => 'value',
						'expires_at' => time() + 999,
					)
				);

		$result = Transient::get( $test_transient_name );

		$this->assertEquals( 'value', $result );
	}

	/**
	 * @covers ::get
	 */
	public function test_get_transient_use_options_expired(): void {

		$test_transient_name = uniqid( __FUNCTION__ );

		\WP_Mock::userFunction( 'get_dropins' )
				->once()
				->andReturn( array( 'object-cache.php' => array() ) );

		\WP_Mock::userFunction( 'get_transient' )
				->never();

		\WP_Mock::userFunction( 'get_option' )
				->once()
				->with( $test_transient_name, )
				->andReturn(
					array(
						'value'      => 'value',
						'expires_at' => time() - 999,
					)
				);

		\WP_Mock::userFunction( 'delete_option' )
				->once()
				->with( $test_transient_name )
				->andReturnTrue();

		$result = Transient::get( $test_transient_name );

		$this->assertFalse( $result );
	}

	/**
	 * Test does calling `get` on the instance of Transient class call the static method.
	 *
	 * @covers ::__call
	 */
	public function test_instance_call_method(): void {

		$sut = new Transient();

		\WP_Mock::userFunction( 'get_dropins' )
				->once()
				->andReturn( array( 'not-object-cache.php' => array() ) );

		$return_value = uniqid( __FUNCTION__ );

		\WP_Mock::userFunction( 'get_transient' )
				->once()
				->andReturn( $return_value );

		$result = $sut->get( 'test' );

		$this->assertEquals( $return_value, $result );
	}

	/**
	 * @covers ::should_use_transients
	 */
	public function test_should_use_transients_bluehost_cloud(): void {

		$test_transient_name = uniqid( __FUNCTION__ );

		\WP_Mock::userFunction( 'get_dropins' )
		        ->once()
		        ->andReturn( array( 'object-cache.php' => array() ) );

		\WP_Mock::userFunction( 'set_transient' )
		        ->once()
		        ->with( $test_transient_name, 'value', 999 )
		        ->andReturnTrue();

		\WP_Mock::userFunction( 'update_option' )
		        ->never();

		\NewfoldLabs\WP\Context\setContext( 'platform', 'atomic' );

		Transient::set( $test_transient_name, 'value', 999 );

		$this->assertConditionsMet();
	}

	/**
	 * {@see WP_Mock::expectFilter()} and {WP_Mock::expectAction()} are not working for me. I have created some dummy
	 *
	 * @covers ::set
	 */
	public function test_set_transient_filters_are_called(): void {

		$test_transient_name = uniqid( __FUNCTION__ );

		\WP_Mock::userFunction( 'get_dropins' )
				->once()
				->andReturn( array( 'object-cache.php' => array() ) );

		WP_Mock::expectFilter(
			"pre_set_transient_{$test_transient_name}",
			'value',
			999,
			$test_transient_name
		);

		WP_Mock::expectFilter(
			"expiration_of_transient_{$test_transient_name}",
			999,
			'value',
			$test_transient_name,
		);

		\WP_Mock::userFunction( 'update_option' )
				->once()
				->andReturn( true );

		WP_Mock::expectAction(
			"set_transient_{$test_transient_name}",
			'value',
			999,
			$test_transient_name
		);

		WP_Mock::expectAction(
			'setted_transient',
			$test_transient_name,
			'value',
			999
		);

		Transient::set( $test_transient_name, 'value', 999 );

		$this->assertConditionsMet();
	}
}
