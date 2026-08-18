FROM php:8.4-fpm-alpine

# Dependências do sistema
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev

# Extensões PHP necessárias para Laravel
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Redis extension
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# PHP config para desenvolvimento
COPY php-local.ini /usr/local/etc/php/conf.d/local.ini

WORKDIR /var/www/api

# Realinha o www-data com o UID/GID do usuário do host, para que os arquivos
# escritos pelo container (composer, artisan, pint, phpstan) fiquem com o
# dono correto no bind mount, evitando erros de permissão dos dois lados.
ARG UID=1000
ARG GID=1000
RUN deluser www-data 2>/dev/null || true; \
    delgroup www-data 2>/dev/null || true; \
    addgroup -g "${GID}" www-data && \
    adduser -D -u "${UID}" -G www-data -h /var/www www-data

# Permissões corretas para www-data
RUN chown -R www-data:www-data /var/www

# Entrypoint: corrige permissões do storage ao subir o container
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]