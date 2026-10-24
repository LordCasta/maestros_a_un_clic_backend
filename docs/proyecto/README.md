# Documentación del proyecto

Documentación de **Maestros a un clic** como proyecto de ingeniería de software: el documento del proyecto, los requisitos, el diseño, las pruebas, el despliegue y la seguridad. La documentación técnica para desarrollar (arquitectura de cada repositorio, contrato de la API, sistema de diseño, guía de trabajo) está enlazada al final.

## Documentos

| # | Documento | Contenido | PDF |
|---|-----------|-----------|-----|
| 1 | [Documento del proyecto](01-documento-del-proyecto.md) | Problema, objetivos, referentes, metodología, factibilidad, alcance, conclusiones y bibliografía | [PDF](pdf/01-documento-del-proyecto.pdf) |
| 2 | [Requisitos](02-requisitos.md) | 49 historias de usuario con criterios y estado; requisitos no funcionales medibles (ISO/IEC 25010) | [PDF](pdf/02-requisitos.pdf) |
| 3 | [Diagramas](03-diagramas.md) | Contexto, casos de uso, clases, entidad-relación, estados, secuencias, componentes y despliegue | [PDF](pdf/03-diagramas.pdf) |
| 4 | [Trazabilidad](04-trazabilidad.md) | Cada historia con sus endpoints, pantallas, pruebas y estado | [PDF](pdf/04-trazabilidad.pdf) |
| 5 | [Pruebas](05-pruebas.md) | Estrategia, integración continua y reporte de resultados | [PDF](pdf/05-pruebas.pdf) |
| 6 | [Despliegue y operación](06-despliegue-y-operacion.md) | Servidor, configuración, procesos, respaldos y monitoreo | [PDF](pdf/06-despliegue-y-operacion.pdf) |
| 7 | [Seguridad y privacidad](07-seguridad-y-privacidad.md) | Controles OWASP, Ley 1581 de 2012 y acciones pendientes | [PDF](pdf/07-seguridad-y-privacidad.pdf) |
| 8 | [Decisiones de arquitectura](08-decisiones-de-arquitectura.md) | Qué se decidió y por qué (ADR) | [PDF](pdf/08-decisiones-de-arquitectura.pdf) |
| 9 | [Informe de análisis funcional](09-informe-analisis-funcional.md) | Estructura funcional, roles, reglas de negocio, interfaces con capturas, matriz y resultados de pruebas funcionales, hallazgos y propuestas de mejora | [PDF](pdf/09-informe-analisis-funcional.pdf) |
| — | **Todo en un solo archivo** | Los nueve documentos con una sola portada | [PDF](pdf/documentacion-completa.pdf) |

## Documentación técnica

| Tema | Backend | Frontend |
|------|---------|----------|
| Arquitectura | [docs/arquitectura.md](../arquitectura.md) | [docs/arquitectura.md](https://github.com/LordCasta/maestros_a_un_clic_frontend/blob/main/docs/arquitectura.md) |
| Modelo de datos | [docs/modelo-de-datos.md](../modelo-de-datos.md) | — |
| Contrato de la API | [docs/api/endpoints.md](../api/endpoints.md) · [convenciones](../api/convenciones.md) · [OpenAPI](../api/openapi.json) | — |
| Tiempo real | [docs/tiempo-real.md](../tiempo-real.md) | — |
| Sistema de diseño | — | [docs/sistema-de-diseno.md](https://github.com/LordCasta/maestros_a_un_clic_frontend/blob/main/docs/sistema-de-diseno.md) |
| Cómo trabajamos | [CONTRIBUTING.md](../../CONTRIBUTING.md) · [guía de trabajo](../guia-de-trabajo.md) | [CONTRIBUTING.md](https://github.com/LordCasta/maestros_a_un_clic_frontend/blob/main/CONTRIBUTING.md) |

## Mantener esta documentación

- **La fuente es el Markdown.** Los PDF se generan desde él; no se editan a mano.
- Para regenerarlos, desde la raíz del backend, ejecuta `npm install` una vez y luego `npm run docs:pdf`. Hace falta Chrome o Edge instalado; si no lo encuentra, indica su ruta en la variable `CHROME_PATH`.
- La portada (integrantes, institución, curso, docente) se edita en [`_pdf/portada.json`](_pdf/portada.json).
- Las pruebas funcionales y las capturas del informe 09 se repiten con los scripts de [`_pruebas/`](_pruebas/), con el backend y el frontend corriendo en local. Las imágenes quedan en `img/`.
- Al cerrar una historia, actualiza su estado en [02-requisitos.md](02-requisitos.md) y en [04-trazabilidad.md](04-trazabilidad.md), y regenera los PDF.
