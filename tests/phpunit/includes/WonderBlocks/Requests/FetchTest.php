<?php

namespace NewfoldLabs\WP\Module\Data\WonderBlocks\Requests;

use Mockery;
use NewfoldLabs\WP\Module\Data\Listeners\Plugin;
use WP_Mock;
use WP_Mock\Tools\TestCase;
use function NewfoldLabs\WP\ModuleLoader\container;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\WonderBlocks\Requests\Fetch
 */
class FetchTest extends TestCase {

	public function setUp(): void
	{
		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) {
				switch ($constant_name) {
					case 'DAY_IN_SECONDS':
						return 60 * 60 * 24;
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);
	}

	/**
	 * @covers ::__construct
	 * @covers ::get_url
	 */
	public function test_get_url_with_endpoint(): void {
		$args = array(
			'endpoint' => 'test-endpoint',
		);

		$sut = new Fetch($args);
		$result = $sut->get_url();
		self::assertEquals('https://patterns.hiive.cloud/test-endpoint', $result);
	}

	/**
	 * @covers ::__construct
	 * @covers ::get_url
	 */
	public function test_get_url_with_endpoint_and_slug(): void {
		$args = array(
			'endpoint' => 'test-endpoint',
			'slug' => 'test-slug',
		);

		$sut = new Fetch($args);
		$result = $sut->get_url();
		self::assertEquals('https://patterns.hiive.cloud/test-endpoint/test-slug', $result);
	}
}
