FROM php:7.4-fpm

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY docker.conf /usr/local/etc/php-fpm.d/docker.conf

RUN apt-get update
RUN apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libzip-dev zip libcurl4-openssl-dev
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype
RUN docker-php-ext-install pdo pdo_mysql mysqli gd zip curl
RUN apt-get install -y autoconf pkg-config libssl-dev
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

