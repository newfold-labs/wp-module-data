<?php

use NewfoldLabs\WP\ModuleLoader\Container;
use NewfoldLabs\WP\Module\Data\Data;
use NewfoldLabs\WP\Module\Data\Helpers\Transient;

use function NewfoldLabs\WP\ModuleLoader\register as registerModule;
use function NewfoldLabs\WP\ModuleLoader\container as moduleContainer;

// Define constants
// Do not allow multiple copies of the module to be active
if ( defined( 'NFD_DATA_MODULE_VERSION' ) ) {
	exit;
} else {
	define( 'NFD_DATA_MODULE_VERSION', '2.0.0' );

	/**
	 * Register the data module
	 */
	if ( function_exists( 'add_action' ) ) {

		add_action(
			'plugins_loaded',
			function () {

				registerModule(
					array(
						'name'     => 'data',
						'label'    => __( 'Data', 'newfold-data-module' ),
						'callback' => function ( Container $container ) {
							$module = new Data( $container );
							$module->start();
						},
						'isActive' => true,
						'isHidden' => true,
					)
				);

			}
		);

	}

	/**
	 * Register activation hook (outside init so it will fire on activation).
	 */
	function nfd_plugin_activate() {
		if ( function_exists( 'moduleContainer' ) ) {
			Transient::set( 'nfd_plugin_activated', container()->plugin()->basename );
		}
	}

	if ( function_exists( 'register_activation_hook' ) && function_exists( 'moduleContainer' ) ) {
		register_activation_hook(
			container()->plugin()->basename,
			'nfd_plugin_activate'
		);
	}

}