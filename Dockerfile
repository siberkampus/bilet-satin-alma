FROM php:8.1-fpm

# Gerekli paketleri kur
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev

# PHP extension'larını kur
RUN docker-php-ext-install pdo_sqlite mbstring exif pcntl bcmath gd zip

# Composer'ı kur
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Çalışma dizinini ayarla
WORKDIR /var/www/html

# Önce composer.json ve composer.lock'u kopyala (cache için)
COPY src/composer.* ./

# Vendor dizini yoksa veya boşsa composer install çalıştır
RUN if [ -f "composer.json" ]; then \
        composer install --no-dev --optimize-autoloader; \
    fi

# Tüm PHP dosyalarını kopyala
COPY src/ /var/www/html/

# Gerekli izinleri ayarla
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Özel dizin izinleri
RUN chmod -R 775 /var/www/html/uploads
RUN chmod 666 /var/www/html/ticket.db

# Session dizini oluştur
RUN mkdir -p /var/www/html/sessions && chmod 775 /var/www/html/sessions

EXPOSE 9000
CMD ["php-fpm"]