.PHONY: all build composer-install composer-update test-php

all: build test-php

build:
	@echo "Building PHP 7 image"
	@docker build -t monolog-logfmt-php7 -f docker/php7/Dockerfile .
	@echo "Building PHP 8 image"
	@docker build -t monolog-logfmt-php8 -f docker/php8/Dockerfile .

composer-install:
	@echo "Running composer install (PHP 7)"
	@docker run \
	--name=monolog-logfmt-php7 \
	--mount type=bind,source="$$(pwd)",target=/usr/src/monolog-logfmt \
	--rm \
	monolog-logfmt-php7 \
	sh -c "composer install"
	@echo "Running composer install (PHP 8)"
	@docker run \
	--name=monolog-logfmt-php8 \
	--mount type=bind,source="$$(pwd)",target=/usr/src/monolog-logfmt \
	--rm \
	monolog-logfmt-php8 \
	sh -c "composer install"

composer-update:
	@echo "Running composer update (PHP 7)"
	@docker run \
	--name=monolog-logfmt-php7 \
	--mount type=bind,source="$$(pwd)",target=/usr/src/monolog-logfmt \
	--rm \
	monolog-logfmt-php7 \
	sh -c "composer update"

test-php:
	@echo "Running tests (PHP 7)"
	@docker run \
	--name=monolog-logfmt-php7 \
	--mount type=bind,source="$$(pwd)",target=/usr/src/monolog-logfmt \
	--rm \
	monolog-logfmt-php7 \
	sh -c "composer install && vendor/bin/phpunit"
	@echo "Running tests (PHP 8)"
	@docker run \
	--name=monolog-logfmt-php8 \
	--mount type=bind,source="$$(pwd)",target=/usr/src/monolog-logfmt \
	--rm \
	monolog-logfmt-php8 \
	sh -c "composer install && vendor/bin/phpunit"
