## Installation 

Plainpad is a web application that can be installed and run in web servers. Users will be able to reach the application 
through their web browsers by using an active internet connection, just like visiting a normal website. The installation 
process is very similar to the popular framework Laravel, so it is very likely that you will be familiar with the next 
steps. Follow this article strictly in order to complete the installation with no problems. 

### Requirements 

* Apache/NGINX
* PHP >= 7.2
* OpenSSL PHP Extension
* PDO PHP Extension
* Mbstring PHP Extension
* Zip PHP Extension

### Guide 

Plainpad requires a few steps and minimum configuration for the installation to your server. 

1. Upload the contents of the zip archive to your server. 
1. Make sure the web server points to the `public` directory.
1. Create a `.env` file based on the `.env.example` provided. 
1. Run `php artisan migrate:fresh --seed` to setup the database.

Alternatively you can open the browser to the `/url/to/public/setup.php` file and submit the 
installation form. 

Make sure the application has writable permissions so that the auto update feature works smoothly.  
    
