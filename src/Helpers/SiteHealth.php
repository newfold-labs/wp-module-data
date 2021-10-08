<?php

namespace Endurance\WP\Module\Data\Helpers;

/**
 * Helper class for gathering and formatting Site Health data
 *
 * @since 1.6
 */
class SiteHealth {

	/**
	 * Site Health debug data
	 *
	 * @since 1.6
	 *
	 * @var array
	 */
	private static $debug_data;

	/**
	 * Ensures the needed WordPress classes are available.
	 *
	 * @since 1.6
	 */
	public function __construct() {
		if ( ! class_exists( 'WP_Debug_Data' ) ) {
			require wp_normalize_path( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
	}

	/**
	 * Retrieves a site's debug data through Site health.
	 *
	 * @since 1.6
	 *
	 * @return array The site's debug data.
	 */
	public static function get_data() {
		if ( ! empty( self::$debug_data ) ) {
			return self::$debug_data;
		}

		self::$debug_data = WP_Debug_Data::debug_data();

		return self::$debug_data;
	}

	/**
	 * Retrieves the debug data for a site that is safe for sharing.
	 *
	 * Any data marked `private` in Site Health (database user, for example) will not be included in this list.
	 *
	 * @since 1.6
	 *
	 * @return array List of Site Health debug data.
	 */
	public static function get_safe_data() {
		if ( empty( self::$debug_data ) ) {
			self::get_data();
		}

		return WP_Debug_Data::format( self::$debug_data, 'debug' );
	}

	/**
	 * Calculates the Site Health score for a site.
	 *
	 * The score is the number of successful tests (good) divided by the total number of tests.
	 *
	 * @since 1.6
	 *
	 * @param string $results A JSON encoded string of Site Health test results.
	 *                        This will usually be the value of the `health-check-site-status-result` transient
	 *                        in WordPress Core.
	 * @return int Site Health score.
	 */
	public static function calculate_score( $results ) {
		$results = json_decode( $results, true );

		$total_tests = array_reduce( $results, function( $total, $item ) {
			return $total += (int) $item;
		});

		// Report a -1 when there are no Site Health tests
		if ( 0 >= $total_tests ) {
			return -1;
		}

		update_option( 'jons-test-1-score', round( (int) $results['good'] / $total_tests ) );
		update_option( 'jons-test-3-tests', $total_tests );

		return round( (int) $results['good'] / $total_tests );
	}

}
