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
		// Plugin activated/deactivated
		add_action( 'activated_plugin', array( $this, 'activated' ), 10, 2 );
		add_action( 'deactivated_plugin', array( $this, 'deactivated' ), 10, 2 );
		add_action( 'deleted_plugin', array( $this, 'deleted' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( $this, 'updated' ), 10, 2);
		
		// transient found - bh plugin was just activated, send that event
		if ( get_transient('bh_plugin_activated') ) {
			$this->activated( 
				'bluehost-wordpress-plugin/bluehost-wordpress-plugin.php', 
				false
			);
			delete_transient('bh_plugin_activated');
		}
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
			'plugins'      => $this->collect_plugin($plugin, true),
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
			'plugins'      => $this->collect_plugin($plugin, false),
		);
		$this->push( 'plugin_deactivated', $data );
	}

	/**
	 * Plugin deleted
	 *
	 * @param string  $plugin Name of the plugin
	 * @param boolean $deleted Whether the plugin deletion was successful
	 * @return void
	 */
	public function deleted( $plugin, $deleted ) {
		// abort if not successfully deleted
		if ( !$deleted ) {
			return;
		}

		$data = array(
			'plugin' => $plugin
		);
		$this->push( 'plugin_deleted', $data );
	}

	/**
	 * Plugin install or update completed
	 *
	 * @param string  $plugin Name of the plugin
	 * @param boolean $deleted Whether the plugin deletion was successful
	 * @return void
	 */
	public function updated( $upgrader_object, $options ) {
		// bail if not plugin install or update
		if ( 'plugin' !== $options['type'] ) {
			return;
		}
		// set event type
		if ( 'update' === $options['action'] ) {
			$event_key = 'plugin_updated';

			$plugins = [];
			if ( isset( $options['plugins'] ) && is_array( $options['plugins'] ) ) {
				foreach ( $options['plugins'] as $index => $pluginslug ) {
					$plugin = $this->collect_plugin( 
						$pluginslug, 
						is_plugin_active( $pluginslug ),
						false
					);
					array_push( $plugins, $plugin );
				}
			}
			$data = array(
				'plugins' => $plugins,
			);
		
		} elseif ('install' === $options['action'] ) {
			$event_key = 'plugin_installed';
			// get all plugins - slug not returned for install actions
			$plugins = $this->collect_plugins();
			$data = array(
				'plugins' => $plugins,
			);

		} else {
			return;
		}
		
		$this->push( $event_key, $data );
	}

	/**
	 * Prepare plugin data for single plugin 
	 * For when single plugin action occurs
	 * 
	 * @param string  $slug Name of the plugin
	 * @param boolean $active Whether the plugin is active
	 */
	public static function collect_plugin( $slug, $active, $wrapper=true ){
		
		$plugin = [];
		$pluginpath = WP_PLUGIN_DIR . '/' . $slug;

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require wp_normalize_path( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		// get data for this plugin
		$data = get_plugin_data( $pluginpath );
		
		// key/slug preparations
		$plugin['slug'] = $slug;
		// $plugin['path'] = $pluginpath;
		// grab needed data points
		$plugin['version'] = $data['Version'];
		$plugin['description'] = $data['Description'];
		$plugin['active'] = $active;
		if ( $wrapper ) {
			$plugins = [];
			array_push( $plugins, $plugin );
			return $plugins;
		} else {
			return $plugin;
		}

	}

	/**
	 * Prepare plugin data for all plugins
	 */
	public static function collect_plugins(){

		$datas = get_plugins();
		$mudatas = get_mu_plugins();
		$plugins = [];
		
		// process normal plugins
		foreach ( $datas as $key => $data ) {
			$plugin = [];
			// key/slug preparations
			$plugin['slug'] = $key;
			// grab needed data points
			$plugin['version'] = $data['Version'];
			$plugin['description'] = $data['Description'];
			$plugin['active'] = is_plugin_active( $key );

			array_push( $plugins, $plugin );
		}

		// process mu plugins
		foreach ( $mudatas as $key => $data ) {
			$plugin = [];
			// key/slug preparations
			$plugin['slug'] = $key;
			// grab needed data points
			$plugin['version'] = $data['Version'];
			$plugin['description'] = $data['Description'];
			$plugin['mu'] = true;
			$plugin['active'] = true;

			array_push( $plugins, $plugin );
		}
		
		return $plugins;
	}
}
