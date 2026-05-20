Maestros a un clic - Backend API (Laravel 12)

Resumen rápido

Este repositorio contiene una API RESTful mínima viable para la plataforma "Maestros a un clic" construida sobre Laravel 12 con Sanctum, Storage preparado para migrar a S3, pattern de Services/Repositories, Form Requests y Resources.

Setup local (Windows PowerShell)

1. Copiar variables de entorno y configurar DB:

```powershell
copy .env.example .env
# Edita .env para apuntar a tu base de datos MySQL local
php artisan key:generate
composer install
```

2. Crear enlace de storage y ejecutar migraciones + seeders

```powershell
php artisan storage:link
php artisan migrate --seed
```

3. Ejecutar tests (si los agregas luego)

```powershell
php artisan test --filter=NameOfTest
```

Endpoints principales

Auth
- POST /api/auth/register/client (multipart/form-data)
- POST /api/auth/register/professional (multipart/form-data)
- POST /api/auth/login (application/json)
- POST /api/auth/logout (Authorization: Bearer)
- GET  /api/auth/me   (Authorization: Bearer)

Profesionales
- GET /api/professionals
- GET /api/professionals/{id}

Favoritos (auth)
- GET /api/favorites
- POST /api/favorites/{professional}
- DELETE /api/favorites/{professional}

Reservas (auth)
- POST /api/bookings
- GET /api/bookings
- GET /api/bookings/{id}
- POST /api/bookings/{id}/cancel

Client dashboard (auth)
- GET /api/client/dashboard

Ejemplos de requests

1) Registro cliente (multipart/form-data)

Fields:
- name
- email
- password
- password_confirmation
- selfie (file)
- document (file)
- commune
- latitude
- longitude

Curl ejemplo:

```bash
curl -X POST "http://localhost/api/auth/register/client" \
  -F "name=Juan Cliente" \
  -F "email=juan@example.test" \
  -F "password=secret123" \
  -F "password_confirmation=secret123" \
  -F "selfie=@/path/to/selfie.jpg" \
  -F "document=@/path/to/doc.pdf"
```

2) Registro profesional (multipart/form-data)

Fields:
- name
- email
- password
- password_confirmation
- profile_photo (file)
- specialties[] (ids)
- hourly_rate
- experience_years
- description
- certificates[] (files)
- portfolio_images[] (files)

Curl ejemplo:

```bash
curl -X POST "http://localhost/api/auth/register/professional" \
  -F "name=Carlos Pro" \
  -F "email=carlos@example.test" \
  -F "password=secret123" \
  -F "password_confirmation=secret123" \
  -F "description=Especialista en fontanería con 10 años de experiencia..." \
  -F "specialties[]=1" \
  -F "profile_photo=@/path/to/photo.jpg" \
  -F "certificates[]=@/path/to/cert.pdf" \
  -F "portfolio_images[]=@/path/to/port1.jpg"
```

3) Login (application/json)

POST /api/auth/login

Body:
```json
{
  "email": "juan@example.test",
  "password": "secret123"
}
```

Respuesta esperada:
```json
{
  "success": true,
  "message": "Autenticado",
  "data": {
    "token": "<plain-text-token>",
    "user": { /* user resource */ },
    "role": "client"
  }
}
```

Notas de seguridad y despliegue

- Las contraseñas se almacenan con Hash::make
- Validaciones estrictas en FormRequests (mime, size)
- Rate limiting de login puede añadirse con throttle middleware en rutas
- Para producción configure FILESYSTEM_DISK=s3 y añada variables AWS_* en .env

Siguientes pasos recomendados

- Completar tests con Pest
- Añadir control granular de roles/permissions (Spatie) si se requiere UI de administración
- Mejorar búsqueda por distancia (Haversine o un motor geoespacial)
- Añadir notificaciones y jobs para confirmaciones de reservas

Si quieres, procedo a:
- Añadir factories/seeders adicionales para profesionales de ejemplo
- Añadir tests básicos para auth, registrations y uploads
- Añadir paginación/filtrado avanzado y cálculo de distancia

```bash
# comandos útiles
php artisan migrate --seed
php artisan storage:link
composer install
php artisan test
```

