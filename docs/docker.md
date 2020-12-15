# Docker

Run the development containers of Plainpad with Docker and Docker Compose utility. Docker allows you to compose 
your application from micro-services, without worrying about inconsistencies between development and production 
environments and without locking into any platform or language. 

Enter the `docker` directory and run `docker-compose up` to start the environment. 

You will need modify the root `.env` so that it matches the following example: 

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
APP_URL=http://localhost:8000/

# This URL is used by the auto update mechanism to resolve available updates
# whenever available. Change this only if you have your own Plainpad repository
# configured.
APP_REPOSITORY=https://cdn.alextselegidis.com/plainpad/updates/stable

# Here you may specify which of the database connections below you wish
# to use as your default connection for all database work. Of course
# you may use many connections at once using the Database library.
DB_CONNECTION=mysql

# The database connection host, this defaults to "localhost" for most servers.
DB_HOST=plainpad-database

# The database connection port, this defaults to "3306" for most servers.
DB_PORT=3306

# The database name to connect to, make sure the database is created.
DB_DATABASE=plainpad

# The database username for the connection.
DB_USERNAME=root

# The database password for the connection.
DB_PASSWORD=root

```

Enter the server container with `docker exect -it plainpad-server bash` and run the seed the database with 
`php artisan migrate:fresh --seed`. 

In the host machine the server is accessible from `http://localhost:8000` and the database from `localhost:8001`.  

You can remove the docker containers with `docker rm plainpad-server plainpad-database`. 

You can remove the server image with `docker rmi plainpad-server:v1`.

The client can be executed locally but needs to know of the server API URL, thus change the `client/.env` to match
the following example: 

```
PORT=3000
CHOKIDAR_USEPOLLING=true
REACT_APP_BASE_URL=api.php
REACT_APP_VERSION=v1.0.0
```

Additionally, make sure the package.json file has the following value set: 

```
"proxy": "http://localhost:8000"
```

You can then execute `npm start` to run the client in development mode. 

[Back](readme.md)
