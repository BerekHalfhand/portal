#!/bin/sh

cd ..

php app/console assetic:dump
php app/console assets:install

find . -type f -exec chmod 664 {} +
find . -type d -exec chmod 775 {} +

chown www-data:www-data -R .

php app/console cache:clear -e prod
php app/console cache:warmup -e prod

chmod 777 -R app/cache

cp ./service/portal_crontab /etc/cron.d/
service cron restart