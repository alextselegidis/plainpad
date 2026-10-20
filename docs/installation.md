## Installation 

Plainpad is a web application that can be installed and run in web servers. Users will be able to reach the application 
through their web browsers by using an active internet connection, just like visiting a normal website. The installation 
process is very similar to the one required by the popular PHP framework Laravel, so it is very likely that you will be 
familiar with the next steps. Follow this article strictly in order to complete the installation with no problems. 

### Server Requirements 

* Apache/NGINX
* MySQL 
* PHP >= 8.2
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
1. Run `php artisan key:generate` to create a random application key.
1. Run `php artisan migrate:fresh --seed` to set up the database.

Alternatively you can open the browser to the `/url/to/public/setup.php` file and submit the 
installation form. 

Log in with the default account `admin@example.org` / `12345678` and change its email and password right away 
from the profile page.

Make sure the entire application directory is owned by and writable for the PHP process user (never `chmod 777`) so 
that the auto update feature works properly.  

The included `.htaccess` (Apache) and `web.config` (IIS) stop other sites from embedding Plainpad in a frame. On nginx 
add the same headers to the server block:

```nginx
add_header X-Frame-Options "DENY" always;
add_header Content-Security-Policy "frame-ancestors 'none'" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "same-origin" always;
```

Behind a reverse proxy, set `TRUSTED_PROXIES` in `.env` to the proxy address (comma separated for several, `*` to 
trust any), so that the login rate limit sees the real client addresses instead of the proxy address.

### Useful Links 

* [Official Laravel Installation Guide](https://laravel.com/docs/8.x/installation)
* [Deploy Laravel To Shared Hosting](https://youtu.be/6g8G3YQtQt4)
    
[Back](readme.md)
