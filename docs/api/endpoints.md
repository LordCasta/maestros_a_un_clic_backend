# Catálogo de endpoints — API v1

Todas las rutas cuelgan de `/api/v1`. Formato de respuesta, errores y tipos: [convenciones.md](convenciones.md).

Este documento da la visión por módulo y el contrato de lo que falta. El detalle exacto de campos de lo ya implementado está en la documentación interactiva, generada desde el código: `http://127.0.0.1:8000/docs/api` (entorno local).

**Leyenda de acceso:** 🌐 público · 🔑 autenticado · 👤 cliente · 🛠 profesional · 🛡 admin

## Parte 1 — Implementados

### Autenticación

| Método | Ruta | Acceso | HU |
|--------|------|--------|----|
| POST | `/auth/register/client` | 🌐 | HU006 |
| POST | `/auth/register/professional` | 🌐 | HU007 |
| POST | `/auth/login` | 🌐 | HU001 |
| POST | `/auth/logout` | 🔑 | HU021 |
| GET | `/auth/me` | 🔑 | — |

Registro, login: máximo 10 intentos por minuto.

#### `POST /auth/register/client`
`multipart/form-data` (por la foto) o JSON si no hay foto.

| Campo | Regla |
|-------|-------|
| `name` | obligatorio, máx. 255 |
| `email` | obligatorio, único |
| `password`, `password_confirmation` | obligatorio, mín. 8 |
| `phone` | opcional |
| `commune_id` | opcional, id de `/communes` |
| `address`, `latitude`, `longitude` | opcionales |
| `birth_date` | opcional, `YYYY-MM-DD` |
| `avatar` | opcional, imagen jpg/png/webp, máx. 5 MB |

Los documentos de identidad **no** se envían aquí, sino en el módulo de verificación.

**201**
```json
{
  "success": true,
  "message": "Cuenta creada.",
  "data": {
    "user": {
      "id": 3,
      "name": "Camila Restrepo",
      "email": "camila@example.com",
      "phone": "3001112233",
      "avatar_url": null,
      "role": "client",
      "commune": { "id": 14, "code": "14", "name": "El Poblado", "type": "comuna" },
      "address": "Calle 10 # 43-20",
      "latitude": null,
      "longitude": null,
      "verification_status": "unverified",
      "rating": { "average": 0, "count": 0 },
      "created_at": "2026-09-30T10:15:00-05:00"
    },
    "token": "1|abc…"
  }
}
```

#### `POST /auth/register/professional`
Mismos campos que el cliente (sin `birth_date`; `commune_id` obligatorio), más:

| Campo | Regla |
|-------|-------|
| `description` | obligatorio, 50–2000 caracteres |
| `experience_years` | obligatorio, entero 0–60 |
| `hourly_rate` | obligatorio, número > 0 |
| `category_ids[]` | obligatorio, al menos una **categoría raíz** de `/categories` |

**201:** igual que el cliente, con `role: "professional"`. Servicios, portafolio y documentos se cargan después en sus módulos.

#### `POST /auth/login`
```json
{ "email": "cliente@maestros.test", "password": "password" }
```
**200:** `data: { user, token }` · **401** credenciales incorrectas · **403** cuenta bloqueada.

#### `POST /auth/logout` · `GET /auth/me`
Logout revoca el token actual y devuelve `data: null`. `me` devuelve el mismo objeto `user` del login.

---

### Catálogos

| Método | Ruta | Acceso | Devuelve |
|--------|------|--------|----------|
| GET | `/communes` | 🌐 | 16 comunas + 5 corregimientos: `[{ id, code, name, type }]` |
| GET | `/categories` | 🌐 | Categorías raíz con `children` (subcategorías) |

```json
{
  "success": true,
  "message": null,
  "data": [
    {
      "id": 1, "parent_id": null, "name": "Plomería", "slug": "plomeria", "icon": "droplets",
      "children": [
        { "id": 2, "parent_id": 1, "name": "Reparación de fugas", "slug": "reparacion-de-fugas", "icon": "droplets" }
      ]
    }
  ]
}
```

`icon` es el nombre de un ícono de [lucide](https://lucide.dev/icons).

---

### Profesionales

| Método | Ruta | Acceso | HU |
|--------|------|--------|----|
| GET | `/professionals` | 🌐 | HU010, HU033, HU039, HU046 |
| GET | `/professionals/{id}` | 🌐 | — |
| GET | `/professionals/{id}/slots` | 🌐 | HU025, HU004 |

Solo aparecen profesionales **verificados y no bloqueados**. Nunca incluyen email, teléfono ni dirección.

#### `GET /professionals`
| Query | Descripción |
|-------|-------------|
| `q` | Busca por nombre |
| `commune_id` | Filtra por comuna |
| `category_id` | Categoría raíz (especialidad) o subcategoría (servicio activo) |
| `min_price`, `max_price` | Rango de tarifa por hora |
| `sort` | `rating` (por defecto), `price_asc`, `price_desc` |
| `page`, `per_page` | Paginación |

**200** (paginado):
```json
{
  "success": true,
  "message": null,
  "data": [
    {
      "id": 5,
      "name": "Juan Castaño",
      "avatar_url": null,
      "commune": { "id": 11, "code": "11", "name": "Laureles-Estadio", "type": "comuna" },
      "is_verified": true,
      "rating": { "average": 4.8, "count": 12 },
      "description": "Plomero con más de diez años…",
      "experience_years": 10,
      "hourly_rate": 45000,
      "categories": [{ "id": 1, "name": "Plomería", "slug": "plomeria", "icon": "droplets", "parent_id": null }]
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 }
}
```

#### `GET /professionals/{id}`
Igual que un elemento del listado, más `services` (solo activos) y `portfolio`:
```json
"services": [
  {
    "id": 9, "title": "Reparación de fugas", "description": "…",
    "category": { "id": 2, "name": "Reparación de fugas", "…": "…" },
    "price_type": "fixed", "price": 80000, "estimated_duration_minutes": 120, "is_active": true
  }
],
"portfolio": [{ "id": 1, "image_url": "http://…/storage/portfolio/a.jpg", "description": null }]
```
**404** si no existe, no está verificado o está bloqueado.

#### `GET /professionals/{id}/slots?date=2026-10-17&service_id=9`
Horas en las que se puede reservar ese servicio ese día. Las calcula `AvailabilityService` a partir de:
- el **horario semanal** del profesional (`availability_rules`): el servicio completo debe caber en una franja;
- sus **bloqueos** (`availability_blocks`);
- sus **reservas que ocupan agenda** (`pending`, `accepted`, `confirmed`, `in_progress`), ampliadas con su tiempo de descanso (`buffer_minutes`) antes y después;
- la hora actual: no devuelve horas pasadas.

Los inicios van cada **30 minutos** desde el comienzo de cada franja (`AvailabilityService::SLOT_STEP_MINUTES`).

| Query | Descripción |
|-------|-------------|
| `date` | `Y-m-d`, desde hoy hasta 60 días adelante. Obligatorio |
| `service_id` | Servicio activo de ese profesional. Obligatorio |

**200:**
```json
{
  "success": true,
  "message": null,
  "data": {
    "date": "2026-10-17",
    "service_id": 9,
    "duration_minutes": 120,
    "slots": [
      { "starts_at": "2026-10-17T08:00:00-05:00", "ends_at": "2026-10-17T10:00:00-05:00" },
      { "starts_at": "2026-10-17T08:30:00-05:00", "ends_at": "2026-10-17T10:30:00-05:00" }
    ]
  }
}
```
Un día sin horario devuelve `slots: []`. **404** si el profesional no es visible públicamente. **422** si la fecha está fuera de rango o el servicio no es suyo o no está activo.

---

### Favoritos (👤 solo clientes)

| Método | Ruta | HU |
|--------|------|----|
| GET | `/favorites` | HU035 |
| POST | `/favorites/{professionalId}` | HU034 |
| DELETE | `/favorites/{professionalId}` | HU034 |

- `GET` → `data: [{ id, professional: <profesional>, created_at }]`.
- `POST` → **201**. Agregar dos veces no duplica. **404** si el profesional no es visible públicamente.
- `DELETE` → **200**, `data: null`.

---

### Reservas

| Método | Ruta | Acceso | HU |
|--------|------|--------|----|
| GET | `/bookings` | 🔑 | HU016, HU017 |
| POST | `/bookings` | 👤 verificado | HU004 |
| GET | `/bookings/{id}` | 🔑 participante o admin | HU026 |
| POST | `/bookings/{id}/accept` | 🛠 el profesional de la reserva | HU004 |
| POST | `/bookings/{id}/reject` | 🛠 el profesional de la reserva | HU004 |
| POST | `/bookings/{id}/confirm` | 👤 el cliente de la reserva | HU036 |
| POST | `/bookings/{id}/cancel` | 🔑 participante o admin | HU012 |

#### `GET /bookings`
Paginado. El cliente ve las suyas, el profesional las suyas, el admin todas. Orden: más reciente primero.

| Query | Descripción |
|-------|-------------|
| `status` | Un estado (`pending`, `accepted`…). `?status=pending` son las solicitudes por responder del panel del profesional |
| `page`, `per_page` | Paginación (`per_page` máx. 50) |

#### `POST /bookings`
```json
{
  "professional_service_id": 9,
  "starts_at": "2026-10-02T09:00:00-05:00",
  "description": "Se está saliendo el agua debajo del lavaplatos.",
  "address": "Calle 10 # 43-20",
  "commune_id": 14
}
```
- El profesional, `ends_at` (inicio + duración del servicio) y `agreed_price` (precio del servicio en ese momento) los calcula el backend.
- Nace en `pending` y avisa al profesional con la notificación `booking.requested` (ver [Notificaciones](#notificaciones)).
- `starts_at` debe ser una de las horas de `GET /professionals/{id}/slots`: dentro del horario y sin bloqueos (si no, **422** en `starts_at`) y sin cruzarse con otra reserva (si no, **409**).

| Respuesta | Cuándo |
|-----------|--------|
| **201** | Reserva creada |
| **403** | No es cliente, o no está verificado (`Debes verificar tu identidad antes de hacer una reserva.`) |
| **409** | Se cruza con otra reserva del profesional, contando su tiempo de descanso |
| **422** | Datos inválidos, fecha pasada, servicio inactivo, o fuera del horario del profesional / en un bloqueo (`starts_at`: `El profesional no atiende en ese horario. Elige una de las horas disponibles.`) |

**201:**
```json
{
  "success": true,
  "message": "Solicitud de reserva enviada.",
  "data": {
    "id": 42,
    "status": "pending",
    "service": { "id": 9, "title": "Reparación de fugas", "…": "…" },
    "client": { "id": 3, "name": "Camila Restrepo", "avatar_url": null, "rating": { "average": 5, "count": 1 } },
    "professional": { "id": 5, "name": "Juan Castaño", "avatar_url": null, "rating": { "average": 4.8, "count": 12 } },
    "description": "Se está saliendo el agua…",
    "address": "Calle 10 # 43-20",
    "commune": { "id": 14, "…": "…" },
    "starts_at": "2026-10-02T09:00:00-05:00",
    "ends_at": "2026-10-02T11:00:00-05:00",
    "agreed_price": 80000,
    "started_at": null,
    "completed_at": null,
    "created_at": "2026-09-30T10:20:00-05:00"
  }
}
```

#### `POST /bookings/{id}/accept`
Sin cuerpo. `pending` → `accepted`. La reserva queda **aceptada, no confirmada**: el cliente la confirma después (`POST /bookings/{id}/confirm`). Avisa al cliente con `booking.accepted`.

#### `POST /bookings/{id}/reject`
```json
{ "reason": "Ese día no tengo los repuestos." }
```
`reason` es opcional (mín. 5 caracteres si se envía) y el cliente lo ve en la notificación. `pending` → `rejected`; la hora vuelve a quedar libre. Avisa al cliente con `booking.rejected`.

| Respuesta | Cuándo |
|-----------|--------|
| **200** | `data`: la reserva con el estado nuevo |
| **403** | No es el profesional de la reserva (`Solo el profesional de la reserva puede responder la solicitud.`) |
| **409** | La reserva ya no está en `pending` |

#### `POST /bookings/{id}/confirm`
Sin cuerpo. `accepted` → `confirmed` (HU036): el cliente confirma después de que el profesional aceptó. Queda registrado en `booking_status_changes` y avisa al profesional con `booking.confirmed`.

| Respuesta | Cuándo |
|-----------|--------|
| **200** | `message: "Reserva confirmada."`, `data`: la reserva en `confirmed` |
| **403** | No es el cliente de la reserva (`Solo el cliente de la reserva puede confirmarla.`) |
| **409** | La reserva no está en `accepted` (todavía pendiente, ya confirmada, cancelada…) |

#### `POST /bookings/{id}/cancel`
```json
{ "reason": "Tengo una emergencia familiar." }
```
`reason` es obligatorio (mín. 5 caracteres). **409** si el estado actual no permite cancelar (p. ej. `completed`).

---

### Notificaciones

Se guardan en la tabla `notifications` y llegan al instante por el canal privado `App.Models.User.{id}` ([../tiempo-real.md](../tiempo-real.md)). El listado y marcar como leídas (`GET /notifications`…) llegan con HU018.

| `type` | A quién | Cuándo |
|--------|---------|--------|
| `booking.requested` | Profesional | Un cliente crea una reserva (HU004) |
| `booking.accepted` | Cliente | El profesional acepta (HU004) |
| `booking.rejected` | Cliente | El profesional rechaza (HU004) |
| `booking.confirmed` | Profesional | El cliente confirma (HU036) |

Datos (los mismos en la base y en el WebSocket):
```json
{
  "id": "9f1c…",
  "type": "booking.requested",
  "booking_id": 42,
  "title": "Nueva solicitud de reserva",
  "body": "Camila Restrepo solicitó «Reparación de fugas» para el 17 de octubre, 9:00 a. m."
}
```

---

### Tiempo real

| Método | Ruta | Acceso | Uso |
|--------|------|--------|-----|
| POST | `/broadcasting/auth` | 🔑 | Autoriza la suscripción a canales privados y de presencia. Lo llama Laravel Echo solo. |

Canales y configuración del cliente: [../tiempo-real.md](../tiempo-real.md).

---

## Parte 2 — Planeados (contrato por módulo)

Rutas y responsables acordados antes de implementar. Al implementar un endpoint se mueve a la Parte 1 con su detalle. Si hace falta cambiar una ruta, se acuerda con el equipo primero.

### Módulo: Cuenta y perfil

| Método | Ruta | Acceso | HU |
|--------|------|--------|----|
| PATCH | `/me` | 🔑 | HU031, HU022 |
| POST | `/me/avatar` | 🔑 | HU032 |
| PUT | `/me/password` | 🔑 | HU019 |
| DELETE | `/me` | 🔑 | HU023 |
| POST | `/auth/forgot-password` | 🌐 | HU020 |
| POST | `/auth/reset-password` | 🌐 | HU020 |

### Módulo: Verificación y administración

| Método | Ruta | Acceso | HU |
|--------|------|--------|----|
| GET | `/verification` | 🔑 | HU008, HU013 |
| POST | `/verification` | 🔑 | HU008, HU013 |
| POST | `/verification/documents/{id}` | 🔑 | HU008 (reemplazar rechazado) |
| GET | `/admin/verifications` | 🛡 | HU014 |
| GET | `/admin/verifications/{id}` | 🛡 | HU014 |
| POST | `/admin/verifications/{id}/approve` | 🛡 | HU014 |
| POST | `/admin/verifications/{id}/reject` | 🛡 | HU014 |
| PATCH | `/admin/verification-documents/{id}` | 🛡 | HU014 |
| GET | `/admin/verification-documents/{id}/file` | 🛡 | HU014 (URL firmada) |
| GET | `/admin/users` | 🛡 | HU038 |
| POST | `/admin/users/{id}/block` | 🛡 | HU038 |
| POST | `/admin/users/{id}/unblock` | 🛡 | HU038 |

### Módulo: Perfil profesional y servicios

| Método | Ruta | Acceso | HU |
|--------|------|--------|----|
| PATCH | `/professional/profile` | 🛠 | HU009 |
| PUT | `/professional/categories` | 🛠 | HU009 |
| GET, POST | `/professional/services` | 🛠 | HU009, HU047 |
| PATCH, DELETE | `/professional/services/{id}` | 🛠 | HU009 |
| GET, POST | `/professional/portfolio` | 🛠 | HU009 |
| DELETE | `/professional/portfolio/{id}` | 🛠 | HU009 |
| GET | `/professional/earnings?from=&to=` | 🛠 | HU037 |

### Módulo: Agenda y disponibilidad

| Método | Ruta | Acceso | HU |
|--------|------|--------|----|
| GET | `/professional/availability` | 🛠 | HU003 |
| PUT | `/professional/availability/rules` | 🛠 | HU003 |
| POST | `/professional/availability/blocks` | 🛠 | HU003 |
| DELETE | `/professional/availability/blocks/{id}` | 🛠 | HU003 |

`GET /professionals/{id}/slots` ya está implementado (ver Parte 1 § Profesionales), igual que la validación de horario y bloqueos al reservar (`AvailabilityService`). Falta la gestión del horario por el profesional.

### Módulo: Ciclo de vida de la reserva

| Método | Ruta | Acceso | HU |
|--------|------|--------|----|
| POST | `/bookings/{id}/reschedule` | 🔑 participante | HU012 |
| POST | `/bookings/{id}/start` | 🛠 | HU027 |
| POST | `/bookings/{id}/complete` | 🛠 | HU028 |
| GET | `/bookings/{id}/history` | 🔑 participante | HU026 |

Todos usan `BookingService::transition()`.

### Módulo: Calificaciones y reportes

| Método | Ruta | Acceso | HU |
|--------|------|--------|----|
| POST | `/bookings/{id}/review` | 🔑 participante | HU005 |
| GET | `/professionals/{id}/reviews` | 🌐 | HU029 |
| GET | `/clients/{id}` | 🛠 | HU044 |
| POST | `/reports` | 🔑 | HU024 |
| GET | `/admin/reports` | 🛡 | HU024 |
| POST | `/admin/reports/{id}/resolve` | 🛡 | HU024 |
| GET | `/admin/alerts` | 🛡 | HU045 |
| POST | `/admin/alerts/{id}/resolve` | 🛡 | HU045 |

### Módulo: Búsqueda avanzada

Amplía `GET /professionals` con `lat`, `lng` (distancia en km y orden por cercanía, HU002, HU040) y `available_now` (HU030).

### Módulo: Chat y notificaciones

| Método | Ruta | Acceso | HU |
|--------|------|--------|----|
| GET, POST | `/bookings/{id}/messages` | 🔑 participante | HU011, HU042 |
| DELETE | `/messages/{id}` | 🔑 autor | HU043 |
| POST | `/bookings/{id}/messages/read` | 🔑 participante | HU011 |
| GET | `/notifications` | 🔑 | HU018 |
| POST | `/notifications/{id}/read` | 🔑 | HU018 |
| POST | `/notifications/read-all` | 🔑 | HU018 |

Los mensajes y el estado en línea viajan por el canal de presencia `presence-booking.{id}`; las notificaciones, por `private-App.Models.User.{id}`. Ya configurados: ver [../tiempo-real.md](../tiempo-real.md).
