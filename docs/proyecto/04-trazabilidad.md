# Matriz de trazabilidad

Relaciona cada historia de usuario con lo que la implementa (endpoints y eventos del backend, vistas y componentes del frontend) y con las pruebas automatizadas que la verifican. Sirve para comprobar que cada requisito está cubierto y para ubicar rápido el código de una funcionalidad.

- **Backend:** rutas bajo `/api/v1` ([contrato](../api/endpoints.md)) y eventos de tiempo real ([tiempo-real.md](../tiempo-real.md)).
- **Frontend:** vistas en `src/modules/<módulo>/views` y componentes en `src/modules/<módulo>/components` del [repositorio del frontend](https://github.com/LordCasta/maestros_a_un_clic_frontend).
- **Pruebas:** `*Test` son pruebas de API del backend (`tests/Feature`); `*.spec` son pruebas del frontend (`__tests__` de cada módulo).
- **Estado:** el de su issue en GitHub al generar el documento. Lo marcado "(planeado)" es el contrato acordado para las historias pendientes.

| HU | Módulo | Backend (API y eventos) | Frontend | Pruebas | Estado |
|----|--------|-------------------------|----------|---------|--------|
| [HU001](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/2) Inicio de sesión | auth | POST /auth/login | LoginView (/login) | Auth/LoginTest · auth/schemas.spec | ✅ Hecha |
| [HU002](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/3) Búsqueda por geolocalización | búsqueda | GET /professionals?lat=&lng= (planeado) | SearchResultsView + mapa (planeado) | — | ⏳ Pendiente |
| [HU003](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/4) Gestión de disponibilidad y agenda | agenda | GET /professional/availability · PUT …/rules · POST/DELETE …/blocks · evento availability.changed | AvailabilityView (WeeklyScheduleEditor, BlocksManager) | AvailabilityTest · WeeklyScheduleEditor.spec · weekly.spec | ✅ Hecha |
| [HU004](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/5) Solicitud y aceptación de servicio | reservas | POST /bookings · POST /bookings/{id}/accept · /reject · GET /professionals/{id}/slots | NewBookingView · BookingResponseActions · PendingRequestsPanel | BookingsTest · ProfessionalSlotsTest · BookingResponseActions.spec · SlotPicker.spec | ✅ Hecha |
| [HU005](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/6) Sistema de calificación recíproca | calificaciones | POST /bookings/{id}/review (planeado) | Calificar desde el detalle (planeado) | — | ⏳ Pendiente |
| [HU006](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/7) Registro de cliente | auth | POST /auth/register/client | RegisterClientView | RegisterClientTest · auth/schemas.spec | ✅ Hecha |
| [HU007](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/8) Registro de profesional | auth | POST /auth/register/professional | RegisterProfessionalView | Auth/RegisterProfessionalTest · auth/schemas.spec | ✅ Hecha |
| [HU008](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/9) Cargue de documentos | verificación-admin | POST /verification/documents (planeado) | Cargue de documentos (planeado) | — | ⏳ Pendiente |
| [HU009](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/10) Edición de perfil profesional | perfil-profesional | GET/PATCH /professional/profile · PUT /professional/categories · /professional/services · /professional/portfolio | AboutMeView · MyServicesView | ProfessionalProfileTest · professional-profile/schemas.spec · ServiceFormModal.spec | ✅ Hecha |
| [HU010](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/11) Filtros y ordenamiento de profesionales | búsqueda | GET /professionals (categoría, precio, orden) | SearchResultsView | ProfessionalsTest | ⏳ Pendiente |
| [HU011](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/12) Chat y confirmación de detalles del servicio | chat-notificaciones | GET/POST /bookings/{id}/messages · POST …/messages/read · evento message.sent | BookingChat (detalle de la reserva) | ChatTest · BookingChat.spec | ✅ Hecha |
| [HU012](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/13) Cancelación y reprogramación de reserva | reservas | POST /bookings/{id}/reschedule · POST /bookings/{id}/cancel | RescheduleBookingModal · CancelBookingModal | BookingRescheduleTest · BookingsTest · RescheduleBookingModal.spec | ✅ Hecha |
| [HU013](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/14) Validación de identidad y antecedentes | verificación-admin | /verification/* (planeado) | Verificación de identidad (planeado) | — | ⏳ Pendiente |
| [HU014](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/15) Revisión y aprobación de documentos del profesional | verificación-admin | /admin/verifications/* (planeado) | Bandeja del administrador (planeado) | — | ⏳ Pendiente |
| [HU016](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/16) Historial de servicios del cliente | reservas | GET /bookings | MyBookingsView (filtros por estado) | BookingsTest · BookingList.spec | ✅ Hecha |
| [HU017](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/17) Historial de servicios del profesional | reservas | GET /bookings · GET /bookings/summary | ProfessionalBookingsView (totales y filtros) | BookingsTest · BookingList.spec | ✅ Hecha |
| [HU018](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/18) Notificaciones del sistema | chat-notificaciones | GET /notifications · POST /notifications/{id}/read · /read-all · canal App.Models.User.{id} | NotificationBell (barra superior) | NotificationsTest · NotificationBell.spec | ✅ Hecha |
| [HU019](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/19) Cambio de contraseña | cuenta | PUT /auth/password (planeado) | Cambio de contraseña (planeado) | — | ⏳ Pendiente |
| [HU020](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/20) Recuperación de contraseña | cuenta | POST /auth/forgot-password · /reset-password (planeado) | Recuperación (planeado) | — | ⏳ Pendiente |
| [HU021](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/21) Cerrar sesión | auth | POST /auth/logout | AppNavbar (Salir) | Auth/SessionTest · router.spec | ✅ Hecha |
| [HU022](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/22) Registro de ubicación manual | cuenta | PATCH /me (planeado; hoy se elige la comuna al registrarse) | Perfil del cliente (planeado) | — | ⏳ Pendiente |
| [HU023](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/23) Eliminación de cuenta | cuenta | DELETE /me (planeado) | Eliminar cuenta (planeado) | — | ⏳ Pendiente |
| [HU024](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/24) Reporte de usuarios | calificaciones | POST /reports · /admin/reports (planeado) | Reportar (planeado) | — | ⏳ Pendiente |
| [HU025](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/25) Visualización de disponibilidad del profesional | agenda | GET /professionals/{id}/slots · evento availability.changed | SlotPicker (en NewBookingView y Reprogramar) | ProfessionalSlotsTest · AvailabilityTest · SlotPicker.spec · dates.spec | ✅ Hecha |
| [HU026](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/26) Estado del servicio | reservas | GET /bookings/{id} · GET /bookings/{id}/history · evento booking.status-changed | BookingDetailView · BookingHistory | BookingHistoryTest · BookingHistory.spec · presence.spec | ✅ Hecha |
| [HU027](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/27) Confirmación de inicio de servicio | reservas | POST /bookings/{id}/start | ServiceProgressCard | BookingsTest · ServiceProgressCard.spec | ✅ Hecha |
| [HU028](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/28) Confirmación de finalización de servicio | reservas | POST /bookings/{id}/complete | ServiceProgressCard | BookingsTest · ServiceProgressCard.spec | ✅ Hecha |
| [HU029](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/29) Visualización de comentarios | calificaciones | GET /professionals/{id}/reviews (planeado) | Comentarios en el perfil (planeado) | — | ⏳ Pendiente |
| [HU030](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/30) Filtro por disponibilidad inmediata | búsqueda | GET /professionals?available_now=1 (planeado) | Filtro en la búsqueda (planeado) | — | ⏳ Pendiente |
| [HU031](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/31) Edición de perfil del cliente | cuenta | PATCH /me (planeado) | Perfil del cliente (planeado) | — | ⏳ Pendiente |
| [HU032](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/32) Subida de foto de perfil | cuenta | POST /me/avatar (planeado; hoy se sube al registrarse) | Foto de perfil (planeado) | — | ⏳ Pendiente |
| [HU033](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/33) Visualización de servicios por categoría | búsqueda | GET /categories · GET /professionals?category_id= | SearchResultsView (categoría) | CatalogTest · ProfessionalsTest | ⏳ Pendiente |
| [HU034](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/34) Guardar profesionales favoritos | favoritos | POST/DELETE /favorites/{professionalId} | FavoriteButton | FavoritesTest · FavoriteButton.spec | ✅ Hecha |
| [HU035](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/35) Visualización de favoritos | favoritos | GET /favorites | MyFavoritesView | FavoritesTest · FavoriteButton.spec | ✅ Hecha |
| [HU036](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/36) Confirmación de reserva por el cliente | reservas | POST /bookings/{id}/confirm | ConfirmBookingButton | BookingsTest · ConfirmBookingButton.spec | ✅ Hecha |
| [HU037](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/37) Visualización de ingresos del profesional | perfil-profesional | GET /professional/earnings?from=&to= | EarningsView | EarningsTest · EarningsView.spec · ranges.spec | ✅ Hecha |
| [HU038](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/38) Bloqueo de usuarios problemáticos | verificación-admin | POST /admin/users/{id}/block (planeado; ya existe el middleware not_blocked) | Administración (planeado) | — | ⏳ Pendiente |
| [HU039](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/39) Búsqueda por nombre | búsqueda | GET /professionals?q= | SearchResultsView (barra de búsqueda) | ProfessionalsTest | ⏳ Pendiente |
| [HU040](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/40) Visualización de distancia | búsqueda | GET /professionals con distancia (planeado) | Listado y perfil (planeado) | — | ⏳ Pendiente |
| [HU041](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/41) Indicador de estado en línea | chat-notificaciones | Canal de presencia booking.{id} | BookingChat (En línea / Desconectado) | BroadcastingTest · presence.spec | ✅ Hecha |
| [HU042](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/42) Adjuntar archivos en el chat | chat-notificaciones | POST /bookings/{id}/messages (attachment) | BookingChat | ChatTest · BookingChat.spec | ✅ Hecha |
| [HU043](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/43) Eliminación de mensajes | chat-notificaciones | DELETE /messages/{id} · evento message.deleted | BookingChat | ChatTest · BookingChat.spec | ✅ Hecha |
| [HU044](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/44) Visualización de perfil del cliente | calificaciones | GET /clients/{id} (planeado) | Perfil del cliente para el profesional (planeado) | — | ⏳ Pendiente |
| [HU045](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/45) Alerta por baja calificación | calificaciones | /admin/alerts (planeado) | Alertas del administrador (planeado) | — | ⏳ Pendiente |
| [HU046](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/46) Ordenamiento por precio | búsqueda | GET /professionals?sort=price_asc o price_desc | SearchResultsView | ProfessionalsTest | ✅ Hecha |
| [HU047](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/47) Visualización de tiempo estimado | perfil-profesional | /professional/services (estimated_duration_minutes) | MyServicesView · NewBookingView · BookingDetailView | ProfessionalProfileTest · professional-profile/schemas.spec | ✅ Hecha |
| [HU048](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/48) Validación de datos obligatorios | auth | FormRequest → 422 con errores por campo | Formularios con VeeValidate + Zod | ApiErrorsTest · forms.spec · auth/schemas.spec | ✅ Hecha |
| [HU049](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/49) Visualización de servicios en proceso | reservas | GET /bookings?status=in_progress | InProgressPanel (paneles de inicio) | BookingsTest · InProgressPanel.spec | ✅ Hecha |
| [HU050](https://github.com/LordCasta/maestros_a_un_clic_backend/issues/50) Cierre de sesión por inactividad | cuenta | — (cierre por inactividad en el cliente, planeado) | Sesión (planeado) | — | ⏳ Pendiente |

## Cobertura por módulo

| Módulo | Responsable | Historias | Hechas |
|--------|-------------|-----------|--------|
| Autenticación (fase 0) | Equipo | HU001, HU006, HU007, HU021, HU048 | 5 de 5 |
| Favoritos (fase 0) | Equipo | HU034, HU035 | 2 de 2 |
| Agenda | LordCasta | HU003, HU025 | 2 de 2 |
| Reservas | LordCasta | HU004, HU012, HU016, HU017, HU026, HU027, HU028, HU036, HU049 | 9 de 9 |
| Perfil profesional | LordCasta | HU009, HU037, HU047 | 3 de 3 |
| Chat y notificaciones | LordCasta | HU011, HU018, HU041, HU042, HU043 | 5 de 5 |
| Búsqueda | barenas12 | HU002, HU010, HU030, HU033, HU039, HU040, HU046 | 1 de 7 |
| Cuenta | barenas12 | HU019, HU020, HU022, HU023, HU031, HU032, HU050 | 0 de 7 |
| Verificación y administración | barenas12 | HU008, HU013, HU014, HU038 | 0 de 4 |
| Calificaciones | barenas12 | HU005, HU024, HU029, HU044, HU045 | 0 de 5 |

Algunas historias pendientes ya tienen una parte hecha desde la fase 0: el filtro por categoría, nombre y precio en la búsqueda; la comuna y la foto al registrarse; y el bloqueo de acceso de los usuarios bloqueados. El detalle está en cada issue, en "Estado tras la fase 0".
