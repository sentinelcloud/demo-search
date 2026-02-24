FROM serversideup/php:8.3-cli

USER root

WORKDIR /app

# Copy composer files first for better layer caching
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy the full application
COPY --chown=www-data:www-data . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize

USER www-data

# Reverb listens on port 6001 inside the container
EXPOSE 6001

# Start the Reverb WebSocket server
CMD ["php", "artisan", "reverb:start", "--host=0.0.0.0", "--port=6001", "--debug"]
