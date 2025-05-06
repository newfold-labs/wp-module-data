// <reference types="Cypress" />
import { wpLogin } from '../wp-module-support/utils.cy';

describe( 'Plugin Activation Tests', { testIsolation: true }, () => {

	before( () => {
		// define commands for repetitive commands
		Cypress.Commands.add( 'maybeDeactivatePlugin', () => {

			cy.get( 'body' ).then( ( $body ) => {
				// check if deactivate link is present
				if (
					$body.find(
						'.deactivate a[id*="deactivate-' + Cypress.env( 'pluginId' ) + '"]'
					).length
				) {
					cy.log('Clicking Deactivate link');
					cy.get(
						'.deactivate a[id*="deactivate-' + Cypress.env('pluginId') + '"]'
					).click();
				}
				// verify plugin is deactivated
				cy.verifyPluginDeactivated();
			} );
		} );

		Cypress.Commands.add( 'verifyPluginDeactivated', () => {
			cy.log( 'Verifying Plugin is Not Active' );
			cy.visit( '/wp-admin/plugins.php' );
			cy.reload( true );
			cy.get(
				'.deactivate a[id*="deactivate-' + Cypress.env( 'pluginId' ) + '"]'
			).should( 'not.exist' );
			cy.get(
				'.activate a[id*="activate-' + Cypress.env( 'pluginId' ) + '"]'
			).should( 'exist' );
		} );

		Cypress.Commands.add( 'activatePlugin', () => {
			cy.log( 'Clicking Activate Link to Activate Plugin' );
			cy.get( 'body' ).then( ( $body ) => {
				// check if activate link is present
				if (
					$body.find(
						'.activate a[id*="activate-' + Cypress.env( 'pluginId' ) + '"]'
					).length
				) {
					cy.get(
						'.activate a[id*="activate-' + Cypress.env( 'pluginId' ) + '"]'
					).click();
				}
			} );
		} );

		Cypress.Commands.add( 'verifyPluginActive', () => {
			cy.log( 'Verifying Plugin is Active' );
			cy.get(
				'.deactivate a[id*="deactivate-' + Cypress.env( 'pluginId' ) + '"]'
			).should( 'exist' );
			cy.get(
				'.activate a[id*="activate-' + Cypress.env( 'pluginId' ) + '"]'
			).should( 'not.exist' );
		} );
	} );

	beforeEach( () => {
		wpLogin();
		cy.visit( '/wp-admin/plugins.php', {
			onLoad() {
				cy.window().then( ( win ) => {
					// load module data into var
				} );
			},
		} );

	} );

	// After adding `function_exists()` around some `bootstrap.php` code, the plugin could no longer be activated
	it( 'Can be activated', () => {

		// deactivate plugin
		cy.maybeDeactivatePlugin();

		// activate plugin
		cy.activatePlugin();

		// Check error message does not appear
		// "Plugin could not be activated because it triggered a fatal error."
		cy.findByText("fatal error").should('not.exist');

		// verify plugin is activated
		cy.verifyPluginActive();
	} );

} );
