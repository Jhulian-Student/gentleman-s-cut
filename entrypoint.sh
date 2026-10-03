#!/bin/bash
set -e

PORT="${PORT:-8080}"
echo "==> Railway PORT is: $PORT"

# Ensure all files and directories have full read/execute permissions
chmod -R 755 /var/www/html
chown -R www-data:www-data /var/www/html

# Configure ports.conf to listen on dynamic PORT, 80, and 8080
cat << EOF > /etc/apache2/ports.conf
Listen $PORT
Listen 80
Listen 8080
EOF

# Configure VirtualHost with DirectoryIndex and allow all access
cat << EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:$PORT *:80 *:8080>
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

echo "==> Starting Apache in foreground on port $PORT..."
exec apache2-foreground
