sudo php app/console assets:install web --symlink
sudo chown -R www-data:www-data app/logs app/cache
sudo chmod -R a+rwx app/logs app/cache
