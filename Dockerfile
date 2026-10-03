FROM php:8.2-apache

# Install PDO MySQL and GD extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules for rewrite and headers, and ensure single MPM (prefork)
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork rewrite headers

# Copy application files into Apache root
COPY . /var/www/html/

# Copy and setup entrypoint script
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set www-data permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["/usr/local/bin/entrypoint.sh"]
