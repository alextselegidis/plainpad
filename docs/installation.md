## Installation 

Plainpad is a web application that can be installed and run in web servers. Users will be able to reach the application 
through their web browsers by using an active internet connection, just like visiting a normal website. The installation 
process is very similar to the one required by the popular PHP framework Laravel, so it is very likely that you will be 
familiar with the next steps. Follow this article strictly in order to complete the installation with no problems. 

### Server Requirements 

* Apache/NGINX
* MySQL 
* PHP >= 7.3
* BCMath PHP Extension
* Ctype PHP Extension
* Fileinfo PHP extension
* JSON PHP Extension
* Mbstring PHP Extension
* OpenSSL PHP Extension
* PDO PHP Extension
* Tokenizer PHP Extension
* XML PHP Extension

### Steps 

Plainpad requires a few steps and minimum configuration for the installation to your server. 

1. Upload the contents of the zip archive to your server. 
1. Make sure the web server is configured to only serve the `public` directory.
1. Create an `.env` file based on the `.env.example` provided and enter your configuration values. 
1. Run `php artisan migrate:fresh --seed` to set up the database.

Alternatively you can open the browser to the `/url/to/public/setup.php` file and submit the 
installation form. 

Make sure the entire application directory is completely writable by the PHP process so that the auto update feature 
works properly.  

### Useful Links 

* [Official Laravel Installation Guide](https://laravel.com/docs/8.x/installation)
* [Deploy Laravel To Shared Hosting](https://youtu.be/6g8G3YQtQt4)
    
[Back](readme.md)
