FROM php:8-apache
MAINTAINER Dennis
RUN apt-get update
RUN apt-get install -y tzdata
RUN apt-get install -y python3
RUN docker-php-ext-install pdo pdo_mysql mysqli
ENV TZ Europe/Berlin
