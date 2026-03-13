# Dependencies

## Runtime

| Package | Purpose |
|---------|---------|
| **ext-json** | JSON encoding/decoding for API and data handling. |
| **newfold-labs/wp-module-context** | Context (brand, platform) for API and behavior. |
| **newfold-labs/wp-module-loader** | Module registration and container; module is registered with the loader. |
| **wp-forge/helpers** | Helper functions (e.g. dataGet) used in Data and elsewhere. |
| **wp-forge/wp-query-builder** | Query building for database operations (e.g. event queue). |
| **wp-forge/wp-upgrade-handler** | Versioned upgrade runs in `upgrades/`; stores version in option `nfd_data_module_version`. |
| **wpscholar/url** | URL handling (e.g. for Hiive or API URLs). |

## Dev

- **johnpbloch/wordpress** – WordPress core for tests.
- **lucatume/wp-browser** – Codeception and WPUnit.
- **newfold-labs/wp-php-standards** – PHPCS.
- **phpunit/phpcov** – Coverage.
- **10up/wp_mock** – PHPUnit mocks.
- **kporras07/composer-symlinks** – Symlinks for wp-content/plugins/wp-module-data (dev convenience).
- Optional: **newfold-labs/wp-plugin-bluehost** (zip), **wpackagist-plugin/jetpack**, **woocommerce**, etc. for integration tests.
