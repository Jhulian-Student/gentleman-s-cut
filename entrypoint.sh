#!/bin/bash
set -e

PORT="${PORT:-8080}"
echo "==> Railway PORT is: $PORT"

# Configure ports.conf to listen on 80, 8080, and $PORT
cat << EOF > /etc/apache2/ports.conf
Listen 80
Listen 8080
EOF

if [ "$PORT" != "80" ] && [ "$PORT" != "8080" ]; then
    echo "Listen $PORT" >> /etc/apache2/ports.conf
fi

# Configure VirtualHost to respond to all listening ports
cat << EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80 *:8080 *:$PORT>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Suppress ServerName warning
echo "ServerName localhost" >> /etc/apache2/apache2.conf 2>/dev/null || true

# Ensure single MPM (prefork)
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

echo "==> Ports listening in Apache:"
cat /etc/apache2/ports.conf

echo "==> Starting Apache in foreground..."
exec apache2-foreground
