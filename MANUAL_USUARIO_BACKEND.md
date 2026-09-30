# Manual de usuario del backend

**Proyecto:** Maestros a un clic  
**Tipo:** API REST para consumo desde frontend  
**Base de consumo:** `/api`

Este documento explica cómo usar el backend **ya construido** desde el frontend o desde una herramienta como Postman/Insomnia. No es una guía de instalación.

---

## 1) Qué hace hoy el backend

La API actual permite:

- Registro de cliente
- Registro de profesional
- Inicio y cierre de sesión con Sanctum
- Obtener el usuario autenticado
- Listar y ver profesionales
- Subir archivos
- Gestionar favoritos
- Crear y consultar reservas
- Cancelar reservas
- Consultar dashboard del cliente

### Lo que todavía no está expuesto como endpoint real
Estas funcionalidades se mencionaron como objetivo, pero **no existen todavía en `routes/api.php`**:

- CRUD de servicios del profesional
- Agenda / disponibilidad del profesional
- Aceptar / rechazar / reprogramar reservas desde el profesional

> El frontend **no debe consumir** esos endpoints todavía.

---

## 2) Reglas globales para consumir la API

### Base URL
Todas las rutas viven bajo:

```text
/api
```

### Headers recomendados
Para evitar respuestas HTML y errores de sesión, enviar siempre:

```http
Accept: application/json
Authorization: Bearer <token>
```

### Archivos
Cuando una ruta reciba archivos, usar:

```http
Content-Type: multipart/form-data
```

### Fechas
Las fechas se manejan en este formato:

```text
YYYY-MM-DD HH:mm:ss
```

### Formato estándar de respuesta
Éxito:

```json
{
  "success": true,
  "message": "Texto opcional",
  "data": {}
}
```

Error de validación:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field": ["mensaje"]
  }
}
```

### Códigos HTTP habituales
- `200`: OK
- `201`: creado
- `401`: no autenticado
- `403`: acceso prohibido por rol o policy
- `404`: recurso no encontrado
- `422`: validación fallida
- `429`: demasiadas solicitudes

---

## 3) Roles actuales

El backend maneja estos roles:

- `client`
- `professional`
- `admin`

### Uso esperado por el frontend
- `client`: busca profesionales, guarda favoritos y crea reservas
- `professional`: consulta su información y sus reservas
- `admin`: reservado para reglas internas/policies

---

## 4) Flujo recomendado de consumo

### Flujo cliente
1. Registrar cliente
2. Iniciar sesión
3. Guardar token
4. Consultar `/api/auth/me`
5. Buscar profesionales
6. Agregar a favoritos
7. Crear reserva
8. Consultar dashboard

### Flujo profesional
1. Registrar profesional
2. Iniciar sesión
3. Guardar token
4. Consultar `/api/auth/me`
5. Ver listado de reservas asignadas

> Importante: hoy el profesional **no puede** gestionar servicios propios ni agenda porque esos endpoints aún no existen.

---

## 5) Autenticación

### POST `/api/auth/register/client`
**Auth:** no  
**Content-Type:** `multipart/form-data`

#### Campos
- `name` obligatorio
- `email` obligatorio, único
- `password` obligatorio, mínimo 8
- `password_confirmation` obligatorio
- `selfie` obligatorio, imagen segura
- `document` obligatorio, archivo seguro
- `commune` opcional
- `latitude` opcional
- `longitude` opcional
- `phone` opcional

#### Ejemplo de request
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

#### Respuesta esperada
```json
{
  "success": true,
  "message": "Usuario registrado",
  "data": {
    "user": {
      "id": 1,
      "name": "Juan Cliente",
      "email": "juan@example.test",
      "role": "client"
    },
    "token": "plain-text-token"
  }
}
```

---

### POST `/api/auth/register/professional`
**Auth:** no  
**Content-Type:** `multipart/form-data`

#### Campos
- `name` obligatorio
- `email` obligatorio, único
- `password` obligatorio, mínimo 8
- `password_confirmation` obligatorio
- `description` obligatorio, mínimo 50 caracteres
- `experience_years` obligatorio, entero mayor o igual a 0
- `specialties[]` obligatorio, IDs existentes
- `hourly_rate` opcional, numérico
- `profile_photo` opcional, imagen
- `certificates[]` opcional, archivos
- `portfolio_images[]` opcional, imágenes
- `commune` opcional
- `latitude` opcional
- `longitude` opcional
- `phone` opcional

#### Ejemplo de request
```bash
curl -X POST "http://localhost/api/auth/register/professional" \
  -H "Accept: application/json" \
  -F "name=Carlos Pro" \
  -F "email=carlos@example.test" \
  -F "password=secret123" \
  -F "password_confirmation=secret123" \
  -F "description=Especialista en fontanería con más de 10 años de experiencia." \
  -F "experience_years=10" \
  -F "specialties[]=1" \
  -F "specialties[]=2" \
  -F "hourly_rate=45000" \
  -F "commune=Santiago" \
  -F "profile_photo=@/path/to/photo.jpg" \
  -F "certificates[]=@/path/to/cert.pdf" \
  -F "portfolio_images[]=@/path/to/port1.jpg"
```

#### Respuesta esperada
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
**Auth:** no  
**Content-Type:** `application/json`

#### Campos
- `email` obligatorio
- `password` obligatorio

#### Ejemplo de request
```json
{
  "email": "juan@example.test",
  "password": "secret123"
}
```

#### Respuesta esperada
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
      "role": "client"
    }
  }
}
```

#### Notas
- La ruta tiene rate limiting.
- Si falta `Accept: application/json`, Laravel puede devolver HTML o un redirect en errores.

---

### POST `/api/auth/logout`
**Auth:** sí, Bearer token

#### Respuesta esperada
```json
{
  "success": true,
  "message": "Sesión cerrada"
}
```

---

### GET `/api/auth/me`
**Auth:** sí, Bearer token

#### Respuesta esperada
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Juan Cliente",
    "email": "juan@example.test",
    "role": "client"
  }
}
```

---

## 6) Profesionales

### GET `/api/professionals`
**Auth:** no

#### Query params soportados
- `commune`
- `specialty_id`
- `min_price`
- `max_price`
- `per_page`

#### Ejemplo
```text
/api/professionals?commune=Santiago&specialty_id=1&min_price=10000&max_price=50000&per_page=10
```

#### Respuesta esperada
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
      "specialties": []
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
**Auth:** no

#### Respuesta esperada
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
    "specialties": []
  }
}
```

---

## 7) Subida de archivos

### POST `/api/uploads`
**Auth:** sí, Bearer token  
**Content-Type:** `multipart/form-data`

#### Campos
- `file` obligatorio
- `folder` opcional (`profiles`, `selfies`, `documents`, `certificates`, `portfolio`)

#### Ejemplo
```bash
curl -X POST "http://localhost/api/uploads" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>" \
  -F "file=@/path/to/file.jpg" \
  -F "folder=profiles"
```

#### Respuesta esperada
```json
{
  "success": true,
  "data": {
    "path": "profiles/abc123.jpg",
    "url": "http://localhost/storage/profiles/abc123.jpg"
  }
}
```

#### Nota
El backend usa `Storage::disk('public')`.

---

## 8) Favoritos

### GET `/api/favorites`
**Auth:** sí, Bearer token  
**Rol esperado:** `client`

#### Respuesta esperada
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
        "email": "carlos@example.test"
      },
      "created_at": "2026-05-19 12:00:00"
    }
  ]
}
```

---

### POST `/api/favorites/{professional}`
**Auth:** sí, Bearer token  
**Rol esperado:** `client`

#### Reglas
- `{professional}` debe ser un usuario con rol `professional`
- El backend evita duplicados

#### Respuesta esperada
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
**Auth:** sí, Bearer token

#### Respuesta esperada
```json
{
  "success": true,
  "message": "Eliminado de favoritos"
}
```

---

## 9) Reservas

### POST `/api/bookings`
**Auth:** sí, Bearer token  
**Rol esperado:** `client`  
**Content-Type:** `application/json`

#### Campos
- `professional_id` obligatorio
- `service_description` obligatorio
- `scheduled_date` obligatorio y futura
- `total` opcional, numérico

#### Ejemplo de request
```json
{
  "professional_id": 2,
  "service_description": "Instalación de grifería",
  "scheduled_date": "2026-06-01 14:00:00",
  "total": 45000
}
```

#### Respuesta esperada
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

#### Validaciones importantes
- El profesional debe existir.
- La fecha debe ser futura.
- El frontend debe enviar la fecha en formato correcto.

---

### GET `/api/bookings`
**Auth:** sí, Bearer token

#### Comportamiento actual
- Si el usuario es `professional`, lista reservas por `professional_id`
- Si el usuario es `client`, lista reservas por `client_id`

#### Respuesta esperada
```json
{
  "success": true,
  "data": []
}
```

---

### GET `/api/bookings/{id}`
**Auth:** sí, Bearer token

#### Acceso
- Cliente dueño
- Profesional asociado
- Admin

#### Respuesta esperada
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
**Auth:** sí, Bearer token

#### Reglas
- Solo el cliente dueño o admin puede cancelar
- El estado pasa a `cancelled`

#### Respuesta esperada
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

## 10) Dashboard del cliente

### GET `/api/client/dashboard`
**Auth:** sí, Bearer token

#### Devuelve
- `active_bookings`
- `favorites`
- `nearby_professionals`
- `popular_categories`

#### Respuesta esperada
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

## 11) Qué recursos debe esperar el frontend

### `UserResource`
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
- `id`
- `client_id`
- `professional_id`
- `service_description`
- `scheduled_date`
- `status`
- `total`
- `created_at`

### `FavoriteResource`
- `id`
- `client_id`
- `professional`
- `created_at`

---

## 12) Errores frecuentes al integrar

### La API responde HTML en vez de JSON
Causa típica: falta `Accept: application/json`.

### `401 Unauthenticated`
Causa: no hay token o el token es inválido.

### `403 Forbidden`
Causa: el rol no coincide o la policy bloqueó la acción.

### `422 Validation failed`
Causa: faltan campos o el formato es incorrecto.

### `429 Too Many Requests`
Causa: el login tiene rate limiting.

---

## 13) Endpoints que el frontend sí puede consumir hoy

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

---

## 14) Resumen para frontend

### Guardar token
Después de login o registro, guardar el token devuelto y reenviarlo en:

```http
Authorization: Bearer <token>
```

### Siempre enviar JSON explícito
En cualquier request que no sea archivo:

```http
Accept: application/json
Content-Type: application/json
```

### Usar multipart solo cuando corresponda
- Registro de cliente
- Registro de profesional
- Subida de archivos

### No consumir rutas no implementadas
No usar todavía:
- servicios del profesional
- agenda/disponibilidad
- aceptar/rechazar/reprogramar reservas

---

## 15) Nota final

Este manual describe **solo lo que existe hoy en el backend**. Si el backend agrega nuevos endpoints, este documento debe actualizarse para mantener la integración frontend sin errores.
# Manual de usuario del backend

**Proyecto:** Maestros a un clic  
**Tipo:** API REST para consumo desde frontend  
**Base de consumo:** `/api`

Este documento explica cómo usar el backend **ya construido** desde el frontend o desde una herramienta como Postman/Insomnia. No es una guía de instalación.

---

## 1) Qué hace hoy el backend

La API actual permite:

- Registro de cliente
- Registro de profesional
- Inicio y cierre de sesión con Sanctum
- Obtener el usuario autenticado
- Listar y ver profesionales
- Subir archivos
- Gestionar favoritos
- Crear y consultar reservas
- Cancelar reservas
- Consultar dashboard del cliente

### Lo que todavía no está expuesto como endpoint real
Estas funcionalidades se mencionaron como objetivo, pero **no existen todavía en `routes/api.php`**:

- CRUD de servicios del profesional
- Agenda / disponibilidad del profesional
- Aceptar / rechazar / reprogramar reservas desde el profesional

> El frontend **no debe consumir** esos endpoints todavía.

---

## 2) Reglas globales para consumir la API

### Base URL
Todas las rutas viven bajo:

```text
/api
```

### Headers recomendados
Para evitar respuestas HTML y errores de sesión, enviar siempre:

```http
Accept: application/json
Authorization: Bearer <token>
```

### Archivos
Cuando una ruta reciba archivos, usar:

```http
Content-Type: multipart/form-data
```

### Fechas
Las fechas se manejan en este formato:

```text
YYYY-MM-DD HH:mm:ss
```

### Formato estándar de respuesta
Éxito:

```json
{
  "success": true,
  "message": "Texto opcional",
  "data": {}
}
```

Error de validación:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field": ["mensaje"]
  }
}
```

### Códigos HTTP habituales
- `200`: OK
- `201`: creado
- `401`: no autenticado
- `403`: acceso prohibido por rol o policy
- `404`: recurso no encontrado
- `422`: validación fallida
- `429`: demasiadas solicitudes

---

## 3) Roles actuales

El backend maneja estos roles:

- `client`
- `professional`
- `admin`

### Uso esperado por el frontend
- `client`: busca profesionales, guarda favoritos y crea reservas
- `professional`: consulta su información y sus reservas
- `admin`: reservado para reglas internas/policies

---

## 4) Flujo recomendado de consumo

### Flujo cliente
1. Registrar cliente
2. Iniciar sesión
3. Guardar token
4. Consultar `/api/auth/me`
5. Buscar profesionales
6. Agregar a favoritos
7. Crear reserva
8. Consultar dashboard

### Flujo profesional
1. Registrar profesional
2. Iniciar sesión
3. Guardar token
4. Consultar `/api/auth/me`
5. Ver listado de reservas asignadas

> Importante: hoy el profesional **no puede** gestionar servicios propios ni agenda porque esos endpoints aún no existen.

---

## 5) Autenticación

### POST `/api/auth/register/client`
**Auth:** no  
**Content-Type:** `multipart/form-data`

#### Campos
- `name` obligatorio
- `email` obligatorio, único
- `password` obligatorio, mínimo 8
- `password_confirmation` obligatorio
- `selfie` obligatorio, imagen segura
- `document` obligatorio, archivo seguro
- `commune` opcional
- `latitude` opcional
- `longitude` opcional
- `phone` opcional

#### Ejemplo de request
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

#### Respuesta esperada
```json
{
  "success": true,
  "message": "Usuario registrado",
  "data": {
    "user": {
      "id": 1,
      "name": "Juan Cliente",
      "email": "juan@example.test",
      "role": "client"
    },
    "token": "plain-text-token"
  }
}
```

---

### POST `/api/auth/register/professional`
**Auth:** no  
**Content-Type:** `multipart/form-data`

#### Campos
- `name` obligatorio
- `email` obligatorio, único
- `password` obligatorio, mínimo 8
- `password_confirmation` obligatorio
- `description` obligatorio, mínimo 50 caracteres
- `experience_years` obligatorio, entero mayor o igual a 0
- `specialties[]` obligatorio, IDs existentes
- `hourly_rate` opcional, numérico
- `profile_photo` opcional, imagen
- `certificates[]` opcional, archivos
- `portfolio_images[]` opcional, imágenes
- `commune` opcional
- `latitude` opcional
- `longitude` opcional
- `phone` opcional

#### Ejemplo de request
```bash
curl -X POST "http://localhost/api/auth/register/professional" \
  -H "Accept: application/json" \
  -F "name=Carlos Pro" \
  -F "email=carlos@example.test" \
  -F "password=secret123" \
  -F "password_confirmation=secret123" \
  -F "description=Especialista en fontanería con más de 10 años de experiencia." \
  -F "experience_years=10" \
  -F "specialties[]=1" \
  -F "specialties[]=2" \
  -F "hourly_rate=45000" \
  -F "commune=Santiago" \
  -F "profile_photo=@/path/to/photo.jpg" \
  -F "certificates[]=@/path/to/cert.pdf" \
  -F "portfolio_images[]=@/path/to/port1.jpg"
```

#### Respuesta esperada
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
**Auth:** no  
**Content-Type:** `application/json`

#### Campos
- `email` obligatorio
- `password` obligatorio

#### Ejemplo de request
```json
{
  "email": "juan@example.test",
  "password": "secret123"
}
```

#### Respuesta esperada
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
      "role": "client"
    }
  }
}
```

#### Notas
- La ruta tiene rate limiting.
- Si falta `Accept: application/json`, Laravel puede devolver HTML o un redirect en errores.

---

### POST `/api/auth/logout`
**Auth:** sí, Bearer token

#### Respuesta esperada
```json
{
  "success": true,
  "message": "Sesión cerrada"
}
```

---

### GET `/api/auth/me`
**Auth:** sí, Bearer token

#### Respuesta esperada
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Juan Cliente",
    "email": "juan@example.test",
    "role": "client"
  }
}
```

---

## 6) Profesionales

### GET `/api/professionals`
**Auth:** no

#### Query params soportados
- `commune`
- `specialty_id`
- `min_price`
- `max_price`
- `per_page`

#### Ejemplo
```text
/api/professionals?commune=Santiago&specialty_id=1&min_price=10000&max_price=50000&per_page=10
```

#### Respuesta esperada
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
      "specialties": []
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
**Auth:** no

#### Respuesta esperada
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
    "specialties": []
  }
}
```

---

## 7) Subida de archivos

### POST `/api/uploads`
**Auth:** sí, Bearer token  
**Content-Type:** `multipart/form-data`

#### Campos
- `file` obligatorio
- `folder` opcional (`profiles`, `selfies`, `documents`, `certificates`, `portfolio`)

#### Ejemplo
```bash
curl -X POST "http://localhost/api/uploads" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>" \
  -F "file=@/path/to/file.jpg" \
  -F "folder=profiles"
```

#### Respuesta esperada
```json
{
  "success": true,
  "data": {
    "path": "profiles/abc123.jpg",
    "url": "http://localhost/storage/profiles/abc123.jpg"
  }
}
```

#### Nota
El backend usa `Storage::disk('public')`.

---

## 8) Favoritos

### GET `/api/favorites`
**Auth:** sí, Bearer token  
**Rol esperado:** `client`

#### Respuesta esperada
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
        "email": "carlos@example.test"
      },
      "created_at": "2026-05-19 12:00:00"
    }
  ]
}
```

---

### POST `/api/favorites/{professional}`
**Auth:** sí, Bearer token  
**Rol esperado:** `client`

#### Reglas
- `{professional}` debe ser un usuario con rol `professional`
- El backend evita duplicados

#### Respuesta esperada
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
**Auth:** sí, Bearer token

#### Respuesta esperada
```json
{
  "success": true,
  "message": "Eliminado de favoritos"
}
```

---

## 9) Reservas

### POST `/api/bookings`
**Auth:** sí, Bearer token  
**Rol esperado:** `client`  
**Content-Type:** `application/json`

#### Campos
- `professional_id` obligatorio
- `service_description` obligatorio
- `scheduled_date` obligatorio y futura
- `total` opcional, numérico

#### Ejemplo de request
```json
{
  "professional_id": 2,
  "service_description": "Instalación de grifería",
  "scheduled_date": "2026-06-01 14:00:00",
  "total": 45000
}
```

#### Respuesta esperada
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

#### Validaciones importantes
- El profesional debe existir.
- La fecha debe ser futura.
- El frontend debe enviar la fecha en formato correcto.

---

### GET `/api/bookings`
**Auth:** sí, Bearer token

#### Comportamiento actual
- Si el usuario es `professional`, lista reservas por `professional_id`
- Si el usuario es `client`, lista reservas por `client_id`

#### Respuesta esperada
```json
{
  "success": true,
  "data": []
}
```

---

### GET `/api/bookings/{id}`
**Auth:** sí, Bearer token

#### Acceso
- Cliente dueño
- Profesional asociado
- Admin

#### Respuesta esperada
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
**Auth:** sí, Bearer token

#### Reglas
- Solo el cliente dueño o admin puede cancelar
- El estado pasa a `cancelled`

#### Respuesta esperada
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

## 10) Dashboard del cliente

### GET `/api/client/dashboard`
**Auth:** sí, Bearer token

#### Devuelve
- `active_bookings`
- `favorites`
- `nearby_professionals`
- `popular_categories`

#### Respuesta esperada
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

## 11) Qué recursos debe esperar el frontend

### `UserResource`
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
- `id`
- `client_id`
- `professional_id`
- `service_description`
- `scheduled_date`
- `status`
- `total`
- `created_at`

### `FavoriteResource`
- `id`
- `client_id`
- `professional`
- `created_at`

---

## 12) Errores frecuentes al integrar

### La API responde HTML en vez de JSON
Causa típica: falta `Accept: application/json`.

### `401 Unauthenticated`
Causa: no hay token o el token es inválido.

### `403 Forbidden`
Causa: el rol no coincide o la policy bloqueó la acción.

### `422 Validation failed`
Causa: faltan campos o el formato es incorrecto.

### `429 Too Many Requests`
Causa: el login tiene rate limiting.

---

## 13) Endpoints que el frontend sí puede consumir hoy

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

---

## 14) Resumen para frontend

### Guardar token
Después de login o registro, guardar el token devuelto y reenviarlo en:

```http
Authorization: Bearer <token>
```

### Siempre enviar JSON explícito
En cualquier request que no sea archivo:

```http
Accept: application/json
Content-Type: application/json
```

### Usar multipart solo cuando corresponda
- Registro de cliente
- Registro de profesional
- Subida de archivos

### No consumir rutas no implementadas
No usar todavía:
- servicios del profesional
- agenda/disponibilidad
- aceptar/rechazar/reprogramar reservas

---

## 15) Nota final

Este manual describe **solo lo que existe hoy en el backend**. Si el backend agrega nuevos endpoints, este documento debe actualizarse para mantener la integración frontend sin errores.


