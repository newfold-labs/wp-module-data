<?php

/**
 * Resets mocks between each test case so the mocks in one do not unintentionally help pass another test.
 */
WP_Mock::activateStrictMode();
/**
 * Patchwork allows redefining PHP functions.
 *
 * @see patchwork.json
 * @see https://github.com/antecedent/patchwork
 */
WP_Mock::setUsePatchwork(true);
WP_Mock::bootstrap();

/**
 * Add functions so strict mode can be disabled on individual tests.
 */
/**
 * @see \FeatureContext::forceStrictModeOn();
 */
function forceWpMockStrictModeOn() {
	$property = new \ReflectionProperty( 'WP_Mock', '__strict_mode' );
	$property->setAccessible( true );
	$property->setValue( true );
}
/**
 * @see \FeatureContext::forceStrictModeOff();
 */
function forceWpMockStrictModeOff() {
	$property = new \ReflectionProperty( 'WP_Mock', '__strict_mode' );
	$property->setAccessible( true );
	$property->setValue( false );
}
