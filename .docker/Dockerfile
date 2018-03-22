FROM php:7.2-apache

MAINTAINER Brian J. Sanders

COPY . /opt/finance-module-poc
COPY .docker/apache-000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /opt/finance-module-poc

RUN docker-php-ext-install pdo_mysql \
    && chown -R www-data:www-data /opt/finance-module-poc \
    && a2enmod rewrite
