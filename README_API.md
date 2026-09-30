# Maestros a un clic - API para Frontend

Documentación de consumo del backend Laravel 12 para integraciones frontend.

## 1) Convenciones globales

- **Base URL:** `/api`
- **Formato estándar de éxito:**

```json
{
  "success": true,
  "message": "Texto opcional",
  "data": {}
}
```

- **Formato estándar de error de validación:**

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field": ["mensaje"]
  }
}
```

- **Headers recomendados para todas las peticiones:**
  - `Accept: application/json`
  - `Authorization: Bearer <token>` para rutas protegidas
- **Archivos:** usar `multipart/form-data`.
- **Fechas:** `YYYY-MM-DD HH:mm:ss`.
- **Moneda:** número entero o decimal según el endpoint.

## 2) Estado actual del backend

### Implementado y consumible hoy
- Autenticación Sanctum
- Registro de cliente
- Registro de profesional
- Login / Logout / Me
- Listado / detalle de profesionales
- Subida de archivos
- Favoritos
- Reservas (crear, listar, detalle, cancelar)
- Dashboard de cliente

### Pendiente / no expuesto todavía en `routes/api.php`
Estas capacidades **no existen como endpoints reales hoy** en la estructura actual:
- CRUD de servicios del profesional
- Agenda / disponibilidad del profesional
- Aceptar / rechazar / reprogramar reservas desde el profesional

> El frontend no debe consumir esos endpoints todavía. Si se implementan después, esta documentación se actualiza con el contrato final.

## 3) Auth

### POST `/api/auth/register/client`
**Auth:** No
**Content-Type:** `multipart/form-data`

Campos:
- `name` required
- `email` required, unique
- `password` required, min 8
- `password_confirmation` required
- `selfie` required, imagen segura
- `document` required, archivo seguro
- `commune` optional
- `latitude` optional
- `longitude` optional
- `phone` optional

Ejemplo de request:

```bash
curl -X POST "http://localhost/api/auth/register/client" \
  -H "Accept: application/json" \
  -F "name=Juan Cliente" \
  -F "email=juan@example.test" \
  -F "password=secret123" \
  -F "password_confirmation=secret123" \
  -F "commune=Santiago" \
  -F "latitude=-33.4489" \
  -F "longitude=-70.6693" \
  -F "selfie=@/path/to/selfie.jpg" \
  -F "document=@/path/to/document.pdf"
```

Respuesta 201:

```json
{
  "success": true,
  "message": "Usuario registrado",
  "data": {
    "user": {
      "id": 1,
      "name": "Juan Cliente",
      "email": "juan@example.test",
      "phone": null,
      "avatar": null,
      "role": "client",
      "commune": "Santiago",
      "is_verified": false,
      "created_at": "2026-05-19 12:00:00"
    },
    "token": "plain-text-token"
  }
}
```

---

### POST `/api/auth/register/professional`
**Auth:** No
**Content-Type:** `multipart/form-data`

Campos:
- `name` required
- `email` required, unique
- `password` required, min 8
- `password_confirmation` required
- `description` required, min 50
- `experience_years` required, integer >= 0
- `specialties[]` required, ids existentes
- `hourly_rate` optional, numeric >= 0
- `profile_photo` optional, imagen
- `certificates[]` optional, archivos
- `portfolio_images[]` optional, imágenes
- `commune` optional
- `latitude` optional
- `longitude` optional
- `phone` optional

Ejemplo de request:

```bash
curl -X POST "http://localhost/api/auth/register/professional" \
  -H "Accept: application/json" \
  -F "name=Carlos Pro" \
  -F "email=carlos@example.test" \
  -F "password=secret123" \
  -F "password_confirmation=secret123" \
  -F "description=Especialista en fontanería con más de 10 años de experiencia en instalaciones y mantenimiento." \
  -F "experience_years=10" \
  -F "specialties[]=1" \
  -F "specialties[]=2" \
  -F "hourly_rate=45000" \
  -F "commune=Santiago" \
  -F "profile_photo=@/path/to/photo.jpg" \
  -F "certificates[]=@/path/to/cert.pdf" \
  -F "portfolio_images[]=@/path/to/port1.jpg"
```

Respuesta 201:

```json
{
  "success": true,
  "message": "Profesional registrado",
  "data": {
    "user": {
      "id": 2,
      "name": "Carlos Pro",
      "email": "carlos@example.test",
      "role": "professional"
    },
    "token": "plain-text-token"
  }
}
```

---

### POST `/api/auth/login`
**Auth:** No
**Content-Type:** `application/json`

Campos:
- `email` required
- `password` required

Ejemplo:

```json
{
  "email": "juan@example.test",
  "password": "secret123"
}
```

Respuesta 200:

```json
{
  "success": true,
  "message": "Autenticado",
  "data": {
    "token": "plain-text-token",
    "role": "client",
    "user": {
      "id": 1,
      "name": "Juan Cliente",
      "email": "juan@example.test",
      "phone": null,
      "avatar": null,
      "role": "client",
      "commune": "Santiago",
      "is_verified": false,
      "created_at": "2026-05-19 12:00:00"
    }
  }
}
```

Notas:
- Tiene rate limit configurado en la ruta.
- Si falla la validación y el cliente no envía `Accept: application/json`, Laravel puede responder con HTML o redirect.

---

### POST `/api/auth/logout`
**Auth:** Sí, Bearer token

Respuesta 200:

```json
{
  "success": true,
  "message": "Sesión cerrada"
}
```

---

### GET `/api/auth/me`
**Auth:** Sí, Bearer token

Respuesta 200:

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Juan Cliente",
    "email": "juan@example.test",
    "phone": null,
    "avatar": null,
    "role": "client",
    "commune": "Santiago",
    "is_verified": false,
    "created_at": "2026-05-19 12:00:00"
  }
}
```

---

## 4) Profesionales

### GET `/api/professionals`
**Auth:** No

Query params soportados actualmente:
- `commune`
- `specialty_id`
- `min_price`
- `max_price`
- `per_page` (default `15`)

Ejemplo:

```text
/api/professionals?commune=Santiago&specialty_id=1&min_price=10000&max_price=50000&per_page=10
```

Respuesta 200:

```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "name": "Carlos Pro",
      "email": "carlos@example.test",
      "avatar": "http://localhost/storage/profiles/photo.jpg",
      "commune": "Santiago",
      "description": "...",
      "experience_years": 10,
      "hourly_rate": "45000.00",
      "is_verified": true,
      "specialties": [
        { "id": 1, "name": "Plomería", "slug": "plomeria" }
      ],
      "portfolio": [
        { "image": "http://localhost/storage/portfolio/1.jpg", "description": null }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 1
  }
}
```

---

### GET `/api/professionals/{id}`
**Auth:** No

Respuesta 200:

```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Carlos Pro",
    "email": "carlos@example.test",
    "avatar": "http://localhost/storage/profiles/photo.jpg",
    "commune": "Santiago",
    "description": "...",
    "experience_years": 10,
    "hourly_rate": "45000.00",
    "is_verified": true,
    "specialties": [],
    "portfolio": []
  }
}
```

---

## 5) Subidas

### POST `/api/uploads`
**Auth:** Sí, Bearer token
**Content-Type:** `multipart/form-data`

Campos:
- `file` required
- `folder` optional (`profiles`, `selfies`, `documents`, `certificates`, `portfolio`, etc.)

Respuesta 200:

```json
{
  "success": true,
  "data": {
    "path": "profiles/abc123.jpg",
    "url": "http://localhost/storage/profiles/abc123.jpg"
  }
}
```

Notas:
- El backend usa `Storage::disk('public')`.
- El frontend debe enviar el archivo como `file`.

---

## 6) Favoritos

### GET `/api/favorites`
**Auth:** Sí, Bearer token
**Rol esperado:** `client`

Respuesta 200:

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "client_id": 10,
      "professional": {
        "id": 2,
        "name": "Carlos Pro",
        "email": "carlos@example.test",
        "avatar": null,
        "commune": "Santiago",
        "description": "...",
        "experience_years": 10,
        "hourly_rate": "45000.00",
        "is_verified": true,
        "specialties": [],
        "portfolio": []
      },
      "created_at": "2026-05-19 12:00:00"
    }
  ]
}
```

---

### POST `/api/favorites/{professional}`
**Auth:** Sí, Bearer token

- `{professional}` debe ser un usuario con rol `professional`
- El backend evita duplicados con `firstOrCreate`

Respuesta 201:

```json
{
  "success": true,
  "message": "Agregado a favoritos",
  "data": {
    "id": 1,
    "client_id": 10,
    "professional": {
      "id": 2,
      "name": "Carlos Pro"
    }
  }
}
```

---

### DELETE `/api/favorites/{professional}`
**Auth:** Sí, Bearer token

Respuesta 200:

```json
{
  "success": true,
  "message": "Eliminado de favoritos"
}
```

---

## 7) Reservas

### POST `/api/bookings`
**Auth:** Sí, Bearer token
**Rol esperado:** `client`
**Content-Type:** `application/json`

Body:

```json
{
  "professional_id": 2,
  "service_description": "Instalación de grifería",
  "scheduled_date": "2026-06-01 14:00:00",
  "total": 45000
}
```

Reglas clave:
- `professional_id` requerido
- `scheduled_date` debe ser futura
- `total` opcional, pero si viene debe ser numérico

Respuesta 201:

```json
{
  "success": true,
  "data": {
    "id": 42,
    "client_id": 10,
    "professional_id": 2,
    "service_description": "Instalación de grifería",
    "scheduled_date": "2026-06-01 14:00:00",
    "status": "pending",
    "total": 45000,
    "created_at": "2026-05-19 12:00:00"
  }
}
```

---

### GET `/api/bookings`
**Auth:** Sí, Bearer token

Comportamiento actual:
- Si el usuario autenticado es `professional`, lista sus reservas por `professional_id`.
- Si el usuario autenticado es `client`, lista sus reservas por `client_id`.

Respuesta 200:

```json
{
  "success": true,
  "data": []
}
```

---

### GET `/api/bookings/{id}`
**Auth:** Sí, Bearer token

- Protegido por policy
- Solo pueden verlo el cliente, el profesional asociado o un admin

Respuesta 200:

```json
{
  "success": true,
  "data": {
    "id": 42,
    "client_id": 10,
    "professional_id": 2,
    "service_description": "Instalación de grifería",
    "scheduled_date": "2026-06-01 14:00:00",
    "status": "pending",
    "total": 45000,
    "created_at": "2026-05-19 12:00:00"
  }
}
```

---

### POST `/api/bookings/{id}/cancel`
**Auth:** Sí, Bearer token

- Protegido por policy
- Actualmente solo el cliente dueño o admin puede cancelar
- El estado pasa a `cancelled`

Respuesta 200:

```json
{
  "success": true,
  "message": "Reserva cancelada",
  "data": {
    "id": 42,
    "status": "cancelled"
  }
}
```

---

## 8) Dashboard del cliente

### GET `/api/client/dashboard`
**Auth:** Sí, Bearer token

Devuelve:
- `active_bookings`
- `favorites`
- `nearby_professionals`
- `popular_categories`

Respuesta 200:

```json
{
  "success": true,
  "data": {
    "active_bookings": [],
    "favorites": [],
    "nearby_professionals": [],
    "popular_categories": []
  }
}
```

---

## 9) Estructura de recursos que el frontend puede esperar

### `UserResource`
Campos:
- `id`
- `name`
- `email`
- `phone`
- `avatar`
- `role`
- `commune`
- `is_verified`
- `created_at`

### `ProfessionalResource`
Campos:
- `id`
- `name`
- `email`
- `avatar`
- `commune`
- `description`
- `experience_years`
- `hourly_rate`
- `is_verified`
- `specialties[]`
- `portfolio[]`

### `BookingResource`
Campos:
- `id`
- `client_id`
- `professional_id`
- `service_description`
- `scheduled_date`
- `status`
- `total`
- `created_at`

### `FavoriteResource`
Campos:
- `id`
- `client_id`
- `professional`
- `created_at`

## 10) Lo que el frontend NO debe consumir todavía

Estas rutas fueron solicitadas como idea funcional, pero hoy **no están implementadas en el backend actual**:

- `GET /api/professional/services`
- `POST /api/professional/services`
- `PUT /api/professional/services/{id}`
- `DELETE /api/professional/services/{id}`
- `GET /api/professional/availability`
- `POST /api/professional/availability`
- `POST /api/bookings/{id}/accept`
- `POST /api/bookings/{id}/reject`
- `POST /api/bookings/{id}/reschedule`

### Observación importante
Existe `availability_status` en `professional_profiles`, pero eso **no es una agenda real**. Solo indica estado general del perfil (`available`, `busy`, `offline`).

## 11) Errores frecuentes al consumir la API

### La API responde HTML en vez de JSON
Causa común: falta el header `Accept: application/json`.

### `401 Unauthenticated`
Causa: falta token o token inválido.

### `403 Forbidden`
Causa: el usuario autenticado no tiene el rol correcto o la policy bloqueó la acción.

### `422 Validation failed`
Causa: faltan campos o el formato no cumple las reglas.

### `429 Too Many Requests`
Causa: el login tiene rate limiting.

## 12) Recomendaciones para frontend

- Guardar el token de Sanctum después del login/registro.
- Enviar `Authorization: Bearer <token>` en todas las rutas protegidas.
- Enviar `Accept: application/json` siempre.
- Usar `multipart/form-data` solo cuando haya archivos.
- No asumir endpoints de servicios/agendas hasta que existan en `routes/api.php`.

---

## 13) Resumen rápido de endpoints consumibles hoy

### Auth
- `POST /api/auth/register/client`
- `POST /api/auth/register/professional`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/me`

### Profesionales
- `GET /api/professionals`
- `GET /api/professionals/{id}`

### Archivos
- `POST /api/uploads`

### Favoritos
- `GET /api/favorites`
- `POST /api/favorites/{professional}`
- `DELETE /api/favorites/{professional}`

### Reservas
- `POST /api/bookings`
- `GET /api/bookings`
- `GET /api/bookings/{id}`
- `POST /api/bookings/{id}/cancel`

### Dashboard cliente
- `GET /api/client/dashboard`
