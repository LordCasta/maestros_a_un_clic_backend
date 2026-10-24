#!/usr/bin/env bash
# Publica en el servidor la versión de main. Lo ejecuta el workflow de despliegue por SSH
# (vía ssh-gate.sh); también se puede correr a mano en el servidor.
set -euo pipefail
cd /var/www/maestros-backend

# artisan corre como www-data, el usuario de PHP-FPM: así los logs y la caché que crea quedan
# con el dueño correcto.
artisan() { sudo -u www-data php artisan "$@"; }

artisan down --retry=15 || true
# Pase lo que pase, la aplicación vuelve a quedar arriba.
trap 'artisan up' EXIT

git fetch --quiet origin main
git reset --hard --quiet origin/main
composer install --no-dev --no-interaction --no-progress --optimize-autoloader
artisan migrate --force
artisan config:cache
artisan route:cache
artisan event:cache

# La cola y Reverb cargan el código nuevo; PHP-FPM recarga el OPcache.
artisan queue:restart
sudo /usr/bin/supervisorctl restart maestros-reverb
sudo /usr/bin/systemctl reload php8.3-fpm

echo "Desplegado $(git rev-parse --short HEAD): $(git log -1 --pretty=%s)"
