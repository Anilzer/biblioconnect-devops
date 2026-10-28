
FROM dunglas/frankenphp:1-php8.2-trixie

RUN apt-get update \
    && apt-get upgrade -y \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

RUN install-php-extensions \
    pdo_pgsql \
    intl \
    zip \
    opcache \
    mbstring

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . /app

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    SERVER_NAME=:80

RUN composer install \
      --no-dev \
      --prefer-dist \
      --no-interaction \
      --no-progress \
      --optimize-autoloader \
      --no-scripts \
    && php bin/console cache:clear \
    && php bin/console importmap:install \
    && php bin/console asset-map:compile

EXPOSE 80
