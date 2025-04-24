<?php
/**
 * Runs after WordPress has been initialised (after plugins are loaded) and before tests are run.
 */

activate_plugin( 'wp-module-data/wp-module-data-plugin.php' );
require codecept_root_dir( 'bootstrap.php' );
