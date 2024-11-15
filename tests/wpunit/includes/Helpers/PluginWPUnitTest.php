<?php

namespace NewfoldLabs\WP\Module\Data\Helpers;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\Helpers\Plugin
 */
class PluginWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	public function set_up() {
		parent::set_up();

		assert(
			file_exists(codecept_absolute_path('wp-content/plugins/bluehost-wordpress-plugin/bluehost-wordpress-plugin.php')),
			'Fixture missing: The Bluehost plugin'
		);

		assert(
			file_exists(codecept_absolute_path('wp-content/plugins/jetpack/jetpack.php')),
			'Fixture missing: Jetpack plugin'
		);
	}

	/**
	 * @covers ::collect
	 * @covers ::get_data
	 */
	public function test_collect(): void {
		$sut = new Plugin();

		$result = $sut->collect('bluehost-wordpress-plugin/bluehost-wordpress-plugin.php');

		$this->assertEquals('The Bluehost Plugin', $result['title']);
		$this->assertEqualSets(['slug','version','title','url','active','mu','auto_updates'], array_keys($result));
	}

	/**
	 * @covers ::collect
	 * @covers ::get_data
	 * @covers ::get_admin_users
	 */
	public function test_collect_jetpack(): void {
		$new_user_id = wp_create_user('admin2', 'password', 'email@example.com');
		$new_user = new \WP_User($new_user_id);
		$new_user->add_role('administrator');

		$sut = new Plugin();

		$result = $sut->collect('jetpack/jetpack.php');

		$this->assertEqualSets(['slug','version','title','url','active','mu','auto_updates','users',], array_keys($result));

		$this->assertCount(2, $result['users']);

		$this->assertEqualSets(['id','email',], array_keys($result['users'][1]));
	}

	/**
	 * @covers ::collect_installed
	 */
	public function test_collect_installed(): void {
		$sut = new Plugin();

		$result = $sut->collect_installed();

		$this->assertGreaterThan(2, count($result));
	}
}
