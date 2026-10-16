# Local demo image: builds assets, installs PHP deps, seeds dummy data on first run.
FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# PHP 8.3 with composer, zip and pdo_sqlite already included.
FROM serversideup/php:8.3-cli
WORKDIR /var/www/html
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize && chmod +x docker/entrypoint.sh && mkdir -p database/data
EXPOSE 8000
ENTRYPOINT ["docker/entrypoint.sh"]
