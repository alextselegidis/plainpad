#!/bin/bash

echo "Reset Build Directory"
rm -rf build/
mkdir build/

echo "Bundle Server"
cp -r server/** build/
cp server/.env.example build/
rm server/storage/**/*.log
rm server/storage/framework/**/*.php
rm -rf build/tests/
rm -rf build/vendor/
rm build/phpunit.xml
cd build
composer install --no-ansi --no-interaction --no-progress --no-scripts --optimize-autoloader
rm composer.lock
cd ../

echo "Bundle Client"
cd client/
npm run build
cd ../
cp -r client/build/** build/public

echo "Copy README File"
cp README.md build/

echo "Zip Build"
cd build
zip build.zip -r .
