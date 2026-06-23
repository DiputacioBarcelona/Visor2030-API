#!/bin/sh
set -e

npm install -g npm@8
npm install --save core-js@3

COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction --ansi

yarn install
yarn encore dev

php bin/console translation:extract --prefix="" ca --force

npm audit fix

npm rebuild node-sass
