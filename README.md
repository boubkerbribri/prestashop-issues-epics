# PrestaShop Issues and Epics

This simple tool gathers data from the [PrestaShop project](https://github.com/PrestaShop/PrestaShop) and organizes it.
It gets data from the issues and the Epics and gives a simple way to display it.

## How to install

Use `composer install` to install all the dependencies. 
Copy the `config.php.dist` file, add your [Github token](https://github.com/settings/tokens/new) and rename it `config.php`.

## How to use

Use the `generate.php` file to create all your data in a `results.json` file. This file will store everything and
can be used elsewhere.

You can also browse to the root of the project, `index.php` will take care of displaying it. 

## Updating data

Just relaunch `generate.php` :-)
