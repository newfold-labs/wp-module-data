<?php

namespace NewfoldLabs\WP\Module\Data\Listeners;

use Mockery;
use NewfoldLabs\WP\Module\Data\EventManager;
use WP_Mock;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\Listeners\Plugin
 */
class PluginTest extends \WP_Mock\Tools\TestCase {

	/**
	 * Restore the modified state after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		\Patchwork\restoreAll();

		unset( $_SERVER['REMOTE_ADDR'] );
		unset( $_SERVER['REQUEST_URI'] );
	}

	/**
	 * Dataprovider with one happy path and the problematic case where the plugin array is empty.
	 *
	 * @see ::test_upgrader_process_complete_fired
	 *
	 * @return array<array{plugins: string[], expect_push_times: int}>
	 */
	public function upgrader_process_complete_data_provider(): array {
		return array(
			array(
				'plugins'           => array(
					'bluehost-wordpress-plugin/bluehost-wordpress-plugin.php',
				),
				'expect_push_times' => 1,
			),
			array(
				'plugins'           => array(
					'',
				),
				'expect_push_times' => 0,
			),
		);
	}

	/**
	 * WordPress fires `upgrader_process_complete` action when a plugin is updated, but can sometimes fire that with
	 * an empty plugin value. It should not push an event when the plugin array is empty.
	 *
	 * @see \Plugin_Upgrader::bulk_upgrade()
	 * @see https://core.trac.wordpress.org/ticket/61940
	 * @see https://jira.newfold.com/browse/PRESS1-427
	 *
	 * @dataProvider upgrader_process_complete_data_provider
	 *
	 * @covers ::installed_or_updated
	 * @covers ::updated
	 *
	 * @param array $plugins          The plugins value sent to the `upgrader_process_complete` action.
	 * @param int   $expect_push_times The number of times the `push` method should be called. I.e. 0 when there is no plugin.
	 */
	public function test_upgrader_process_complete_fired( array $plugins, int $expect_push_times ): void {

		/**
		 * It is difficult to mock the `Plugin_Upgrader` class, so we will just pass `null` for now.
		 *
		 * @see \WP_Upgrader
		 */
		$upgrader = null;

		$options = array(
			'action'  => 'update',
			'type'    => 'plugin',
			'bulk'    => true,
			'plugins' => $plugins,
		);

		$event_manager = Mockery::mock( EventManager::class );
		$event_manager->expects( 'push' )->times( $expect_push_times );

		$sut = new Plugin( $event_manager );

		/**
		 * This will only be called if the plugin is not empty, meaning we don't test with the current problematic
		 * return value.
		 *
		 * @see \NewfoldLabs\WP\Module\Data\Helpers\Plugin::collect()
		 */
		$plugin_collected = array(
			'slug'         => 'bluehost-wordpress-plugin',
			'version'      => '3.10.0',
			'title'        => 'The Bluehost Plugin',
			'url'          => 'https://bluehost.com',
			'active'       => false,
			'mu'           => false,
			'auto_updates' => true,
		);

		\Patchwork\redefine(
			array( \NewfoldLabs\WP\Module\Data\Helpers\Plugin::class, 'collect' ),
			function () use ( $plugin_collected ) {
					return $plugin_collected;
			}
		);

		/**
		 * The Event constructor calls a lot of WordPress functions to determine the environment.
		 *
		 * @see \NewfoldLabs\WP\Module\Data\Event::__construct()
		 */
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['REQUEST_URI'] = '/wp-admin/update.php?action=upgrade-plugin&plugin=bluehost-wordpress-plugin%2Fbluehost-wordpress-plugin.php&_wpnonce=1234567890';
		WP_Mock::userFunction( 'get_site_url' )->andReturn( 'http://localhost' );
		$wp_user                = \Mockery::mock( \WP_User::class );
		$wp_user->data          = new \stdClass();
		$wp_user->ID            = 1;
		$wp_user->user_nicename = 'admin';
		$wp_user->roles         = array( 'admin' );
		WP_Mock::userFunction( 'get_user_by' )->andReturn( $wp_user );
		WP_Mock::userFunction( 'get_current_user_id' )->andReturn( $wp_user->ID );
		WP_Mock::userFunction( 'get_user_locale' )->andReturn( 'en-US' );

		$sut->installed_or_updated( $upgrader, $options );

		$this->assertConditionsMet();
	}
}
