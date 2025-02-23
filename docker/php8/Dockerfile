FROM php:8.4-cli

RUN apt-get update && apt-get install -y git zip

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /usr/src/monolog-logfmt

COPY . .

RUN composer install

CMD ["vendor/bin/phpunit"]
