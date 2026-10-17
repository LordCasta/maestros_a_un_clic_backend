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

### Perfil, servicios, portafolio e ingresos del profesional (🛠 solo profesionales, HU009, HU047, HU037)

| Método | Ruta | HU |
|--------|------|----|
| GET, PATCH | `/professional/profile` | HU009 |
| PUT | `/professional/categories` | HU009 |
| GET, POST | `/professional/services` | HU009, HU047 |
| PATCH, DELETE | `/professional/services/{id}` | HU009, HU047 |
| GET, POST | `/professional/portfolio` | HU009 |
| DELETE | `/professional/portfolio/{id}` | HU009 |
| GET | `/professional/earnings?from=&to=` | HU037 |

Lo público (descripción, especialidades, servicios activos y galería) se ve en `GET /professionals/{id}`.

#### `GET /professional/profile` · `PATCH /professional/profile`
El perfil propio para editarlo. A diferencia del público, incluye la dirección y las coordenadas.
```json
{ "success": true, "message": null, "data": {
  "description": "Plomero con diez años…", "experience_years": 10, "hourly_rate": 45000, "service_radius_km": 10,
  "commune": { "id": 11, "…": "…" }, "address": "Calle 10 # 43-20", "latitude": 6.2442, "longitude": -75.5812,
  "categories": [{ "id": 1, "name": "Plomería", "…": "…" }],
  "verification_status": "approved"
} }
```
`PATCH` cambia **solo los campos enviados**; los demás se conservan y la verificación no se pierde.

| Campo | Regla |
|-------|-------|
| `description` | Hasta 2000 caracteres |
| `experience_years` | 0 a 70 |
| `hourly_rate` | Número **mayor que 0** |
| `service_radius_km` | 1 a 100 |
| `commune_id`, `address` | Ubicación. Se guarda en `users` y la usa la búsqueda por zona y cercanía (HU002) |
| `latitude`, `longitude` | Van juntas: si llega una, la otra es obligatoria |

**200**: `message: "Perfil actualizado."` y `data` con la forma del `GET`.

#### `PUT /professional/categories`
```json
{ "category_ids": [1, 4] }
```
Reemplaza las especialidades (categorías raíz, al menos una). **422** en `category_ids` si se quita una especialidad con servicios activos (`Tienes servicios activos en Plomería. Desactívalos antes de quitar la especialidad.`).

#### `GET /professional/services` · `POST` · `PATCH /{id}` · `DELETE /{id}`
`GET` lista todos los servicios propios, también los inactivos. Cada servicio tiene la forma de `services` del perfil público.

```json
{
  "category_id": 2,
  "title": "Reparación de fugas",
  "description": "Incluye revisión de tuberías.",
  "price_type": "fixed",
  "price": 80000,
  "estimated_duration_minutes": 90
}
```
| Campo | Regla |
|-------|-------|
| `category_id` | Subcategoría de una de **sus** especialidades (si no: `Agrega primero esa especialidad a tu perfil.`) |
| `price_type` | `fixed` o `hourly` |
| `price` | Mayor que 0 |
| `estimated_duration_minutes` | 15 a 720 (HU047). Define cuánto ocupa la reserva en la agenda y se muestra al cliente antes de reservar |
| `is_active` | Solo en `PATCH`. `false` lo oculta del perfil y de las reservas nuevas |

- `PATCH` cambia solo lo enviado. Las reservas existentes conservan su precio (`agreed_price`) y su horario.
- `DELETE` responde **409** si el servicio tiene reservas (`Este servicio tiene reservas. Desactívalo para que no se pueda volver a reservar.`).
- **403** si el servicio es de otro profesional.

#### `GET /professional/portfolio` · `POST` · `DELETE /{id}`
`POST` es `multipart/form-data` con `image` (JPG, PNG o WEBP, hasta 5 MB) y `description` opcional (hasta 255). Máximo **12 fotos** (**422** en `image`). Respuesta: `{ "id", "image_url", "description" }`. `DELETE` borra también el archivo; **403** si la foto es de otro profesional.

#### `GET /professional/earnings?from=2026-10-01&to=2026-10-31`
Ingresos por los servicios **finalizados** (`completed`) cuya `completed_at` cae en el rango (fechas `Y-m-d` en hora de Colombia, ambos días incluidos). Sin fechas: del primer día del mes en curso a hoy. Máximo un año por consulta (**422** en `to`). El ingreso de cada servicio es su `agreed_price`.
```json
{ "success": true, "message": null, "data": {
  "from": "2026-10-01", "to": "2026-10-20",
  "total": 250000, "count": 3, "average": 83333.33,
  "by_day": [ { "date": "2026-10-02", "total": 200000, "count": 2 }, { "date": "2026-10-15", "total": 50000, "count": 1 } ],
  "bookings": [ { "id": 42, "status": "completed", "agreed_price": 50000, "client": { "…": "…" }, "completed_at": "…", "…": "…" } ]
} }
```
`by_day` solo trae los días con ingresos; `bookings` son las reservas del rango con la forma de `BookingResource`, de la más reciente a la más antigua.

---

### Agenda del profesional (🛠 solo profesionales, HU003)

| Método | Ruta | HU |
|--------|------|----|
| GET | `/professional/availability` | HU003 |
| PUT | `/professional/availability/rules` | HU003 |
| POST | `/professional/availability/blocks` | HU003 |
| DELETE | `/professional/availability/blocks/{id}` | HU003 |

La agenda la usa `GET /professionals/{id}/slots` para calcular las horas libres. Cada cambio emite `availability.changed` por el canal público `professional.{id}.availability`, y los clientes que estén viendo las horas libres las vuelven a pedir (ver [../tiempo-real.md](../tiempo-real.md)).

#### `GET /professional/availability`
```json
{
  "success": true,
  "message": null,
  "data": {
    "buffer_minutes": 30,
    "rules": [
      { "id": 1, "weekday": 1, "start_time": "08:00", "end_time": "12:00" },
      { "id": 2, "weekday": 1, "start_time": "14:00", "end_time": "18:00" }
    ],
    "blocks": [
      { "id": 4, "starts_at": "2026-10-20T09:00:00-05:00", "ends_at": "2026-10-20T11:00:00-05:00", "reason": "Cita médica" }
    ]
  }
}
```
- `weekday`: 0 = domingo … 6 = sábado. Horas en `H:i`, hora de Colombia.
- `blocks`: solo los que aún no terminan.

#### `PUT /professional/availability/rules`
Reemplaza el horario semanal **completo** y el descanso entre servicios. Las reservas que ya existen no cambian aunque queden fuera del horario nuevo.
```json
{
  "buffer_minutes": 30,
  "rules": [
    { "weekday": 1, "start_time": "08:00", "end_time": "12:00" },
    { "weekday": 1, "start_time": "14:00", "end_time": "18:00" }
  ]
}
```
| Campo | Regla |
|-------|-------|
| `buffer_minutes` | 0 a 240. Descanso que se bloquea antes y después de cada reserva |
| `rules` | Obligatorio; puede ser `[]` (no atiende ningún día). Máx. 28 franjas |
| `rules.*.end_time` | Después de `start_time` |
| Franjas del mismo día | No pueden cruzarse: **422** en `rules.N.start_time` (`Esta franja se cruza con otra del mismo día.`) |

**200**: `message: "Horario guardado."` y `data` con la misma forma que el `GET`.

#### `POST /professional/availability/blocks`
```json
{ "starts_at": "2026-10-20T09:00:00-05:00", "ends_at": "2026-10-20T11:00:00-05:00", "reason": "Cita médica" }
```
Marca el intervalo como ocupado. `starts_at` debe ser futura y `ends_at` posterior; `reason` es opcional (máx. 255).

| Respuesta | Cuándo |
|-----------|--------|
| **201** | `message: "Horas bloqueadas."`, `data`: el bloqueo |
| **409** | Hay reservas activas en ese intervalo (`Tienes reservas activas en ese horario. Cancélalas o reprográmalas antes de bloquearlo.`) |
| **422** | Fechas inválidas |

#### `DELETE /professional/availability/blocks/{id}`
**200** con `data: null`. **403** si el bloqueo es de otro profesional.

---

### Reservas

| Método | Ruta | Acceso | HU |
|--------|------|--------|----|
| GET | `/bookings` | 🔑 | HU016, HU017, HU049 |
| GET | `/bookings/summary` | 🔑 | HU017 |
| POST | `/bookings` | 👤 verificado | HU004 |
| GET | `/bookings/{id}` | 🔑 participante o admin | HU026 |
| GET | `/bookings/{id}/history` | 🔑 participante o admin | HU026 |
| POST | `/bookings/{id}/accept` | 🛠 el profesional de la reserva | HU004 |
| POST | `/bookings/{id}/reject` | 🛠 el profesional de la reserva | HU004 |
| POST | `/bookings/{id}/confirm` | 👤 el cliente de la reserva | HU036 |
| POST | `/bookings/{id}/start` | 🛠 el profesional de la reserva | HU027 |
| POST | `/bookings/{id}/complete` | 🛠 el profesional de la reserva | HU028 |
| POST | `/bookings/{id}/cancel` | 🔑 participante o admin | HU012 |

#### `GET /bookings`
Paginado. El cliente ve las suyas, el profesional las suyas, el admin todas. Orden: más reciente primero.

| Query | Descripción |
|-------|-------------|
| `status` | Uno o varios estados separados por coma: `?status=pending` (solicitudes por responder), `?status=cancelled,rejected`, `?status=in_progress` (servicios en curso, HU049) |
| `page`, `per_page` | Paginación (`per_page` máx. 50) |

#### `GET /bookings/summary`
Cuántas reservas tiene el usuario en cada estado, con el mismo alcance que el listado (el admin, todas). Siempre trae los siete estados:
```json
{ "success": true, "message": null, "data": {
  "total": 16,
  "by_status": { "pending": 2, "accepted": 0, "rejected": 1, "confirmed": 3, "in_progress": 0, "completed": 9, "cancelled": 1 }
} }
```

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

#### `GET /bookings/{id}/history`
Cambios de estado de la reserva, del más antiguo al más reciente (`booking_status_changes`):
```json
{
  "success": true,
  "message": null,
  "data": [
    { "id": 1, "from_status": null, "to_status": "pending", "reason": null, "previous_starts_at": null,
      "changed_by": { "id": 3, "name": "Camila Restrepo" }, "created_at": "2026-10-16T10:20:00-05:00" },
    { "id": 2, "from_status": "pending", "to_status": "accepted", "reason": null, "previous_starts_at": null,
      "changed_by": { "id": 5, "name": "Juan Castaño" }, "created_at": "2026-10-16T10:31:00-05:00" }
  ]
}
```
`reason` está en rechazos y cancelaciones; `previous_starts_at`, al reprogramar (HU012). **403** si no participa en la reserva (el admin sí la ve).

Cada cambio de estado también se emite en tiempo real como `booking.status-changed` por el canal de la reserva (ver [../tiempo-real.md](../tiempo-real.md)).

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

#### `POST /bookings/{id}/start`
Sin cuerpo. `confirmed` → `in_progress` (HU027) y guarda `started_at`. Solo **desde la hora pactada** (`starts_at`). Avisa al cliente con `booking.started`.

| Respuesta | Cuándo |
|-----------|--------|
| **200** | `message: "Servicio iniciado."` |
| **403** | No es el profesional de la reserva (`Solo el profesional de la reserva puede iniciar o finalizar el servicio.`) |
| **409** | Todavía no es la hora (`Solo puedes iniciar el servicio desde la hora programada (17 de octubre, 9:00 a. m.).`) o la reserva no está en `confirmed` |

#### `POST /bookings/{id}/complete`
Sin cuerpo. `in_progress` → `completed` (HU028) y guarda `completed_at`. Desde ese estado se puede calificar (`POST /bookings/{id}/review`, HU005). Avisa al cliente con `booking.completed`. **403** igual que `start`; **409** si la reserva no está en `in_progress`.

#### `POST /bookings/{id}/reschedule`
```json
{ "starts_at": "2026-10-21T15:00:00-05:00" }
```
Mueve la reserva a otra hora libre del profesional (HU012). Se puede desde `pending`, `accepted` o `confirmed` y **antes de la hora programada**.
- Conserva la duración y vuelve a `pending`: el profesional debe aceptar el nuevo horario.
- La fecha anterior queda en el historial (`previous_starts_at`).
- La nueva hora puede cruzarse con la anterior de la misma reserva, pero no con otras reservas.
- Avisa a la otra parte con `booking.rescheduled`.

| Respuesta | Cuándo |
|-----------|--------|
| **200** | `message: "Reserva reprogramada. El profesional debe aceptar el nuevo horario."` |
| **403** | No participa en la reserva |
| **409** | Ya llegó la hora, el estado no lo permite (`in_progress`, `completed`…) o la nueva hora se cruza con otra reserva |
| **422** | `starts_at` falta, ya pasó o está fuera del horario del profesional / en un bloqueo |

#### `POST /bookings/{id}/cancel`
```json
{ "reason": "Tengo una emergencia familiar." }
```
`reason` es obligatorio (mín. 5 caracteres). Solo **antes de la hora programada** (el admin puede siempre). Libera la agenda y avisa a la otra parte con `booking.cancelled`; si cancela el admin, a las dos.

**409** si ya llegó la hora (`La hora de la reserva ya llegó; ya no se puede cancelar.`) o si el estado actual no permite cancelar (p. ej. `completed`).

---

### Chat de la reserva (🔑 cliente y profesional de la reserva, HU011, HU041, HU042, HU043)

| Método | Ruta | HU |
|--------|------|----|
| GET | `/bookings/{id}/messages` | HU011 |
| POST | `/bookings/{id}/messages` | HU011, HU042 |
| POST | `/bookings/{id}/messages/read` | HU011 |
| DELETE | `/messages/{id}` | HU043 |

Solo participan el cliente y el profesional de la reserva (el admin tampoco lo lee): **403** a cualquier otro.

#### `GET /bookings/{id}/messages`
Los 200 mensajes más recientes, del más antiguo al más nuevo:
```json
{ "success": true, "message": null, "data": {
  "can_send": true,
  "messages": [
    { "id": 1, "booking_id": 42, "sender": { "id": 3, "name": "Camila Restrepo", "avatar_url": null, "rating": { "…": "…" } },
      "body": "¿Debo comprar algún repuesto?", "attachment_url": null, "read_at": null, "created_at": "2026-10-16T10:40:00-05:00" }
  ]
} }
```
`can_send` es `true` mientras la reserva está en `pending`, `accepted`, `confirmed` o `in_progress`. Cuando termina, se rechaza o se cancela, el historial se puede leer pero ya no escribir.

#### `POST /bookings/{id}/messages`
`multipart/form-data` (o JSON si no hay imagen):

| Campo | Regla |
|-------|-------|
| `body` | Hasta 2000 caracteres. Obligatorio si no hay imagen (`Escribe un mensaje o adjunta una imagen.`) |
| `attachment` | Imagen JPG, PNG o WEBP de hasta 5 MB (HU042). Se guarda en el **disco privado** |

**201** con el mensaje. `attachment_url` es una URL **firmada que vence en 60 minutos**: se vuelve a generar cada vez que se pide el historial.

- **409** si el chat está cerrado (`El chat se cierra cuando la reserva termina, se rechaza o se cancela.`).
- Emite `message.sent` por el canal de presencia `booking.{id}` y avisa a la otra parte con la notificación `message.received`.

#### `POST /bookings/{id}/messages/read`
Marca como leídos los mensajes que la otra parte le envió al usuario. `data: { "marked": 3 }`.

#### `DELETE /messages/{id}`
Solo el autor (**403** `Solo puedes eliminar tus propios mensajes.`). El mensaje desaparece del historial de las dos partes y se emite `message.deleted` con `{ "id", "booking_id" }`. Es un **borrado lógico** (soft delete): el registro y su adjunto se conservan por si hay un reporte (HU024).

**En línea (HU041):** el canal de presencia `booking.{id}` informa quién está conectado (`here`, `joining`, `leaving`); no hace falta un endpoint.

---

### Notificaciones

Se guardan en la tabla `notifications` y llegan al instante por el canal privado `App.Models.User.{id}` ([../tiempo-real.md](../tiempo-real.md)).

| Método | Ruta | Acceso | HU |
|--------|------|--------|----|
| GET | `/notifications` | 🔑 | HU018 |
| POST | `/notifications/{id}/read` | 🔑 la propia | HU018 |
| POST | `/notifications/read-all` | 🔑 | HU018 |

#### `GET /notifications`
Paginado (20 por página), de la más reciente a la más antigua. `?unread=1` trae solo las no leídas, y su `meta.total` es el contador de la campana.
```json
{ "success": true, "message": null, "data": [
  { "id": "9f1c…", "type": "booking.requested", "booking_id": 42, "title": "Nueva solicitud de reserva",
    "body": "Camila Restrepo solicitó…", "read_at": null, "created_at": "2026-10-16T10:20:00-05:00" }
], "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1 } }
```

#### `POST /notifications/{id}/read` · `POST /notifications/read-all`
Marca una o todas las propias como leídas. Una notificación de otro usuario responde **404**. `read-all` devuelve `data: { "marked": 3 }`.

| `type` | A quién | Cuándo |
|--------|---------|--------|
| `booking.requested` | Profesional | Un cliente crea una reserva (HU004) |
| `booking.accepted` | Cliente | El profesional acepta (HU004) |
| `booking.rejected` | Cliente | El profesional rechaza (HU004) |
| `booking.confirmed` | Profesional | El cliente confirma (HU036) |
| `booking.started` | Cliente | El profesional inicia el servicio (HU027) |
| `booking.completed` | Cliente | El profesional finaliza el servicio (HU028); invita a calificar |
| `booking.cancelled` | La otra parte (las dos si cancela el admin) | Se cancela la reserva (HU012), con el motivo |
| `booking.rescheduled` | La otra parte | Se reprograma la reserva (HU012), con la fecha anterior y la nueva |
| `message.received` | La otra parte | Mensaje nuevo en el chat (HU011). `body`: el texto (recortado) o "Te envió una imagen." |

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

Implementado: ver Parte 1 § Perfil, servicios, portafolio e ingresos del profesional.

### Módulo: Agenda y disponibilidad

Implementado: ver Parte 1 § Agenda del profesional y § Profesionales (`/slots`). Falta `available_now` en la búsqueda (HU030, módulo Búsqueda).

### Módulo: Ciclo de vida de la reserva

| Método | Ruta | Acceso | HU |
|--------|------|--------|----|
| POST | `/bookings/{id}/reschedule` | 🔑 participante | HU012 |

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

Implementado: ver Parte 1 § Chat de la reserva y § Notificaciones. Los mensajes y el estado en línea viajan por el canal de presencia `presence-booking.{id}`; las notificaciones, por `private-App.Models.User.{id}` ([../tiempo-real.md](../tiempo-real.md)).
