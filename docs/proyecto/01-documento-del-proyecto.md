# Documento del proyecto

> Versión revisada del entregable del proyecto de aula (13/04/2026). Se corrigió la numeración, se actualizaron los datos técnicos y legales, y el alcance refleja lo que el sistema hace hoy. Los requisitos, los diagramas, las pruebas, el despliegue y la seguridad tienen documentos propios, enlazados en cada sección.

## 1. Introducción

¿Alguna vez has necesitado instalar cajones nuevos en la cocina y no sabías quién podía hacerlo? Muchas personas enfrentan situaciones parecidas con tareas del hogar: reparaciones, renovaciones, instalaciones y diseño. El problema es que siempre hay que buscar en lugares distintos o preguntar a conocidos para encontrar a alguien que haga el trabajo. **Maestros a un clic** centraliza a esos profesionales, cerca de ti, para que solicites su servicio con un clic.

La solución es una plataforma web en la que:

- **Los profesionales** se registran, eligen sus especialidades, publican sus servicios con precio y duración estimada, muestran un portafolio de trabajos y definen su horario y sus tiempos de descanso. Para generar confianza, pasan por una verificación de identidad: suben documento, selfie y certificados, que revisa un administrador.
- **Los clientes** se registran, buscan profesionales por categoría, nombre, precio y cercanía, y reservan una hora libre real de la agenda del profesional. Para reservar también deben estar verificados.
- **Las dos partes** se comunican por un chat dentro de cada reserva, reciben notificaciones en tiempo real de cada cambio y, al terminar el servicio, se califican mutuamente de 1 a 5 estrellas. Una calificación baja genera una alerta para que el administrador revise el caso.

## 2. Planteamiento del problema

En Medellín, encontrar un profesional confiable para el hogar es difícil. Las alternativas actuales son:

- **Empresas que tercerizan profesionales**: tienen costos altos y el cliente no elige quién hace el trabajo.
- **El voz a voz**: depende de los contactos de cada persona y los resultados no siempre son los esperados.
- **Los grupos de redes sociales**: no hay ninguna validación de identidad ni de experiencia, y es común que haya estafas.

Además, coordinar horarios por mensajes es lento y propenso a cruces: el profesional acepta dos trabajos a la misma hora o no deja tiempo para desplazarse.

## 3. Justificación

La plataforma busca dar **credibilidad** al profesional y **tranquilidad** al cliente, para que este vuelva a usarla. Lo logra con cuatro mecanismos:

1. **Verificación de identidad** de clientes y profesionales, con documentos y certificados revisados por un administrador.
2. **Calificación recíproca y visible**: el cliente califica al profesional y el profesional al cliente, así otros profesionales pueden evaluar a un cliente antes de aceptar un trabajo.
3. **Agenda real**: el profesional define su horario, sus bloqueos y un tiempo de descanso entre servicios. El cliente solo ve y reserva horas libres, y el sistema impide que dos servicios se crucen.
4. **Trazabilidad**: cada cambio de estado de una reserva queda registrado, con quién lo hizo, cuándo y por qué.

El flujo principal es este: el profesional se registra y se verifica, y publica sus servicios. El cliente busca un profesional y envía una solicitud para una hora libre. El profesional la acepta o la rechaza; el cliente confirma. El profesional inicia y finaliza el servicio, y las dos partes se califican.

## 4. Objetivos

### 4.1 Objetivo general

Desarrollar una plataforma web de servicios profesionales y oficios que conecte a especialistas en mantenimiento, infraestructura y diseño del hogar con clientes de Medellín, y que facilite una contratación transparente, verificada y sin intermediarios.

### 4.2 Objetivos específicos

1. Construir un sistema de **perfiles profesionales** en el que el prestador muestre evidencia fotográfica de su trabajo, sus especialidades, sus servicios con precio y duración, y su ubicación.
2. Implementar un **motor de reservas** que calcule la disponibilidad real del profesional (horario, bloqueos, descanso y reservas existentes), evite cruces de horario y lleve cada reserva por un ciclo de vida auditable: solicitud, aceptación, confirmación, inicio y finalización.
3. Establecer un **ecosistema de confianza** basado en verificación de identidad, ubicación por comunas de Medellín y calificación recíproca.
4. Ofrecer **comunicación en tiempo real** entre las partes: chat por reserva, estado en línea y notificaciones.
5. Aplicar prácticas profesionales de ingeniería de software: arquitectura por capas y módulos, contrato de API documentado, pruebas automatizadas, integración continua y control de versiones con revisión por pull request.

## 5. Referentes del medio

| Referente | Cómo funciona | Limitación frente a esta propuesta |
|-----------|---------------|------------------------------------|
| [Hombre en Casa Medellín](https://hombreencasamedellin.com/) | Página informativa; la solicitud se hace por WhatsApp y la empresa contacta a un profesional | Proceso manual. El cliente no elige al profesional ni ve su agenda |
| [Multihelpers](https://multihelpers.com/) | Se escribe a la empresa, que asigna el profesional | El cliente no elige ni conoce el perfil del profesional |
| [Hogaru](https://www.hogaru.com/) | Servicio de aseo para hogares y empresas | Un solo nicho. La empresa asigna a la persona y no se ve su perfil |

**Diferencial de Maestros a un clic:** el cliente elige al profesional viendo su perfil verificado, su portafolio, sus calificaciones y su agenda real. La reserva es directa y queda trazada de principio a fin.

## 6. Marco de referencia

- **Marco contextual.** La plataforma arranca en Medellín y usa la división administrativa en 16 comunas y 5 corregimientos para ubicar a clientes y profesionales y sugerir comunas vecinas.
- **Marco tecnológico.** Se enmarca en la economía colaborativa (*gig economy*). Usa una API REST, comunicación en tiempo real por WebSockets, una base de datos relacional y geolocalización.
- **Marco conceptual.** Es un *marketplace de servicios*: intermediación digital, validación de identidad (*KYC, Know Your Customer*) para reducir fraudes y reputación basada en calificación recíproca.

## 7. Metodología

### 7.1 Sustento teórico

El proyecto se fundamenta en **Scrum** (Schwaber y Sutherland, 2020): entregas iterativas e incrementales, colaboración del equipo y adaptación al cambio. Las historias de usuario, con criterios de aceptación verificables, forman el *product backlog*.

### 7.2 Cómo se aplicó

| Práctica | Implementación en el proyecto |
|----------|-------------------------------|
| Backlog | Cada historia de usuario es un issue de GitHub con sus criterios de aceptación como lista verificable, su módulo y sus dependencias |
| Reparto del trabajo | Por módulos completos (backend y frontend de cada uno) entre dos desarrolladores *full stack*; cada uno es dueño de sus módulos |
| Fase 0 (base común) | Antes de repartir: esquema completo de la base de datos, contrato de la API, sistema de diseño, convenciones, CI y un módulo de referencia (favoritos) |
| Iteraciones | Una rama por historia, pull request revisado y fusión a `main` solo con la integración continua en verde |
| Definición de terminado | Cumple los criterios de aceptación, tiene pruebas automatizadas (caso feliz, validación y permisos), está documentado en el contrato de la API y fusionado en los dos repositorios |
| Calidad automática | En cada PR, la integración continua corre las pruebas (PHP 8.2 y 8.5), el formato (Pint, Prettier), el lint (ESLint, oxlint), los tipos (TypeScript), el build, el verificador de colores del sistema de diseño y la auditoría de dependencias. El check **Guardas** impide aflojar reglas sin aprobación |
| Trabajo con IA | Los dos desarrolladores usan asistentes de IA (Claude Code y Antigravity) que leen las mismas reglas del repositorio (`AGENTS.md`), para mantener un estilo único |

Las etapas clásicas siguen presentes (planificación, diseño, desarrollo, pruebas, despliegue y mantenimiento), pero en ciclos cortos por historia.

## 8. Estudio de factibilidad

### 8.1 Viabilidad técnica

Todas las tecnologías son de código abierto, maduras y con amplia documentación.

| Capa | Tecnología | Uso |
|------|------------|-----|
| Frontend | Vue 3, TypeScript, Vite, Tailwind CSS 4, Pinia y Pinia Colada | Aplicación web de una sola página (SPA), responsive |
| Backend | Laravel 12 (PHP 8.2+), Laravel Sanctum | API REST versionada (`/api/v1`) y autenticación por token |
| Tiempo real | Laravel Reverb (WebSockets) y Laravel Echo | Chat, estado en línea, notificaciones y agenda en vivo |
| Base de datos | MySQL 8 | Base relacional con integridad referencial |
| Calidad | Pest, Vitest, ESLint, oxlint, Pint, Prettier, GitHub Actions | Pruebas automatizadas, estilo y CI |
| Despliegue previsto | AWS EC2 con Nginx, Supervisor y certificado TLS | Servidor único para API, cola, Reverb y archivos |

La arquitectura detallada está en [08-decisiones-de-arquitectura.md](08-decisiones-de-arquitectura.md) y en los documentos `docs/arquitectura.md` de cada repositorio.

### 8.2 Viabilidad económica

**Costos de infraestructura (estimados, primer año):**

| Concepto | Costo |
|----------|-------|
| Vue, Laravel, Reverb, MySQL Community Edition (licencias de código abierto: MIT y GPL) | $0 |
| Servidor AWS EC2 (instancia pequeña) y disco | ≈ 25 USD/mes |
| Dominio y certificado TLS (Let's Encrypt) | ≈ 15 USD/año y $0 |
| Correo transaccional (recuperación de contraseña) | Plan gratuito de un proveedor SMTP en la fase inicial |

**Costos de talento humano** (rangos de referencia en Colombia, para una operación real):

| Rol | Funciones principales | Dedicación | Costo aproximado |
|-----|-----------------------|------------|------------------|
| Líder de proyecto | Coordinar el equipo, cronograma y objetivos | Completa durante el desarrollo | $4–6 millones COP/mes |
| Desarrollador frontend (Vue) | Interfaz, componentes, integración con la API | Completa | $3–5 millones COP/mes |
| Desarrollador backend (Laravel) | Lógica de negocio, API, seguridad | Completa | $3–5 millones COP/mes |
| Administrador de base de datos | Esquema, optimización, respaldos | Parcial | $2–3 millones COP/mes |
| DevOps / nube | Servidores, despliegue, CI/CD, monitoreo | Parcial | $3–4 millones COP/mes |
| Diseño UI/UX | Prototipos, pruebas de usabilidad | Parcial | $2–3 millones COP/mes |
| QA | Pruebas funcionales, de seguridad y rendimiento | Parcial | $2–3 millones COP/mes |
| Seguridad / KYC | Verificación de identidad, protección de datos | Parcial | $3–4 millones COP/mes |
| Soporte | Atención a usuarios y profesionales | Completa tras el lanzamiento | $2–3 millones COP/mes |

**Modelo de ingresos posible:** comisión por servicio completado o suscripción de los profesionales para destacar su perfil. El cliente ahorra frente a las empresas que tercerizan, porque contrata directamente.

### 8.3 Viabilidad de mercado

Hay una demanda clara por la dificultad de encontrar profesionales confiables, y los referentes actuales no dejan elegir al profesional ni ofrecen procesos automatizados. Las principales dificultades previstas son:

1. **Adopción inicial**: los profesionales pueden resistirse a la verificación, y los clientes pueden preferir los métodos tradicionales.
2. **Competencia** con plataformas ya conocidas.
3. **Percepción de la verificación** como invasiva o compleja.
4. **Arranque en frío de la reputación**: al inicio no habrá reseñas, y se necesitarán incentivos para las primeras calificaciones.
5. **Cumplimiento legal** en el tratamiento de datos personales.
6. **Costos de mercadeo** para posicionar la marca.
7. **Escalabilidad de la oferta**: si la demanda crece rápido, puede faltar disponibilidad de profesionales.

### 8.4 Viabilidad operativa

La plataforma es **autogestionable**. Clientes y profesionales administran sus perfiles, documentos, agendas y reservas. El administrador solo interviene en los casos críticos: aprobar la verificación de documentos, atender alertas por baja calificación y revisar reportes. Así se puede atender a muchos más usuarios sin aumentar el personal en la misma proporción. Las notificaciones, la agenda y los cambios de estado son automáticos.

### 8.5 Viabilidad legal y normativa

| Norma | Aplicación en la plataforma |
|-------|-----------------------------|
| **Ley 1581 de 2012** (protección de datos personales) | Tratamiento de nombres, ubicación, selfies, documentos y certificados con finalidad explícita, seguridad y derechos del titular. Ver [07-seguridad-y-privacidad.md](07-seguridad-y-privacidad.md) |
| **Decreto 1377 de 2013** (hoy compilado en el Decreto 1074 de 2015) | Autorización previa e informada para el tratamiento, y procedimientos de consulta, rectificación y supresión |
| **Ley 1266 de 2008** (hábeas data financiero) | No aplica hoy: no se maneja información crediticia. Se tendría en cuenta si se integraran pagos o validaciones financieras |
| **Ley 527 de 1999** (comercio electrónico) | Validez de los mensajes de datos: las solicitudes, aceptaciones y confirmaciones digitales quedan registradas con fecha y autor |
| **Ley 1273 de 2009** (delitos informáticos) | Protección de la información y de los datos: control de acceso, almacenamiento privado de documentos y registro de cambios |
| **CONPES 3854 de 2016 y 3995 de 2020** (política de seguridad y confianza digital) | Marco de buenas prácticas de seguridad digital que orienta los controles del sistema |
| **ISO/IEC 27001 y 27018** | Referentes de gestión de seguridad de la información y protección de datos en la nube para la operación |
| **ISO/IEC 25010** | Modelo de calidad de producto usado para formular los requisitos no funcionales ([02-requisitos.md](02-requisitos.md)) |
| **RGPD (Unión Europea)** | Referente voluntario de buenas prácticas para una posible expansión |

> **Corrección respecto al entregable original.** La Ley 1621 de 2013 regula las actividades de inteligencia y contrainteligencia del Estado, no la seguridad digital, así que se reemplazó por la Ley 1273 de 2009 y los documentos CONPES. MySQL no tiene licencias de Microsoft: la edición Community es de Oracle y se distribuye bajo GPL.

## 9. Alcance y limitaciones

### 9.1 Alcance

| Incluido | Estado |
|----------|--------|
| Registro e inicio de sesión de clientes y profesionales, cierre de sesión y validación de datos | Implementado |
| Perfil profesional: descripción, especialidades, tarifa, ubicación, servicios con precio y duración, y portafolio | Implementado |
| Agenda: horario semanal, descanso configurable, bloqueos y cálculo de horas libres en tiempo real | Implementado |
| Ciclo de vida de la reserva: solicitar, aceptar o rechazar, confirmar, reprogramar, cancelar, iniciar y finalizar, con historial auditable | Implementado |
| Chat por reserva con imágenes, estado en línea y eliminación de mensajes | Implementado |
| Notificaciones en tiempo real y centro de notificaciones | Implementado |
| Historiales, totales, servicios en proceso e ingresos del profesional | Implementado |
| Favoritos y búsqueda por nombre, categoría y precio | Implementado |
| Verificación de identidad (cargue de documentos y revisión del administrador), bloqueo de usuarios | Implementado |
| Calificación recíproca, comentarios, reportes y alertas por baja calificación | Implementado |
| Búsqueda por cercanía con distancia, comunas aledañas, "disponible ahora" y mapa | Implementado |
| Gestión de la cuenta: editar perfil, foto, contraseña, recuperación, eliminación con anonimización y cierre por inactividad | Implementado |

Las 49 historias de usuario están terminadas.

El detalle por historia, con sus endpoints, pantallas y pruebas, está en [04-trazabilidad.md](04-trazabilidad.md).

### 9.2 Fuera del alcance

- **Pagos en línea.** El precio se acuerda en la plataforma, pero el pago se hace por fuera.
- **Consulta automática de antecedentes con un proveedor externo.** No existe una API pública de la Policía o la Procuraduría que se pueda integrar. El administrador registra el resultado manualmente, y el modelo de datos queda listo para un proveedor futuro (decisión D10).
- **Aplicaciones móviles nativas.** La aplicación web es responsive y funciona en el navegador del celular.

### 9.3 Limitaciones

- La plataforma facilita la conexión y la verificación, pero no garantiza la calidad del trabajo del profesional.
- La verificación depende de la revisión humana de un administrador.
- En la fase inicial, la cobertura geográfica se limita a Medellín.
- Los costos de infraestructura en la nube pueden variar con la demanda.

## 10. Requisitos

Los 49 requisitos funcionales (historias de usuario con sus criterios de aceptación y su estado) y los requisitos no funcionales, medibles y con su forma de verificación, están en **[02-requisitos.md](02-requisitos.md)**.

## 11. Análisis y diseño

- **Diagramas** (casos de uso, clases del dominio, entidad-relación, estados de la reserva, secuencias, componentes y despliegue), generados a partir del sistema actual: **[03-diagramas.md](03-diagramas.md)**. Reemplazan a los diagramas PNG del entregable original, que eran previos al modelo definitivo.
- **Decisiones de arquitectura** y su justificación: **[08-decisiones-de-arquitectura.md](08-decisiones-de-arquitectura.md)**.
- **Modelo de datos** completo: [docs/modelo-de-datos.md](../modelo-de-datos.md).
- **Contrato de la API**: [docs/api/endpoints.md](../api/endpoints.md), [convenciones](../api/convenciones.md) y la especificación OpenAPI exportada en [docs/api/openapi.json](../api/openapi.json).

## 12. Pruebas

La estrategia, las herramientas y el reporte de resultados están en **[05-pruebas.md](05-pruebas.md)**.

## 13. Despliegue y operación

La guía para poner el sistema en producción y operarlo está en **[06-despliegue-y-operacion.md](06-despliegue-y-operacion.md)**.

## 14. Seguridad y privacidad

Los controles implementados y el cumplimiento de la Ley 1581 de 2012 están en **[07-seguridad-y-privacidad.md](07-seguridad-y-privacidad.md)**.

## 15. Conclusiones

- El proyecto es **viable** técnica, económica, operativa y legalmente: usa tecnologías de código abierto maduras, un costo de infraestructura bajo y un modelo autogestionable.
- La propuesta se **diferencia** de los referentes porque el cliente elige al profesional viendo su perfil verificado, su reputación y su agenda real, y la reserva queda trazada de principio a fin.
- Construir primero una **base común** (esquema completo, contrato de API, sistema de diseño, convenciones y CI) permitió que dos personas trabajaran en paralelo por módulos sin conflictos y con un estilo uniforme.
- Las **pruebas automatizadas y la integración continua** en cada pull request detectan regresiones antes de llegar a `main`. Junto con los documentos de requisitos y trazabilidad, dejan evidencia verificable de que cada historia cumple sus criterios.
- La implementación inicial en Medellín permite validar el modelo antes de escalarlo a otras ciudades.

## 16. Glosario

| Término | Significado |
|---------|-------------|
| Cliente | Persona que busca y reserva servicios |
| Profesional | Persona que ofrece servicios; también "maestro" |
| Servicio | Lo que ofrece un profesional, con precio y duración estimada |
| Reserva | Solicitud de un cliente para un servicio en una fecha y hora; pasa por varios estados |
| Disponibilidad / horas libres | Horas en las que se puede reservar: el horario del profesional menos sus bloqueos y reservas, con su descanso |
| Descanso (*buffer*) | Tiempo que se bloquea antes y después de cada reserva para desplazarse o descansar |
| Verificación (KYC) | Revisión de la identidad del usuario a partir de sus documentos |
| API REST | Interfaz por la que el frontend se comunica con el backend mediante HTTP y JSON |
| WebSocket | Conexión permanente que permite enviar eventos en tiempo real |
| CI | Integración continua: verificaciones automáticas en cada cambio |
| PR | *Pull request*: propuesta de cambio que se revisa antes de unirla a la rama principal |

## 17. Bibliografía

- Congreso de Colombia. (1999). *Ley 527 de 1999*, por medio de la cual se define y reglamenta el acceso y uso de los mensajes de datos, del comercio electrónico y de las firmas digitales.
- Congreso de Colombia. (2008). *Ley 1266 de 2008*, disposiciones generales del hábeas data.
- Congreso de Colombia. (2009). *Ley 1273 de 2009*, de la protección de la información y de los datos.
- Congreso de Colombia. (2012). *Ley Estatutaria 1581 de 2012*, disposiciones generales para la protección de datos personales.
- Presidencia de la República de Colombia. (2013). *Decreto 1377 de 2013*, reglamentario de la Ley 1581 de 2012.
- Departamento Nacional de Planeación. (2016). *Documento CONPES 3854: Política Nacional de Seguridad Digital*.
- Departamento Nacional de Planeación. (2020). *Documento CONPES 3995: Política Nacional de Confianza y Seguridad Digital*.
- International Organization for Standardization. (2022). *ISO/IEC 27001: Information security management systems*.
- International Organization for Standardization. (2023). *ISO/IEC 25010: Product quality model*.
- OWASP Foundation. (2021). *OWASP Top 10* y *Application Security Verification Standard (ASVS) 4.0*.
- Parlamento Europeo y Consejo. (2016). *Reglamento (UE) 2016/679 (RGPD)*.
- Schwaber, K. y Sutherland, J. (2020). *The Scrum Guide*.
- Sommerville, I. (2016). *Software Engineering* (10.ª ed.). Pearson.
- Documentación oficial de Laravel 12, Vue 3 y Tailwind CSS 4 (laravel.com, vuejs.org, tailwindcss.com).
