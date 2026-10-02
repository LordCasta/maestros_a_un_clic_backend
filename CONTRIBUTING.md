# Cómo trabajamos

Reglas para desarrollar en paralelo sin pisarnos. Aplican igual en el repo del frontend.

## 1. Reparto por módulos

Cada módulo tiene **un responsable** que lo hace completo: backend, frontend, tests y documentación. Los módulos y sus endpoints están en [docs/api/endpoints.md](docs/api/endpoints.md) (Parte 2).

El reparto sigue las cadenas de dependencia, para que cada uno avance sin esperar al otro:

| Módulo | Etiqueta | Responsable | Orden sugerido |
|--------|----------|-------------|----------------|
| Perfil profesional y servicios | `módulo: perfil-profesional` | @LordCasta | 1 |
| Agenda y disponibilidad | `módulo: agenda` | @LordCasta | 2 |
| Ciclo de vida de la reserva | `módulo: reservas` | @LordCasta | 3 |
| Chat y notificaciones | `módulo: chat-notificaciones` | @LordCasta | 4 |
| Cuenta y perfil | `módulo: cuenta` | @barenas12 | 1 |
| Verificación y administración | `módulo: verificación-admin` | @barenas12 | 2 |
| Búsqueda avanzada | `módulo: búsqueda` | @barenas12 | 3 |
| Calificaciones y reportes | `módulo: calificaciones` | @barenas12 | 4 |

Por qué este orden:
- **@LordCasta:** las reservas necesitan servicios con precio y duración (perfil profesional) y horarios libres (agenda). El chat va al final porque vive dentro de una reserva.
- **@barenas12:** la verificación habilita reservar (decisión N2) y el panel de admin. La búsqueda avanzada usa la ubicación del perfil. Las calificaciones dependen de que existan reservas completadas, que llegan con el módulo de reservas.

Puntos de contacto entre los dos (acordarlos antes de implementar):
- `HU030` (disponible ahora, búsqueda) usa la agenda.
- `HU005` (calificar) se habilita al completar la reserva (`HU028`).
- `HU018` (notificaciones) la implementa @LordCasta; los otros módulos solo llaman a `$user->notify(...)`.

Si necesitas tocar código de un módulo ajeno, avisa al responsable antes.

## 2. Tareas

- Cada historia de usuario (HU) es un **issue de este repo** (también para el trabajo del frontend), con la etiqueta de su módulo. Las HU que avanzaron en la fase 0 tienen la etiqueta `fase 0: parcial` y dicen qué falta.
- Un PR del frontend cierra el issue con `Closes LordCasta/maestros_a_un_clic_backend#N`.
- El tablero del proyecto muestra quién tiene qué y en qué estado va.
- Una rama y un pull request por HU (o por un grupo pequeño de HU muy relacionadas).

## 3. Ramas

- `main` siempre funciona y está protegida: nadie hace push directo.
- Se trabaja en ramas cortas creadas desde `main` actualizado:

| Prefijo | Uso | Ejemplo |
|---------|-----|---------|
| `feature/` | Funcionalidad nueva | `feature/HU004-aceptar-reserva` |
| `fix/` | Corrección | `fix/cruce-horario-descanso` |
| `docs/` | Solo documentación | `docs/diagrama-secuencia-reserva` |
| `chore/` | Dependencias, configuración, CI | `chore/actualizar-laravel` |

Mantén la rama al día con `git pull --rebase origin main` y ábrela como PR en 1 a 3 días. Las ramas largas terminan en conflictos.

## 4. Commits

[Conventional Commits](https://www.conventionalcommits.org/es/) en español, con el módulo como alcance:

```
feat(reservas): aceptar y rechazar solicitudes (HU004)
fix(agenda): respetar el tiempo de descanso al reprogramar
docs(api): documentar endpoints de verificación
test(favoritos): cubrir profesional bloqueado
refactor(auth): extraer creación de sesión
chore(deps): actualizar laravel/framework
```

Si el cambio rompe el contrato de la API, usa `!` (`feat(api)!: …`) y explica el cambio en el cuerpo con `BREAKING CHANGE:`.

## 5. Pull requests

1. Título en formato de commit convencional; se usa como mensaje al fusionar.
2. Completa la plantilla: qué HU cierra, qué cambió y cómo probarlo.
3. El CI debe estar en verde.
4. **La otra persona revisa y aprueba.** Nadie aprueba su propio PR.
5. Se fusiona con **Squash and merge** y se borra la rama.

### Qué revisar en un PR ajeno
- ¿Sigue [docs/arquitectura.md](docs/arquitectura.md)? (capas, enums, `ApiResponse`, `UploadService`)
- ¿Tiene tests de caso feliz, validación y permisos?
- ¿Está actualizada la documentación de la API?
- ¿Algo se puede simplificar?

## 6. Cambios que afectan a los dos

**Esquema de base de datos.** Desde que la fase 0 está en `main`, **las migraciones existentes no se editan**: todo cambio va en una migración nueva. En el mismo PR se actualiza [docs/modelo-de-datos.md](docs/modelo-de-datos.md).

**Contrato de la API.** Si un endpoint cambia de ruta, de campos o de comportamiento:
1. Se acuerda primero con la otra persona.
2. El PR del backend actualiza [docs/api/endpoints.md](docs/api/endpoints.md).
3. El PR del frontend enlaza al del backend. Se fusiona primero el backend.

**Componentes compartidos** (frontend `shared/`, backend `Support/`, `Controller` base, `bootstrap/app.php`): cualquier cambio lo revisan los dos, aunque sea pequeño.

## 7. Definición de listo

Una HU está lista cuando:

- [ ] Cumple todos sus criterios de aceptación.
- [ ] Backend con tests (feliz, validación, permisos) en verde.
- [ ] Frontend conectado a la API real, sin datos quemados.
- [ ] Documentación de la API actualizada.
- [ ] Revisada y aprobada por la otra persona.
- [ ] Fusionada en `main` en los dos repos.

## 8. Trabajo con asistentes de IA

Cada uno usa la herramienta que prefiera (Antigravity, Claude Code…). En este repo:

| Archivo | Quién lo lee | Cómo se mantiene |
|---------|--------------|------------------|
| `AGENTS.md` | Antigravity, Codex, Cursor (siempre activo) | **Generado** por Laravel Boost |
| `CLAUDE.md` | Claude Code | **Generado** por Laravel Boost |
| `.ai/guidelines/maestros-a-un-clic.md` | Fuente de las reglas del proyecto dentro de los dos anteriores | Se edita a mano |
| `.claude/skills/` | Claude Code | Skills oficiales de Boost (Laravel, testing) |
| `.mcp.json` | Asistentes con MCP | Servidor de Laravel Boost: rutas, esquema de BD y documentación de la versión instalada |

Reglas:
- **No edites `AGENTS.md` ni `CLAUDE.md` a mano**: se regeneran. Cambia `.ai/guidelines/maestros-a-un-clic.md` y corre `php artisan boost:update` (también corre solo tras `composer update`). Haz el cambio en un PR que revisen los dos.
- **El asistente corre `php artisan test` y `vendor/bin/pint --dirty`** antes de dar algo por terminado.
- **Un PR hecho con IA se revisa igual que cualquier otro.** Quien lo abre responde por el código.
- En el frontend, `AGENTS.md` se edita a mano y hay reglas de diseño y skills propias: ver su `CONTRIBUTING.md`.
