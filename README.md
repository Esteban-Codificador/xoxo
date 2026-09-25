# AI Engineer Roadmap

Plataforma educativa para formar **AI Engineers** de forma progresiva, desde los fundamentos de programación hasta LLM Engineering, RAG, agentes, MLOps y AI System Design. Reúne un roadmap visual, un grafo de conocimiento, un LMS, aprendizaje basado en proyectos, seguimiento de progreso con evidencia y un CMS educativo.

El nombre es provisional.

## Estado

**Fases 0–2 completadas (discovery y arquitectura). Todavía no hay código de aplicación.**

El estado vivo del proyecto está en [`docs/progress.md`](docs/progress.md).

## Principio de diseño

La **plataforma** (código) y el **contenido** (datos) están desacoplados. El currículo se administra desde el CMS y se guarda en PostgreSQL; el repositorio solo contiene un paquete de contenido para el arranque inicial. Añadir tracks, lecciones o recursos no requiere modificar código.

## Stack planificado

Laravel 13 · Inertia 3 · React 19 + TypeScript · Tailwind CSS 4 · shadcn/ui · PostgreSQL 16 · Redis 7 · Pest 5 · Vitest · Playwright · Docker (infraestructura) · GitHub Actions.

El detalle y los motivos están en [`docs/architecture.md`](docs/architecture.md).

## Documentación

| Documento | Contenido |
|---|---|
| [spec/master-spec.md](docs/spec/master-spec.md) | Especificación maestra (documento rector) |
| [product-discovery.md](docs/product-discovery.md) | Propósito, usuarios, alcance, riesgos y resolución de contradicciones de la spec |
| [architecture.md](docs/architecture.md) | Módulos, motor de progreso, publicación, seguridad y registro de decisiones (ADR) |
| [database.md](docs/database.md) | Modelo de datos: ERD, tablas, constraints e índices |
| [frontend-architecture.md](docs/frontend-architecture.md) | Estructura React, grafo, renderizado de Markdown y design system |
| [content-architecture.md](docs/content-architecture.md) | Modelo de contenido, paquete, importador y contrato pedagógico |
| [curriculum.md](docs/curriculum.md) | Currículo inicial: tracks, lecciones, skills y proyectos |
| [roadmap.md](docs/roadmap.md) | Versiones, fases, puerta de validación y permisos |
| [progress.md](docs/progress.md) | Estado actual, próximos pasos, known issues y decisiones |

## Instalación

Se documentará al cerrar la Fase 3, **solo con comandos verificados** (§76 de la spec).
