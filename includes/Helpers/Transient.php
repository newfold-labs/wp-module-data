<?php
namespace NewfoldLabs\WP\Module\Data\Helpers;

/**
 * Custom Transient class to handle an Options API based fallback
 */
class Transient {

	/**
	 * Whether to use transients to store temporary data
	 *
	 * If the site has an object-cache.php drop-in, then we can't reliably
	 * use the transients API. We'll try to fall back to the options API.
	 */
	protected static function should_use_transients(): bool {
		require_once constant( 'ABSPATH' ) . '/wp-admin/includes/plugin.php';
		return ! array_key_exists( 'object-cache.php', get_dropins() );
	}

	/**
	 * Wrapper for get_transient() with Options API fallback
	 *
	 * @see \get_transient()
	 *
	 * @param string $key The key of the transient to retrieve
	 * @return mixed The value of the transient
	 */
	public static function get( string $key ) {
		if ( self::should_use_transients() ) {
			return \get_transient( $key );
		}

		/**
		 * @var array{value:mixed, expires_at:int} $data The saved value and the Unix time it expires at.
		 */
		$data = get_option( $key );
		if ( is_array( $data ) && isset( $data['expires_at'], $data['value'] ) ) {
			if ( $data['expires_at'] > time() ) {
				return $data['value'];
			} else {
				\delete_option( $key );
			}
		}

		return false;
	}

	/**
	 * Wrapper for set_transient() with Options API fallback
	 *
	 * @see \set_transient()
	 * @see \update_option()
	 *
	 * @param string  $key        Key to use for storing the transient
	 * @param mixed   $value      Value to be saved
	 * @param integer $expires_in Optional expiration time in seconds from now. Default is 1 hour
	 *
	 * @return bool Whether the value was saved
	 */
	public static function set( string $key, $value, int $expires_in = 3600 ): bool {
		if ( self::should_use_transients() ) {
			return \set_transient( $key, $value, $expires_in );
		}

		$data = array(
			'value'      => $value,
			'expires_at' => $expires_in + time(),
		);
		return \update_option( $key, $data, false );
	}

	/**
	 * Wrapper for delete_transient() with Options API fallback
	 *
	 * @see \delete_transient()
	 * @see \delete_option()
	 *
	 * @param string $key The key of the transient/option to delete
	 * @return bool Whether the value was deleted
	 */
	public static function delete( $key ): bool {
		if ( self::should_use_transients() ) {
			return \delete_transient( $key );
		}

		return \delete_option( $key );
	}
}
