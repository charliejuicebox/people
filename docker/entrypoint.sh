#!/bin/sh
set -e

if [ ! -f .env ]; then
    cp .env.example .env
    sed -i 's/^PEOPLE_DEV_LOGIN=.*/PEOPLE_DEV_LOGIN=true/' .env
    sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' .env
    php artisan key:generate --force
fi

mkdir -p database/data
if [ ! -f database/data/database.sqlite ]; then
    touch database/data/database.sqlite
    if [ -f roster/roster.csv ]; then
        # A real roster was dropped in: load it instead of the dummy demo people.
        php artisan migrate --force
        php artisan people:import-roster roster/roster.csv
    else
        php artisan migrate --seed --force
    fi
else
    php artisan migrate --force
fi

exec php artisan serve --host=0.0.0.0 --port=8000
