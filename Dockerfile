# Docker image: https://hub.docker.com/_/docker/

# use php offical image variant 'php:<version>-apache' from https://hub.docker.com/_/php
FROM php:7.3.29-apache

# libpng-dev is required by gd extension
# zip is required by Composer
RUN apt-get update -y && apt-get install -y libpng-dev 
RUN apt-get install -y zip

# install php extensions
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install fileinfo
RUN docker-php-ext-install gd

# install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --no-dev

COPY . /var/www/html
# overwrite the default 000-default.conf file
COPY 000-default.conf /etc/apache2/sites-available/