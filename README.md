# Maestros a un clic — Backend

API REST del marketplace que conecta clientes con maestros y profesionales del hogar en Medellín: búsqueda por comuna y especialidad, reservas con agenda, verificación de identidad y calificación recíproca.

Laravel 12 · PHP 8.2+ · MySQL 8 · Sanctum · Pest

Frontend: [maestros_a_un_clic_frontend](https://github.com/LordCasta/maestros_a_un_clic_frontend)

## Documentación

| Documento | Contenido |
|-----------|-----------|
| [docs/arquitectura.md](docs/arquitectura.md) | Capas, reglas y checklist para agregar un módulo. **Leer antes de programar.** |
| [docs/modelo-de-datos.md](docs/modelo-de-datos.md) | Diagrama ER, máquina de estados de la reserva, decisiones y trazabilidad con las HU |
| [docs/api/convenciones.md](docs/api/convenciones.md) | Formato de respuestas y errores, tipos, enums |
| [docs/api/endpoints.md](docs/api/endpoints.md) | Endpoints implementados y planeados por módulo |

## Instalación local

Requisitos: PHP 8.2+ con `pdo_mysql` y `pdo_sqlite`, Composer, MySQL 8.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Crea la base de datos `maestros_backend` en MySQL y ajusta `DB_USERNAME` / `DB_PASSWORD` en `.env`. Luego:

```bash
php artisan migrate:fresh --seed   # esquema + comunas + categorías + datos demo
php artisan storage:link           # sirve avatares y portafolio en /storage
php artisan serve                  # http://127.0.0.1:8000/api/v1
```

> `migrate:fresh` **borra todas las tablas** de la base configurada en `.env`. Úsalo solo en tu base local.

### Cuentas demo

Todas con contraseña `password`:

| Correo | Rol | Para probar |
|--------|-----|-------------|
| `admin@maestros.test` | Administrador | Panel de admin |
| `cliente@maestros.test` | Cliente verificado | Reservas en varios estados, favoritos |
| `pendiente@maestros.test` | Cliente sin verificar | Bloqueo de reservas por verificación |
| `profesional@maestros.test` | Profesional verificado | Servicios, agenda, reservas recibidas |

Además hay 12 profesionales verificados más y 2 pendientes de verificación, repartidos por comunas.

## Tests y formato

```bash
php artisan test          # SQLite en memoria: no toca tu base MySQL
vendor/bin/pint --dirty   # formatea los archivos que cambiaste
```

Un cambio no se integra si los tests fallan o si Pint encuentra algo por formatear.
