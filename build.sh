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

echo "Create Zip"

cd build

find . -name '.DS_Store' -type f -delete

zip -r build.zip . -x \
    '.idea/*' \
    '.git/*' \
    '.github/*' \
    '.run/*' \
    'docker/*' \
    'tests/*' \
    '.editorconfig' \
    '.gitattributes' \
    '.gitignore' \
    '.phpstorm.meta.php' \
    '.prettierignore' \
    '.prettierrc.json' \
    'package-lock.json' \
    'phpunit.xml' \
    '_ide_helper_models.php' \
    '_ide_helper.php' \
    'build.sh' \
    '*.zip'

cd ..

mv build/build.zip .