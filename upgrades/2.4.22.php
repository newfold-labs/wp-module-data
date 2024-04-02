<?php

use NewfoldLabs\WP\ModuleLoader\Container;

add_action(
	'newfold_container_set',
	function ( Container $container ) {
		nfd_update_options_table( $container );
	}
);
