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

# Configure Apache to use Railway's dynamic PORT
ENV PORT=80
EXPOSE 80

RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Give www-data ownership
RUN chown -R www-data:www-data /var/www/html

CMD ["apache2-foreground"]
