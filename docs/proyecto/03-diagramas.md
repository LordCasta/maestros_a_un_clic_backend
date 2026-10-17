# Diagramas del sistema

Diagramas de análisis y diseño hechos a partir del sistema actual: modelos, migraciones, rutas y servicios. Están en Mermaid para que se versionen con el código: GitHub los dibuja al abrir este archivo y el generador de PDF los incluye. Reemplazan a los diagramas PNG del entregable original, que eran anteriores al modelo definitivo.

## 1. Contexto del sistema

Quién usa el sistema y con qué se integra.

```mermaid
flowchart LR
    cliente(["Cliente"])
    profesional(["Profesional"])
    admin(["Administrador"])

    subgraph sistema["Maestros a un clic"]
        web["Aplicación web<br/>(Vue 3)"]
        api["API REST /api/v1<br/>(Laravel 12)"]
        rt["Tiempo real<br/>(Laravel Reverb)"]
    end

    mapas["Google Maps<br/>(mapas embebidos)"]
    correo["Servidor de correo<br/>(recuperación de contraseña)"]

    cliente --> web
    profesional --> web
    admin --> web
    web -- "HTTPS + token" --> api
    web <-- "WebSocket" --> rt
    api -- "eventos" --> rt
    web --> mapas
    api --> correo
```

## 2. Casos de uso

Un diagrama por actor. Las historias que cubre cada caso están en [02-requisitos.md](02-requisitos.md).

### 2.1 Cliente

```mermaid
flowchart LR
    C(["Cliente"])
    subgraph s["Maestros a un clic"]
        direction TB
        UC1(["Registrarse e iniciar sesión"])
        UC2(["Gestionar su cuenta"])
        UC3(["Verificar su identidad"])
        UC4(["Buscar y filtrar profesionales"])
        UC5(["Ver perfil, servicios y comentarios"])
        UC6(["Guardar favoritos"])
        UC7(["Solicitar una reserva en una hora libre"])
        UC8(["Confirmar, reprogramar o cancelar"])
        UC9(["Chatear con imágenes"])
        UC10(["Recibir notificaciones"])
        UC11(["Calificar al profesional"])
        UC12(["Reportar a un usuario"])
    end
    C --- UC1 & UC2 & UC3 & UC4 & UC5 & UC6
    C --- UC7 & UC8 & UC9 & UC10 & UC11 & UC12
```

### 2.2 Profesional

```mermaid
flowchart LR
    P(["Profesional"])
    subgraph s["Maestros a un clic"]
        direction TB
        UP1(["Registrarse e iniciar sesión"])
        UP2(["Verificar su identidad"])
        UP3(["Editar perfil, servicios y portafolio"])
        UP4(["Configurar horario, descanso y bloqueos"])
        UP5(["Aceptar o rechazar solicitudes"])
        UP6(["Reprogramar o cancelar"])
        UP7(["Iniciar y finalizar el servicio"])
        UP8(["Chatear con imágenes"])
        UP9(["Recibir notificaciones"])
        UP10(["Ver historial e ingresos"])
        UP11(["Calificar al cliente"])
    end
    P --- UP1 & UP2 & UP3 & UP4 & UP5 & UP6
    P --- UP7 & UP8 & UP9 & UP10 & UP11
```

### 2.3 Administrador y sistema

```mermaid
flowchart LR
    A(["Administrador"])
    S(["Sistema"])
    subgraph s["Maestros a un clic"]
        direction TB
        UA1(["Revisar documentos de verificación"])
        UA2(["Atender reportes"])
        UA3(["Atender alertas por baja calificación"])
        UA4(["Bloquear y desbloquear usuarios"])
        US1(["Calcular horas libres"])
        US2(["Enviar notificaciones y eventos en tiempo real"])
        US3(["Generar alerta por baja calificación"])
    end
    A --- UA1 & UA2 & UA3 & UA4
    S --- US1 & US2 & US3
```

## 3. Clases del dominio

Modelos principales con sus atributos clave y los servicios que aplican las reglas de negocio. Los estados, roles y tipos son enumeraciones de PHP (`app/Enums`).

```mermaid
classDiagram
    direction LR
    class User {
        +string name
        +string email
        +Role role
        +VerificationStatus verification_status
        +Commune commune
        +float rating_avg
        +int rating_count
        +datetime blocked_at
    }
    class ClientProfile {
        +date birth_date
    }
    class ProfessionalProfile {
        +text description
        +int experience_years
        +decimal hourly_rate
        +int service_radius_km
        +int buffer_minutes
    }
    class Category {
        +string name
        +Category parent
    }
    class ProfessionalService {
        +string title
        +PriceType price_type
        +decimal price
        +int estimated_duration_minutes
        +bool is_active
    }
    class PortfolioItem {
        +string image_path
    }
    class AvailabilityRule {
        +int weekday
        +time start_time
        +time end_time
    }
    class AvailabilityBlock {
        +datetime starts_at
        +datetime ends_at
    }
    class Booking {
        +BookingStatus status
        +datetime starts_at
        +datetime ends_at
        +decimal agreed_price
        +datetime started_at
        +datetime completed_at
    }
    class BookingStatusChange {
        +BookingStatus from_status
        +BookingStatus to_status
        +text reason
        +datetime previous_starts_at
    }
    class Message {
        +text body
        +string attachment_path
        +datetime read_at
    }
    class Review {
        +int rating
        +text comment
    }
    class VerificationRequest {
        +VerificationStatus status
        +BackgroundCheckStatus background_check_status
    }
    class VerificationDocument {
        +DocumentType type
        +DocumentStatus status
    }
    class Report {
        +string reason
        +ReportStatus status
    }
    class AdminAlert {
        +AlertType type
    }
    class BookingService {
        +create(client, data) Booking
        +accept() / reject() / confirm()
        +start() / complete()
        +reschedule() / cancel()
        +transition(booking, to, by)
    }
    class AvailabilityService {
        +slots(service, date) list
        +withinSchedule(profile, start, end) bool
        +hasBookingConflict(profile, start, end) bool
    }
    class ChatService {
        +send(booking, sender, body, attachment) Message
        +delete(message)
        +markAsRead(booking, reader) int
    }

    User "1" -- "0..1" ClientProfile
    User "1" -- "0..1" ProfessionalProfile
    ProfessionalProfile "*" -- "*" Category : especialidades
    Category "1" -- "*" Category : subcategorías
    ProfessionalProfile "1" -- "*" ProfessionalService
    ProfessionalService "*" -- "1" Category
    ProfessionalProfile "1" -- "*" PortfolioItem
    ProfessionalProfile "1" -- "*" AvailabilityRule
    ProfessionalProfile "1" -- "*" AvailabilityBlock
    User "1" -- "*" Booking : como cliente
    User "1" -- "*" Booking : como profesional
    Booking "*" -- "1" ProfessionalService
    Booking "1" -- "*" BookingStatusChange
    Booking "1" -- "*" Message
    Booking "1" -- "0..2" Review
    User "1" -- "*" VerificationRequest
    VerificationRequest "1" -- "*" VerificationDocument
    User "1" -- "*" Report : reporta / es reportado
    User "1" -- "*" AdminAlert
    BookingService ..> Booking : gestiona
    BookingService ..> AvailabilityService : usa
    ChatService ..> Message : gestiona
```

## 4. Modelo entidad-relación

Entidades y relaciones con sus llaves. Las columnas completas de cada tabla están en [docs/modelo-de-datos.md](../modelo-de-datos.md) §2.

```mermaid
erDiagram
    communes ||--o{ users : "ubica"
    communes }o--o{ communes : "vecinas"
    users ||--o| client_profiles : "tiene"
    users ||--o| professional_profiles : "tiene"
    professional_profiles }o--o{ categories : "professional_category"
    categories ||--o{ categories : "subcategorías"
    professional_profiles ||--o{ professional_services : "ofrece"
    categories ||--o{ professional_services : "clasifica"
    professional_profiles ||--o{ portfolio_items : "muestra"
    professional_profiles ||--o{ availability_rules : "horario"
    professional_profiles ||--o{ availability_blocks : "bloqueos"
    users ||--o{ verification_requests : "solicita"
    verification_requests ||--o{ verification_documents : "contiene"
    users ||--o{ bookings : "cliente"
    users ||--o{ bookings : "profesional"
    professional_services ||--o{ bookings : "se reserva"
    bookings ||--o{ booking_status_changes : "historial"
    bookings ||--o{ messages : "chat"
    bookings ||--o{ reviews : "calificaciones"
    users ||--o{ favorites : "guarda"
    users ||--o{ reports : "reporta"
    users ||--o{ admin_alerts : "genera"
    users ||--o{ notifications : "recibe"

    users {
        bigint id PK
        string role
        string verification_status
        bigint commune_id FK
        decimal rating_avg
    }
    bookings {
        bigint id PK
        bigint client_id FK
        bigint professional_id FK
        bigint professional_service_id FK
        string status
        datetime starts_at
        datetime ends_at
        decimal agreed_price
    }
    professional_services {
        bigint id PK
        bigint professional_profile_id FK
        bigint category_id FK
        decimal price
        int estimated_duration_minutes
    }
```

## 5. Estados

### 5.1 Reserva

Todo cambio pasa por `BookingService::transition()` y queda en `booking_status_changes`.

```mermaid
stateDiagram-v2
    [*] --> pending: el cliente solicita (HU004)
    pending --> accepted: el profesional acepta
    pending --> rejected: el profesional rechaza
    accepted --> confirmed: el cliente confirma (HU036)
    pending --> pending: reprogramar (HU012)
    accepted --> pending: reprogramar
    confirmed --> pending: reprogramar
    pending --> cancelled: cancelar con motivo
    accepted --> cancelled
    confirmed --> cancelled
    confirmed --> in_progress: inicia, desde la hora pactada (HU027)
    in_progress --> completed: finaliza (HU028)
    completed --> [*]: se habilita calificar (HU005)
    rejected --> [*]
    cancelled --> [*]
```

### 5.2 Verificación de identidad

```mermaid
stateDiagram-v2
    [*] --> unverified: se registra
    unverified --> pending: envía documentos (HU008, HU013)
    pending --> approved: el administrador aprueba (HU014)
    pending --> rejected: el administrador rechaza con motivo
    rejected --> pending: reenvía documentos
    approved --> [*]
```

## 6. Secuencias

### 6.1 Solicitar y aceptar una reserva

```mermaid
sequenceDiagram
    autonumber
    actor C as Cliente
    actor P as Profesional
    participant W as App web
    participant API as API (Laravel)
    participant DB as MySQL
    participant RT as Reverb

    C->>W: Elige servicio y día
    W->>API: GET /professionals/{id}/slots
    API->>DB: Horario, bloqueos y reservas activas
    API-->>W: Horas libres
    C->>W: Elige hora y envía
    W->>API: POST /bookings
    API->>DB: Transacción: bloquea la agenda, valida cruces y crea la reserva (pending)
    API-->>W: 201 Reserva creada
    API-)RT: Notificación booking.requested y availability.changed
    RT-)P: Toast "Nueva solicitud"
    P->>W: Acepta
    W->>API: POST /bookings/{id}/accept
    API->>DB: transition(pending → accepted) y registro en el historial
    API-->>W: 200
    API-)RT: booking.status-changed y booking.accepted
    RT-)C: El detalle se actualiza y llega el aviso
```

### 6.2 Mensaje en el chat

```mermaid
sequenceDiagram
    autonumber
    actor C as Cliente
    actor P as Profesional
    participant W as App web
    participant API as API
    participant RT as Reverb

    C->>W: Abre el detalle de la reserva
    W->>RT: Se une al canal de presencia booking.{id}
    RT-->>W: Quién está conectado (estado en línea)
    C->>W: Escribe y adjunta una foto
    W->>API: POST /bookings/{id}/messages (multipart)
    API->>API: Valida, guarda la imagen en el disco privado
    API-->>W: 201 Mensaje con URL firmada temporal
    API-)RT: message.sent en booking.{id}
    RT-)P: El mensaje aparece en su chat
    API-)RT: Notificación message.received
```

## 7. Componentes

```mermaid
flowchart TB
    subgraph front["Frontend · Vue 3 + TypeScript"]
        direction TB
        app["app/<br/>router, layouts, guards"]
        modules["modules/<br/>auth · professionals · bookings · schedule · chat ·<br/>notifications · professional-profile · favorites · catalog"]
        shared["shared/<br/>ui (componentes base) · http · realtime · forms · types"]
        app --> modules --> shared
    end

    subgraph back["Backend · Laravel 12"]
        direction TB
        routes["Rutas /api/v1<br/>auth:sanctum · role · not_blocked"]
        requests["FormRequest<br/>(validación)"]
        controllers["Controllers"]
        policies["Policies<br/>(autorización)"]
        services["Services<br/>Booking · Availability · Chat · ProfessionalProfile · Upload"]
        resources["Resources<br/>(forma de las respuestas)"]
        events["Eventos y notificaciones"]
        routes --> requests --> controllers
        controllers --> policies
        controllers --> services --> resources
        services --> events
    end

    db[("MySQL")]
    files[("Archivos<br/>público / privado")]
    queue[["Cola de trabajos"]]
    reverb["Reverb<br/>(WebSockets)"]

    shared -- "HTTP JSON" --> routes
    shared <-- "WebSocket" --> reverb
    services --> db
    services --> files
    events --> queue --> reverb
```

## 8. Despliegue

Topología prevista para producción. Los pasos están en [06-despliegue-y-operacion.md](06-despliegue-y-operacion.md).

```mermaid
flowchart LR
    user(["Navegador"])

    subgraph ec2["Servidor (AWS EC2, Ubuntu)"]
        nginx["Nginx<br/>TLS · HTTPS / WSS"]
        spa["Frontend compilado<br/>(archivos estáticos)"]
        fpm["PHP-FPM<br/>API Laravel"]
        worker["queue:work<br/>(Supervisor)"]
        reverb["reverb:start<br/>(Supervisor)"]
        mysql[("MySQL 8")]
        storage[("storage/<br/>public y private")]
        cron["cron<br/>schedule:run y respaldos"]
    end

    user -- "443 HTTPS" --> nginx
    nginx --> spa
    nginx -- "/api" --> fpm
    nginx -- "/app (wss)" --> reverb
    fpm --> mysql
    fpm --> storage
    fpm -- "trabajos" --> mysql
    worker -- "toma trabajos" --> mysql
    worker -- "publica eventos" --> reverb
    cron --> fpm
```
