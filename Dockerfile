FROM php:8.1-apache
COPY ./public/ /var/www/html/
COPY ./config.php /var/www/html/config.php
EXPOSE 80
