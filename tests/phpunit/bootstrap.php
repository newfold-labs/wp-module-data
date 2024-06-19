<?php

/**
 * I was having trouble with {@see WP_Mock::expectAction()} so mocked it here. Because we're using PHP 7.1, we're
 * not on the latest version of WP_Mock, which requires 7.4.
 */
function do_action(...$args) {}
function apply_filters(...$args) { return $args[1]; }

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
