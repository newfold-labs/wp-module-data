<?php

namespace Endurance\WP\Module\Data\Listeners;

/**
 * Monitors generic plugin events
 */
class Plugin extends Listener {

	/**
	 * Register the hooks for the subscriber
	 *
	 * @return void
	 */
	public function register_hooks() {
		// Plugin search
		add_filter( 'plugins_api_args', array( $this, 'search' ), 10, 2 );

		// Plugin activated/deactivated
		add_action( 'activated_plugin', array( $this, 'activated' ), 10, 2 );
		add_action( 'deactivated_plugin', array( $this, 'deactivated' ), 10, 2 );
	}

	/**
	 * Install plugins search
	 *
	 * @param object $args   Plugin API arguments
	 * @param string $action Type of information being requested from the api
	 * @return object Plugin API arguments
	 */
	public function search( $args, $action ) {
		// Must be a non-null search
		if ( 'query_plugins' === $action && ! empty( $args->search ) ) {
			$this->push( 'plugin_search', array( 'term' => esc_attr( strtolower( $args->search ) ) ) );
		}

		return $args;
	}

	/**
	 * Plugin activated
	 *
	 * @param string  $plugin Name of the plugin
	 * @param boolean $network_wide Whether plugin was network activated
	 * @return void
	 */
	public function activated( $plugin, $network_wide ) {
		$data = array(
			'plugin'       => $plugin,
			'network_wide' => $network_wide,
		);
		$this->push( 'plugin_activated', $data );
	}

	/**
	 * Plugin deactivated
	 *
	 * @param string  $plugin Name of the plugin
	 * @param boolean $network_wide Whether plugin was network deactivated
	 * @return void
	 */
	public function deactivated( $plugin, $network_wide ) {
		$data = array(
			'plugin'       => $plugin,
			'network_wide' => $network_wide,
		);
		$this->push( 'plugin_deactivated', $data );
	}
}
