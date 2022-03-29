<div style="text-align: center;">
 <a href="https://newfold.com/" target="_blank">
  <img src="https://newfold.com/content/experience-fragments/newfold/site-header/master/_jcr_content/root/header/logo.coreimg.svg/1621395071423/newfold-digital.svg" alt="Newfold Logo" title="Newfold Digital" height="42" />
 </a>
</div>

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
 
 ## Updates
 This module has a constant `NFD_DATA_MODULE_VERSION` which needs to be incremented in conjuction with new releases and updates via github tagging. 

 ## Usage
 
 This module is forced active and cannot be disabled by users. There is no UI or other options.
 
 [More on Newfold WordPress Modules](https://github.com/newfold-labs/wp-module-loader)