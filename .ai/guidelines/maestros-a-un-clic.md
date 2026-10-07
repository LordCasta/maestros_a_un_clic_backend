# Maestros a un clic — reglas del proyecto

Antes de escribir código, lee `docs/arquitectura.md`. Para crear un endpoint o un módulo, sigue la skill `.agents/skills/nuevo-modulo-api/SKILL.md`. Resumen de lo que no se negocia:

- API versionada en `/api/v1`. Respuestas solo con `$this->ok()`, `$this->created()`, `$this->paginated()` del controller base; nunca `response()->json()` a mano. Los errores se formatean solos en `bootstrap/app.php`.
- Capas: Ruta (`auth:sanctum`, `not_blocked`, `role:…`) → FormRequest (solo valida, `authorize()` devuelve `true`) → Controller (llama a la Policy y al Service) → Service (reglas, transacciones, archivos) → Resource.
- Sin patrón Repository: consultas reutilizables como scopes del modelo.
- Estados, roles y tipos siempre con los enums de `app/Enums`. Todo cambio de estado de una reserva pasa por `BookingService::transition()`.
- Archivos solo mediante `App\Services\UploadService`. Documentos KYC y adjuntos del chat van al disco privado.
- El esquema está definido en `docs/modelo-de-datos.md`. Las migraciones existentes no se editan: todo cambio va en una migración nueva y se refleja en ese documento.
- Cada endpoint nuevo lleva tests (feliz, validación, permisos) y se documenta en `docs/api/endpoints.md` (de "Planeados" a "Implementados"). Si cambia un Resource, avisa que hay que actualizar `src/shared/types/models.ts` del frontend.
- Mensajes para el usuario en español. Los nombres de campos nuevos se agregan a `lang/es/validation.php` → `attributes`.
- Antes de terminar: `php artisan test` y `vendor/bin/pint --dirty` en verde.

## Flujo de trabajo (docs/guia-de-trabajo.md)

- Rama desde `main` actualizado: `feature/HU0xx-descripcion`, `fix/…`, `docs/…`, `chore/…`.
- Commits convencionales en español con el módulo como alcance: `feat(reservas): aceptar solicitudes (HU004)`.
- PR del backend con `Refs #N` (el PR del frontend es el que cierra el issue con `Closes`). Solo si la HU no tiene frontend, `Closes #N`.
- `main` está protegido: no hay push directo y el CI debe estar en verde.

## Guardas del proyecto (no se tocan para hacer pasar algo)

Si un test o Pint falla, se corrige el código, nunca la regla. El check obligatorio **Guardas** (`.github/workflows/guardas.yml`) falla si el PR toca alguno de estos archivos, salvo que una persona ponga la etiqueta `cambio-de-reglas`: `phpunit.xml`, `tests/Pest.php`, `tests/TestCase.php`, `pint.json`, `.editorconfig`, `.github/`, `.ai/`, `.agents/`, `.claude/`, `AGENTS.md`, `CLAUDE.md`, `boost.json`, `config/boost.php`, `docs/arquitectura.md` y los scripts `test`/`setup` de `composer.json`. También falla si se edita o borra una migración que ya existe en `main`: el cambio va en una migración nueva.

Prohibido como atajo (el check los lista en el PR para quien revisa): `->skip()`, `->todo()`, `->only()`, `markTestSkipped()`, `markTestIncomplete()`, `withoutMiddleware()` en tests, borrar o vaciar un test, `@phpstan-ignore`, `@codingStandardsIgnore`, `phpcs:ignore`, `--no-verify`. Si un test quedó desactualizado porque el comportamiento cambió a propósito, se actualiza y se explica en el PR. Nunca pongas tú la etiqueta `cambio-de-reglas`: la pone una persona.
