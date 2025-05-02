<?php

namespace NewfoldLabs\WP\Module\Data;

class ActivateTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Repeated activations were failing because the database table was being repeatedly created.
	 */
	public function testModulePluginIsActivated() {

		deactivate_plugins( 'wp-module-data/wp-module-data-plugin.php' );

		activate_plugin( 'wp-module-data/wp-module-data-plugin.php' );

		$this->assertTrue( is_plugin_active( 'wp-module-data/wp-module-data-plugin.php' ) );
	}
}
