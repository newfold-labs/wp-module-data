<?php

namespace NewfoldLabs\WP\Module\Data;

class ActivateTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	protected function setUp(): void {
		parent::setUp();

		add_filter( 'query', array( $this, 'remove_temporary_from_query' ) );
	}

	protected function tearDown(): void {

		remove_filter( 'query', array( $this, 'remove_temporary_from_query' ) );

		parent::tearDown();
	}

	/**
	 * WordPress PHPUnit tests are run with the `TEMPORARY` keyword in the query.
	 *
	 * @param string $query The SQL query about to be executed.
	 */
	public function remove_temporary_from_query( $query ): string {
		return str_replace( ' TEMPORARY ', ' ', $query );
	}

	/**
	 * Activation was failing because `function_exists()` was used in `bootstrap.php` around a function that was
	 * defined lower in the file than the code that called it. This was fixed by moving the function to the top of
	 * the file. Unfortunately, this test was not able to reproduce that error, presumably because of the PHPUnit
	 * / Codeception / Composer boostrap already loading the file. It is tested by Cypress.
	 *
	 * @see tests/cypress/module/activation.cy.js
	 */
	public function testModulePluginIsActivated(): void {

		deactivate_plugins( 'wp-module-data/wp-module-data-plugin.php' );

		activate_plugin( 'wp-module-data/wp-module-data-plugin.php' );

		$this->assertTrue( is_plugin_active( 'wp-module-data/wp-module-data-plugin.php' ) );
	}

	public function testRegisterActivationHook(): void {

		$file                   = 'wp-module-data/wp-module-data-plugin.php';
		$activation_action_name = "activate_$file";

		$this->assertTrue( has_action( $activation_action_name ) );
	}

	public function testRegisterDeactivationHook(): void {

		$file                     = 'wp-module-data/wp-module-data-plugin.php';
		$deactivation_action_name = "deactivate_$file";

		$this->assertTrue( has_action( $deactivation_action_name ) );
	}

	public function testCreatesTableOnActivation(): void {

		deactivate_plugins( 'wp-module-data/wp-module-data-plugin.php' );

		$this->deleteTable( 'nfd_data_event_queue' );

		activate_plugin( 'wp-module-data/wp-module-data-plugin.php' );

		$this->assertTableExists( 'nfd_data_event_queue' );
	}

	public function testDeletesTableOnDeactivation(): void {

		activate_plugin( 'wp-module-data/wp-module-data-plugin.php' );

		$this->assertTableExists( 'nfd_data_event_queue' );

		deactivate_plugins( 'wp-module-data/wp-module-data-plugin.php' );

		$this->assertTableDoesNotExist( 'nfd_data_event_queue' );
	}

	protected function deleteTable( $unprefixed_table_name ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				'DROP TABLE IF EXISTS %i;',
				$wpdb->prefix . $unprefixed_table_name,
			)
		);

		$this->assertEmpty( $wpdb->last_error, $wpdb->last_error . ' : ' . $wpdb->last_query );

		$this->assertTableDoesNotExist( $unprefixed_table_name );
	}

	protected function assertTableExists( $unprefixed_table_name ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s;',
				$wpdb->prefix . $unprefixed_table_name,
			)
		);

		$this->assertEmpty( $wpdb->last_error, $wpdb->last_error . ' : ' . $wpdb->last_query );

		$this->assertNotEmpty( $results, "Table $unprefixed_table_name does not exist" );
	}

	protected function assertTableDoesNotExist( $unprefixed_table_name ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s;',
				$wpdb->prefix . $unprefixed_table_name,
			)
		);

		$this->assertEmpty( $wpdb->last_error, $wpdb->last_error . ' : ' . $wpdb->last_query );

		$this->assertEmpty( $results, "Table $unprefixed_table_name should not exist" );
	}
}
