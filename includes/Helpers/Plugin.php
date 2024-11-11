<?php

namespace NewfoldLabs\WP\Module\Data\Helpers;

/**
 * Helper class for gathering and formatting plugin data
 */
class Plugin {
	/**
	 * Prepare plugin data for a single plugin
	 *
	 * @param string $basename The plugin basename (filename relative to WP_PLUGINS_DIR).
	 *
	 * @return array{slug:string, version:string, title:string, url:string, active:bool, mu:bool, auto_updates:bool} Hiive relevant plugin details
	 */
	public static function collect( $basename ) {

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require wp_normalize_path( constant( 'ABSPATH' ) . '/wp-admin/includes/plugin.php' );
		}

		return self::get_data( $basename, get_plugin_data( constant( 'WP_PLUGIN_DIR' ) . '/' . $basename ) );
	}

	/**
	 * Prepare plugin data for all plugins
	 *
	 * @return array of plugins
	 */
	public static function collect_installed() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require wp_normalize_path( constant( 'ABSPATH' ) . '/wp-admin/includes/plugin.php' );
		}

		$plugins = array();

		// Collect standard plugins
		foreach ( get_plugins() as $slug => $data ) {
			array_push( $plugins, self::get_data( $slug, $data ) );
		}

		// Collect mu plugins
		foreach ( get_mu_plugins() as $slug => $data ) {
			array_push( $plugins, self::get_data( $slug, $data, true ) );
		}

		return $plugins;
	}

	/**
	 * Grab relevant data from plugin data - and only what we want
	 *
	 * @param string $basename The plugin basename (filename relative to WP_PLUGINS_DIR).
	 * @param array  $data The plugin meta-data from its header.
	 * @param bool   $mu   Whether the plugin is installed as a must-use plugin.
	 *
	 * @return array{slug:string, version:string, title:string, url:string, active:bool, mu:bool, auto_updates:bool} Hiive relevant plugin details
	 */
	public static function get_data( $basename, $data, $mu = false ) {
		$plugin                 = array();
		$plugin['slug']         = $basename;
		$plugin['version']      = $data['Version'] ? $data['Version'] : '0.0';
		$plugin['title']        = $data['Name'] ? $data['Name'] : '';
		$plugin['url']          = $data['PluginURI'] ? $data['PluginURI'] : '';
		$plugin['active']       = is_plugin_active( $basename );
		$plugin['mu']           = $mu;
		$plugin['auto_updates'] = ( ! $mu && self::does_it_autoupdate( $basename ) );

		if ( strpos( $basename, 'jetpack' ) !== false ) {
			$plugin['users'] = self::get_admin_users();
		}

		return $plugin;
	}

	/**
	 * Whether the plugin is set to auto update
	 *
	 * @param string $slug Name of the plugin
	 *
	 * @return bool
	 */
	public static function does_it_autoupdate( $slug ) {
		// Check plugin setting for auto updates on all plugins
		if ( get_site_option( 'auto_update_plugin', 'true' ) ) {
			return true;
		}

		// check core setting for auto updates on this plugin
		$wp_auto_updates = (array) get_site_option( 'auto_update_plugins', array() );

		return in_array( $slug, $wp_auto_updates, true );
	}

	/**
	 * Get Admin and SuperAdmin user accounts
	 *
	 * @return $users Array of Admin & Super Admin users
	 */
	private static function get_admin_users() {
		// Get all admin users
		$admin_users = get_users(
			array(
				'role' => 'administrator',
			)
		);
		$users       = array();

		// Add administrators to the $users and check for super admin
		foreach ( $admin_users as $user ) {
			$users[] = array(
				'id'          => $user->ID,
				'username'    => $user->user_login,
				'email'       => $user->user_email,
				'name'        => $user->display_name,
				'roles'       => $user->roles,
				'super_admin' => is_super_admin( $user->ID ),
			);
		}

		return $users;
	}
}
