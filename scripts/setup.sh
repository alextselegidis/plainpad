#!/bin/bash

echo "Setup Server"
cd server
composer install
cd ../../

echo "Setup Client"
cd client
cp .env.example .env
npm install
cd ../../

echo "Create .env File"
cp .env.example .env

echo "Change Storage Permissions"
chmod -R 777 server/storage
