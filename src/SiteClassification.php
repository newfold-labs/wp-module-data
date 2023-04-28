<?php

namespace NewfoldLabs\WP\Module\Data;


class SiteClassification {

	public function get() {
		$classification = get_transient( 'nfd_site_classification' );
		if ( false === $classification ) {
			$classification = $this->fetch();
			set_transient( 'nfd_site_classification', $classification, DAY_IN_SECONDS );
		}

		return $classification;
	}

	public function fetch() {
		$classification = array();

		$response = wp_remote_get(
			NFD_HIIVE_BASE_URL . '/workers/site-classification',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
				),
			)
		);

		if ( ! is_wp_error( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			if ( $data && is_array( $data ) ) {
				$classification = $data;
			}
		}

		return $classification;
	}

}
