#!/bin/bash
set -e

# Default to 80 if PORT not set
PORT="${PORT:-80}"
echo "==> Configuring Apache for Railway on port: $PORT"

# Replace only port 80, keeping SSL ports intact
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$PORT>/" /etc/apache2/sites-available/000-default.conf

# Set ServerName to suppress warnings
echo "ServerName localhost" >> /etc/apache2/apache2.conf

echo "==> Starting Apache in foreground..."
exec apache2-foreground
