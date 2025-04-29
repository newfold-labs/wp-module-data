<?php

namespace NewfoldLabs\WP\Module\Data;

class SmokeTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Test that the module is activated and the plugin is loaded.
	 */
	public function testModulePluginIsActivated() {
		$this->assertTrue( is_plugin_active( 'wp-module-data/wp-module-data-plugin.php' ) );
	}
}
