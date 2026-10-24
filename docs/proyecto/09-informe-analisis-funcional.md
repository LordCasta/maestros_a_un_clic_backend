# Informe de análisis funcional del software

Este informe describe qué hace **Maestros a un clic**, cómo está construido y cómo se comporta hoy, con evidencia. Se elaboró el 23 de octubre de 2026 sobre la versión de `main` de ese día:

- backend `077976b`;
- frontend `0f6be1e`.

La validación se hizo sobre la aplicación en ejecución en un entorno local: Windows 11, PHP 8.5, MariaDB 10.4, Node.js 24 y Chrome.

El informe resume y enlaza los documentos de detalle:

- [requisitos](02-requisitos.md);
- [diagramas](03-diagramas.md);
- [trazabilidad](04-trazabilidad.md);
- [pruebas](05-pruebas.md);
- [seguridad](07-seguridad-y-privacidad.md).

Los scripts que ejecutaron las pruebas funcionales y tomaron las capturas están en [`_pruebas/`](_pruebas/), para que la validación se pueda repetir.

## 1. Descripción y estructura funcional del sistema

### 1.1 Información general del proyecto

| Campo | Valor |
|-------|-------|
| Nombre | Maestros a un clic |
| Tipo de software | Aplicación web (marketplace de servicios del hogar): API REST + aplicación de una sola página |
| Institución | Institución Universitaria Pascual Bravo |
| Programa y curso | Tecnología en Desarrollo de Software, sexto semestre · Ingeniería de Software 2 |
| Docente | Maira Perez |
| Integrantes | Juan Pablo Castaño (LordCasta) y Brayan Arenas (barenas12) |
| Repositorios | [Backend](https://github.com/LordCasta/maestros_a_un_clic_backend) (Laravel) · [Frontend](https://github.com/LordCasta/maestros_a_un_clic_frontend) (Vue) |
| Ámbito geográfico | Medellín: 16 comunas y 5 corregimientos |
| Estado | Las 49 historias de usuario implementadas; en evaluación funcional para la entrega final |

### 1.2 Problema o necesidad que resuelve

En Medellín, conseguir un plomero, electricista o carpintero confiable depende del voz a voz, de grupos de redes sociales sin ninguna validación o de empresas que cobran más y no dejan elegir quién hace el trabajo. Coordinar la cita por mensajes es lento y produce cruces de horario. El cliente no sabe a quién deja entrar a su casa, y el profesional no sabe a qué cliente va a atender.

El sistema responde a esa necesidad con cuatro mecanismos:

- **Verificación de identidad:** documentos revisados por un administrador.
- **Calificación recíproca:** el cliente califica al profesional y el profesional al cliente.
- **Agenda real:** no se pueden reservar dos servicios a la misma hora.
- **Historial auditable** de cada reserva.

### 1.3 Objetivo y alcance del software

**Objetivo general.** Conectar a clientes de Medellín con profesionales del hogar verificados mediante una plataforma web que permita buscarlos, reservar una hora libre de su agenda, comunicarse durante el servicio y calificarse mutuamente, de forma transparente y sin intermediarios.

**Alcance implementado:**

- Registro e inicio de sesión de clientes y profesionales, recuperación de contraseña y cierre de sesión por inactividad.
- Gestión de la cuenta: datos, foto, ubicación manual, contraseña y eliminación con anonimización.
- Perfil profesional con especialidades, tarifa, servicios con precio y duración, y portafolio.
- Agenda con horario semanal, descanso entre servicios, bloqueos y cálculo de horas libres en tiempo real.
- Búsqueda por nombre, categoría, precio, cercanía, comunas aledañas y "disponible ahora", con mapa.
- Ciclo completo de la reserva: solicitar, aceptar o rechazar, confirmar, reprogramar, cancelar, iniciar y finalizar, con historial.
- Chat por reserva con imágenes, estado en línea y eliminación de mensajes; notificaciones en tiempo real.
- Verificación de identidad (KYC) y panel de administración: verificaciones, usuarios, reportes y alertas.
- Calificaciones recíprocas, comentarios públicos, reportes de usuarios y alertas por baja calificación.
- Favoritos, historiales, totales e ingresos del profesional.

**Fuera del alcance:**

- **Pagos en línea:** el precio se pacta en la plataforma, pero el pago se hace por fuera.
- **Consulta automática de antecedentes con un proveedor externo:** no existe una API pública para integrar (decisión D10).
- **Aplicaciones móviles nativas:** la aplicación web es *responsive*.

### 1.4 Usuarios y roles

| Rol | Quién es | Qué puede hacer |
|-----|----------|-----------------|
| Visitante | Persona sin sesión | Ver la página de inicio, buscar profesionales, ver perfiles, servicios y comentarios públicos, y registrarse |
| Cliente | Persona que necesita un servicio | Todo lo del visitante, más: verificar su identidad, reservar (solo si está verificado), confirmar, reprogramar o cancelar, chatear, calificar, reportar, guardar favoritos y gestionar su cuenta |
| Profesional | Prestador de servicios del hogar | Publicar su perfil, servicios y portafolio; definir su agenda; aceptar o rechazar solicitudes; iniciar y finalizar servicios; chatear; ver el perfil del cliente; calificarlo; ver sus ingresos y verificarse. Solo aparece en las búsquedas cuando está verificado |
| Administrador | Equipo de la plataforma | Revisar y aprobar o rechazar verificaciones por documento, bloquear y desbloquear usuarios, atender reportes y resolver alertas. No ve los chats |
| Sistema | Procesos automáticos | Calcular la disponibilidad, enviar notificaciones y eventos en tiempo real, y generar alertas por reportes y bajas calificaciones |

Permisos por rol sobre las acciones principales (✅ permitido · ❌ denegado):

| Acción | Visitante | Cliente | Profesional | Administrador |
|--------|:---------:|:-------:|:-----------:|:-------------:|
| Buscar y ver perfiles | ✅ | ✅ | ✅ | ✅ |
| Solicitar una reserva | ❌ | ✅ verificado | ❌ | ❌ |
| Responder solicitudes, iniciar y finalizar | ❌ | ❌ | ✅ el suyo | ❌ |
| Chat de una reserva | ❌ | ✅ participante | ✅ participante | ❌ |
| Ver el perfil de un cliente | ❌ | ❌ | ✅ si tiene una reserva con él | ❌ |
| Panel de administración | ❌ | ❌ | ❌ | ✅ |

### 1.5 Módulos y funcionalidades

El sistema se organiza en los mismos módulos en el backend (controladores, servicios y políticas) y en el frontend (`src/modules`). Cada uno tuvo un responsable.

| Módulo | Funcionalidades | Historias | Responsable |
|--------|-----------------|-----------|-------------|
| Autenticación | Registro de cliente y profesional (en dos pasos), inicio y cierre de sesión, validación de datos | HU001, HU006, HU007, HU021, HU048 | Equipo (fase 0) |
| Cuenta | Datos y foto, ubicación manual, cambio y recuperación de contraseña, eliminación de cuenta, cierre por inactividad | HU019, HU020, HU022, HU023, HU031, HU032, HU050 | barenas12 |
| Perfil profesional | Descripción, especialidades, tarifa, servicios con precio y duración, portafolio, ingresos | HU009, HU037, HU047 | LordCasta |
| Agenda | Horario semanal, descanso, bloqueos, horas libres en tiempo real | HU003, HU025 | LordCasta |
| Búsqueda | Nombre, categoría y subcategoría, precio, orden, cercanía, comunas aledañas, disponible ahora, mapa | HU002, HU010, HU030, HU033, HU039, HU040, HU046 | barenas12 |
| Reservas | Solicitar, aceptar o rechazar, confirmar, reprogramar, cancelar, iniciar, finalizar, historial, totales, servicios en curso | HU004, HU012, HU016, HU017, HU026, HU027, HU028, HU036, HU049 | LordCasta |
| Chat y notificaciones | Chat por reserva con imágenes, estado en línea, eliminar mensajes, centro de notificaciones | HU011, HU018, HU041, HU042, HU043 | LordCasta |
| Verificación y administración | Cargue de documentos, revisión por documento, aprobación o rechazo, bloqueo de usuarios | HU008, HU013, HU014, HU038 | barenas12 |
| Calificaciones y reportes | Calificación recíproca, comentarios, perfil del cliente, reportes, alertas | HU005, HU024, HU029, HU044, HU045 | barenas12 |
| Favoritos | Guardar y ver profesionales favoritos | HU034, HU035 | Equipo (fase 0) |

Flujo principal del sistema:

```mermaid
flowchart LR
    A["Profesional se registra<br/>y se verifica"] --> B["Publica servicios<br/>y su agenda"]
    C["Cliente busca<br/>y elige una hora libre"] --> D["Solicitud<br/>(pendiente)"]
    B --> D
    D -->|"profesional acepta"| E["Aceptada"]
    E -->|"cliente confirma"| F["Confirmada"]
    F -->|"desde la hora pactada"| G["En curso"]
    G --> H["Completada"]
    H --> I["Calificación recíproca"]
    D -.->|"rechaza o cancela"| X["Cerrada"]
    E -.-> X
    F -.-> X
```

### 1.6 Inventario de funcionalidades y estado de desarrollo

Las 49 historias de usuario están implementadas y sus issues cerrados en GitHub (la numeración del documento original no tiene HU015). Al revisar criterio por criterio para este informe se encontraron **5 historias con un criterio de aceptación incompleto**. Se marcan como *Parcial* y se detallan en la sección 3.3.

| HU | Funcionalidad | Módulo | Estado |
|----|---------------|--------|--------|
| HU001 | Inicio de sesión | Autenticación | ✅ Completa |
| HU002 | Búsqueda por geolocalización | Búsqueda | ✅ Completa |
| HU003 | Gestión de disponibilidad y agenda | Agenda | ✅ Completa |
| HU004 | Solicitud y aceptación de servicio | Reservas | ✅ Completa |
| HU005 | Calificación recíproca | Calificaciones | ✅ Completa |
| HU006 | Registro de cliente | Autenticación | ✅ Completa |
| HU007 | Registro de profesional | Autenticación | ✅ Completa |
| HU008 | Cargue de documentos | Verificación | ✅ Completa |
| HU009 | Edición de perfil profesional | Perfil profesional | ✅ Completa |
| HU010 | Filtros y ordenamiento | Búsqueda | ✅ Completa |
| HU011 | Chat de la reserva | Chat | ✅ Completa |
| HU012 | Cancelación y reprogramación | Reservas | ✅ Completa |
| HU013 | Validación de identidad y antecedentes | Verificación | ⚠️ Parcial: la comparación selfie-documento la hace el administrador, no un proveedor automático (decisión D10) |
| HU014 | Revisión y aprobación de documentos | Administración | ✅ Completa |
| HU016 | Historial del cliente | Reservas | ✅ Completa |
| HU017 | Historial del profesional | Reservas | ✅ Completa |
| HU018 | Notificaciones | Notificaciones | ✅ Completa |
| HU019 | Cambio de contraseña | Cuenta | ✅ Completa |
| HU020 | Recuperación de contraseña | Cuenta | ✅ Completa |
| HU021 | Cerrar sesión | Autenticación | ✅ Completa |
| HU022 | Ubicación manual | Cuenta | ✅ Completa |
| HU023 | Eliminación de cuenta | Cuenta | ✅ Completa |
| HU024 | Reporte de usuarios | Calificaciones | ✅ Completa |
| HU025 | Disponibilidad del profesional | Agenda | ✅ Completa |
| HU026 | Estado del servicio | Reservas | ✅ Completa |
| HU027 | Inicio de servicio | Reservas | ✅ Completa |
| HU028 | Finalización de servicio | Reservas | ✅ Completa |
| HU029 | Visualización de comentarios | Calificaciones | ✅ Completa |
| HU030 | Disponible ahora | Búsqueda | ⚠️ Parcial: el resultado se calcula al buscar, pero la lista no se refresca sola mientras está abierta |
| HU031 | Edición de perfil del cliente | Cuenta | ✅ Completa |
| HU032 | Foto de perfil | Cuenta | ✅ Completa |
| HU033 | Servicios por categoría | Búsqueda | ✅ Completa |
| HU034 | Guardar favoritos | Favoritos | ✅ Completa |
| HU035 | Ver favoritos | Favoritos | ✅ Completa |
| HU036 | Confirmación por el cliente | Reservas | ✅ Completa |
| HU037 | Ingresos del profesional | Perfil profesional | ✅ Completa |
| HU038 | Bloqueo de usuarios | Administración | ✅ Completa |
| HU039 | Búsqueda por nombre | Búsqueda | ✅ Completa |
| HU040 | Visualización de distancia | Búsqueda | ⚠️ Parcial: la distancia aparece en el listado, pero no en el perfil del profesional |
| HU041 | Estado en línea | Chat | ✅ Completa |
| HU042 | Adjuntos en el chat | Chat | ✅ Completa |
| HU043 | Eliminar mensajes | Chat | ✅ Completa |
| HU044 | Perfil del cliente | Calificaciones | ⚠️ Parcial: muestra nombre, foto, calificación y servicios, pero no los comentarios que recibió |
| HU045 | Alerta por baja calificación | Calificaciones | ⚠️ Parcial: la alerta identifica al usuario, pero no enlaza a su perfil para revisarlo |
| HU046 | Ordenamiento por precio | Búsqueda | ✅ Completa |
| HU047 | Tiempo estimado | Perfil profesional | ✅ Completa |
| HU048 | Validación de datos obligatorios | Autenticación | ✅ Completa |
| HU049 | Servicios en proceso | Reservas | ✅ Completa |
| HU050 | Cierre por inactividad | Cuenta | ✅ Completa |

**Resumen:** 44 completas (90 %) y 5 parciales (10 %). Ninguna historia quedó sin implementar.

### 1.7 Reglas de negocio

| ID | Regla | Dónde se hace cumplir |
|----|-------|-----------------------|
| RN‑01 | Una cuenta tiene un solo rol: cliente, profesional o administrador | `users.role` y middleware `role:` en las rutas |
| RN‑02 | El cliente debe tener la identidad verificada para reservar (decisión N2) | `BookingPolicy::create` (403) |
| RN‑03 | Solo los profesionales verificados y no bloqueados aparecen en la búsqueda y tienen perfil público | `User::publiclyListed()` |
| RN‑04 | Una reserva solo puede caer dentro del horario semanal del profesional y fuera de sus bloqueos | `AvailabilityService::withinSchedule` (422) |
| RN‑05 | Dos reservas activas del mismo profesional no se pueden cruzar, contando el descanso configurado antes y después de cada una | `AvailabilityService::hasBookingConflict` y bloqueo de fila en la transacción (409) |
| RN‑06 | El precio pactado es una copia del precio del servicio al reservar; si el profesional lo cambia después, la reserva no cambia (N3) | `bookings.agreed_price` |
| RN‑07 | Los estados siguen una máquina de estados: pendiente → aceptada → confirmada → en curso → completada; rechazar o cancelar cierra la reserva | `BookingStatus::allowedTransitions` (409 si la transición no es válida) |
| RN‑08 | El servicio solo se puede iniciar desde la hora pactada | `BookingService::start` (409) |
| RN‑09 | Reprogramar devuelve la reserva a *pendiente* para que el profesional vuelva a aceptar; cancelar exige un motivo | `BookingService` y `CancelBookingRequest` |
| RN‑10 | Cada cambio de estado queda registrado con autor, fecha y motivo | `booking_status_changes` (D8) |
| RN‑11 | No se puede bloquear un horario que tiene una reserva activa | `AvailabilityService::addBlock` (409) |
| RN‑12 | El chat solo lo ven los dos participantes de la reserva y se cierra cuando la reserva termina | `MessagePolicy` y `ChatService` |
| RN‑13 | Solo se califica una reserva completada, una vez por cada parte; el promedio se actualiza solo | `ReviewService` (409) |
| RN‑14 | Una calificación de 2 o menos, o un promedio menor a 3 con 3 o más calificaciones, genera una alerta para el administrador; un reporte también | `ReviewService`, `ModerationService` |
| RN‑15 | Un reporte hecho desde una reserva solo puede venir de uno de sus participantes y apuntar al otro | `StoreReportRequest` (422) |
| RN‑16 | El perfil de un cliente solo lo ve un profesional que tiene o tuvo una reserva con él | `UserPolicy::viewClientProfile` (403) |
| RN‑17 | Un usuario bloqueado no puede entrar ni usar la API; un administrador no puede ser bloqueado | Middleware `not_blocked`, `ModerationService` |
| RN‑18 | No se puede eliminar la cuenta con reservas activas o pendientes; al eliminarla se anonimiza y la otra parte conserva su historial (D12) | `AccountService::delete` |
| RN‑19 | Los documentos de identidad y los adjuntos del chat son privados: solo se entregan con un enlace firmado que vence | Disco privado y URL firmada (D9) |

## 2. Documentación técnica y funcionamiento del software

### 2.1 Matriz de requerimientos funcionales

Requerimientos funcionales agrupados a partir de las historias de usuario.

**Prioridad:**
- **Alta:** sin el requerimiento no existe el flujo principal.
- **Media:** mejora la confianza o la operación.
- **Baja:** comodidad.

La relación detallada de cada historia con sus endpoints, pantallas y pruebas está en la [matriz de trazabilidad](04-trazabilidad.md).

| ID | Requerimiento | Actor | Historias | Prioridad | Estado |
|----|---------------|-------|-----------|-----------|--------|
| RF‑01 | Registrar clientes y profesionales con validación de datos | Visitante | HU006, HU007, HU048 | Alta | ✅ |
| RF‑02 | Autenticar, cerrar sesión y cerrar por inactividad | Usuario | HU001, HU021, HU050 | Alta | ✅ |
| RF‑03 | Gestionar la cuenta: datos, foto, ubicación, contraseña, recuperación y eliminación | Usuario | HU019, HU020, HU022, HU023, HU031, HU032 | Media | ✅ |
| RF‑04 | Gestionar el perfil profesional, sus servicios con precio y duración, y su portafolio | Profesional | HU009, HU047 | Alta | ✅ |
| RF‑05 | Definir el horario semanal, el descanso y los bloqueos, y calcular las horas libres | Profesional, Sistema | HU003, HU025 | Alta | ✅ |
| RF‑06 | Buscar profesionales por nombre, categoría, precio, cercanía y disponibilidad, con orden y sugerencias | Visitante, Cliente | HU002, HU010, HU030, HU033, HU039, HU040, HU046 | Alta | ⚠️ HU030 y HU040 parciales |
| RF‑07 | Solicitar, aceptar o rechazar y confirmar una reserva sin cruces de horario | Cliente, Profesional | HU004, HU036 | Alta | ✅ |
| RF‑08 | Reprogramar, cancelar, iniciar y finalizar una reserva, con historial auditable | Cliente, Profesional | HU012, HU026, HU027, HU028 | Alta | ✅ |
| RF‑09 | Consultar historiales, totales, servicios en curso e ingresos | Cliente, Profesional | HU016, HU017, HU037, HU049 | Media | ✅ |
| RF‑10 | Comunicarse por chat con imágenes, estado en línea y eliminación de mensajes | Cliente, Profesional | HU011, HU041, HU042, HU043 | Media | ✅ |
| RF‑11 | Notificar en tiempo real los eventos de la reserva y del chat | Sistema | HU018 | Media | ✅ |
| RF‑12 | Verificar la identidad con documentos revisados por un administrador | Usuario, Administrador | HU008, HU013, HU014 | Alta | ⚠️ HU013 parcial |
| RF‑13 | Calificar recíprocamente y mostrar comentarios | Cliente, Profesional | HU005, HU029 | Media | ✅ |
| RF‑14 | Ver el perfil del cliente antes de atenderlo | Profesional | HU044 | Media | ⚠️ HU044 parcial |
| RF‑15 | Reportar usuarios, generar alertas y moderar (bloquear y desbloquear) | Usuario, Administrador, Sistema | HU024, HU038, HU045 | Media | ⚠️ HU045 parcial |
| RF‑16 | Guardar y ver profesionales favoritos | Cliente | HU034, HU035 | Baja | ✅ |

### 2.2 Modelo de datos y diagrama entidad-relación

La base de datos es relacional (MySQL 8 en producción; en el entorno local se usó MariaDB 10.4, compatible). Tiene **21 tablas de dominio** más las de Laravel (caché, colas, sesiones y tokens), con llaves foráneas en todas las relaciones. Los estados, roles y tipos se guardan como texto y se modelan con *enums* de PHP (decisión D15).

```mermaid
erDiagram
    communes ||--o{ users : "ubica"
    communes }o--o{ communes : "vecinas"
    users ||--o| client_profiles : "tiene"
    users ||--o| professional_profiles : "tiene"
    professional_profiles }o--o{ categories : "especialidades"
    categories ||--o{ categories : "subcategorías"
    professional_profiles ||--o{ professional_services : "ofrece"
    categories ||--o{ professional_services : "clasifica"
    professional_profiles ||--o{ portfolio_items : "muestra"
    professional_profiles ||--o{ availability_rules : "horario"
    professional_profiles ||--o{ availability_blocks : "bloqueos"
    users ||--o{ verification_requests : "solicita"
    verification_requests ||--o{ verification_documents : "contiene"
    users ||--o{ bookings : "cliente / profesional"
    professional_services ||--o{ bookings : "se reserva"
    bookings ||--o{ booking_status_changes : "historial"
    bookings ||--o{ messages : "chat"
    bookings ||--o{ reviews : "calificaciones"
    users ||--o{ favorites : "guarda"
    users ||--o{ reports : "reporta / es reportado"
    users ||--o{ admin_alerts : "genera"
    users ||--o{ notifications : "recibe"
```

| Entidad | Qué guarda |
|---------|-----------|
| `users` | Cuenta de cualquier rol: datos de contacto, comuna y coordenadas, estado de verificación, promedio de calificación y bloqueo. Se elimina con *soft delete* y anonimización (D12) |
| `client_profiles` / `professional_profiles` | Datos propios de cada rol: descripción, experiencia, tarifa, radio de servicio y descanso entre servicios del profesional |
| `communes`, `commune_neighbors` | Las 21 comunas y corregimientos de Medellín con su centroide y sus vecinas (para sugerir comunas aledañas) |
| `categories`, `professional_category` | Especialidades y subcategorías jerárquicas, y las especialidades de cada profesional |
| `professional_services` | Servicios que ofrece el profesional, con precio (por hora o fijo) y duración estimada |
| `portfolio_items` | Fotos de trabajos anteriores |
| `availability_rules`, `availability_blocks` | Horario semanal y horas bloqueadas. Las horas libres se calculan, no se guardan (D7) |
| `verification_requests`, `verification_documents` | Solicitudes de verificación y sus documentos (cédula, selfie, certificados, evidencias) en almacenamiento privado |
| `bookings`, `booking_status_changes` | Reservas con su precio pactado y el historial de cada cambio de estado |
| `messages` | Chat de cada reserva, con adjuntos privados |
| `reviews` | Calificaciones de 1 a 5 y comentarios, en los dos sentidos |
| `favorites`, `reports`, `admin_alerts`, `notifications` | Favoritos, denuncias, alertas para el administrador y notificaciones de cada usuario |

El diccionario completo de columnas está en [`docs/modelo-de-datos.md`](../modelo-de-datos.md), y el diagrama de clases y los diagramas de estados, en [03-diagramas.md](03-diagramas.md).

### 2.3 Interfaces del sistema

**Interfaz de usuario.** Es una aplicación web de una sola página (Vue 3) con un sistema de diseño propio: colores, tipografía y componentes base. Se adapta a celular, tablet y escritorio. Tiene **29 pantallas**:

| Rol | Pantallas |
|-----|-----------|
| Público (7) | Inicio, búsqueda con filtros y mapa, perfil del profesional, inicio de sesión, registro de cliente, registro de profesional en dos pasos, recuperar y restablecer contraseña |
| Cliente (9) | Panel de inicio, mis reservas con filtros y totales, detalle de la reserva (datos, chat, historial y acciones), nueva reserva con selector de horas, favoritos, perfil, ubicación, seguridad (contraseña y eliminar cuenta) y verificación |
| Profesional (9) | Panel con solicitudes pendientes y servicios en curso, reservas recibidas, detalle de la reserva con perfil del cliente, disponibilidad, servicios, sobre mí (perfil, especialidades y portafolio), ingresos, cuenta y verificación |
| Administrador (4) | Verificaciones (con revisión por documento), usuarios, reportes y alertas |

Todas las pantallas comparten la barra superior con la campana de notificaciones. Los formularios validan en el navegador antes de enviar y muestran junto a cada campo los errores que devuelve el servidor.

**Interfaz de programación (API REST).** Es la que usa el frontend y la que podría usar una aplicación móvil:

- **Rutas:** 74, bajo `/api/v1`.
- **Autenticación:** token Bearer (Laravel Sanctum).
- **Formato de respuesta:** JSON uniforme `{ success, message, data }`, con `meta` en los listados paginados.
- **Errores:** también uniformes. 422 con el error de cada campo, 401, 403, 404, 409 (regla de negocio) y 429 (demasiados intentos).

El contrato está en [`docs/api/endpoints.md`](../api/endpoints.md) y en la especificación [OpenAPI 3.1](../api/openapi.json). En local también hay documentación interactiva en `/docs/api`.

**Interfaz de tiempo real.** Usa WebSockets con Laravel Reverb y tres tipos de canal:

- **Privado del usuario:** notificaciones.
- **De presencia por reserva:** mensajes, mensajes eliminados, cambios de estado y quién está en línea.
- **Público de la agenda de cada profesional:** las horas libres se actualizan solas cuando alguien reserva. Ver [`docs/tiempo-real.md`](../tiempo-real.md).

**Interfaces externas:**
- **Mapa:** Google Maps embebido, sin clave de API.
- **Correo electrónico:** enlace de recuperación de contraseña. En local queda en el log.
- **Almacenamiento de archivos:** un disco público para fotos y portafolio, y uno privado para documentos y adjuntos.

### 2.4 Capturas de pantalla de las funcionalidades implementadas

Las capturas se tomaron automáticamente con Chrome sobre la aplicación en local, con las cuentas de demostración, y reflejan su estado real. El script es [`_pruebas/capturas.mjs`](_pruebas/capturas.mjs). Las de escritorio miden 1366 px de ancho y las de celular, 390 px.

#### Visitante

![Página de inicio](img/inicio.jpg)
*Figura 1. Página de inicio con el buscador principal.*

![Búsqueda de profesionales](img/busqueda.jpg)
*Figura 2. Búsqueda (HU010, HU033): filtros por comuna, categoría, subcategoría, orden y tarifa, botones "Cerca de mí" y "Disponible ahora", y mapa de la zona.*

![Perfil público del profesional](img/perfil-profesional.jpg)
*Figura 3. Perfil público del profesional: sello de verificado, calificación, experiencia, servicios con precio y duración estimada (HU047), y opiniones de clientes (HU029).*

![Búsqueda en celular](img/busqueda-movil.jpg)
*Figura 4. La búsqueda en un celular (390 px).*

![Inicio de sesión](img/login.jpg)
*Figura 5. Inicio de sesión (HU001), con acceso al registro de cliente o profesional y a la recuperación de contraseña.*

![Error de inicio de sesión](img/login-error.jpg)
*Figura 6. Validación: con una contraseña incorrecta, el sistema muestra el error y no da acceso.*

![Registro de profesional, paso 1](img/registro-profesional-1.jpg)
*Figura 7. Registro de profesional, paso 1 (HU007): datos de la cuenta y comuna.*

![Registro de profesional, paso 2](img/registro-profesional-2.jpg)
*Figura 8. Registro de profesional, paso 2: especialidades, experiencia, tarifa y descripción.*

![Recuperar contraseña](img/recuperar-contrasena.jpg)
*Figura 9. Recuperación de contraseña (HU020).*

#### Cliente

![Nueva reserva](img/cliente-nueva-reserva.jpg)
*Figura 10. Solicitar una reserva (HU004, HU025): el cliente elige el servicio, el día y una hora libre de la agenda real, y ve el resumen con fecha, duración y precio.*

![Mis reservas](img/cliente-reservas.jpg)
*Figura 11. Mis reservas (HU016), con totales y filtros por estado.*

![Detalle de la reserva con chat](img/cliente-detalle-reserva.jpg)
*Figura 12. Detalle de una reserva confirmada: datos, profesional, acciones para reprogramar o cancelar (HU012), chat (HU011) e historial de estados con autor y fecha (HU026).*

![Favoritos](img/cliente-favoritos.jpg)
*Figura 13. Profesionales favoritos (HU035).*

![Perfil del cliente](img/cliente-perfil.jpg)
*Figura 14. Edición del perfil y la foto (HU031, HU032).*

![Seguridad de la cuenta](img/cliente-seguridad.jpg)
*Figura 15. Seguridad: cambio de contraseña (HU019). Más abajo, en la misma página, está la eliminación de la cuenta (HU023).*

![Verificación en revisión](img/verificacion-en-revision.jpg)
*Figura 16. Verificación de identidad (HU008): documentos enviados y en revisión.*

#### Profesional

![Reservas recibidas](img/profesional-reservas.jpg)
*Figura 17. Reservas recibidas (HU017), con totales por estado.*

![Disponibilidad](img/profesional-agenda.jpg)
*Figura 18. Disponibilidad (HU003): horario semanal con varias franjas por día, descanso entre servicios y horas bloqueadas.*

![Mis servicios](img/profesional-servicios.jpg)
*Figura 19. Servicios del profesional con precio y duración estimada (HU009, HU047).*

![Sobre mí](img/profesional-sobre-mi.jpg)
*Figura 20. Perfil profesional (HU009): descripción, experiencia, tarifa, radio de servicio y ubicación. Más abajo, en la misma página, están las especialidades y el portafolio.*

![Ingresos](img/profesional-ingresos.jpg)
*Figura 21. Ingresos por rango de fechas (HU037).*

![Perfil del cliente visto por el profesional](img/profesional-perfil-cliente.jpg)
*Figura 22. Perfil del cliente desde la solicitud (HU044): calificación, comuna, servicios completados y antigüedad.*

#### Administrador

![Bandeja de verificaciones](img/admin-verificaciones.jpg)
*Figura 23. Bandeja de verificaciones (HU014), con filtros por estado, rol y tipo de documento.*

![Revisión de documentos](img/admin-verificacion-detalle.jpg)
*Figura 24. Revisión de una solicitud: cada documento se abre por un enlace seguro y se aprueba o rechaza por separado.*

![Usuarios](img/admin-usuarios.jpg)
*Figura 25. Moderación de usuarios (HU038): búsqueda, filtros, bloqueo y desbloqueo.*

![Reportes](img/admin-reportes.jpg)
*Figura 26. Bandeja de reportes (HU024): quién reporta, a quién, motivo y descripción.*

![Alertas](img/admin-alertas.jpg)
*Figura 27. Alertas del sistema (HU045), generadas por reportes y bajas calificaciones.*

### 2.5 Tecnologías y herramientas utilizadas

| Capa | Tecnología | Versión | Uso |
|------|------------|---------|-----|
| Backend | PHP | 8.2 a 8.5 | Lenguaje del servidor (la CI prueba en 8.2 y 8.5) |
| | Laravel | 12.69 | Framework de la API: rutas, validación, ORM, colas, notificaciones |
| | Laravel Sanctum | 4 | Autenticación por token |
| | Laravel Reverb | 1.12 | Servidor de WebSockets propio |
| | Scramble | 0.13 | Documentación OpenAPI generada desde el código |
| Frontend | Vue | 3.5 | Interfaz de una sola página |
| | TypeScript | 5.9 | Tipado estático |
| | Vite | 8 | Servidor de desarrollo y empaquetado |
| | Vue Router, Pinia, Pinia Colada | 5 · 4 · 1.4 | Navegación, estado y caché de datos del servidor |
| | VeeValidate + Zod | 4.15 · 3.25 | Formularios y validación |
| | Tailwind CSS | 4.3 | Estilos con tokens del sistema de diseño |
| | Laravel Echo + pusher-js | 2.5 · 8.6 | Cliente de tiempo real |
| Datos | MySQL / MariaDB | 8 / 10.4 | Base de datos relacional |
| Calidad | Pest | 3.8 | Pruebas de API (backend) |
| | Vitest + Vue Test Utils | 4.1 · 2.4 | Pruebas de componentes y funciones (frontend) |
| | Pint, ESLint, oxlint, Prettier | — | Estilo y análisis estático |
| | Puppeteer (Chrome) | — | Pruebas funcionales en el navegador, capturas y PDF de la documentación |
| Proceso | Git y GitHub | — | Ramas por historia, *pull requests* con revisión y *squash merge* |
| | GitHub Actions | — | CI obligatoria: pruebas, estilo, tipos, build, auditoría de dependencias y "Guardas" |
| | GitHub Issues | — | Una historia de usuario por issue, con sus criterios |
| Despliegue previsto | AWS EC2, Nginx, Supervisor, TLS | — | Ver [06-despliegue-y-operacion.md](06-despliegue-y-operacion.md) |

### 2.6 Validaciones y restricciones del sistema

Toda entrada se valida en el servidor. Cada endpoint con datos tiene un `FormRequest`, y un error responde 422 con el mensaje de cada campo en español. El frontend valida además con Zod para avisar antes de enviar, pero nunca reemplaza al servidor.

| Tipo | Validación o restricción |
|------|---------------------------|
| Registro y cuenta | Nombre y correo obligatorios, correo con formato válido y único, contraseña de 8 caracteres o más con confirmación, comuna existente. Al profesional se le exige además al menos una especialidad, una descripción de 50 caracteres o más, experiencia de 0 a 60 años y una tarifa mayor a 0 |
| Archivos | Fotos de perfil y portafolio: JPG, PNG o WEBP de hasta 5 MB. Documentos de verificación: además PDF, de hasta 10 MB, y mínimo 3 por solicitud. Para aprobarla deben estar el documento de identidad y la selfie. Adjuntos del chat: imágenes de hasta 5 MB |
| Reservas | La fecha debe ser futura, estar dentro del horario, no tocar bloqueos ni cruzarse con otra reserva más el descanso. El servicio debe estar activo y el cliente verificado |
| Estados | Solo las transiciones permitidas: una acción fuera de orden responde 409 con el motivo (por ejemplo, iniciar antes de la hora o calificar una reserva no completada) |
| Agenda | Las franjas de un mismo día no se pueden cruzar, el fin debe ser posterior al inicio y el descanso va de 0 a 240 minutos |
| Chat | Solo los participantes. Texto de hasta 2000 caracteres, o una imagen. Se cierra cuando la reserva termina. Solo se eliminan los mensajes propios |
| Calificaciones y reportes | Calificación entera de 1 a 5 y comentario de hasta 1000 caracteres, una vez por parte. Reporte con motivo (3 a 100 caracteres) y descripción (10 a 2000). No se puede uno reportar a sí mismo ni usar una reserva ajena |
| Acceso | Rutas privadas con token (401 sin token). Rol por ruta (403). *Policies* por recurso: solo el dueño o un participante (403). Los usuarios bloqueados no entran |
| Abuso | Registro, inicio de sesión y recuperación aceptan como máximo 10 intentos por minuto (429). La recuperación responde igual exista o no el correo |
| Sesión | Se cierra tras 15 minutos sin actividad en el navegador. Los tokens se revocan al cerrar sesión, restablecer la contraseña, bloquear o eliminar la cuenta |
| Privacidad | El perfil público no expone correo, teléfono ni dirección. Los documentos y adjuntos solo se entregan con URL firmada que vence |

## 3. Validación funcional y oportunidades de mejora

### 3.1 Matriz de pruebas funcionales

Las pruebas funcionales son de **caja negra**: se ejecutaron sobre la aplicación en marcha, sin mirar el código, contra el mismo contrato que usa la interfaz. Los casos CP-41 y CP-42 se hicieron directamente en la pantalla, con Chrome.

Cada caso registra el resultado esperado y el obtenido. Las cuentas son las de demostración, y para no afectar a esas cuentas, las pruebas de bloqueo y eliminación usan usuarios creados durante la prueba.

| ID | HU / RNF | Caso | Tipo | Resultado esperado | Resultado obtenido | Estado |
|----|----------|------|------|--------------------|--------------------|--------|
| CP‑01 | HU001 | Iniciar sesión con credenciales válidas | Positivo | 200 con token | 200, token emitido, rol cliente | ✅ |
| CP‑02 | HU001 | Iniciar sesión con contraseña incorrecta | Negativo | 401 "Credenciales incorrectas." | 401 "Credenciales incorrectas." | ✅ |
| CP‑03 | HU006, HU048 | Registro de cliente con datos vacíos | Negativo | 422 en nombre, correo y contraseña | 422 en `name`, `email` y `password` | ✅ |
| CP‑04 | HU020 | Recuperar contraseña con un correo no registrado | Negativo | Misma respuesta que con un correo registrado | 200 y 200 con el mismo mensaje | ✅ |
| CP‑05 | HU019 | Cambiar la contraseña con la actual incorrecta | Negativo | 422 en la contraseña actual | 422 en `current_password` | ✅ |
| CP‑06 | HU022 | Actualizar la ubicación con una comuna inexistente | Negativo | 422 en la comuna | 422 en `commune_id` | ✅ |
| CP‑07 | HU021 | Cerrar sesión invalida el token | Positivo | Cierre 200 y luego 401 con el mismo token | 200 y luego 401 | ✅ |
| CP‑08 | RNF-SEG-01 | Consultar reservas sin token | Negativo | 401 | 401 | ✅ |
| CP‑09 | RNF-SEG-02 | Un cliente entra al panel de administración | Negativo | 403 | 403 | ✅ |
| CP‑10 | HU033, HU010 | Filtrar por categoría | Positivo | Todos los resultados tienen la especialidad | 2 resultados de Carpintería, todos con ella | ✅ |
| CP‑11 | HU046 | Ordenar por precio ascendente | Positivo | Tarifas de menor a mayor | 13 tarifas en orden: 40.000, 50.000, 55.000… | ✅ |
| CP‑12 | HU039 | Buscar por nombre | Positivo | Aparece el profesional | "Juan": aparece Juan Castaño | ✅ |
| CP‑13 | HU002 | Comuna sin profesionales | Positivo | Sugiere de comunas aledañas | Popular: 1 sugerido de una comuna vecina | ✅ |
| CP‑14 | HU040 | Buscar por ubicación | Positivo | Distancia en km, del más cercano al más lejano | 9 resultados: 1,2 · 1,5 · 2,6 · 2,9 · 3,8 km… | ✅ |
| CP‑15 | HU030 | Filtro "disponible ahora" | Positivo | Solo quienes están en su horario en ese momento | 0 resultados a las 10:47 p. m. (nadie atiende de noche) | ✅ |
| CP‑16 | HU029 | Comentarios públicos de un profesional | Positivo | Lista de reseñas | 1 reseña | ✅ |
| CP‑17 | HU025 | Horas libres de un servicio | Positivo | Lista de horas del día | Primera hora libre: 24 oct, 8:00 a. m. | ✅ |
| CP‑18 | HU004 | Cliente verificado solicita una reserva | Positivo | 201, estado pendiente | 201, reserva en `pending` | ✅ |
| CP‑19 | RN-02 | Cliente sin verificar intenta reservar | Negativo | 403 | 403 "Debes verificar tu identidad…" | ✅ |
| CP‑20 | HU025 | La hora reservada deja de estar libre | Positivo | El horario ya no aparece | 8:00 a. m. ya no aparece | ✅ |
| CP‑21 | HU018 | El profesional recibe la notificación | Positivo | Notificación de nueva solicitud | "Nueva solicitud de reserva" | ✅ |
| CP‑22 | HU004 | El profesional acepta | Positivo | 200, aceptada | 200, `accepted` | ✅ |
| CP‑23 | HU003 | Bloquear un horario con una reserva activa | Negativo | 409 | 409 "Tienes reservas activas en ese horario…" | ✅ |
| CP‑24 | HU036 | El cliente confirma | Positivo | 200, confirmada | 200, `confirmed` | ✅ |
| CP‑25 | HU027 | Iniciar antes de la hora pactada | Negativo | 409 | 409 "Solo puedes iniciar el servicio desde la hora programada…" | ✅ |
| CP‑26 | HU011 | Enviar un mensaje en el chat | Positivo | 201 | 201 | ✅ |
| CP‑27 | HU011 | Un tercero intenta leer el chat | Negativo | 403 | 403 | ✅ |
| CP‑28 | HU026 | Historial de estados | Positivo | pendiente → aceptada → confirmada | pending → accepted → confirmed | ✅ |
| CP‑29 | HU012 | Cancelar con motivo | Positivo | 200, cancelada | 200, `cancelled` | ✅ |
| CP‑30 | HU017 | Resumen de reservas por estado | Positivo | Totales por estado | Total 6: 2 pendientes, 2 confirmadas, 1 completada, 1 cancelada | ✅ |
| CP‑31 | HU037 | Ingresos del profesional | Positivo | Total y servicios del rango | Del 1 al 23 de octubre: $0 en 0 servicios | ✅ |
| CP‑32 | HU005 | Calificar una reserva no completada | Negativo | 409 | 409 "Solo se pueden calificar reservas completadas." | ✅ |
| CP‑33 | HU024 | Reportar a la otra parte de una reserva | Positivo | 201 | 201 | ✅ |
| CP‑34 | HU024 | Reportar con una reserva ajena | Negativo | 422 | 422 en `booking_id` | ✅ |
| CP‑35 | HU045 | El reporte genera una alerta | Positivo | Alerta pendiente | Alerta sobre Juan Castaño | ✅ |
| CP‑36 | HU044 | El profesional ve a su cliente | Positivo | 200 con calificación y servicios | 200: Camila Restrepo, 1 servicio completado | ✅ |
| CP‑37 | HU008 | Enviar documentos de verificación | Positivo | 201, en revisión | 201, `pending` | ✅ |
| CP‑38 | HU014 | La solicitud llega al administrador | Positivo | Aparece en la bandeja | Solicitud con 3 documentos | ✅ |
| CP‑39 | HU038 | Bloquear a un usuario | Positivo | Bloqueo 200 e inicio de sesión 403 | 200 y 403 "Tu cuenta está bloqueada…" | ✅ |
| CP‑40 | HU023 | Eliminar la cuenta | Positivo | 200 y el correo anterior ya no entra | 200 y luego 401 | ✅ |
| CP‑41 | HU001 | Contraseña incorrecta desde la pantalla | Negativo | Se muestra el error y no entra | "Credenciales incorrectas." y sigue en el login | ✅ |
| CP‑42 | HU007 | Registro de profesional en dos pasos desde la pantalla | Positivo | Crea la cuenta y lleva a su panel | Cuenta creada y redirigida a `/professional/dashboard` | ✅ |
| CP‑43 | RN-16 | Profesional sin reservas con el cliente ve su perfil | Negativo | 403 | 403 | ✅ |
| CP‑44 | RNF-SEG-04 | 11 intentos de inicio de sesión en un minuto | Negativo | Diez 401 y el undécimo 429 | 401 ×10, 429 | ✅ |

### 3.2 Resultados de las pruebas realizadas

| Verificación | Alcance | Resultado |
|--------------|---------|-----------|
| Pruebas funcionales (sección 3.1) | 44 casos: 28 positivos y 16 negativos, de 35 historias, 2 reglas de negocio y 3 requisitos de seguridad | ✅ 44 de 44 aprobados (23 de octubre de 2026) |
| Pruebas automatizadas del backend (Pest) | 220 pruebas de API sobre SQLite en memoria | ✅ 220 aprobadas, 992 aserciones |
| Pruebas automatizadas del frontend (Vitest) | 158 pruebas en 38 archivos | ✅ 158 aprobadas |
| Diseño adaptable | 29 pantallas de los cuatro roles a 390, 1024 y 1366 px (87 combinaciones) | ✅ Ninguna se desborda, después de corregir 4 problemas (3.3) |
| Análisis estático | Tipos, ESLint, oxlint, Pint, Prettier y verificador de colores | ✅ Sin errores |
| Auditoría de dependencias | `composer audit` y `npm audit` | ⚠️ Backend sin hallazgos. Frontend: aviso alto en `zod` (3.3, E-14) |
| Integración continua | Cada *pull request* | ✅ Obligatoria: no se puede fusionar con un check en rojo |

Observaciones de la ejecución:

- **El límite de intentos funciona.** En el primer intento de la batería, el límite de 10 intentos por minuto en autenticación (RNF-SEG-04) bloqueó 5 casos con 429, porque el script hacía demasiados registros e inicios de sesión seguidos. Se agregaron pausas de un minuto entre bloques, y se convirtió en un caso propio (CP-44).
- **Resultado según la hora.** CP-15 dio 0 resultados porque se ejecutó de noche, fuera del horario de todos los profesionales. Eso es lo esperado: la prueba automatizada `UserReportsAndModerationTest` cubre el caso positivo con una hora fija.
- **Ingresos en cero.** CP-31 dio $0 porque en los datos de demostración no hay servicios completados en octubre.

### 3.3 Errores y funcionalidades incompletas

**Severidad:**
- **Alta:** afecta la confianza, la seguridad o un flujo principal.
- **Media:** afecta una funcionalidad o la experiencia.
- **Baja:** detalle.

Los errores encontrados en la revisión final del 21 al 23 de octubre de 2026 que ya se corrigieron se dejan para constancia, con el *pull request* que los cerró.

**Corregidos durante la revisión:**

| ID | Hallazgo | Severidad | Corrección |
|----|----------|-----------|------------|
| E‑01 | El registro de profesional fallaba con 422: el formulario de dos pasos enviaba solo los datos del segundo paso | Alta | Frontend #26 |
| E‑02 | "Olvidé mi contraseña" revelaba qué correos tienen cuenta (respondía distinto si no existía) | Alta | Backend #76 |
| E‑03 | Eliminar la cuenta no borraba los datos personales ni los archivos (incumplía D12 y la Ley 1581) | Alta | Backend #77 |
| E‑04 | Al eliminar una cuenta, la otra parte veía la reserva con el cliente o profesional vacío | Alta | Backend #77 |
| E‑05 | Cualquier usuario autenticado podía consultar el perfil de cualquier cliente | Media | Backend #78 |
| E‑06 | Un reporte se podía asociar a una reserva ajena | Media | Backend #78 |
| E‑07 | El enlace del correo de recuperación usaba `env()` fuera de la configuración: con la configuración en caché apuntaba a `localhost` | Media | Backend #76 |
| E‑08 | La pantalla de nueva reserva era más ancha que la pantalla en todos los tamaños (hasta 1066 px en un celular de 390) | Media | Frontend #29 |
| E‑09 | En celular, la barra superior mostraba cortados "Iniciar sesión" y "Registrarse" y escondía el botón de menú. Con sesión, desbordaba en tablet y dejaba el buscador en 62 px | Media | Frontend #29 |
| E‑10 | Una prueba automatizada fallaba todas las noches entre las 10 p. m. y las 2 a. m. (dependía de la hora real) | Baja | Backend #76 |

**Abiertos:**

| ID | Hallazgo | Severidad | Detalle |
|----|----------|-----------|---------|
| E‑11 | **Los paneles de inicio y la página principal muestran datos de maqueta** | Alta | Ver el detalle debajo de la tabla |
| E‑12 | HU044 incompleta: el perfil del cliente no muestra los comentarios que recibió | Media | `ClientProfileResource` no incluye las reseñas |
| E‑13 | Falta la política de tratamiento de datos y su aceptación al registrarse (Ley 1581) | Media | El login dice "Al continuar aceptas… la Política de Privacidad" (figura 6), pero esa política no existe. Acción pendiente en [07](07-seguridad-y-privacidad.md) |
| E‑14 | Aviso de seguridad alto en `zod` < 4.6.6: consumo de memoria al validar colecciones enormes | Baja | Sin arreglo en zod 3, y `@vee-validate/zod` aún no admite zod 4. En este proyecto zod solo valida formularios en el navegador del propio usuario, así que el riesgo real es bajo |
| E‑15 | HU040 parcial: la distancia no aparece en el perfil del profesional | Baja | `GET /professionals/{id}` no recibe coordenadas |
| E‑16 | HU045 parcial: la alerta no enlaza al perfil del usuario | Baja | El administrador debe buscarlo en "Usuarios" |
| E‑17 | HU030 parcial: "disponible ahora" no se refresca solo mientras la lista está abierta | Baja | Se calcula al buscar |
| E‑18 | HU013 parcial: no hay comparación automática selfie-documento | Baja | Decisión D10: no existe un proveedor público. El administrador compara a mano |
| E‑19 | La página de verificación usa el mismo texto para clientes y profesionales: al cliente le habla de "sello de profesional verificado" y de "aceptar servicios" | Baja | Figura 16 |
| E‑20 | Los selectores de filtro de las bandejas del administrador empiezan vacíos, sin una opción "Todos" visible | Baja | Figuras 26 y 27 |
| E‑21 | Los datos de demostración (`DemoSeeder`) generan descripciones en latín de relleno y nombres y correos en inglés (figura 25) | Baja | Para las capturas se reemplazaron los textos del profesional principal por unos realistas |
| E‑22 | Faltan límites del lado del servidor: los tokens no vencen (`sanctum.expiration`), el chat no limita la frecuencia de mensajes y CORS acepta cualquier origen | Media | Ver [07](07-seguridad-y-privacidad.md) |
| E‑23 | Sin prueba automatizada del cierre por inactividad (HU050) ni de las vistas del administrador; sin pruebas de extremo a extremo en la CI ni medición de cobertura | Media | Se verificaron a mano y con la batería funcional de este informe |

**Detalle de E-11.** El panel del profesional muestra cifras fijas en el código:

- "8 citas hoy", "$4.850.000 de ingresos" y "4.9/5 con 127 opiniones";
- una agenda del día con clientes inventados en barrios de Bogotá;
- "Zona norte" y "74 % de la meta".

El panel del cliente tiene un texto que dice "páginas pendientes de desarrollo" y profesionales recomendados fijos. La página de inicio muestra "4.9/5, más de 2.000 reseñas" y profesionales de ejemplo. Las solicitudes pendientes y los servicios en curso de los paneles sí son reales. Al ser cifras inventadas, quien las vea puede creer que son ciertas, y eso le resta confianza a la plataforma.

![Panel del profesional con datos de maqueta](img/profesional-inicio.jpg)
*Figura 28. Panel del profesional: "8 citas listas para hoy", "Zona norte", "12 min" y "74 %" son valores fijos en el código (E-11).*

![Panel del cliente con texto de maqueta](img/cliente-inicio.jpg)
*Figura 29. Panel del cliente: el texto menciona "páginas pendientes de desarrollo" (E-11).*

### 3.4 Análisis de fortalezas y debilidades

**Fortalezas:**

- **Cobertura funcional completa.** Las 49 historias están implementadas: 44 completas y 5 con un criterio menor por cerrar. La batería funcional pasó entera.
- **Reglas de negocio sólidas y centralizadas.** La agenda calcula la disponibilidad real, impide cruces con bloqueo de fila en la transacción y lleva un historial auditable. La máquina de estados no admite transiciones inválidas.
- **Seguridad y privacidad desde el diseño.** Hay permisos por rol y por recurso, documentos privados con enlaces que vencen y un límite de intentos. La recuperación no revela cuentas y la eliminación anonimiza. Varias de estas protecciones se comprobaron con casos negativos.
- **Tiempo real bien integrado.** El chat, el estado en línea, las notificaciones y la agenda se actualizan solos.
- **Calidad verificable.** El proyecto tiene:
  - 378 pruebas automatizadas;
  - CI obligatoria en los dos repositorios;
  - contrato de API documentado y OpenAPI;
  - trazabilidad de cada historia a su código y sus pruebas;
  - decisiones de arquitectura registradas.
- **Interfaz cuidada y adaptable.** Usa un sistema de diseño propio con validación en el navegador. Después de las correcciones, las 29 pantallas funcionan en celular, tablet y escritorio.

**Debilidades:**

- **Datos de maqueta en los paneles y en la página de inicio (E-11).** Es lo más visible para un usuario nuevo.
- **Pruebas en el navegador manuales.** Las pruebas de la interfaz completa no están en la CI, y la cobertura de código no se mide. Los dos errores de pantalla de la revisión (E-01 y E-08) solo se detectaron usando la aplicación.
- **Pendientes de producción:** política de datos, CORS, vencimiento de tokens y límite del chat (E-13, E-22).
- **Desarrollo desigual entre módulos.** Algunas pantallas tienen textos que no se adaptan al rol (E-19) o criterios menores sin cerrar (E-12, E-15, E-16).
- **Sin pagos en línea ni consulta automática de antecedentes.** Ambos están fuera del alcance por decisión, pero limitan el modelo de negocio.

### 3.5 Propuestas de mejora

| # | Propuesta | Resuelve | Esfuerzo | Prioridad |
|---|-----------|----------|----------|-----------|
| 1 | Conectar los paneles y la página de inicio a datos reales: citas del día, ingresos del mes, solicitudes pendientes, calificación, profesionales cercanos y categorías del catálogo. Lo que no tenga dato real se quita | E-11 | Medio | Alta |
| 2 | Publicar la política de tratamiento de datos y pedir su aceptación al registrarse (campo `privacy_accepted_at` en una migración nueva) | E-13 | Bajo | Alta |
| 3 | Antes de desplegar: limitar CORS al dominio del frontend, hacer vencer los tokens y limitar la frecuencia de mensajes del chat | E-22 | Bajo | Alta |
| 4 | Completar los criterios menores: comentarios en el perfil del cliente, distancia en el perfil del profesional, enlace de la alerta al usuario y textos de verificación según el rol | E-12, E-15, E-16, E-19 | Bajo | Media |
| 5 | Llevar la batería funcional de este informe a pruebas de extremo a extremo en la CI (por ejemplo, Playwright) y medir la cobertura | E-23 | Medio | Media |
| 6 | Migrar a zod 4 cuando `@vee-validate/zod` lo admita, y mantener la auditoría de dependencias en cada PR | E-14 | Bajo | Media |
| 7 | Datos de demostración realistas en el `DemoSeeder` y opción "Todos" en los filtros del administrador | E-20, E-21 | Bajo | Baja |
| 8 | Hacia una versión 2: pagos en línea con una pasarela local, integración con un proveedor de verificación de identidad y aplicación móvil | Alcance | Alto | Futuro |

### 3.6 Conclusiones sobre el estado actual del software

**Maestros a un clic cumple su objetivo.** Un cliente de Medellín puede encontrar un profesional verificado, reservar una hora que de verdad está libre, coordinar por chat, recibir el servicio y calificarlo, con la misma confianza del lado del profesional. Las 49 historias de usuario están implementadas, y los 44 casos de la batería funcional se aprobaron sobre la aplicación en marcha. Las pruebas automatizadas (220 de backend y 158 de frontend) también pasan.

**La revisión final sirvió.** Encontró y corrigió 10 errores, 4 de severidad alta:
- un registro de profesional que no funcionaba;
- una fuga de información en la recuperación de contraseña;
- una eliminación de cuenta que no protegía los datos personales;
- reservas que quedaban sin contraparte al eliminar una cuenta.

Ninguno se veía en las pruebas automatizadas. Los encontró la validación funcional sobre la aplicación real, y por eso se propone llevar esa batería a la integración continua.

**Está listo para presentar, con trabajo pendiente antes de producción.** El software está en un estado estable y presentable para la entrega académica. Para ponerlo en producción con usuarios reales falta, sobre todo:

- quitar los datos de maqueta de los paneles (E-11);
- completar la parte legal de protección de datos (E-13);
- cerrar los límites del servidor (E-22).

Las tres mejoras son de esfuerzo bajo o medio y están priorizadas en la sección 3.5.
