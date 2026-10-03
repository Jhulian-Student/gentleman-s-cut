#!/bin/bash
set -e

PORT="${PORT:-8080}"
echo "==> Starting configuration for Railway on port: $PORT"

# Ensure all files and directories have full read/execute permissions
chmod -R 755 /var/www/html
chown -R www-data:www-data /var/www/html

# Listen exclusively on Railway's assigned port (prevents duplicate listener error)
echo "Listen $PORT" > /etc/apache2/ports.conf

# Configure VirtualHost for the exact port
cat << EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:$PORT>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    DirectoryIndex index.html index.php

    <Directory /var/www/html>
        Options +FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>
</VirtualHost>
EOF

# Suppress ServerName warning
echo "ServerName localhost" >> /etc/apache2/apache2.conf 2>/dev/null || true

# Ensure single MPM (prefork)
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

echo "==> Apache ports.conf content:"
cat /etc/apache2/ports.conf

echo "==> Starting Apache in foreground..."
exec apache2-foreground
