#!/usr/bin/env bash
# Prepara un servidor Ubuntu 24.04 limpio para Maestros a un clic (una sola vez).
# Ver docs/proyecto/06-despliegue-y-operacion.md.
#
#   sudo DOMAIN=maestros.eastus2.cloudapp.azure.com EMAIL=tu@correo.com \
#        REVERB_APP_KEY=... REVERB_APP_SECRET=... DEPLOY_PUBKEY="ssh-ed25519 AAAA..." SEED_DEMO=1 \
#        bash provision.sh
#
# Instala Nginx, PHP 8.3, MySQL, Supervisor y Certbot; clona el backend en /var/www/maestros-backend;
# crea la base de datos, el .env de producción y el certificado HTTPS; deja corriendo la cola y Reverb,
# y autoriza la llave de GitHub Actions solo para desplegar.
# Con SEED_DEMO=1 carga los datos de demostración y les cambia la contraseña por una aleatoria.
set -euo pipefail

: "${DOMAIN:?Falta DOMAIN}"
: "${EMAIL:?Falta EMAIL, para el certificado de Lets Encrypt}"
: "${REVERB_APP_KEY:?Falta REVERB_APP_KEY}"
: "${REVERB_APP_SECRET:?Falta REVERB_APP_SECRET}"
: "${DEPLOY_PUBKEY:?Falta DEPLOY_PUBKEY (llave pública de GitHub Actions)}"
SEED_DEMO="${SEED_DEMO:-0}"
APP_USER="${SUDO_USER:-maestros}"          # dueño del código: el usuario que entra por SSH
REPO="https://github.com/LordCasta/maestros_a_un_clic_backend.git"
BACKEND=/var/www/maestros-backend
FRONTEND=/var/www/maestros-frontend
PHP=8.3

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }
# artisan corre como www-data (el usuario de PHP-FPM) para que logs y caché queden con su dueño.
artisan() { sudo -u www-data php artisan "$@"; }

step "Memoria de intercambio (la VM tiene 1 GB)"
if ! swapon --show | grep -q /swapfile; then
  fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
  echo '/swapfile none swap sw 0 0' >> /etc/fstab
  sysctl -w vm.swappiness=10 && echo 'vm.swappiness=10' > /etc/sysctl.d/99-maestros.conf
fi

step "Paquetes"
export DEBIAN_FRONTEND=noninteractive
apt-get update -q
apt-get install -yq nginx mysql-server supervisor certbot python3-certbot-nginx git unzip rsync curl \
  php$PHP-fpm php$PHP-cli php$PHP-mysql php$PHP-mbstring php$PHP-xml php$PHP-curl php$PHP-zip \
  php$PHP-bcmath php$PHP-intl php$PHP-gd php$PHP-sqlite3
if ! command -v composer >/dev/null; then
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

step "MySQL ajustado a poca memoria"
cat > /etc/mysql/mysql.conf.d/maestros.cnf <<'EOF'
[mysqld]
performance_schema = OFF
innodb_buffer_pool_size = 128M
max_connections = 40
EOF
systemctl restart mysql
DB_PASSWORD=$(openssl rand -hex 24)
mysql <<SQL
CREATE DATABASE IF NOT EXISTS maestros CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'maestros'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
ALTER USER 'maestros'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON maestros.* TO 'maestros'@'localhost';
FLUSH PRIVILEGES;
SQL

step "PHP-FPM con pocos procesos"
POOL=/etc/php/$PHP/fpm/pool.d/www.conf
sed -i 's/^pm = .*/pm = ondemand/; s/^pm.max_children = .*/pm.max_children = 5/' "$POOL"
sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 12M/; s/^post_max_size = .*/post_max_size = 40M/' /etc/php/$PHP/fpm/php.ini
systemctl restart php$PHP-fpm

step "Código del backend"
mkdir -p "$BACKEND" "$FRONTEND" /var/log/maestros
if [ ! -d "$BACKEND/.git" ]; then git clone "$REPO" "$BACKEND"; fi
if [ ! -f "$FRONTEND/index.html" ]; then
  echo '<!doctype html><meta charset="utf-8"><title>Maestros a un clic</title><p>El frontend se publica con el próximo despliegue.</p>' > "$FRONTEND/index.html"
fi
chown -R "$APP_USER":www-data "$BACKEND" "$FRONTEND"
chown www-data:www-data /var/log/maestros
# El usuario de despliegue comparte grupo con PHP para poder reemplazar archivos de bootstrap/cache.
usermod -aG www-data "$APP_USER"
cd "$BACKEND"
chmod -R ug+rwX storage bootstrap/cache
chgrp -R www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod g+s {} +

step ".env de producción"
if [ ! -f .env ]; then
  cp .env.example .env
  set_env() { sed -i "s|^$1=.*|$1=$2|" .env; }
  set_env APP_KEY "base64:$(openssl rand -base64 32)"
  set_env APP_ENV production
  set_env APP_DEBUG false
  set_env APP_URL "https://$DOMAIN"
  set_env FRONTEND_URL "https://$DOMAIN"
  set_env LOG_STACK daily
  set_env LOG_LEVEL warning
  set_env DB_DATABASE maestros
  set_env DB_USERNAME maestros
  set_env DB_PASSWORD "$DB_PASSWORD"
  set_env REVERB_APP_ID maestros
  set_env REVERB_APP_KEY "$REVERB_APP_KEY"
  set_env REVERB_APP_SECRET "$REVERB_APP_SECRET"
  set_env REVERB_HOST "$DOMAIN"
  set_env REVERB_PORT 443
  set_env REVERB_SCHEME https
  printf '\nREVERB_SERVER_HOST=127.0.0.1\nREVERB_SERVER_PORT=8080\n' >> .env
  chown "$APP_USER":www-data .env && chmod 640 .env
fi

step "Dependencias, base de datos y datos iniciales"
# Con las dependencias de desarrollo: el DemoSeeder usa Faker. Después se quitan.
sudo -u "$APP_USER" composer install --no-interaction --no-progress --optimize-autoloader
artisan migrate --force
artisan db:seed --class=CommuneSeeder --force
artisan db:seed --class=CategorySeeder --force
DEMO_PASSWORD=""
if [ "$SEED_DEMO" = "1" ] && [ "$(artisan tinker --execute 'echo App\Models\User::count();')" = "0" ]; then
  artisan db:seed --class=DemoSeeder --force
  # En una URL pública, "password" sería una puerta abierta: todas las cuentas demo reciben otra.
  DEMO_PASSWORD=$(openssl rand -base64 12 | tr -d '/+=' | cut -c1-14)
  artisan tinker --execute "App\Models\User::query()->update(['password' => Illuminate\Support\Facades\Hash::make('$DEMO_PASSWORD')]);"
fi
sudo -u "$APP_USER" composer install --no-dev --no-interaction --no-progress --optimize-autoloader
sudo -u "$APP_USER" php artisan storage:link || true
artisan config:cache
artisan route:cache
artisan event:cache

step "Nginx"
sed "s|__DOMAIN__|$DOMAIN|g; s|__PHP__|$PHP|g" deploy/nginx.conf > /etc/nginx/sites-available/maestros
ln -sf /etc/nginx/sites-available/maestros /etc/nginx/sites-enabled/maestros
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

step "Certificado HTTPS (Let's Encrypt)"
certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "$EMAIL" --redirect

step "Cola, Reverb y tareas programadas"
cp deploy/supervisor.conf /etc/supervisor/conf.d/maestros.conf
supervisorctl reread && supervisorctl update
echo "* * * * * www-data php $BACKEND/artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/maestros

step "Despliegue automático desde GitHub Actions"
# deploy.sh corre como $APP_USER: necesita artisan como www-data y reiniciar estos servicios.
cat > /etc/sudoers.d/maestros-deploy <<EOF
$APP_USER ALL=(www-data) NOPASSWD: /usr/bin/php
$APP_USER ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart maestros-reverb, /usr/bin/supervisorctl restart maestros-queue, /usr/bin/systemctl reload php$PHP-fpm
EOF
chmod 440 /etc/sudoers.d/maestros-deploy && visudo -cf /etc/sudoers.d/maestros-deploy
# La llave de GitHub solo puede ejecutar ssh-gate.sh (desplegar backend o publicar frontend).
AUTH=$(getent passwd "$APP_USER" | cut -d: -f6)/.ssh/authorized_keys
KEY_BODY=$(echo "$DEPLOY_PUBKEY" | awk '{print $2}')
if ! grep -q "$KEY_BODY" "$AUTH"; then
  echo "command=\"bash $BACKEND/deploy/ssh-gate.sh\",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty $DEPLOY_PUBKEY" >> "$AUTH"
fi

step "Listo"
echo "Aplicación:      https://$DOMAIN"
echo "Salud del API:   https://$DOMAIN/up"
if [ -n "$DEMO_PASSWORD" ]; then
  echo "Cuentas demo (cliente@, profesional@, pendiente@, admin@maestros.test), contraseña: $DEMO_PASSWORD"
fi
