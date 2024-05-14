<?php

namespace NewfoldLabs\WP\Module\Data\WonderBlocks;

use Mockery;
use WP_Mock;
use WP_Mock\Tools\TestCase;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\WonderBlocks\WonderBlocks
 */
class WonderBlocksTest extends TestCase {

	public function setUp(): void {
		parent::setUp();

		\Patchwork\restoreAll();
	}

	public function test_request_has_hiive_token(): void {

		$fetch_request = Mockery::mock(Requests\Fetch::class);

		$md5_hash = 'a1b2c3d4e5f6..........32hexchars';
		$fetch_request->shouldReceive('get_md5_hash')
		              ->andReturn($md5_hash);

		$fetch_request->shouldReceive('get_endpoint')
		              ->andReturn('test-endpoint');

		\Patchwork\redefine(
			'defined',
			function ( string $constant_name ) {
				switch ($constant_name) {
					case 'NFD_DATA_WB_DEV_MODE':
						return false;
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		WP_Mock::userFunction('get_transient')
		       ->with("nfd_data_wb_test-endpoint_a1b2c3d4e5f6..........32hexchars")
		       ->once()
		       ->andReturnFalse();

		$fetch_request->shouldReceive('get_url')
			->andReturn('https://patterns.hiive.cloud');

		WP_Mock::userFunction('get_option')
			->with('nfd_data_token')
			->once()
			->andReturn('hiive_auth_token');

		$fetch_request->shouldReceive('get_args')
			->andReturn(array('fetch-args'));

		$test_request_args_for_hiive_auth_token = function() {
			self::assertEquals( 'https://patterns.hiive.cloud',  func_get_arg(0) );
			self::assertArrayHasKey( 'headers', func_get_arg(1) );
			self::assertArrayHasKey( 'X-Hiive-Token', func_get_arg(1)['headers'] );
			self::assertEquals( 'hiive_auth_token',  func_get_arg(1)['headers']['X-Hiive-Token'] );
			return true;
		};

		WP_Mock::userFunction('wp_remote_request')
			->withArgs($test_request_args_for_hiive_auth_token)
			->once()
			->andReturn(array());

		WP_Mock::userFunction('is_wp_error')
			->with(\WP_Mock\Functions::type( 'array' ))
			->once()
			->andReturn(false);

		WP_Mock::userFunction('wp_remote_retrieve_response_code')
			->with(\WP_Mock\Functions::type( 'array' ))
			->once()
			->andReturn(200);

		WP_Mock::userFunction('wp_remote_retrieve_body')
			->with(\WP_Mock\Functions::type( 'array' ))
			->once()
			->andReturn(json_encode(array('data'=>array('result'=>'test-success'))));

		$fetch_request->shouldReceive('should_cache')
			->andReturnFalse();

		$result = WonderBlocks::fetch($fetch_request);

		self::assertEquals('test-success',$result['result']);
	}

	public function test_request_has_hiive_token_in_dev_mode_no_cached_value(): void {

		$fetch_request = Mockery::mock(Requests\Fetch::class);

		$md5_hash = 'a1b2c3d4e5f6..........32hexchars';
		$fetch_request->shouldReceive('get_md5_hash')
		              ->andReturn($md5_hash);

		$endpoint = 'test-endpoint';
		$fetch_request->shouldReceive('get_endpoint')
		              ->andReturn($endpoint);

		/** @see WonderBlocks::is_dev_mode() */

		\Patchwork\redefine(
			'defined',
			function ( string $constant_name ) {
				switch ($constant_name) {
					case 'NFD_DATA_WB_DEV_MODE':
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
					case 'NFD_DATA_WB_DEV_MODE':
						return true;
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		WP_Mock::userFunction('get_transient')->never();

		$fetch_request->shouldReceive('get_url')
			->andReturn('https://patterns.hiive.cloud');

		WP_Mock::userFunction('get_option')
			->with('nfd_data_token')
			->once()
			->andReturn('hiive_auth_token');

		$fetch_request->shouldReceive('get_args')
			->andReturn(array('fetch-args'));

		$test_request_args_for_hiive_auth_token = function() {
			self::assertEquals( 'https://patterns.hiive.cloud',  func_get_arg(0) );
			self::assertArrayHasKey( 'headers', func_get_arg(1) );
			self::assertArrayHasKey( 'X-Hiive-Token', func_get_arg(1)['headers'] );
			self::assertEquals( 'hiive_auth_token',  func_get_arg(1)['headers']['X-Hiive-Token'] );
			return true;
		};

		WP_Mock::userFunction('wp_remote_request')
			->withArgs($test_request_args_for_hiive_auth_token)
			->once()
			->andReturn(array());

		WP_Mock::userFunction('is_wp_error')
			->with(\WP_Mock\Functions::type( 'array' ))
			->once()
			->andReturn(false);

		WP_Mock::userFunction('wp_remote_retrieve_response_code')
			->with(\WP_Mock\Functions::type( 'array' ))
			->once()
			->andReturn(200);

		WP_Mock::userFunction('wp_remote_retrieve_body')
			->with(\WP_Mock\Functions::type( 'array' ))
			->once()
			->andReturn(json_encode(array('data'=>array('result'=>'test-success'))));

		$fetch_request->shouldReceive('should_cache')
			->andReturnFalse();

		$result = WonderBlocks::fetch($fetch_request);

		self::assertEquals('test-success',$result['result']);
	}

	public function test_request_has_hiive_token_not_dev_mode_with_cached_value(): void {

		$fetch_request = Mockery::mock(Requests\Fetch::class);

		$md5_hash = 'a1b2c3d4e5f6..........32hexchars';
		$fetch_request->shouldReceive('get_md5_hash')
		              ->andReturn($md5_hash);

		$endpoint = 'test-endpoint';
		$fetch_request->shouldReceive('get_endpoint')
		              ->andReturn($endpoint);

		/** @see WonderBlocks::is_dev_mode() */

		\Patchwork\redefine(
			'defined',
			function ( string $constant_name ) {
				switch ($constant_name) {
					case 'NFD_DATA_WB_DEV_MODE':
						return false;
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		WP_Mock::userFunction('get_transient')
		       ->with("nfd_data_wb_test-endpoint_a1b2c3d4e5f6..........32hexchars")
		       ->once()
		       ->andReturn(array('result'=>'test-success'));

		$result = WonderBlocks::fetch($fetch_request);

		self::assertEquals('test-success', $result['result']);
	}

}
