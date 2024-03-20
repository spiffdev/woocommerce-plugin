# WooCommerce Plugin

This is the development repository of Spiff's WooCommerce plugin, Spiff Connect.

The spiff-connect folder contains all the files of the plugin except for assets which are included at build time.

## To run locally

* Copy a build of api.js into spiff-connect/public/js/.
* Run `docker-compose up`.
* Visit localhost:8888.

## To run tests

* Install the project dependencies with composer.
* Run `./vendor/bin/phpunit tests`.
