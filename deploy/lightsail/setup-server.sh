#!/usr/bin/env bash
# Configura un Lightsail Ubuntu 24.04 limpio para apps Laravel (Nginx + PHP-FPM 8.4).
# Uso (una sola vez): sudo bash setup-server.sh
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive
timedatectl set-timezone America/Guatemala

apt-get update
apt-get install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt-get update && apt-get upgrade -y

apt-get install -y nginx supervisor git unzip curl mariadb-client \
  certbot python3-certbot-nginx unattended-upgrades fail2ban \
  php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath php8.4-imagick php8.4-opcache php8.4-sqlite3

# Node 20 para compilar assets con Vite
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

# Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Swap de 2 GB como colchón
if [ ! -f /swapfile ]; then
  fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
  echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

# Ajustes de PHP para producción
for ini in /etc/php/8.4/fpm/php.ini /etc/php/8.4/cli/php.ini; do
  sed -i 's/^upload_max_filesize.*/upload_max_filesize = 50M/' "$ini"
  sed -i 's/^post_max_size.*/post_max_size = 50M/' "$ini"
  sed -i 's/^memory_limit.*/memory_limit = 512M/' "$ini"
  sed -i 's/^;\?date.timezone.*/date.timezone = America\/Guatemala/' "$ini"
done
sed -i 's/^;\?opcache.enable=.*/opcache.enable=1/' /etc/php/8.4/fpm/php.ini
# Laravel + Filament tienen ~22k archivos PHP; el límite por defecto (10k) se queda corto
cat > /etc/php/8.4/fpm/conf.d/99-laravel.ini <<INI
opcache.memory_consumption=256
opcache.interned_strings_buffer=32
opcache.max_accelerated_files=32531
realpath_cache_size=4096K
realpath_cache_ttl=600
INI

# Comprimir CSS/JS/JSON/SVG (Ubuntu solo comprime HTML por defecto)
cat > /etc/nginx/conf.d/gzip.conf <<NGX
gzip_vary on;
gzip_proxied any;
gzip_comp_level 5;
gzip_min_length 1024;
gzip_types text/plain text/css text/xml application/json application/javascript application/xml image/svg+xml font/woff2;
NGX

# Pool FPM principal: cada proceso de Laravel+Filament usa ~230 MB, así que en 4 GB caben ~8
# (con 20 el servidor se quedaba sin memoria y se caía). Los procesos se reciclan y no se dejan colgados.
sed -i -E 's/^pm = .*/pm = dynamic/; s/^pm.max_children = .*/pm.max_children = 8/; s/^;?pm.start_servers = .*/pm.start_servers = 3/; s/^;?pm.min_spare_servers = .*/pm.min_spare_servers = 2/; s/^;?pm.max_spare_servers = .*/pm.max_spare_servers = 4/; s/^;?pm.max_requests = .*/pm.max_requests = 300/; s/^;?request_terminate_timeout = .*/request_terminate_timeout = 90s/' /etc/php/8.4/fpm/pool.d/www.conf
grep -q '^request_terminate_timeout' /etc/php/8.4/fpm/pool.d/www.conf || echo 'request_terminate_timeout = 90s' >> /etc/php/8.4/fpm/pool.d/www.conf

# Pool aparte para las fotos (/img): máximo 2 a la vez, para que nunca dejen sin servicio a las páginas
cat > /etc/php/8.4/fpm/pool.d/img.conf <<'POOL'
[img]
user = www-data
group = www-data
listen = /run/php/php8.4-fpm-img.sock
listen.owner = www-data
listen.group = www-data
pm = ondemand
pm.max_children = 2
pm.process_idle_timeout = 10s
pm.max_requests = 100
request_terminate_timeout = 60s
php_admin_value[memory_limit] = 384M
POOL

# Límite de pedidos por segundo a los robots de Meta (anuncios de Facebook/Instagram), que revisan el sitio sin parar
cat > /etc/nginx/conf.d/limites.conf <<'NGX'
map $http_user_agent $clave_robot {
    default "";
    ~*(meta-externalads|meta-externalagent|facebookexternalhit) $binary_remote_addr;
}
limit_req_zone $clave_robot zone=robots:10m rate=3r/s;
NGX

# www-data necesita home para llaves SSH de GitHub y caché de composer/npm
mkdir -p /var/www/.ssh && chown -R www-data:www-data /var/www && chmod 700 /var/www/.ssh

# Script de deploy disponible globalmente
SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
install -m 755 "$SCRIPT_DIR/deploy.sh" /usr/local/bin/deploy-site

# Seguridad: bloquear fuerza bruta en SSH y ocultar versión de Nginx
cat > /etc/fail2ban/jail.local <<J
[sshd]
enabled = true
maxretry = 5
findtime = 10m
bantime = 1h
J
systemctl enable --now fail2ban
echo "server_tokens off;" > /etc/nginx/conf.d/security.conf

rm -f /etc/nginx/sites-enabled/default
systemctl enable --now php8.4-fpm nginx supervisor
systemctl restart php8.4-fpm nginx

dpkg-reconfigure -f noninteractive unattended-upgrades
echo "Servidor listo."
