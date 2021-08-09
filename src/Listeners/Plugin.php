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
		if ( get_transient( 'bh_plugin_activated' ) ) {
			$this->activated( 
				'bluehost-wordpress-plugin/bluehost-wordpress-plugin.php', 
				false
			);
			delete_transient( 'bh_plugin_activated' );
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
			'plugins'      => $this->collect_plugin( $plugin, true ),
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
			'plugins'      => $this->collect_plugin( $plugin, false ),
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
		
		$slug = self::glean_plugin_slugname( $plugin );
		$data = array(
			'plugin' => $slug,
		);
		$this->push( 'plugin_deleted', $data );
	}

	/**
	 * Plugin install or update completed
	 *
	 * @param string  $upgrader_object Upgrader Object from upgrade hook
	 * @param boolean $options Options from upgrade hook including type, action & plugins.
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
			$data = array(
				'plugins' => $this->collect_plugins(),
			);

		} else {
			return;
		}
		
		$this->push( $event_key, $data );
	}

	/**
	 * Grab relevant data from plugin data - and only what we want
	 * @param Array $data The plugin meta data from the header
	 * @return Array Hiive relevant plugin details
	 */
	public static function glean_plugin_data( $data ){
		$plugin = [];
		$plugin['version'] = $data['Version'];
		$plugin['title'] = $data['Name'] ? $data['Name'] : '';
		$plugin['url'] = $data['PluginURI'] ? $data['PluginURI'] : '';
		return $plugin;
	}

	/**
	 * Get plugin dir name or main file if no dir. 
	 * @param string  $slug Relative path to plugin file
	 * @return string slug for plugin
	 */
	public static function glean_plugin_slugname( $slug ){
		$newslug = '';
		// if has `/` split on `/` and get second to last index.
		if ( str_contains( $slug, '/' ) ) {
			$parts = explode( '/', $slug );
			end( $parts );
			$newslug = prev( $parts );
		} else {
			// if not, split on . and get second to last index.
			$parts = explode( '.', $slug );
			end( $parts );
			$newslug = prev( $parts );
		}
		return $newslug;
	}

	/**
	 * Does this plugin autoupdate?
	 * @param string  $slug plugin identifier
	 * @return boolean Whether plugin is configured to auto-update
	 */
	public static function does_it_autoupdate( $slug ){
		// check bluehost plugin setting for auto updates on all plugins
		$bh_auto_updates = get_site_option( 'auto_update_plugin', 'true' );
		if ( 'true' === $bh_auto_updates ) {
			return true;
		}

		// check core setting for auto updates on this plugin
		$wp_auto_updates = (array) get_site_option( 'auto_update_plugins', array() );
		if ( in_array( $slug, $wp_auto_updates, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Prepare plugin data for single plugin 
	 * For when single plugin action occurs
	 * 
	 * @param string  $slug Name of the plugin
	 * @param boolean $active Whether the plugin is active
	 * @param boolean $wrapper Whether to include a plugins wrapper array
	 * @return Array of data for plugin 
	 */
	public static function collect_plugin( $slug, $active, $wrapper=true ){
		
		$plugin = [];
		$pluginpath = WP_PLUGIN_DIR . '/' . $slug;

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require wp_normalize_path( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		// get data for this plugin
		$plugin = self::glean_plugin_data( get_plugin_data( $pluginpath ) ); 
		// key/slug preparations
		$plugin['slug'] = self::glean_plugin_slugname( $slug );
		// set other needed data points
		if ( $active ) {
			$plugin['active'] = true;
		}
		if ( self::does_it_autoupdate( $slug ) ) {
			$plugin['au'] = true;
		}

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
	 * @return Array of plugins
	 */
	public static function collect_plugins(){

		if ( ! function_exists( 'get_plugins' ) ) {
			require wp_normalize_path( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		$datas = get_plugins();
		$mudatas = get_mu_plugins();
		$plugins = [];
		
		// process normal plugins
		foreach ( $datas as $key => $data ) {
			$plugin = self::glean_plugin_data( $data ); 
			// key/slug preparations
			$plugin['slug'] = self::glean_plugin_slugname( $key );
			// set additional needed data points
			if ( is_plugin_active( $key ) ) {
				$plugin['active'] = true;
			}
			if ( self::does_it_autoupdate( $key ) ) {
				$plugin['au'] = true;
			}

			array_push( $plugins, $plugin );
		}

		// process mu plugins
		foreach ( $mudatas as $key => $data ) {
			$plugin = self::glean_plugin_data( $data ); 
			// key/slug preparations
			$plugin['slug'] = self::glean_plugin_slugname( $key );
			// set additional needed data points
			$plugin['mu'] = true;
			$plugin['active'] = true;

			array_push( $plugins, $plugin );
		}
		
		return $plugins;
	}

}
