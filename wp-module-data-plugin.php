<?php
/**
 * Data Module Plugin
 *
 * @package           NewfoldLabs\WP\Module\Data
 * @author            Newfold Digital
 * @copyright         Copyright 2025 by Newfold Digital - All rights reserved.
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Data Module Test Plugin
 * Description:       Minimal plugin to activate and test module functionality.
 * Requires PHP:      7.3
 * Author:            Bluehost
 * Author URI:        https://bluehost.com
 * Text Domain:       wp-module-data
 * Domain Path:       /languages
 * License:           GPL 2.0 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace NewfoldLabs\WP\Module\Data;

use NewfoldLabs\WP\ModuleLoader\Container;
use NewfoldLabs\WP\ModuleLoader\Plugin;
use function NewfoldLabs\WP\ModuleLoader\container as setContainer;
use function NewfoldLabs\WP\Context\setContext;

require __DIR__ . '/vendor/autoload.php';

/**
 * Because `bootstrap.php` is in the `files` autoloader, when (integration) tests are run it is immediately loaded and
 * quickly returns because ABSPATH is not defined. Then later when WordPress is loaded for the tests, the autoloader
 * does not run again, so the code in bootstrap.php is not run and the module is not loaded.
 */
if ( ! defined( 'NFD_DATA_MODULE_VERSION' ) ) {
	require 'bootstrap.php';

	foreach ( glob( __DIR__ . '/vendor/newfold-labs/*/bootstrap.php' ) as $bootstrap ) {
		require $bootstrap;
	}
}

/*
 * Initialize module settings via container
 */
$nfd_module_container = new Container();

/**
 * Context setup
 *
 * @see vendor/newfold-labs/wp-module-context/bootstrap.php
 */
add_action(
	'newfold/context/set',
	function () {
		// set brand
		setContext( 'brand.name', 'wp-module-data' );
	}
);

// Set plugin to container
$nfd_module_container->set(
	'plugin',
	$nfd_module_container->service(
		function () {
			return new Plugin(
				array(
					'id'    => 'bluehost',
					'file'  => __FILE__,
					'brand' => get_option( 'mm_brand', 'bluehost' ),
				)
			);
		}
	)
);

setContainer( $nfd_module_container );
