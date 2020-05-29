FROM php:5.6-apache
#Install git
RUN apt-get update && apt-get install -y libmcrypt-dev \
    && apt-get install -y git
RUN apt-get install -y sendmail libpng-dev
RUN docker-php-ext-install pdo pdo_mysql mysql
RUN a2enmod rewrite
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install mcrypt
RUN docker-php-ext-install gd
#Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=. --filename=composer
RUN mv composer /usr/local/bin/
RUN chmod -R 775 /var/www
#EXPOSE 80
