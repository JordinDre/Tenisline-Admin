#!/usr/bin/env bash
# Agrega un proyecto Laravel al servidor (Nginx, worker de colas, scheduler).
# Uso: sudo bash add-site.sh <nombre> <dominio> <repo-ssh> [rama]
# Ej:  sudo bash add-site.sh tenisline tenisline.com git@github.com:JordinDre/Tenisline-Admin.git main
set -euo pipefail

NAME=$1; DOMAIN=$2; REPO=$3; BRANCH=${4:-main}
DIR=/var/www/$NAME
KEY=/var/www/.ssh/$NAME

# 1) Deploy key propia para el repo
if [ ! -f "$KEY" ]; then
  sudo -u www-data ssh-keygen -t ed25519 -N "" -C "$NAME@lightsail" -f "$KEY"
  cat >> /var/www/.ssh/config <<CFG
Host github-$NAME
  HostName github.com
  User git
  IdentityFile $KEY
  IdentitiesOnly yes
CFG
  chown www-data:www-data /var/www/.ssh/config
  sudo -u www-data ssh-keyscan github.com >> /var/www/.ssh/known_hosts 2>/dev/null
  echo
  echo "== Agrega esta llave en GitHub > $REPO > Settings > Deploy keys (solo lectura) =="
  cat "$KEY.pub"
  read -rp "Presiona Enter cuando la hayas agregado..."
fi

# 2) Código
if [ ! -d "$DIR/.git" ]; then
  sudo -u www-data git clone -b "$BRANCH" "${REPO/github.com/github-$NAME}" "$DIR"
fi

# 3) Nginx
cat > /etc/nginx/sites-available/$NAME <<NGX
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    root $DIR/public;
    index index.php;
    client_max_body_size 50M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Strict-Transport-Security "max-age=31536000" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~* \.(css|js|woff2?|ttf|svg|png|jpe?g|gif|webp|ico)\$ {
        expires 30d;
        add_header Cache-Control "public";
        access_log off;
        try_files \$uri /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
NGX
ln -sf /etc/nginx/sites-available/$NAME /etc/nginx/sites-enabled/$NAME
nginx -t && systemctl reload nginx

# 4) Worker de colas
cat > /etc/supervisor/conf.d/$NAME-worker.conf <<SUP
[program:$NAME-worker]
command=php $DIR/artisan queue:work --sleep=3 --tries=3 --max-time=3600
user=www-data
numprocs=1
autostart=true
autorestart=true
stopwaitsecs=3600
redirect_stderr=true
stdout_logfile=$DIR/storage/logs/worker.log
SUP

# 5) Scheduler
CRON="* * * * * cd $DIR && php artisan schedule:run >> /dev/null 2>&1"
( crontab -u www-data -l 2>/dev/null | grep -vF "$DIR" || true ; echo "$CRON" ) | crontab -u www-data -

echo
echo "Siguiente: crea $DIR/.env, luego corre: sudo -u www-data -H deploy-site $NAME $BRANCH"
echo "Después: sudo supervisorctl reread && sudo supervisorctl update"
