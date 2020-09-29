## Upgrade 

The self hosted version supports an automated updates that will notify admin users whenever new packages are available 
for download.

It is important the Plainpad installation directory is writable so that the modified files can be replaced and the old 
can be removed. 

In addition, Plainpad requires the PHP Zip extension to be installed for the update process to work.

#### Upgrading from `1.0.0-beta.3` to `1.0.0-beta.4`

Steps: 

1. Make sure your server is running PHP v7.3+ 
1. Make sure the entire Plainpad directory is writable 777 (the PHP process should be able to make changes to the file system). 
1. Go to the settings page and click the update button.
1. An error will be displayed due to the laravel upgrade, that's fine this time. 
1. Finalize the upgrade by running "php artisan refresh" from the terminal.  

If you are still facing errors with your installation, check the following places for further information: 

* storage/logs/update-***.log for updater logs
* storage/logs/laravel-***.log for framework logs
* /var/log/apache2|nginx/error.log for server logs

#### Upgrading from `1.0.0-beta.2` to `1.0.0-beta.3`

Steps: 

1. Make sure your server is running PHP v7.2+ 
1. Make sure the entire Plainpad directory is writable 777 (the PHP process should be able to make changes to the file system). 
1. Go to the settings page and click the update button.

#### Upgrading from `1.0.0-beta.1` to `1.0.0-beta.2`

Steps: 

1. Make sure your server is running PHP v7.2+ 
1. Make sure the entire Plainpad directory is writable 777 (the PHP process should be able to make changes to the file system). 
1. Go to the settings page and click the update button.

#### Upgrading from `1.0.0-alpha.1` to `1.0.0-beta.1`

Steps: 

1. Make sure your server is running PHP v7.2+ 
1. Make sure the entire Plainpad directory is writable 777 (the PHP process should be able to make changes to the file system). 
1. Go to the settings page and click the update button.

[Back](readme.md)
