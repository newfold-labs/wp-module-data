<?php

namespace NewfoldLabs\WP\Module\Data;

use WpOrg\Requests\Transport\Curl;

/**
 * Exploratory test to observe behaviour with Requests library.
 */
class HiiveConnectionIntegrationTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * @see Curl::setup_handle()
	 * @see Curl::format_get()
	 *
	 * @see https://github.com/newfold-labs/wp-module-data/pull/205
	 */
	public function test_send_get_with_body() {

		if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && constant( 'WP_HTTP_BLOCK_EXTERNAL' ) ) {
			$this->markTestSkipped( '`integration.suite.yml` needs `WP_HTTP_BLOCK_EXTERNAL: false` for this test to run.' );
		}

		add_filter( 'pre_option_nfd_data_token', fn() => 'auth_token' );

		$sut = new HiiveConnection();

		try {
			$result = $sut->hiive_request(
				'route/whatever',
				array( 'body' => 'not-empty' ),
				array( 'method' => 'GET' )
			);
		} catch ( \PHPUnit\Framework\Error\Warning $e ) {
			// "http_build_query() expects parameter 1 to be array, string given".
			$this->fail( $e->getMessage() );
		}

		// Indicates a response from Hiive rather than an error before the request is sent.
		$this->assertEquals(
			'The route api/route/whatever could not be found.',
			json_decode( $result['body'], JSON_THROW_ON_ERROR )['message']
		);
	}
}
