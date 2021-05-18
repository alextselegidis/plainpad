#!/bin/bash

echo "Setup Server"
cd server
composer install && composer update
cd ..

echo "Setup Client"
cd client
cp .env.example .env
npm install && npm update

echo "Create .env File"
cp .env.example .env

cd ..

echo "Change Storage Permissions"
chmod -R 777 server/storage
