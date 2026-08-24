FROM php:8.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        unzip \
        git \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath zip intl opcache gd pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHPStan/Larastan analysis of the full app tree exceeds the 128M CLI default.
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini

RUN groupadd -g 1000 appuser \
    && useradd -u 1000 -g appuser -m -s /bin/bash appuser

WORKDIR /var/www/html

RUN chown appuser:appuser /var/www/html

USER appuser

EXPOSE 9000

CMD ["php-fpm"]
