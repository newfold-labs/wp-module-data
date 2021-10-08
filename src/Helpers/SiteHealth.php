<?php

namespace Endurance\WP\Module\Data\Helpers;

/**
 * Helper class for gathering and formatting Site Health data
 */
class SiteHealth {
	public function __construct() {
		if ( ! class_exists( 'WP_Debug_Data' ) ) {
			require wp_normalize_path( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
	}

	public static function get_data() {
		return WP_Debug_Data::debug_data();
	}

	public function get_safe_data() {
		return WP_Debug_Data::format( self::get_data(), 'debug' );
	}

	public static function calculate_score( $results ) {
		if ( ! is_array( $results ) ) {
			$results = json_decode( $results, true );
		}

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
