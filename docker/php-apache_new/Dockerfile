FROM php:8-apache
MAINTAINER Dennis
RUN apt-get update
RUN apt-get install tzdata
RUN docker-php-ext-install pdo pdo_mysql mysqli
ENV TZ Europe/Berlin
