FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql mysqli gd zip mbstring \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers

COPY docker/apache-config.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-custom.ini

WORKDIR /var/www/html
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html/vistas/img /var/www/html/extensiones/logs \
    && chmod -R 775 /var/www/html/vistas/img /var/www/html/extensiones/logs

EXPOSE 80

CMD ["apache2-foreground"]
