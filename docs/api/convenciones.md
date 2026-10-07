# Convenciones de la API

Reglas que cumplen **todos** los endpoints. El frontend puede asumirlas sin revisar cada endpoint.

## Base

| | |
|---|---|
| URL base | `/api/v1` (local: `http://127.0.0.1:8000/api/v1`) |
| Formato | JSON. Archivos con `multipart/form-data`. |
| Headers | `Accept: application/json` siempre. `Authorization: Bearer <token>` en rutas protegidas. |
| Nombres | `snake_case` en JSON, query params y campos de formulario. |
| Versionado | Un cambio que rompa el contrato va en `/api/v2`. Agregar campos no rompe el contrato. |
| Docs interactivas | `http://127.0.0.1:8000/docs/api` (solo en entorno `local`). Las genera Scramble desde el código: permite probar cada endpoint. |

## Respuesta exitosa

```json
{
  "success": true,
  "message": "Reserva cancelada.",
  "data": { }
}
```

- `success`, `message` y `data` **siempre** están presentes.
- `message` es un texto para mostrar al usuario en acciones (crear, cancelar…) y `null` en lecturas.
- `data` puede ser un objeto, una lista o `null` (p. ej. al cerrar sesión).

### Listas paginadas

```json
{
  "success": true,
  "message": null,
  "data": [ ],
  "meta": { "current_page": 1, "last_page": 4, "per_page": 15, "total": 52 }
}
```

Query params: `page` (desde 1) y `per_page` (por defecto 15, máximo 50).

## Respuesta de error

```json
{
  "success": false,
  "message": "Mensaje en español, listo para mostrar",
  "errors": { "email": ["El campo email ya está en uso."] }
}
```

`errors` solo aparece en 422.

| Código | Cuándo | `message` |
|--------|--------|-----------|
| 401 | Falta el token o no es válido | `No autenticado.` |
| 401 | Login con credenciales incorrectas | `Credenciales incorrectas.` |
| 403 | Tipo de cuenta no permitido | `Esta acción no está disponible para tu tipo de cuenta.` |
| 403 | Regla de negocio (p. ej. sin verificar) | Mensaje específico, p. ej. `Debes verificar tu identidad antes de hacer una reserva.` |
| 403 | Cuenta bloqueada | `Tu cuenta está bloqueada. Contacta a soporte.` |
| 404 | Recurso o ruta inexistente, o sin acceso público | `Recurso no encontrado.` |
| 409 | Conflicto de estado u horario | Mensaje específico, p. ej. `El profesional ya tiene una reserva en ese horario.` |
| 422 | Datos inválidos | `Los datos enviados no son válidos.` + `errors` |
| 429 | Demasiados intentos (login, registro: 10 por minuto) | `Demasiadas solicitudes. Intenta de nuevo en un momento.` |
| 500 | Error inesperado | `Error interno del servidor.` |

**Regla para el frontend:** ante un 401 en cualquier ruta protegida, cerrar la sesión local y llevar al login.

## Tipos de datos

| Tipo | Formato | Ejemplo |
|------|---------|---------|
| Fecha y hora | ISO 8601 con desfase de Colombia | `"2026-10-02T09:00:00-05:00"` |
| Fecha sola | `YYYY-MM-DD` | `"1990-04-15"` |
| Dinero | Número en pesos colombianos | `80000` |
| Archivos | URL absoluta en campos `*_url` | `"avatar_url": "http://…/storage/avatars/x.jpg"` |
| Relaciones | Objeto anidado, no solo el id | `"commune": { "id": 14, "name": "El Poblado", … }` |
| Calificación | Objeto | `"rating": { "average": 4.8, "count": 12 }` |

Al **enviar** fechas, se acepta ISO 8601 (`2026-10-02T09:00:00-05:00`) o `YYYY-MM-DD HH:mm:ss` en hora de Colombia.

## Valores de enums

| Campo | Valores |
|-------|---------|
| `role` | `client`, `professional`, `admin` |
| `verification_status` | `unverified`, `pending`, `approved`, `rejected` |
| `booking.status` | `pending`, `accepted`, `rejected`, `confirmed`, `in_progress`, `completed`, `cancelled` |
| `price_type` | `hourly`, `fixed` |
| `commune.type` | `comuna`, `corregimiento` |
| `document.type` | `id_document`, `selfie`, `certificate`, `work_evidence` |
| `document.status` | `pending`, `approved`, `rejected` |
| `report.status` | `open`, `resolved`, `dismissed` |

## Diseño de rutas

- Recursos en plural: `/bookings`, `/professionals/{id}`.
- Acciones que cambian estado: `POST /recurso/{id}/accion` → `/bookings/{id}/cancel`, `/bookings/{id}/accept`.
- Lo del usuario autenticado cuelga de su rol: `/professional/services`, `/professional/availability`.
- Administración bajo `/admin/…`.
- Datos públicos nunca incluyen email, teléfono ni dirección de otra persona.
