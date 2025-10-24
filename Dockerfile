FROM php:8.1-fpm

RUN apt-get update && apt-get install -y \
    git curl unzip zip sqlite3 libsqlite3-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_sqlite mbstring

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY src/composer.json composer.lock* ./
RUN if [ -f "composer.json" ]; then composer install --no-dev --optimize-autoloader --no-interaction; fi

COPY src/ /var/www/html/

RUN mkdir -p /var/www/html/uploads /var/www/html/sessions \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads /var/www/html/sessions

EXPOSE 9000
CMD ["php-fpm"]
