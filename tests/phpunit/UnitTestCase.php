<?php
/**
 * Unit test superclass to provide convenience methods and common setup/teardown tasks for tests.
 *
 * @package NewfoldLabs\WP\Module\Data
 */

namespace NewfoldLabs\WP\Module\Data;

use WP_Mock\Tools\TestCase as WP_Mock_TestCase;

/**
 * Sets up temp directory and resets Patchwork and WP Mock.
 *
 * @phpcs:disable WordPress.WP.AlternativeFunctions.unlink_unlink
 * @phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
 * @phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
 */
class UnitTestCase extends WP_Mock_TestCase {

	/**
	 * A temporary directory for the test case which will be removed afterwards.
	 *
	 * No trailing slash.
	 *
	 * @var string
	 */
	protected $temp_dir;

	/**
	 * Set up a temp directory for the test case.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->temp_dir = sprintf( '%s/%s', sys_get_temp_dir(), uniqid( $this->getName() ) );
	}

	/**
	 * * Reset Patchwork
	 * * Ensure WP Mock is in strict mode
	 * * Delete the temp directory after the test case.
	 */
	public function tearDown(): void {
		parent::tearDown();

		\Patchwork\restoreAll();

		forceWpMockStrictModeOn();

		$this->delete_temp( $this->temp_dir );
	}

	/**
	 * Delete a sub-dir or file from the temp directory.
	 *
	 * @see https://stackoverflow.com/a/57088472/336146
	 *
	 * @param string $path Absolute filepath to delete.
	 *
	 * @return bool true on success or false on failure.
	 */
	protected function delete_temp( $path ): bool {
		if ( 0 !== strpos( $path, sys_get_temp_dir() ) ) {
			return false;
		}
		if ( is_file( $path ) ) {
			return unlink( $path );
		} elseif ( is_dir( $path ) ) {
			$scan = glob( rtrim( $path, '/' ) . '/*' );
			foreach ( $scan as $sub_paths ) {
				$this->delete_temp( $sub_paths );
			}
			return @rmdir( $path );
		}
		return false;
	}
}
