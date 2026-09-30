# Maestros a un clic — reglas del proyecto

Antes de escribir código, lee `docs/arquitectura.md`. Resumen de lo que no se negocia:

- API versionada en `/api/v1`. Respuestas solo con `$this->ok()`, `$this->created()`, `$this->paginated()` del controller base; nunca `response()->json()` a mano. Los errores se formatean solos en `bootstrap/app.php`.
- Capas: Ruta (`auth:sanctum`, `not_blocked`, `role:…`) → FormRequest (solo valida, `authorize()` devuelve `true`) → Controller (llama a la Policy y al Service) → Service (reglas, transacciones, archivos) → Resource.
- Sin patrón Repository: consultas reutilizables como scopes del modelo.
- Estados, roles y tipos siempre con los enums de `app/Enums`. Todo cambio de estado de una reserva pasa por `BookingService::transition()`.
- Archivos solo mediante `App\Services\UploadService`. Documentos KYC y adjuntos del chat van al disco privado.
- El esquema está definido en `docs/modelo-de-datos.md`. No crear migraciones que lo contradigan sin actualizar ese documento.
- Cada endpoint nuevo lleva tests (feliz, validación, permisos) y se documenta en `docs/api/endpoints.md`.
- Mensajes para el usuario en español. Los nombres de campos nuevos se agregan a `lang/es/validation.php` → `attributes`.
