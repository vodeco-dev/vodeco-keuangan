#!/bin/sh

# Mulai PHP-FPM di background
php-fpm -D

# Mulai Nginx di foreground (agar container tetap berjalan)
nginx -g "daemon off;"