# Requisitos

Requisitos funcionales (historias de usuario) y no funcionales del sistema. La fuente de verdad del estado de cada historia es su issue en GitHub; este documento muestra el estado al momento de generarlo.

## 1. Actores

| Actor | Descripción |
|-------|-------------|
| Visitante | Persona sin sesión: explora la búsqueda y los perfiles públicos |
| Cliente | Busca profesionales, reserva servicios, chatea y califica. Debe estar verificado para reservar |
| Profesional | Publica servicios, gestiona su agenda, responde solicitudes, presta el servicio y califica al cliente |
| Administrador | Verifica documentos, atiende reportes y alertas, y bloquea usuarios |
| Sistema | Calcula disponibilidad, emite notificaciones y eventos en tiempo real, y genera alertas automáticas |

## 2. Requisitos funcionales

Las 49 historias de usuario del proyecto (no existe la HU015: la numeración del documento original la omite). Estado a la fecha de este documento: **27 hechas** y **22 pendientes**. Cada una es un issue en GitHub, donde se marcan sus criterios.

### 2.1 Resumen

| HU | Nombre | Módulo | Responsable | Estado |
|----|--------|--------|-------------|--------|
| [HU001](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/2) | Inicio de sesión | auth | LordCasta | ✅ Hecha |
| [HU002](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/3) | Búsqueda por geolocalización | búsqueda | barenas12 | ⏳ Pendiente |
| [HU003](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/4) | Gestión de disponibilidad y agenda | agenda | LordCasta | ✅ Hecha |
| [HU004](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/5) | Solicitud y aceptación de servicio | reservas | LordCasta | ✅ Hecha |
| [HU005](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/6) | Sistema de calificación recíproca | calificaciones | barenas12 | ⏳ Pendiente |
| [HU006](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/7) | Registro de cliente | auth | LordCasta | ✅ Hecha |
| [HU007](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/8) | Registro de profesional | auth | LordCasta | ✅ Hecha |
| [HU008](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/9) | Cargue de documentos | verificación-admin | barenas12 | ⏳ Pendiente |
| [HU009](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/10) | Edición de perfil profesional | perfil-profesional | LordCasta | ✅ Hecha |
| [HU010](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/11) | Filtros y ordenamiento de profesionales | búsqueda | barenas12 | ⏳ Pendiente |
| [HU011](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/12) | Chat y confirmación de detalles del servicio | chat-notificaciones | LordCasta | ✅ Hecha |
| [HU012](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/13) | Cancelación y reprogramación de reserva | reservas | LordCasta | ✅ Hecha |
| [HU013](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/14) | Validación de identidad y antecedentes | verificación-admin | barenas12 | ⏳ Pendiente |
| [HU014](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/15) | Revisión y aprobación de documentos del profesional | verificación-admin | barenas12 | ⏳ Pendiente |
| [HU016](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/16) | Historial de servicios del cliente | reservas | LordCasta | ✅ Hecha |
| [HU017](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/17) | Historial de servicios del profesional | reservas | LordCasta | ✅ Hecha |
| [HU018](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/18) | Notificaciones del sistema | chat-notificaciones | LordCasta | ✅ Hecha |
| [HU019](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/19) | Cambio de contraseña | cuenta | barenas12 | ⏳ Pendiente |
| [HU020](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/20) | Recuperación de contraseña | cuenta | barenas12 | ⏳ Pendiente |
| [HU021](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/21) | Cerrar sesión | auth | LordCasta | ✅ Hecha |
| [HU022](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/22) | Registro de ubicación manual | cuenta | barenas12 | ⏳ Pendiente |
| [HU023](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/23) | Eliminación de cuenta | cuenta | barenas12 | ⏳ Pendiente |
| [HU024](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/24) | Reporte de usuarios | calificaciones | barenas12 | ⏳ Pendiente |
| [HU025](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/25) | Visualización de disponibilidad del profesional | agenda | LordCasta | ✅ Hecha |
| [HU026](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/26) | Estado del servicio | reservas | LordCasta | ✅ Hecha |
| [HU027](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/27) | Confirmación de inicio de servicio | reservas | LordCasta | ✅ Hecha |
| [HU028](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/28) | Confirmación de finalización de servicio | reservas | LordCasta | ✅ Hecha |
| [HU029](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/29) | Visualización de comentarios | calificaciones | barenas12 | ⏳ Pendiente |
| [HU030](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/30) | Filtro por disponibilidad inmediata | búsqueda | barenas12 | ⏳ Pendiente |
| [HU031](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/31) | Edición de perfil del cliente | cuenta | barenas12 | ⏳ Pendiente |
| [HU032](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/32) | Subida de foto de perfil | cuenta | barenas12 | ⏳ Pendiente |
| [HU033](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/33) | Visualización de servicios por categoría | búsqueda | barenas12 | ⏳ Pendiente |
| [HU034](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/34) | Guardar profesionales favoritos | favoritos | LordCasta | ✅ Hecha |
| [HU035](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/35) | Visualización de favoritos | favoritos | LordCasta | ✅ Hecha |
| [HU036](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/36) | Confirmación de reserva por el cliente | reservas | LordCasta | ✅ Hecha |
| [HU037](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/37) | Visualización de ingresos del profesional | perfil-profesional | LordCasta | ✅ Hecha |
| [HU038](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/38) | Bloqueo de usuarios problemáticos | verificación-admin | barenas12 | ⏳ Pendiente |
| [HU039](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/39) | Búsqueda por nombre | búsqueda | barenas12 | ⏳ Pendiente |
| [HU040](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/40) | Visualización de distancia | búsqueda | barenas12 | ⏳ Pendiente |
| [HU041](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/41) | Indicador de estado en línea | chat-notificaciones | LordCasta | ✅ Hecha |
| [HU042](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/42) | Adjuntar archivos en el chat | chat-notificaciones | LordCasta | ✅ Hecha |
| [HU043](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/43) | Eliminación de mensajes | chat-notificaciones | LordCasta | ✅ Hecha |
| [HU044](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/44) | Visualización de perfil del cliente | calificaciones | barenas12 | ⏳ Pendiente |
| [HU045](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/45) | Alerta por baja calificación | calificaciones | barenas12 | ⏳ Pendiente |
| [HU046](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/46) | Ordenamiento por precio | búsqueda | LordCasta | ✅ Hecha |
| [HU047](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/47) | Visualización de tiempo estimado | perfil-profesional | LordCasta | ✅ Hecha |
| [HU048](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/48) | Validación de datos obligatorios | auth | LordCasta | ✅ Hecha |
| [HU049](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/49) | Visualización de servicios en proceso | reservas | LordCasta | ✅ Hecha |
| [HU050](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/50) | Cierre de sesión por inactividad | cuenta | barenas12 | ⏳ Pendiente |

### 2.2 Detalle

#### HU001 · Inicio de sesión

**Yo como cliente**, quiero iniciar sesión en el sistema, para validar los datos de mi cuenta

Criterios de aceptación:

1. El sistema valida las credenciales
2. El sistema muestra error si son incorrectas
3. Redirige al panel principal

Estado: ✅ Hecha

#### HU002 · Búsqueda por geolocalización

**Yo como cliente**, quiero buscar profesionales en mi comuna mediante un mapa o listado, para contratar un servicio rápido y eficiente cerca de mi ubicación.

Criterios de aceptación:

1. El sistema solicita permisos de ubicación al usuario.
2. El buscador filtra profesionales activos dentro del radio de acción configurado.
3. Se despliega una lista ordenada por proximidad (km).
4. Si no hay profesionales en la comuna, el sistema sugiere los más cercanos en comunas aledañas.

Estado: ⏳ Pendiente

#### HU003 · Gestión de disponibilidad y agenda

**Yo como profesional**, quiero configurar mis días y horas de trabajo en un calendario, para evitar que los clientes reserven en momentos donde no tengo disponibilidad.

Criterios de aceptación:

1. El profesional puede marcar bloques de horas como "ocupado" o "disponible".
2. El sistema bloquea automáticamente un tiempo adicional (configurable) después de cada servicio para descanso.
3. Los cambios en la agenda se reflejan en tiempo real para los clientes.
4. El calendario impide que se solapen dos servicios distintos.

Estado: ✅ Hecha

#### HU004 · Solicitud y aceptación de servicio

**Yo como cliente**, quiero solicitar una reserva seleccionando una hora disponible del profesional, para concretar la contratación sin necesidad de intermediarios.

Criterios de aceptación:

1. El cliente visualiza la disponibilidad real del profesional.
2. Al enviar la solicitud, el profesional recibe una alerta en su panel.
3. El profesional tiene la opción de "Aceptar" o "Rechazar" la solicitud.
4. El estado de la reserva cambia a "Confirmado" solo si el profesional acepta.

Estado: ✅ Hecha

#### HU005 · Sistema de calificación recíproca

**Yo como usuario (cliente o profesional)**, quiero calificar mi experiencia con la otra parte tras finalizar el servicio, para mantener la reputación y calidad dentro de la plataforma.

Criterios de aceptación:

1. La opción de calificar solo se habilita una vez que el servicio ha sido marcado como "Completado".
2. El sistema permite una calificación de 1 a 5 estrellas.
3. Se permite dejar un comentario opcional sobre el servicio o la atención.
4. El promedio de estrellas se actualiza automáticamente en el perfil del usuario calificado.

Estado: ⏳ Pendiente

#### HU006 · Registro de cliente

**Yo como cliente**, quiero registrarme en la plataforma con mi correo y contraseña elegida., para acceder al sistema.

Criterios de aceptación:

1. El correo debe ser válido
2. Se debe validar la existencia del correo
3. La contraseña debe ser mayor a 5 caracteres
4. Me debe permitir iniciar sesión inmediatamente después de crear el usuario

Estado: ✅ Hecha

#### HU007 · Registro de profesional

**Yo como profesional.**, quiero registrarme en la plataforma con mi correo y contraseña elegida., para acceder al sistema y poder cargar mis documentos.

Criterios de aceptación:

1. El correo debe ser válido
2. Se debe validar la existencia del correo
3. La contraseña debe ser mayor a 5 caracteres
4. Me debe permitir iniciar sesión inmediatamente después de crear el usuario

Estado: ✅ Hecha

#### HU008 · Cargue de documentos

**Yo como profesional.**, quiero después de registrarme, subir mis documentos como certificaciones, fotos, diplomas requeridos., para poder que validen mis documentos y ser profesional verificado.

Criterios de aceptación:

1. El sistema permite cargar múltiples archivos (imágenes y PDF) por categoría (certificados, documento de identidad, evidencias de trabajos).
2. El sistema valida formato y tamaño máximo de archivo antes de subirlo.
3. El sistema solicita como mínimo: documento de identidad, selfie y al menos un certificado o evidencia de trabajo.
4. Al finalizar el cargue, el estado del perfil queda en "Pendiente de verificación" y no puede ofrecer servicios hasta ser aprobado.
5. El profesional puede ver el estado de revisión (Pendiente, Aprobado, Rechazado) y el motivo si es rechazado.
6. El profesional puede reemplazar documentos rechazados y reenviar a verificación.

Estado: ⏳ Pendiente

#### HU009 · Edición de perfil profesional

**Yo como profesional**, quiero editar mi perfil (descripción, especialidades, tarifa por hora, ubicación, agenda y portafolio), para mantener mi información actualizada y atraer más clientes.

Criterios de aceptación:

1. El sistema permite actualizar campos básicos del perfil sin perder información previamente aprobada.
2. El sistema valida que la tarifa por hora sea un valor numérico mayor a 0.
3. El sistema permite agregar o quitar especialidades dentro del catálogo de servicios.
4. Los cambios de ubicación actualizan la zona de cobertura para búsquedas por geolocalización.
5. Las fotos del portafolio se muestran en una galería dentro del perfil.

Estado: ✅ Hecha

#### HU010 · Filtros y ordenamiento de profesionales

**Yo como cliente**, quiero filtrar y ordenar profesionales por servicio, precio, calificación y distancia, para encontrar la mejor opción según mis necesidades.

Criterios de aceptación:

1. El cliente puede filtrar por categoría de servicio y subcategoría.
2. El cliente puede definir un rango de precio por hora.
3. El cliente puede ordenar por calificación (descendente) y por distancia (ascendente).
4. Los resultados muestran información clave: nombre, calificación promedio, precio, distancia y disponibilidad próxima.
5. Al aplicar filtros, el listado y el mapa se actualizan sin recargar toda la página.

Estado: ⏳ Pendiente

#### HU011 · Chat y confirmación de detalles del servicio

**Yo como cliente**, quiero comunicarme con el profesional dentro de la plataforma antes del servicio, para aclarar detalles del trabajo, materiales y condiciones.

Criterios de aceptación:

1. El sistema habilita el chat solo cuando exista una solicitud enviada o una reserva confirmada.
2. El chat guarda el historial de mensajes por solicitud o reserva.
3. El cliente puede enviar fotos o descripciones del problema (con validación de tamaño y formato).
4. El profesional puede confirmar por el chat detalles como materiales requeridos y tiempo estimado.
5. El sistema envía notificaciones cuando llega un mensaje nuevo.

Estado: ✅ Hecha

#### HU012 · Cancelación y reprogramación de reserva

**Yo como cliente o profesional**, quiero cancelar o reprogramar una reserva, para gestionar cambios de disponibilidad o imprevistos.

Criterios de aceptación:

1. El sistema permite cancelar antes de la hora programada con un motivo obligatorio.
2. Al cancelar, el sistema libera automáticamente el bloque de agenda correspondiente.
3. Para reprogramar, el usuario selecciona un nuevo horario disponible del profesional.
4. El estado de la reserva cambia a "Cancelado" o "Reprogramado" y queda registro del motivo y la fecha anterior.
5. Ambas partes reciben notificación de la cancelación o reprogramación.

Estado: ✅ Hecha

#### HU013 · Validación de identidad y antecedentes

**Yo como usuario (cliente o profesional)**, quiero completar un proceso de verificación de identidad y antecedentes, para aumentar la seguridad y confianza en la plataforma.

Criterios de aceptación:

1. El sistema solicita documento de identidad y selfie para validación.
2. El sistema valida coincidencia entre selfie y foto del documento mediante el proveedor de verificación definido.
3. El sistema registra el resultado de la verificación (Aprobado/Rechazado/Pendiente) y la fecha.
4. Si la verificación es rechazada, el usuario recibe el motivo y puede reenviar la documentación.
5. Mientras el usuario esté "Pendiente" o "Rechazado", el sistema limita acciones críticas (publicar servicios, confirmar reservas) según el rol.

Estado: ⏳ Pendiente

#### HU014 · Revisión y aprobación de documentos del profesional

**Yo como gestor de la aplicación (administrador/validador)**, quiero revisar las selfies y documentos cargados por el profesional, para aprobar o rechazar la verificación y asegurar la autenticidad de la información.

Criterios de aceptación:

1. El sistema muestra una bandeja de solicitudes "Pendiente de verificación" con filtros por fecha, tipo de documento y estado.
2. Al abrir una solicitud, el gestor visualiza: selfie, documento de identidad, certificados y evidencias; con opción de zoom/descarga.
3. El gestor puede marcar cada documento como "Aprobado" o "Rechazado" y agregar observaciones obligatorias cuando rechaza.
4. El gestor puede aprobar la solicitud solo si los documentos mínimos requeridos están completos y aprobados.
5. Al aprobar, el estado del profesional cambia a "Verificado" y se habilita la publicación/aceptación de servicios.
6. Al rechazar, el sistema notifica al profesional el motivo y mantiene el estado en "Rechazado" hasta que reenvíe la información.
7. El sistema registra auditoría de la revisión: quién revisó, fecha/hora y decisión tomada.

Estado: ⏳ Pendiente

#### HU016 · Historial de servicios del cliente

**Yo como cliente**, quiero ver mi historial de servicios solicitados, para llevar control de lo que he contratado.

Criterios de aceptación:

1. El sistema muestra lista de servicios realizados y pendientes.
2. Se visualiza fecha, profesional y estado.
3. Se puede acceder al detalle de cada servicio.
4. Los servicios cancelados también aparecen en el historial.

Estado: ✅ Hecha

#### HU017 · Historial de servicios del profesional

**Yo como profesional**, quiero ver mi historial de trabajos realizados, para llevar control de mis servicios.

Criterios de aceptación:

1. Se muestran todos los servicios realizados y cancelados.
2. Se visualiza fecha, cliente y estado.
3. Se puede ver el detalle de cada servicio.
4. Se muestra el total de servicios realizados.

Estado: ✅ Hecha

#### HU018 · Notificaciones del sistema

**Yo como usuario**, quiero recibir notificaciones, para estar informado sobre solicitudes y cambios.

Criterios de aceptación:

1. El sistema envía notificación cuando hay una nueva solicitud.
2. Notifica cuando un servicio es aceptado o rechazado.
3. Notifica mensajes nuevos en el chat.
4. Las notificaciones se pueden visualizar dentro del sistema.

Estado: ✅ Hecha

#### HU019 · Cambio de contraseña

**Yo como usuario**, quiero cambiar mi contraseña, para mantener mi cuenta segura.

Criterios de aceptación:

1. Debo ingresar mi contraseña actual.
2. La nueva contraseña debe cumplir requisitos mínimos.
3. El sistema confirma el cambio exitoso.
4. Si la contraseña actual es incorrecta, muestra error.

Estado: ⏳ Pendiente

#### HU020 · Recuperación de contraseña

**Yo como usuario**, quiero recuperar mi contraseña, para poder acceder si la olvido.

Criterios de aceptación:

1. El usuario ingresa su correo.
2. El sistema envía un enlace de recuperación.
3. El enlace permite crear una nueva contraseña.
4. El enlace tiene un tiempo de expiración.

Estado: ⏳ Pendiente

#### HU021 · Cerrar sesión

**Yo como usuario**, quiero cerrar sesión, para proteger mi cuenta cuando termine de usarla.

Criterios de aceptación:

1. Existe un botón visible para cerrar sesión.
2. Al cerrar sesión se redirige al inicio.
3. Se elimina la sesión activa.

Estado: ✅ Hecha

#### HU022 · Registro de ubicación manual

**Yo como usuario**, quiero ingresar mi ubicación manualmente, para usar la plataforma si no quiero activar GPS.

Criterios de aceptación:

1. El usuario puede escribir su dirección o comuna.
2. El sistema valida la ubicación ingresada.
3. Se usa esa ubicación para búsquedas.
4. Se puede actualizar en cualquier momento.

Estado: ⏳ Pendiente

#### HU023 · Eliminación de cuenta

**Yo como usuario**, quiero eliminar mi cuenta, para dejar de usar la plataforma.

Criterios de aceptación:

1. El sistema solicita confirmación antes de eliminar.
2. Se valida que no tenga servicios pendientes.
3. La cuenta se elimina del sistema.
4. Se muestra mensaje de confirmación.

Estado: ⏳ Pendiente

#### HU024 · Reporte de usuarios

**Yo como usuario**, quiero reportar a otro usuario, para informar comportamientos indebidos.

Criterios de aceptación:

1. Se puede reportar desde el perfil o servicio.
2. Se debe ingresar un motivo.
3. El reporte queda registrado en el sistema.
4. El administrador puede revisar los reportes.

Estado: ⏳ Pendiente

#### HU025 · Visualización de disponibilidad del profesional

**Yo como cliente**, quiero ver los horarios disponibles del profesional, para elegir el mejor momento para mi servicio.

Criterios de aceptación:

1. Se muestra un calendario con horarios disponibles.
2. No se muestran horarios ocupados.
3. Se actualiza en tiempo real.
4. Permite seleccionar una fecha y hora fácilmente.

Estado: ✅ Hecha

#### HU026 · Estado del servicio

**Yo como usuario**, quiero ver el estado del servicio, para saber en qué etapa se encuentra.

Criterios de aceptación:

1. El servicio puede estar en estados como pendiente, confirmado, en proceso o completado.
2. El estado se actualiza automáticamente.
3. El usuario puede ver el estado en su historial.
4. Se notifica cuando cambia el estado.

Estado: ✅ Hecha

#### HU027 · Confirmación de inicio de servicio

**Yo como profesional**, quiero marcar el inicio del servicio, para indicar que ya comencé el trabajo.

Criterios de aceptación:

1. El profesional puede iniciar el servicio desde la reserva.
2. El estado cambia a "En proceso".
3. El cliente recibe notificación.
4. Solo se puede iniciar en la hora programada o después.

Estado: ✅ Hecha

#### HU028 · Confirmación de finalización de servicio

**Yo como profesional**, quiero marcar el servicio como finalizado, para cerrar el trabajo realizado.

Criterios de aceptación:

1. El profesional puede finalizar el servicio.
2. El estado cambia a "Completado".
3. Se habilita la opción de calificación.
4. El cliente recibe notificación.

Estado: ✅ Hecha

#### HU029 · Visualización de comentarios

**Yo como usuario**, quiero ver los comentarios de otros usuarios, para tomar mejores decisiones.

Criterios de aceptación:

1. Se muestran comentarios en el perfil.
2. Cada comentario incluye calificación y texto.
3. Se ordenan por fecha o relevancia.
4. Solo usuarios que usaron el servicio pueden comentar.

Estado: ⏳ Pendiente

#### HU030 · Filtro por disponibilidad inmediata

**Yo como cliente**, quiero filtrar profesionales disponibles de inmediato, para encontrar ayuda rápida.

Criterios de aceptación:

1. Existe un filtro de “disponible ahora”.
2. Solo muestra profesionales con horario libre cercano.
3. Se actualiza en tiempo real.
4. Se combina con otros filtros (precio, calificación, etc.).

Estado: ⏳ Pendiente

#### HU031 · Edición de perfil del cliente

**Yo como cliente**, quiero editar mi perfil, para mantener mis datos actualizados.

Criterios de aceptación:

1. Puedo cambiar nombre, foto y datos básicos.
2. El sistema guarda los cambios correctamente.
3. Se validan los campos obligatorios.
4. Los cambios se reflejan inmediatamente.

Estado: ⏳ Pendiente

#### HU032 · Subida de foto de perfil

**Yo como usuario**, quiero subir una foto de perfil, para que otros me identifiquen fácilmente.

Criterios de aceptación:

1. Se permite subir imágenes.
2. El sistema valida formato y tamaño.
3. La foto se actualiza en el perfil.
4. Se puede cambiar en cualquier momento.

Estado: ⏳ Pendiente

#### HU033 · Visualización de servicios por categoría

**Yo como cliente**, quiero ver servicios por categoría, para encontrar más fácil lo que necesito.

Criterios de aceptación:

1. Se muestran categorías disponibles.
2. Al seleccionar una, se listan los profesionales.
3. Se puede navegar entre categorías.
4. Se muestran resultados relacionados.

Estado: ⏳ Pendiente

#### HU034 · Guardar profesionales favoritos

**Yo como cliente**, quiero guardar profesionales como favoritos, para encontrarlos rápidamente después.

Criterios de aceptación:

1. Existe opción de agregar a favoritos.
2. Se pueden ver en una lista de favoritos.
3. Se pueden eliminar de favoritos.
4. Se mantienen guardados en la cuenta.

Estado: ✅ Hecha

#### HU035 · Visualización de favoritos

**Yo como cliente**, quiero ver mis profesionales favoritos, para acceder rápido a ellos.

Criterios de aceptación:

1. Se muestra lista de favoritos.
2. Se puede acceder al perfil de cada uno.
3. Se puede eliminar de la lista.
4. La lista se guarda correctamente.

Estado: ✅ Hecha

#### HU036 · Confirmación de reserva por el cliente

**Yo como cliente**, quiero confirmar una reserva, para asegurar que el servicio se realizará.

Criterios de aceptación:

1. El cliente puede confirmar después de aceptación.
2. El sistema muestra el estado confirmado.
3. Se notifica al profesional.
4. Se guarda el registro.

Estado: ✅ Hecha

#### HU037 · Visualización de ingresos del profesional

**Yo como profesional**, quiero ver mis ingresos generados, para llevar control de lo que gano.

Criterios de aceptación:

1. Se muestra total de ingresos.
2. Se puede ver por fechas.
3. Se listan servicios asociados.
4. Los datos se actualizan automáticamente.

Estado: ✅ Hecha

#### HU038 · Bloqueo de usuarios problemáticos

**Yo como administrador**, quiero bloquear usuarios, para evitar comportamientos indebidos.

Criterios de aceptación:

1. El administrador puede bloquear usuarios.
2. El usuario bloqueado no puede acceder.
3. Se guarda el motivo del bloqueo.
4. Se puede desbloquear posteriormente.

Estado: ⏳ Pendiente

#### HU039 · Búsqueda por nombre

**Yo como cliente**, quiero buscar profesionales por nombre, para encontrarlos directamente.

Criterios de aceptación:

1. Existe barra de búsqueda.
2. Se muestran resultados coincidentes.
3. Se puede seleccionar el perfil.
4. Funciona en tiempo real.

Estado: ⏳ Pendiente

#### HU040 · Visualización de distancia

**Yo como cliente**, quiero ver la distancia del profesional, para saber qué tan cerca está.

Criterios de aceptación:

1. Se muestra distancia en km.
2. Se actualiza según ubicación.
3. Aparece en listados y perfil.
4. Se usa para ordenar resultados.

Estado: ⏳ Pendiente

#### HU041 · Indicador de estado en línea

**Yo como usuario**, quiero ver si el otro usuario está en línea, para saber si puedo comunicarme rápido.

Criterios de aceptación:

1. Se muestra indicador de en línea o desconectado.
2. Se actualiza automáticamente.
3. Aparece en el chat.
4. Es visible para ambas partes.

Estado: ✅ Hecha

#### HU042 · Adjuntar archivos en el chat

**Yo como usuario**, quiero enviar archivos en el chat, para compartir información del servicio.

Criterios de aceptación:

1. Se permite enviar imágenes.
2. Se valida tamaño y formato.
3. El archivo se visualiza en el chat.
4. Se guarda en el historial.

Estado: ✅ Hecha

#### HU043 · Eliminación de mensajes

**Yo como usuario**, quiero eliminar mensajes enviados, para corregir errores.

Criterios de aceptación:

1. Se puede eliminar un mensaje.
2. El mensaje desaparece del chat.
3. Se actualiza para ambas partes.
4. Se mantiene la conversación.

Estado: ✅ Hecha

#### HU044 · Visualización de perfil del cliente

**Yo como profesional**, quiero ver el perfil del cliente, para conocer con quién trabajaré.

Criterios de aceptación:

1. Se muestra nombre y foto.
2. Se muestra calificación del cliente.
3. Se pueden ver comentarios.
4. Se accede desde la solicitud.

Estado: ⏳ Pendiente

#### HU045 · Alerta por baja calificación

**Yo como administrador**, quiero recibir alertas de usuarios con baja calificación, para revisar posibles problemas.

Criterios de aceptación:

1. El sistema detecta calificación baja.
2. Se genera una alerta automática.
3. Se puede revisar el perfil.
4. Se guarda el registro.

Estado: ⏳ Pendiente

#### HU046 · Ordenamiento por precio

**Yo como cliente**, quiero ordenar por precio, para elegir según mi presupuesto.

Criterios de aceptación:

1. Se puede ordenar de menor a mayor.
2. También de mayor a menor.
3. Se actualiza el listado.
4. Funciona junto con otros filtros.

Estado: ✅ Hecha

#### HU047 · Visualización de tiempo estimado

**Yo como cliente**, quiero ver el tiempo estimado del servicio, para organizar mi tiempo.

Criterios de aceptación:

1. El profesional indica duración estimada.
2. Se muestra en la reserva.
3. El cliente puede visualizarlo antes de confirmar.
4. Se guarda con el servicio.

Estado: ✅ Hecha

#### HU048 · Validación de datos obligatorios

**Yo como usuario**, quiero que el sistema valide datos obligatorios, para evitar errores al registrar información.

Criterios de aceptación:

1. El sistema detecta campos vacíos.
2. Muestra mensajes de error.
3. No permite continuar si faltan datos.
4. Indica claramente qué falta.

Estado: ✅ Hecha

#### HU049 · Visualización de servicios en proceso

**Yo como usuario**, quiero ver mis servicios en proceso, para saber cuáles están activos.

Criterios de aceptación:

1. Se muestran servicios en curso.
2. Se visualiza estado actual.
3. Se puede acceder al detalle.
4. Se actualiza en tiempo real.

Estado: ✅ Hecha

#### HU050 · Cierre de sesión por inactividad

**Yo como usuario**, quiero que el sistema cierre mi sesión por inactividad, para proteger mi cuenta.

Criterios de aceptación:

1. El sistema detecta inactividad.
2. Cierra sesión automáticamente.
3. Muestra mensaje informativo.
4. Redirige al inicio de sesión.

Estado: ⏳ Pendiente

## 3. Requisitos no funcionales

Se formulan con el modelo de calidad **ISO/IEC 25010**. Cada uno tiene una meta medible, la forma en que el sistema la cumple y cómo se verifica. El estado indica si ya está cumplido, si es una meta que se mide en el despliegue o si tiene una acción pendiente.

### 3.1 Seguridad

| ID | Requisito | Cómo se cumple | Verificación | Estado |
|----|-----------|----------------|--------------|--------|
| RNF-SEG-01 | Solo usuarios autenticados acceden a sus datos | Autenticación por token (Laravel Sanctum) en todas las rutas privadas | Tests de 401 sin token en cada módulo | ✅ |
| RNF-SEG-02 | Cada usuario solo opera sobre sus recursos y según su rol | Middleware `role:` (tipo de cuenta) y Policies (dueño del recurso); los usuarios bloqueados no entran (`not_blocked`) | Tests de 403 por endpoint (otro usuario, otro rol) | ✅ |
| RNF-SEG-03 | Contraseñas protegidas | Hash bcrypt (cast `hashed`), mínimo 8 caracteres y confirmación | Tests de registro | ✅ |
| RNF-SEG-04 | Resistencia a fuerza bruta en el acceso | Máximo 10 intentos por minuto en registro e inicio de sesión (`throttle:10,1`) | Revisión de rutas; prueba manual | ✅ |
| RNF-SEG-05 | Toda entrada se valida en el servidor | FormRequest por endpoint con errores 422 por campo. El cliente valida además con Zod, sin reemplazar al servidor | Tests de validación en cada endpoint | ✅ |
| RNF-SEG-06 | Los documentos de identidad y los adjuntos del chat no son públicos | Disco privado y URL firmada que vence (60 min); sin firma responde 403 | ChatTest; prueba manual de URL sin firma | ✅ |
| RNF-SEG-07 | Tráfico cifrado | HTTPS (TLS) y WebSockets seguros (`wss`) en producción | Checklist de despliegue | 🎯 En despliegue |
| RNF-SEG-08 | La API solo acepta peticiones del frontend oficial | Publicar `config/cors.php` con el dominio del frontend en producción (hoy acepta cualquier origen, valor por defecto de Laravel) | Checklist de despliegue | ⚠️ Pendiente |
| RNF-SEG-09 | Las dependencias no tienen vulnerabilidades conocidas | Auditoría de dependencias (`composer audit`, `npm audit`) en cada PR | CI (job "Seguridad de dependencias") | ✅ |

### 3.2 Privacidad y protección de datos

| ID | Requisito | Cómo se cumple | Verificación | Estado |
|----|-----------|----------------|--------------|--------|
| RNF-PRI-01 | El perfil público no expone datos de contacto | `ProfessionalResource` excluye correo, teléfono, dirección y coordenadas | ProfessionalsTest | ✅ |
| RNF-PRI-02 | El chat solo lo ven las partes de la reserva | Policy `chat` (ni otros usuarios ni el administrador) | ChatTest | ✅ |
| RNF-PRI-03 | Autorización informada para el tratamiento de datos (Ley 1581) | Aceptación explícita de la política de tratamiento al registrarse | — | ⚠️ Pendiente (ver [07](07-seguridad-y-privacidad.md)) |
| RNF-PRI-04 | Derecho de supresión | Eliminación de cuenta con anonimización (HU023, decisión D12) | — | ⏳ En desarrollo |

### 3.3 Integridad y auditoría

| ID | Requisito | Cómo se cumple | Verificación | Estado |
|----|-----------|----------------|--------------|--------|
| RNF-INT-01 | Cada cambio de estado de una reserva queda registrado con autor, fecha y motivo | Tabla `booking_status_changes`; todo cambio pasa por `BookingService::transition()` | BookingHistoryTest, BookingsTest | ✅ |
| RNF-INT-02 | No se pueden reservar dos servicios a la misma hora | Validación de cruces con descanso y bloqueo de la fila del profesional dentro de la transacción (`lockForUpdate`) | BookingsTest, ProfessionalSlotsTest | ✅ |
| RNF-INT-03 | Los estados solo cambian por transiciones válidas | Máquina de estados en `BookingStatus`; una transición inválida responde 409 | Tests de 409 en cada acción | ✅ |
| RNF-INT-04 | Integridad referencial | Llaves foráneas en todas las relaciones; los servicios con reservas no se borran (se ocultan) | Migraciones; ProfessionalProfileTest | ✅ |
| RNF-INT-05 | El precio pactado no cambia después de reservar | Copia del precio en `bookings.agreed_price` | BookingsTest, ProfessionalProfileTest | ✅ |

### 3.4 Eficiencia de desempeño

| ID | Requisito | Cómo se cumple | Verificación | Estado |
|----|-----------|----------------|--------------|--------|
| RNF-REN-01 | El 95 % de las respuestas de la API en menos de 500 ms con la carga esperada del piloto | Listados paginados (15–20, máximo 50), índices en las columnas de búsqueda, carga anticipada de relaciones (`with`), promedio de calificación precalculado (D13) | Medición con el servidor de producción | 🎯 En despliegue |
| RNF-REN-02 | Eventos en tiempo real entregados en menos de 2 s | Laravel Reverb con cola; los eventos se emiten después de confirmar la transacción | Prueba manual con dos sesiones | ✅ (local) |
| RNF-REN-03 | Carga inicial del frontend ligera | Rutas cargadas bajo demanda (*lazy loading*) y build optimizado de Vite | Build en CI | ✅ |

### 3.5 Disponibilidad y fiabilidad

| ID | Requisito | Cómo se cumple | Verificación | Estado |
|----|-----------|----------------|--------------|--------|
| RNF-DIS-01 | Disponibilidad mensual ≥ 99 % durante el piloto | Procesos supervisados (Supervisor reinicia la cola y Reverb) y verificación de salud (`/up`) | Monitoreo externo | 🎯 En despliegue |
| RNF-DIS-02 | Recuperación ante pérdida de datos | Respaldo diario de la base y los archivos, con 7 días de retención; restauración documentada | Prueba de restauración ([06](06-despliegue-y-operacion.md)) | 🎯 En despliegue |
| RNF-DIS-03 | Un fallo del tiempo real no impide usar la app | Sin WebSocket la app sigue funcionando y se actualiza al recargar o al volver a la pestaña | Prueba manual con Reverb apagado | ✅ |

### 3.6 Usabilidad y accesibilidad

| ID | Requisito | Cómo se cumple | Verificación | Estado |
|----|-----------|----------------|--------------|--------|
| RNF-USA-01 | Interfaz usable en celular, tableta y computador | Diseño responsive con Tailwind; menú móvil | Revisión manual en tres anchos | ✅ |
| RNF-USA-02 | Mensajes claros y en español | Mensajes de validación y de error en español, con el nombre del campo | ApiErrorsTest y tests de cada formulario | ✅ |
| RNF-USA-03 | Toda vista con datos muestra cargando, error, vacío y contenido | Patrón obligatorio del frontend (`docs/arquitectura.md`) | Revisión en PR; tests de componentes | ✅ |
| RNF-USA-04 | Accesibilidad base (meta: WCAG 2.1 nivel AA) | HTML semántico, `aria-label` en controles de solo ícono, foco visible, contraste de la paleta, diálogos que se cierran con Esc | Revisión manual y auditoría Lighthouse | 🎯 Auditoría pendiente |
| RNF-USA-05 | Identidad visual consistente | Sistema de diseño con tokens; un verificador en CI rechaza colores fuera de la paleta | `npm run lint:tokens` | ✅ |

### 3.7 Mantenibilidad

| ID | Requisito | Cómo se cumple | Verificación | Estado |
|----|-----------|----------------|--------------|--------|
| RNF-MAN-01 | Código modular y por capas | Backend: Ruta → FormRequest → Policy → Service → Resource. Frontend: módulos autocontenidos con API pública | Revisión en PR; regla de lint de imports | ✅ |
| RNF-MAN-02 | Cada cambio pasa pruebas automáticas antes de unirse | CI obligatorio en `main`: tests, formato, lint, tipos y build | Protección de la rama | ✅ |
| RNF-MAN-03 | Reglas de calidad protegidas | Check **Guardas**: la configuración de calidad no se modifica sin aprobación explícita | CI | ✅ |
| RNF-MAN-04 | Contrato de API documentado y sincronizado | `docs/api/endpoints.md` se actualiza en el mismo PR; especificación OpenAPI generada del código | Revisión en PR | ✅ |

### 3.8 Compatibilidad y portabilidad

| ID | Requisito | Cómo se cumple | Verificación | Estado |
|----|-----------|----------------|--------------|--------|
| RNF-COM-01 | Navegadores modernos | Últimas dos versiones de Chrome, Edge, Firefox y Safari | Revisión manual | ✅ |
| RNF-COM-02 | Versiones de PHP soportadas | PHP 8.2 a 8.5 | CI corre las pruebas en 8.2 y 8.5 | ✅ |
| RNF-COM-03 | Base de datos | MySQL 8 en desarrollo y producción; SQLite en memoria para las pruebas | CI | ✅ |

### 3.9 Escalabilidad

| ID | Requisito | Cómo se cumple | Estado |
|----|-----------|----------------|--------|
| RNF-ESC-01 | La API puede crecer horizontalmente | API sin estado de sesión (tokens); colas para el trabajo diferido | ✅ Diseño |
| RNF-ESC-02 | El tiempo real puede crecer | Reverb admite varias instancias coordinadas con Redis | 🎯 Futuro |
| RNF-ESC-03 | Los archivos pueden salir del servidor | Discos de Laravel intercambiables (local → S3) sin cambiar el código | 🎯 Futuro |

**Leyenda:** ✅ cumplido · 🎯 meta que se verifica en el despliegue · ⚠️ acción pendiente · ⏳ depende de una HU en desarrollo.
