FROM php:7.3-fpm

RUN apt-get update

RUN docker-php-ext-enable  memcached

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apt-get install -y \
        libzip-dev \
        zip \
  && docker-php-ext-configure zip --with-libzip \
  && docker-php-ext-install zip

RUN apt-get install -y libpq-dev libpng-dev\
    && docker-php-ext-install pdo pdo_pgsql

RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-webp-dir=/usr/include/  --with-jpeg-dir=/usr/include/
RUN docker-php-ext-install pcntl gd

COPY ./user-config.ini $PHP_INI_DIR/conf.d/


RUN apt-get install -y libc-client-dev libkrb5-dev && rm -r /var/lib/apt/lists/*

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap

RUN apt-get update && apt-get install -y zlib1g-dev libicu-dev g++

#RUN docker-php-ext-configure intl
#
#RUN docker-php-ext-install intl

RUN apt-get update && apt-get install -y \
    libmagickwand-dev --no-install-recommends

#RUN pecl install memcached-3.1.3
#RUN docker-php-ext-enable memcached

#RUN pecl install imagick-3.4.4
#RUN docker-php-ext-enable imagick

RUN docker-php-ext-install pgsql

#RUN docker-php-ext-install cli

#RUN docker-php-ext-install json

#RUN docker-php-ext-install zip

#RUN docker-php-ext-install mysql

# рабочая конфигурация но недостаточная
#RUN docker-php-ext-install gd
#RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/

#RUN apt-get update && \
#    apt-get install -y \
#    libfreetype6-dev \
#    libwebp-dev \
#    libjpeg62-turbo-dev \
#    libpng-dev \
#    libgmp-dev \
#    libldap2-dev && \
#    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-webp-dir=/usr/include/  --with-jpeg-dir=/usr/include/ && \
#    docker-php-ext-install gd


#RUN docker-php-ext-install curl

RUN docker-php-ext-install xml

RUN docker-php-ext-install bcmath

RUN apt policy php7.3-cli