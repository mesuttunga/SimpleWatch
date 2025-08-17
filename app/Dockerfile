FROM php:8.2-apache

# Install curl for healthcheck
RUN apt-get update && apt-get install -y curl && rm -rf /var/lib/apt/lists/*

# Copy app
COPY public/ /var/www/html/

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Non-root user (security best practice)
RUN chown -R www-data:www-data /var/www/html

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
  CMD curl -f http://localhost/ || exit 1

EXPOSE 80

USER www-data
