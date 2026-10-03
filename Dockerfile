FROM php:8.2-apache

# Install PDO MySQL and GD extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules for rewrite and headers
RUN a2enmod rewrite headers

# Copy application files into Apache root
COPY . /var/www/html/

# Create entrypoint script to dynamically configure runtime PORT for Railway
RUN printf '#!/bin/sh\n\
PORT="${PORT:-80}"\n\
sed -i "s/Listen .*/Listen $PORT/" /etc/apache2/ports.conf\n\
sed -i "s/<VirtualHost \\*:[0-9]*>/<VirtualHost \\*:$PORT>/" /etc/apache2/sites-available/000-default.conf\n\
echo "Starting Apache on port $PORT..."\n\
exec apache2-foreground\n' > /usr/local/bin/start-server.sh \
    && chmod +x /usr/local/bin/start-server.sh

# Give www-data ownership
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["/usr/local/bin/start-server.sh"]
