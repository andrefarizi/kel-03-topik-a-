FROM php:8.2-fpm-alpine

# Install dependensi sistem yang dibutuhkan Laravel
RUN apk add --no-cache \
    zip libzip-dev freetype-dev libjpeg-turbo-dev libpng-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mbstring zip exif pcntl

# Atur working directory
WORKDIR /var/www/html

# Pindahkan file web ke dalam kontainer
COPY . /var/www/html

# Set permission ke www-data (non-root user)
RUN chown -R www-data:www-data /var/www/html

# Jalankan kontainer sebagai non-root user (Syarat Keamanan Rubrik)
USER www-data

EXPOSE 9000
CMD ["php-fpm"]