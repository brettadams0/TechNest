# syntax=docker/dockerfile:1

# ---- Dependencies: production autoloader only, no dev tools ---------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# The runtime image below provides the required extensions; this stage only
# resolves files, so its own PHP build is irrelevant.
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist \
    --optimize-autoloader --no-scripts --ignore-platform-reqs

# ---- Runtime: Apache + PHP 8.3 -------------------------------------------
FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'expose_php = Off\n' > "$PHP_INI_DIR/conf.d/technest.ini" \
    && printf 'ServerName localhost\nServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-enabled/technest-security.conf

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/technest-entrypoint

WORKDIR /var/www/technest
COPY --from=vendor /app/vendor ./vendor
COPY bin ./bin
COPY database ./database
COPY public ./public
COPY src ./src
COPY templates ./templates
RUN mkdir -p var && chown -R www-data:www-data var && chmod +x /usr/local/bin/technest-entrypoint

ENV APP_ENV=local
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=3s CMD curl -fsS http://localhost/health || exit 1

ENTRYPOINT ["technest-entrypoint"]
CMD ["apache2-foreground"]
