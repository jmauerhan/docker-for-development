#Dockerfile

FROM php:latest

RUN apt-get update && apt-get install -y libpq-dev zip unzip
RUN docker-php-ext-install pgsql pdo pdo_pgsql
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

CMD php -S 0.0.0.0:80 -t /var/www/public
