#Dockerfile

FROM php:latest

RUN apt-get update && apt-get install -y libpq-dev
RUN docker-php-ext-install pgsql pdo pdo_pgsql

CMD php -S 0.0.0.0:80 -t /var/www/public