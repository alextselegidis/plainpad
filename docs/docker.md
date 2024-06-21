# Docker

Run the development containers of Plainpad with Docker and Docker Compose utility. Docker allows you to compose 
your application from microservices, without worrying about inconsistencies between development and production 
environments and without locking into any platform or language. 

Run `docker-compose up -d` to start the environment. 

You will need modify the server's `.env` so that it matches the following example: 

``` 
# This value is the name of your application. This value is used when the
# framework needs to place the application's name in a notification or
# any other location as required by the application or its packages.
APP_NAME=Plainpad

# This value determines the "environment" your application is currently
# running in. This may determine how you prefer to configure various
# services the application utilizes. Set this in your ".env" file.
APP_ENV=local

# This key is used by the Illuminate encrypter service and should be set
# to a random, 32 character string, otherwise these encrypted strings
# swill not be safe. Please do this before deploying an application and
# make sure you do not lose this key!
APP_KEY=E6972E6A6DC41B193CD90ED830085859

# When your application is in debug mode, detailed error messages with
# stack traces will be shown on every error that occurs within your
# application. If disabled, a simple generic error page is shown.
APP_DEBUG=true

# This URL is used by the console to properly generate URLs when using
# the Artisan command line tool. You should set this to the root of
# your application so that it is used when running Artisan tasks.
APP_URL=http://localhost

# This URL is used by the auto update mechanism to resolve available updates
# whenever available. Change this only if you have your own Plainpad repository
# configured.
APP_REPOSITORY=https://cdn.alextselegidis.com/plainpad/updates/stable

# Here you may specify which of the database connections below you wish
# to use as your default connection for all database work. Of course
# you may use many connections at once using the Database library.
DB_CONNECTION=mysql

# The database connection host, this defaults to "localhost" for most servers.
DB_HOST=mysql

# The database connection port, this defaults to "3306" for most servers.
DB_PORT=3306

# The database name to connect to, make sure the database is created.
DB_DATABASE=plainpad

# The database username for the connection.
DB_USERNAME=user

# The database password for the connection.
DB_PASSWORD=password

```

Enter the server container with `docker exect -it plainpad-php-fpm-1 bash` and run the seed the database with 
`php artisan migrate:fresh --seed`. 

In the host machine the server is accessible from `http://localhost` and the database from `localhost:3306`.  

You can remove the docker containers with `docker compose down --volumes`.

You can then execute `npm start` from within the client directory and the php-fpm container to run the dev server in 
development mode. 

[Back](readme.md)
