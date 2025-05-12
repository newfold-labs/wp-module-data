[![PHPUnit ](https://newfold-labs.github.io/wp-module-data/phpunit/coverage.svg)](https://newfold-labs.github.io/wp-module-data/phpunit/html)

<a href="https://newfold.com/" target="_blank">
  <img src="https://newfold.com/content/experience-fragments/newfold/site-header/master/_jcr_content/root/header/logo.coreimg.svg/1621395071423/newfold-digital.svg" alt="Newfold Logo" title="Newfold Digital" align="right" height="42" />
</a>


# WordPress Data Module
 
Connects a WordPress site to Newfold systems to provide basic features and metrics.
 
 ## Installation
 
 ### 1. Add the Newfold Satis to your `composer.json`.
 
  ```bash
 composer config repositories.newfold composer https://newfold-labs.github.io/satis
 ```
 
 ### 2. Require the `newfold-labs/wp-module-data` package.
 
 ```bash
 composer require newfold-labs/wp-module-data
 ```
 
 ## Releasing Updates

 Run the `Newfold Prepare Release` github action to automatically bump the version (either patch, minor or major version), and update build and language files all at once. It will create a PR with changed files for review. Using this workflow, we can skip all the manual release steps below. 
 
### Manual release steps

1. This module has a constant `NFD_DATA_MODULE_VERSION` which needs to be incremented in conjuction with new releases and updates via github tagging.

2. Also update the version in the `package.json` file.

3. Run `npm install` to update the package lock file.

4. Update build files and/or language files to reflect new version (this module does not yet have a js build step or translations, so this isn't needed - yet).

4. Create release branch and release PR.

 ## Usage
 
 This module is forced active and cannot be disabled by users. There is no UI or other options.
 
 [More on Newfold WordPress Modules](https://github.com/newfold-labs/wp-module-loader)
