#!/bin/sh
# Fresh demo data on every start, then serve on port 5119.
set -e
rm -f database/database.sqlite database/database.sqlite-wal database/database.sqlite-shm
touch database/database.sqlite
php artisan migrate:fresh --seed --force
exec php artisan serve --host=0.0.0.0 --port=5119 --no-reload
