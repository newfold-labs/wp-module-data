<?php

use NewfoldLabs\WP\Module\Data\Data;
use NewfoldLabs\WP\Module\Data\Helpers\Transient;
use function NewfoldLabs\WP\ModuleLoader\container;

// Define constants
// Do not allow multiple copies of the module to be active
if ( defined( 'NFD_DATA_MODULE_VERSION' ) ) {
	exit;
} else {
	define( 'NFD_DATA_MODULE_VERSION', '1.8.3' );
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'after_setup_theme', 'eig_module_data_register' );
}

/**
 * Register the data module
 */
function eig_module_data_register() {
	eig_register_module(
		array(
			'name'     => 'data',
			'label'    => __( 'Data', 'newfold-data-module' ),
			'callback' => 'eig_module_data_load',
			'isActive' => true,
			'isHidden' => true,
		)
	);
}

/**
 * Load the data module
 */
function eig_module_data_load() {
	$module = new Data();
	$module->start();
}

/**
 * Register activation hook outside init so it will fire on activation.
 */
function nfd_plugin_activate() {
	Transient::set( 'nfd_plugin_activated', container()->plugin()->basename );
}

if ( function_exists( 'register_activation_hook' ) ) {
	register_activation_hook(
		container()->plugin()->basename,
		'nfd_plugin_activate'
	);
}
