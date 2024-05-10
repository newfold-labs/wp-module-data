<?php

namespace NewfoldLabs\WP\Module\Data\WonderBlocks;

use Mockery;
use WP_Mock;
use WP_Mock\Tools\TestCase;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\WonderBlocks\WonderBlocks
 */
class WonderBlocksTest extends TestCase {

	public function test_request_has_hiive_token(): void {

		$fetch_request = Mockery::mock(Requests\Fetch::class);

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

}
