#!/bin/sh

set -ex

git clone --depth 1 https://github.com/sameyasu/heroku-hc.git /app

cd /app
composer install
