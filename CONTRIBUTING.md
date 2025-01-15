# Contributing

## Requirements

PHP, NPM, Composer, xdebug

## Environment

The local development server uses the wp-env default ports `8888` and `8889` for HTTP, and `62088` to expose MySQL for tests.

```bash
composer install
npm install
npx wp-env start --xdebug
```

It can be accessed using:

http://localhost:8888 `admin` `password`

### PHP Version

The repo's PHP version should be set to [the minimum required by the Bluehost WordPress plugin](https://github.com/newfold-labs/wp-plugin-bluehost/blob/main/bluehost-wordpress-plugin.php#L17), currently PHP 7.3. This is not high enough for latest WooCommerce. Use a `.wp-env.override` file to change wp-env PHP version as needed:

`.wp-env.override.json`
```json
{
  "php": "7.4"
}
```

## Scripts

Run `composer list` (all) or `jq '."scripts-descriptions"' composer.json` (our custom defined) to display the list of scripts:

<!-- TODO: autogenerate this table with a GitHub Actions workflow -->

```json
{
  "create-symlinks": "Create symlinks between /wordpress, /wp-content and the root plugin for convenience.",
  "cs-fix": "Automatically fix coding standards issues where possible.",
  "cs-fix-changes": "Filter PHP files those with uncommitted changes and automatically fix coding standards issues where possible.",
  "cs-fix-diff": "Filter PHP files to those modified since branching from main and automatically fix coding standards issues where possible.",
  "test": "Run tests.",
  "test-coverage": "Run PHPUnit tests with coverage. Use 'XDEBUG_MODE=coverage composer test-coverage' to run, 'open ./tests/_output/html/index.html' to view."
}
```

And e.g. `composer cs-fix-diff` to run a script.

## IDE

For code completion and reference, Composer installs a copy of WordPress in the `wordpress` directory. Plugins relevant to features developed in this repo are installed in `wp-content/plugins` via [WPackagist](https://wpackagist.org/), e.g.:

`composer require --dev wpackagist-plugin/woocommerce-payments`

The `.editorconfig` file is used to configure coding standards of the project in the IDE.

## Xdebug

Xdebug is required for code coverage and debug breakpoints.

* [PhpStorm documentation](https://www.jetbrains.com/help/phpstorm/configuring-xdebug.html)
* [wp-env VS Code configuration](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#xdebug-ide-support)
* [Firefox extension](https://addons.mozilla.org/en-US/firefox/addon/xdebug-helper-for-firefox/)
* [Chrome extension](https://chromewebstore.google.com/detail/xdebug-chrome-extension/oiofkammbajfehgpleginfomeppgnglk)

## Testing

PHPUnit tests use the [WP Mock](https://github.com/10up/wp_mock) and [WP-Browser](https://github.com/lucatume/wp-browser) ([documentation](https://wpbrowser.wptestkit.dev/)) libraries. [Patchwork](https://github.com/antecedent/patchwork) ([documentation](https://antecedent.github.io/patchwork/)) is used in conjunction with WP Mock to redefine internal PHP functions, e.g. to specify a return value for `time()`.

| file/directory   |                               |                                                                                                                                                                                                                 |
|------------------|-------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/_output`  |                               | Code coverage reports                                                                                                                                                                                           |
| `tests/_support` |                               | Fixtures (sample test data) and helper classes                                                                                                                                                                  |
| `tests/phpunit`  | WP_Mock tests                 | WP_Mock is used when testing individual functions without loading WordPress                                                                                                                                     |
| `tests/wpunit`   | WP_Browser tests              | WP Browser loads WordPress and can be used to test the plugin in a more realistic environment. The "wpunit" tests do not activate the plugin, so tests are still only testing the classes that are instantiated |
| `.env.testing`   | WP Browser configuration file |                                                                                                                                                                                                                 |
| `patchwork.json` | Patchwork configuration file  | List of PHP functions that can be redefined in tests                                                                                                                                                            |

Run `composer test` to run both WP Mock and WP Browser tests.

Run `XDEBUG_MODE=coverage composer test-coverage` to output a code coverage report, which can be viewed in a browser at `tests/_output/html/index.html`.

## Linting

Run `composer fix-changes` to automatically fix coding standards issues in PHP files with uncommitted changes. This runs `phpcbf` then `phpcs` using the Newfold coding standard.

<!-- TODO:  ## GitHub Actions -->

