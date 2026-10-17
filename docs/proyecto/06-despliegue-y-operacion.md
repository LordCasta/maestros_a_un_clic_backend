# Despliegue y operación

Guía para poner Maestros a un clic en producción en un servidor Linux (por ejemplo AWS EC2 con Ubuntu 24.04) y operarlo. La topología está en [03-diagramas.md §8](03-diagramas.md#8-despliegue). Para el entorno de desarrollo, ver el README de cada repositorio.

## 1. Requisitos del servidor

| Componente | Versión | Para qué |
|------------|---------|----------|
| PHP-FPM | 8.2 o superior (probado hasta 8.5) con `mbstring`, `pdo_mysql`, `intl`, `gd`, `zip`, `bcmath` | API Laravel |
| Composer | 2 | Dependencias del backend |
| Node.js | 20 o superior | Compilar el frontend (puede hacerse en la CI y subir solo `dist/`) |
| MySQL | 8 | Base de datos |
| Nginx | Estable | Servidor web, TLS y proxy de WebSockets |
| Supervisor | — | Mantener vivos la cola y Reverb |
| Certbot | — | Certificado TLS de Let's Encrypt |

Tamaño sugerido para el piloto: 2 vCPU y 2–4 GB de RAM (por ejemplo, `t3.small`), con 30 GB de disco.

## 2. Variables de entorno del backend (`.env`)

| Variable | Producción |
|----------|------------|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_URL` | `https://api.<dominio>`. Se usa para armar las URLs de archivos y las firmadas |
| `APP_KEY` | Generada con `php artisan key:generate` (no se comparte) |
| `DB_*` | Usuario de MySQL exclusivo de la app, con contraseña fuerte |
| `QUEUE_CONNECTION` | `database` (o `redis` si se agrega) |
| `BROADCAST_CONNECTION` | `reverb` |
| `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` | Valores propios, no los de desarrollo |
| `REVERB_HOST` / `REVERB_PORT` / `REVERB_SCHEME` | `api.<dominio>` / `443` / `https` (lo que ve el navegador) |
| `REVERB_SERVER_HOST` / `REVERB_SERVER_PORT` | `127.0.0.1` / `8080` (donde escucha Reverb detrás de Nginx) |
| `MAIL_*` | Proveedor SMTP para la recuperación de contraseña |
| `SANCTUM_STATEFUL_DOMAINS` | No aplica: la API usa tokens Bearer |

Y en el frontend (antes de compilar): `VITE_API_BASE_URL=https://api.<dominio>/api/v1`, más `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT=443` y `VITE_REVERB_SCHEME=https`.

## 3. Instalación del backend

```bash
cd /var/www/maestros-backend
git clone https://github.com/LordCasta/maestros_a_un_clic_backend.git .
composer install --no-dev --optimize-autoloader
cp .env.example .env    # y completar las variables de la tabla
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=CommuneSeeder --force   # comunas y corregimientos de Medellín
php artisan db:seed --class=CategorySeeder --force  # catálogo de categorías
# Nunca DemoSeeder ni DatabaseSeeder en producción: crean usuarios de demostración.
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan event:cache
chown -R www-data:www-data storage bootstrap/cache
```

## 4. Frontend

```bash
npm ci && npm run build      # genera dist/
```

Copia `dist/` a `/var/www/maestros-frontend`. Nginx debe devolver `index.html` en cualquier ruta, porque el enrutamiento lo hace Vue.

## 5. Nginx

```nginx
# Frontend
server {
    server_name <dominio>;
    root /var/www/maestros-frontend;
    location / { try_files $uri $uri/ /index.html; }
}

# API y WebSockets
server {
    server_name api.<dominio>;
    root /var/www/maestros-backend/public;
    index index.php;
    client_max_body_size 6M;           # imágenes de hasta 5 MB

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
    # Reverb (WebSockets)
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_read_timeout 60s;
    }
}
```

Después: `certbot --nginx -d <dominio> -d api.<dominio>` para el TLS.

## 6. Procesos permanentes (Supervisor)

```ini
[program:maestros-queue]
command=php /var/www/maestros-backend/artisan queue:work --sleep=1 --tries=3 --max-time=3600
user=www-data
autostart=true
autorestart=true
stdout_logfile=/var/log/maestros/queue.log

[program:maestros-reverb]
command=php /var/www/maestros-backend/artisan reverb:start --host=127.0.0.1 --port=8080
user=www-data
autostart=true
autorestart=true
stdout_logfile=/var/log/maestros/reverb.log
```

Y en el `crontab` de `www-data`: `* * * * * php /var/www/maestros-backend/artisan schedule:run >> /dev/null 2>&1`.

## 7. Actualizar a una versión nueva

```bash
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan event:cache
php artisan queue:restart          # los workers cargan el código nuevo
supervisorctl restart maestros-reverb
php artisan up
```

Las migraciones solo agregan cambios, nunca editan las existentes (regla del proyecto), así que se pueden aplicar sin perder datos.

## 8. Respaldos y restauración

| Qué | Cómo | Frecuencia | Retención |
|-----|------|------------|-----------|
| Base de datos | `mysqldump --single-transaction maestros > backup-$(date +%F).sql`, comprimido | Diario (cron) | 7 días en el servidor y copia fuera de él (por ejemplo, S3) |
| Archivos | `storage/app/public` y `storage/app/private` (portafolio, avatares, documentos y adjuntos) | Diario | 7 días |

**Restaurar:** `mysql maestros < backup-AAAA-MM-DD.sql` y copiar de vuelta las carpetas de `storage/app`. Conviene probar una restauración al menos una vez antes del lanzamiento.

## 9. Monitoreo

- **Salud:** `GET https://api.<dominio>/up` responde 200 si la app arranca. Configurar un monitor externo gratuito que lo consulte cada 5 minutos (RNF-DIS-01).
- **Errores:** `storage/logs/laravel.log`, con rotación diaria (`LOG_CHANNEL=daily`).
- **Cola:** `php artisan queue:failed` lista los trabajos fallidos; `queue:retry all` los reintenta.
- **Tiempo real:** si las notificaciones no llegan, revisa `supervisorctl status` y `/var/log/maestros/reverb.log`.

## 10. Lista de verificación antes de lanzar

- [ ] `APP_DEBUG=false` y `APP_ENV=production`.
- [ ] HTTPS activo en el frontend y en la API; WebSockets por `wss`.
- [ ] CORS limitado al dominio del frontend (publicar `config/cors.php`, RNF-SEG-08).
- [ ] Claves de Reverb y de la app propias, no las de desarrollo.
- [ ] Usuario de MySQL con permisos solo sobre su base.
- [ ] Respaldos programados y una restauración probada.
- [ ] Monitor de `/up` activo.
- [ ] Política de tratamiento de datos publicada y aceptada en el registro (RNF-PRI-03).
