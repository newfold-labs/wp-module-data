<?php

namespace NewfoldLabs\WP\Module\Data\WonderBlocks\Requests;

use Mockery;
use WP_Mock\Tools\TestCase;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\WonderBlocks\Requests\Request
 */
class RequestTest extends TestCase {

    /**
     * @covers ::get_base_url
     */
    public function test_get_base_url()
    {
        $sut = Mockery::mock(Request::class)->makePartial();

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

        $result = $sut->get_base_url();

        self::assertEquals('https://patterns.hiive.cloud', $result);
    }

    /**
     * @covers ::get_base_url
     */
    public function test_get_base_url_dev_mode()
    {
        $sut = Mockery::mock(Request::class)->makePartial();

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

        $result = $sut->get_base_url();

        self::assertEquals('http://localhost:8888', $result);
    }
    /**
     * @covers ::get_base_url
     */
    public function test_get_base_url_dev_mode_false()
    {
        $sut = Mockery::mock(Request::class)->makePartial();

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
                        return false;
                    default:
                        return \Patchwork\relay(func_get_args());
                }
            }
        );

        $result = $sut->get_base_url();

        self::assertEquals('https://patterns.hiive.cloud', $result);
    }

    /**
     * @covers ::get_endpoint
     */
    public function test_get_endpoint()
    {
        $sut = new class() extends Request {
            protected $endpoint = 'test-endpoint';

            public function get_md5_hash(): string
            {
                return '';
            }
        };

        $result = $sut->get_endpoint();

        self::assertEquals('test-endpoint', $result);
    }
}
