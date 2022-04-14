<?php

use NewfoldLabs\WP\Module\Data\Data;
use NewfoldLabs\WP\Module\Data\Helpers\Encryption;
use NewfoldLabs\WP\Module\Data\Helpers\Transient;

use function NewfoldLabs\WP\ModuleLoader\register as registerModule;
use function NewfoldLabs\WP\ModuleLoader\container;

// Do not allow multiple copies of the module to be active
if ( defined( 'NFD_DATA_MODULE_VERSION' ) ) {
	exit;
}

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
					'callback' => function () {
						$module = new Data();
						$module->start();
					},
					'isActive' => true,
					'isHidden' => true,
				)
			);

		}
	);
}

if ( function_exists( 'add_filter' ) ) {

	// Auto-encrypt token on save.
	add_filter(
		'pre_update_option_nfd_data_token',
		function ( $value ) {
			$encryption = new Encryption();

			return $encryption->encrypt( $value );
		}
	);

}

// Register activation hook (outside init so it will fire on activation).
if ( function_exists( 'register_activation_hook' ) ) {

	register_activation_hook(
		container()->plugin()->basename,
		function () {
			Transient::set( 'nfd_plugin_activated', container()->plugin()->basename );
		}
	);

}
