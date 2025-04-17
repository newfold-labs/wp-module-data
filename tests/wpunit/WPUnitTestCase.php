<?php
/**
 * Unit test superclass to provide convenience methods and common setup/teardown tasks for tests.
 *
 * @package NewfoldLabs\WP\Module\Data
 */

namespace NewfoldLabs\WP\Module\Data;

use Mockery;

/**
 * Deletes users; closes Mockery.
 */
class WPUnitTestCase extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * * Deletes all created users except the admin:1 which exists by default.
	 * * Calls `Mockery::close()` to assert calls to mocks.
	 */
	protected function tearDown(): void {

		array_map(
			'wp_delete_user',
			get_users(
				array(
					'exclude' => array( 1 ),
					'fields'  => 'ID',
				)
			)
		);

		Mockery::close();
	}
}
