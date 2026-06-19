# PHP runtime for the Piggy Rewards app.
FROM php:8.2-cli

# MySQL PDO driver.
RUN docker-php-ext-install pdo_mysql

# Composer (copied from the official image).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install dependencies first for better layer caching.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

# Application code (compiled CSS is committed, so no Tailwind build is needed).
COPY . .
RUN composer dump-autoload --optimize --no-dev

EXPOSE 8080

# Serve through Slim's front controller. The built-in server routes
# unknown paths to public/index.php and serves real files (CSS) directly.
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
