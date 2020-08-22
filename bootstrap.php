<?php

if ( function_exists( 'add_action' ) ) {
	add_action( 'after_setup_theme', 'eig_module_data_register' );
}

/**
 * Register the data module
 */
function eig_module_data_register() {
	eig_register_module( array(
		'name'     => 'data',
		'label'    => __( 'Data', 'endurance' ),
		'callback' => 'eig_module_data_load',
		'isActive' => true,
	) );
}

/**
 * Load the data module
 */
function eig_module_data_load() {
	require dirname( __FILE__ ) . '/.php';
}
