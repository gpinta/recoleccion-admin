FROM php:8.2-apache

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    libldap2-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install pdo pdo_mysql zip intl mbstring bcmath curl ldap

RUN a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /var/www/html
WORKDIR /var/www/html

RUN composer install --no-dev --optimize-autoloader

# Cambiar el propietario de TODO el proyecto a www-data
RUN chown -R www-data:www-data /var/www/html

RUN chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Configurar Apache para puerto 8081
COPY laravel.conf /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/Listen 80/Listen 8081/' /etc/apache2/ports.conf

EXPOSE 8081
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]