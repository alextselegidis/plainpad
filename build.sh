#!/bin/bash

echo "Reset Build Directory"
rm -rf build/ build.zip
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
composer install --no-ansi --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader
composer dumpautoload
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
find . -name '.DS_Store' -type f -delete
zip -r build.zip . \
    -x .git/**\* \
    -x .idea/**\* \
    -x tests/**\* \
    -x .editorconfig \
    -x .gitattributes \
    -x .phpstorm.meta.php \
    -x .prettierignore \
    -x package-lock.json \
    -x phpunit.xml \
    -x _ide_helper.php \
    -x vite.config.js \
    -x \*.zip

cd ..

mv build/build.zip .