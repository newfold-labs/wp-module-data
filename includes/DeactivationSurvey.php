<?php

namespace NewfoldLabs\WP\Module\Data;

use function NewfoldLabs\WP\ModuleLoader\container;

/**
 * Class DeactivationSurvey
 *
 * Brand plugins deactivation survey modal.
 *
 * @package NewfoldLabs\WP\Module\Data
 */
class DeactivationSurvey {

    public function __construct() {
        $this->deactivation_survey_assets();
        $this->deactivation_survey_runtime();
    }

    public function deactivation_survey_assets() {
        $assetsDir = container()->plugin()->url . 'vendor/newfold-labs/wp-module-data/includes/assets/';

        // accessible a11y dialog
        wp_register_script(
            'nfd-data-a11y-dialog',
            $assetsDir . 'js/a11y-dialog.min.js',
            array(),
            '8.0.4',
        );

        // deactivation-survey.js
        wp_enqueue_script(
            'nfd-data-deactivation-survey',
            $assetsDir . 'js/deactivation-survey.js',
            array( 'nfd-data-a11y-dialog' ),
            container()->plugin()->version,
            true
        );

        // Styles
		wp_enqueue_style(
			'nfd-data-deactivation-survey-style',
			$assetsDir . 'css/deactivation-survey.css',
			array(),
			container()->plugin()->version
		);
    }

    public function deactivation_survey_runtime() {
        $plugin_slug = explode( '/', container()->plugin()->basename )[0];

        wp_localize_script(
			'nfd-data-deactivation-survey',
			'newfoldDataDeactivationSurvey',
			array(
				'restApiRoot'  => \get_home_url() . '/index.php?rest_route=',
				'restApiNonce' => wp_create_nonce( 'wp_rest' ),
                'brand'        => ucwords( container()->plugin()->id ),
				'pluginSlug'   => $plugin_slug,
                'strings'      => array(
                    'surveyTitle'     => __( 'Plugin Deactivation Survey', $plugin_slug ),
                    'dialogTitle'     => __( 'Thank You for Using the ' . ucwords( container()->plugin()->id ) . ' Plugin!', $plugin_slug ),
                    'dialogDesc'      => __( 'Please take a moment to let us know why you\'re deactivating this plugin.', $plugin_slug ),
                    'formAriaLabel'   => __( 'Plugin Deactivation Form', $plugin_slug ),
                    'label'           => __( 'Why are you deactivating this plugin?', $plugin_slug ),
                    'placeholder'     => __( 'Please share the reason here...', $plugin_slug ),
                    'submit'          => __( 'Submit & Deactivate', $plugin_slug ),
                    'submitAriaLabel' => __( 'Submit and Deactivate Plugin', $plugin_slug ),
                    'cancel'          => __( 'Cancel', $plugin_slug ),
                    'cancelAriaLabel' => __( 'Cancel Deactivation', $plugin_slug ),
                    'skip'            => __( 'Skip & Deactivate', $plugin_slug ),
                    'skipAriaLabel'   => __( 'Skip and Deactivate Plugin', $plugin_slug ),
                )
			)
		);
    }

}
