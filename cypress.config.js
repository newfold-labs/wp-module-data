const { defineConfig } = require( 'cypress' );
const { phpVersion, core } = require( './.wp-env.json' );
const wpVersion = /[^/]*$/.exec( core )[ 0 ];

module.exports = defineConfig( {
	// projectId: '71eo94',
	env: {
		baseUrl: process.env.BASE_URL || 'http://localhost:8889',
		wpUsername: process.env.WP_ADMIN_USERNAME || 'admin',
		wpPassword: process.env.WP_ADMIN_PASSWORD || 'password',
		wpVersion,
		phpVersion,
		pluginId: 'data-module-test-plugin', // used in selectors– hyphenated plugin name (title)
		// appId: 'wppbh',
		pluginSlug: 'wp-module-data-plugin',
	},
	downloadsFolder: 'tests/cypress/downloads',
	fixturesFolder: 'tests/cypress/fixtures',
	screenshotsFolder: 'tests/cypress/screenshots',
	video: true,
	videosFolder: 'tests/cypress/videos',
	chromeWebSecurity: false,
	viewportWidth: 1024,
	viewportHeight: 768,
	// # TODO: can we block everything except Hiive?!
	blockHosts: [
		'*doubleclick.net',
		'*jnn-pa.googleapis.com',
		'*youtube.com',
	],
	e2e: {
		setupNodeEvents( on, config ) {
			on( 'task', {
				log( message ) {
					// eslint-disable-next-line no-console
					console.log( message );
					return null;
				},
				table( message ) {
					// eslint-disable-next-line no-console
					console.table( message );
					return null;
				},
			} );

			// Ensure that the base URL is always properly set.
			if ( config.env && config.env.baseUrl ) {
				config.baseUrl = config.env.baseUrl;
			}

			// Ensure that we have a semantically correct WordPress version number for comparisons.
			if ( config.env.wpVersion ) {
				if ( config.env.wpVersion.split( '.' ).length !== 3 ) {
					config.env.wpSemverVersion = `${ config.env.wpVersion }.0`;
				} else {
					config.env.wpSemverVersion = config.env.wpVersion;
				}
			}

			// Ensure that we have a semantically correct PHP version number for comparisons.
			if ( config.env.phpVersion ) {
				if ( config.env.phpVersion.split( '.' ).length !== 3 ) {
					config.env.phpSemverVersion = `${ config.env.phpVersion }.0`;
				} else {
					config.env.phpSemverVersion = config.env.phpVersion;
				}
			}

			return config;
		},
		baseUrl: 'http://localhost:8889',
		specPattern: [
			'tests/cypress/module/**/*.cy.{js,jsx,ts,tsx}',
		],
		supportFile: 'tests/cypress/support/index.js',
		testIsolation: false,
		excludeSpecPattern: [
			'tests/cypress/wp-module-support/*.cy.js', // skip any module's wp-module-support files
		],
		experimentalRunAllSpecs: true,
	},
	retries: 0,
	experimentalMemoryManagement: true,
} );

// Check against plugin support at https://wordpress.org/plugins/woocommerce/
const supportsWoo = ( env ) => {
	const semver = require( 'semver' );
	if (
		semver.satisfies( env.wpSemverVersion, '>=6.6.0' ) &&
		semver.satisfies( env.phpSemverVersion, '>=7.4.0' )
	) {
		return true;
	}
	return false;
};
