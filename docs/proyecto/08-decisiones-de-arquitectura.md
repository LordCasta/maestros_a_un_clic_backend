# Decisiones de arquitectura

Registro de las decisiones técnicas importantes (*Architecture Decision Records*): qué se decidió, por qué y qué consecuencias tiene. Las decisiones del modelo de datos (D1–D15) y las de negocio (N1–N3) están detalladas en [docs/modelo-de-datos.md](../modelo-de-datos.md) §1 y §6; aquí se resumen junto con las de arquitectura.

## ADR-01 · Dos repositorios: backend y frontend

- **Decisión:** el backend (Laravel) y el frontend (Vue) viven en repositorios separados, con su propia CI, comunicados solo por la API.
- **Motivo:** responsabilidades claras, despliegues independientes y una forma de organizarse que el equipo considera más ordenada.
- **Consecuencia:** el contrato de la API (`docs/api/endpoints.md` y OpenAPI) es la frontera entre los dos, y un cambio que toca los dos lados se hace con un PR en cada repositorio: primero el backend.

## ADR-02 · API REST versionada con un formato de respuesta único

- **Decisión:** API bajo `/api/v1`; toda respuesta tiene la forma `{ success, message, data, meta? }` y todo error, `{ success: false, message, errors? }`.
- **Motivo:** el frontend maneja todas las respuestas igual; una versión futura (`/v2`) no rompe a los clientes existentes.
- **Consecuencia:** los controladores solo responden con los helpers del controlador base, y los errores se formatean en un único lugar (`bootstrap/app.php`).

## ADR-03 · Autenticación con tokens (Laravel Sanctum)

- **Decisión:** token Bearer por sesión, que se revoca al cerrar sesión.
- **Motivo:** la SPA y la API están en dominios distintos; los tokens evitan la complejidad de las cookies entre dominios y sirven también para autenticar los canales de WebSockets.

## ADR-04 · Arquitectura por capas en el backend, sin patrón Repository

- **Decisión:** Ruta (middleware de rol) → FormRequest (validación) → Controller → Policy (autorización por recurso) → Service (reglas, transacciones, archivos) → Resource (forma de la respuesta). Las consultas reutilizables son *scopes* del modelo.
- **Motivo:** cada capa tiene una sola responsabilidad y las reglas de negocio quedan en un lugar fácil de probar. Un Repository sobre Eloquent agregaría una capa sin beneficio en este tamaño de proyecto.

## ADR-05 · Frontend por módulos con API pública

- **Decisión:** `src/modules/<módulo>` autocontenidos (vistas, componentes, API, queries, rutas), que se usan entre sí solo por su `index.ts`; `src/shared` para lo común y `src/app` para el armado.
- **Motivo:** permite que dos personas trabajen en paralelo por módulos sin pisarse. ESLint bloquea los imports a archivos internos de otro módulo.

## ADR-06 · Estado del servidor con Pinia Colada

- **Decisión:** los datos de la API se manejan como consultas con caché (Pinia Colada); Pinia solo guarda la sesión y los avisos.
- **Motivo:** evita duplicar el estado del servidor en *stores*. Invalidar una clave refresca todas las pantallas que la usan, que es lo que aprovechan los eventos de tiempo real.

## ADR-07 · Tiempo real con Laravel Reverb (decisión N1)

- **Decisión:** WebSockets propios con Reverb: un canal privado por usuario para las notificaciones, uno de presencia por reserva para el chat, el estado y quién está conectado, y uno público por profesional para su agenda.
- **Motivo:** chat, estado en línea y agenda en vivo sin depender de un servicio de pago. Es compatible con el protocolo de Pusher, así que se usa Laravel Echo en el cliente.
- **Consecuencia:** hacen falta dos procesos más en producción: la cola y Reverb.

## ADR-08 · Disponibilidad calculada, no guardada (decisión D7)

- **Decisión:** se guardan el horario semanal y los bloqueos; las horas libres se calculan al consultarlas, descontando las reservas activas y el descanso.
- **Motivo:** evita mantener miles de "cupos" sincronizados. Una reserva nueva valida los cruces dentro de una transacción que bloquea la agenda del profesional, para que dos clientes no tomen la misma hora.

## ADR-09 · Máquina de estados y auditoría de la reserva (decisión D8)

- **Decisión:** los siete estados y sus transiciones válidas están en el enum `BookingStatus`; todo cambio pasa por un único método que valida, registra el historial y emite los eventos.
- **Motivo:** un solo lugar garantiza que nunca haya transiciones inválidas y que todo cambio quede auditado.

## ADR-10 · Archivos privados con URL firmada (decisión D9)

- **Decisión:** los documentos de identidad y los adjuntos del chat van a un disco privado y se entregan con URLs firmadas que vencen; el portafolio y los avatares son públicos.
- **Motivo:** Ley 1581 de 2012. Nadie puede ver una cédula o una foto del chat con solo adivinar la URL.

## ADR-11 · Sistema de diseño con tokens obligatorios

- **Decisión:** la paleta, la tipografía y las sombras se definen como tokens de Tailwind; un verificador en la CI rechaza los colores escritos a mano.
- **Motivo:** una identidad visual consistente aunque la construyan dos personas y asistentes de IA.

## ADR-12 · Calidad protegida (CI obligatoria y check Guardas)

- **Decisión:** `main` está protegida. Solo se fusiona por PR, con todas las verificaciones en verde. El check Guardas falla si un PR modifica las reglas de calidad (lint, CI, configuración de pruebas, instrucciones para agentes) o una migración existente, salvo que una persona lo apruebe con la etiqueta `cambio-de-reglas`.
- **Motivo:** el equipo trabaja con asistentes de IA, que tienden a "arreglar" un fallo relajando la regla en lugar del código. Así eso queda bloqueado.

## ADR-13 · Precio y duración por servicio (decisiones D6 y N3)

- **Decisión:** el profesional publica servicios con precio (fijo o por hora) y duración estimada. Al reservar se copia el precio a la reserva.
- **Motivo:** la duración define cuánto ocupa la reserva en la agenda, y un cambio de precio posterior no altera las reservas ya hechas.

## ADR-14 · Verificación obligatoria para reservar (decisión N2)

- **Decisión:** cualquiera puede registrarse y explorar, pero solo un cliente con la identidad verificada puede crear reservas.
- **Motivo:** proteger a los profesionales, que van a la casa del cliente, sin frenar el registro.
