FROM php:7.3.6-fpm-stretch

RUN apt-get update

RUN apt-get install -y libmemcached-dev zlib1g-dev

RUN pecl install memcached

RUN docker-php-ext-enable  memcached

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apt-get install -y \
        libzip-dev \
        zip \
  && docker-php-ext-configure zip --with-libzip \
  && docker-php-ext-install zip

RUN apt-get install -y libpq-dev libpng-dev libjpeg-dev\
    && docker-php-ext-install pdo pdo_pgsql

RUN docker-php-ext-configure gd --with-jpeg-dir=/usr/include/


RUN docker-php-ext-install pcntl gd

COPY ./user-config.ini $PHP_INI_DIR/conf.d/

#RUN apt-get install -y libssh2-1-dev

#RUN pecl install ssh2-1.1.2

#RUN docker-php-ext-enable ssh2

RUN apt-get install -y libc-client-dev libkrb5-dev && rm -r /var/lib/apt/lists/*

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap

RUN apt-get update && apt-get install -y zlib1g-dev libicu-dev g++

RUN docker-php-ext-configure intl

RUN docker-php-ext-install intl

RUN apt-get update && apt-get install -y \
    libmagickwand-dev --no-install-recommends \
    && pecl install imagick \
	&& docker-php-ext-enable imagick