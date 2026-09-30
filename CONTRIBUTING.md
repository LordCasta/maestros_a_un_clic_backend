# Cómo trabajamos

Reglas para desarrollar en paralelo sin pisarnos. Aplican igual en el repo del frontend.

## 1. Reparto por módulos

Cada módulo tiene **un responsable** que lo hace completo: backend, frontend, tests y documentación. Los módulos y sus endpoints están en [docs/api/endpoints.md](docs/api/endpoints.md) (Parte 2).

| Módulo | Responsable |
|--------|-------------|
| Cuenta y perfil | _por asignar_ |
| Verificación y administración | _por asignar_ |
| Perfil profesional y servicios | _por asignar_ |
| Agenda y disponibilidad | _por asignar_ |
| Ciclo de vida de la reserva | _por asignar_ |
| Calificaciones y reportes | _por asignar_ |
| Búsqueda avanzada | _por asignar_ |
| Chat y notificaciones | _por asignar_ |

Si necesitas tocar código de un módulo ajeno, avisa al responsable antes.

## 2. Tareas

- Cada historia de usuario (HU) es un **issue** en GitHub, con la etiqueta de su módulo.
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
