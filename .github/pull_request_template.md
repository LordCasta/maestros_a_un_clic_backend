## Qué cierra

Closes #<!-- número del issue de la HU -->

## Qué cambió

<!-- Qué se agregó o modificó, en pocas líneas. -->

## Cómo probarlo

<!-- Endpoint, cuenta demo y pasos. Ej: POST /api/v1/bookings/1/accept con profesional@maestros.test -->

## Checklist

- [ ] Sigue [docs/arquitectura.md](../docs/arquitectura.md).
- [ ] Tests nuevos: caso feliz, validación y permisos.
- [ ] `php artisan test` y `vendor/bin/pint --test` en verde.
- [ ] [docs/api/endpoints.md](../docs/api/endpoints.md) actualizado (endpoint movido a "Implementados").
- [ ] Si cambia el esquema: migración **nueva** y [docs/modelo-de-datos.md](../docs/modelo-de-datos.md) actualizado.
- [ ] Si cambia el contrato de la API: acordado con el equipo y enlazado el PR del frontend.
