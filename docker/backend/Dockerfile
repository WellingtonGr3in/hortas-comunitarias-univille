# Backend PHP (Slim). O código entra na imagem, então roda igual em qualquer PC.
# Ao subir: aplica as migrations pendentes e inicia o servidor na porta 8181.
FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev unzip \
    && docker-php-ext-install pdo_mysql mysqli intl zip \
    && rm -rf /var/lib/apt/lists/*

RUN printf "date.timezone=America/Sao_Paulo\nmemory_limit=512M\n" > /usr/local/etc/php/conf.d/hortas.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

# Dependências primeiro, para o Docker reaproveitar esta camada quando só o código muda.
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

COPY backend/ ./
RUN composer dump-autoload --optimize --no-dev

EXPOSE 8181

CMD ["sh", "-c", "php run-migrations.php && exec php -S 0.0.0.0:8181 -t public public/index.php"]
