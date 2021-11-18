<a href="https://endurance.com/" target="_blank">
    <img src="https://bluehost.com/resources/logos/endurance.svg" alt="Endurance Logo" title="Endurance" align="right" height="42" />
</a>

# Data WordPress Module
 
Connects a WordPress site to Endurance systems to provide basic features and metrics.
 
 ## Installation
 
 ### 1. Add the Bluehost Satis to your `composer.json`.
 
  ```bash
 composer config repositories.bluehost composer https://bluehost.github.io/satis
 ```
 
 ### 2. Require the `bluehost/endurance-wp-module-data` package.
 
 ```bash
 composer require bluehost/endurance-wp-module-data
 ```
 
 ## Updates
 This module has a constant `NFD_DATA_MODULE_VERSION` which needs to be incremented in conjuction with new releases and updates via github tagging. 

 ## Usage
 
 This module is forced active and cannot be disabled by users. There is no UI or other options.
 
 ## More on Endurance WordPress Modules
 
* <a href="https://github.com/bluehost/endurance-wp-module-loader#endurance-wordpress-modules">What are modules?</a>
* <a href="https://github.com/bluehost/endurance-wp-module-loader#creating--registering-a-module">Creating/registering modules</a>
* <a href="https://github.com/bluehost/endurance-wp-module-loader#installing-from-our-satis">Installing from our Satis</a>
* <a href="https://github.com/bluehost/endurance-wp-module-loader#local-development">Local development notes</a>
* <a href="https://github.com/bluehost/endurance-wp-module-loader#understanding-the-module-lifecycle">Understanding the module lifecycle</a>
