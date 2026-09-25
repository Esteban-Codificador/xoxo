# AI Engineer Roadmap

Plataforma educativa para formar **AI Engineers** de forma progresiva, desde los fundamentos de programación hasta LLM Engineering, RAG, agentes, MLOps y AI System Design. Reúne un roadmap visual, un grafo de conocimiento, un LMS, aprendizaje basado en proyectos, seguimiento de progreso con evidencia y un CMS educativo.

El nombre es provisional.

## Objetivo

Llevar a una persona desde "sé programar" hasta "diseño, evalúo y opero sistemas de IA en producción", con **evidencia verificable** (evaluaciones y proyectos) y no solo con contenido consumido. El detalle está en [`docs/product-discovery.md`](docs/product-discovery.md).

## Estado

**Fase 4 (design system) completada.** Hay autenticación, esquema de datos, publicación versionada, auditoría, roles, un importador de contenido con un currículo de muestra, la interfaz en español (i18n tipado), un dashboard con los tracks publicados, un resumen de contenido para el equipo editorial y el renderizador de lecciones (código resaltado, fórmulas, diagramas y video). **Aún no hay progreso del estudiante, páginas de lección ni CMS**: llegan en la Fase 5. En local, `/_dev/design-system` muestra los componentes y las lecciones reales.

El estado vivo está en [`docs/progress.md`](docs/progress.md). Las capturas se añadirán cuando existan pantallas propias (Fase 5).

## Principio de diseño

La **plataforma** (código) y el **contenido** (datos) están desacoplados. El currículo vive en PostgreSQL y se administra desde el CMS. El directorio `content/` es un paquete de arranque que se importa con `php artisan content:import`: añadir tracks o lecciones no requiere tocar código.

## Stack

Laravel 13 · Inertia 3 · React 19 + TypeScript · Tailwind CSS 4 · shadcn/ui · TipTap (editor, Fase 5) · PostgreSQL 16 · Redis 7 · Pest 5 · Vitest · GitHub Actions. Base: el starter kit oficial de React para Laravel.

Motivos y alternativas: [`docs/architecture.md`](docs/architecture.md).

## Instalación

Requisitos: PHP 8.4 (con `pdo_pgsql`, `redis`, `intl`), Composer 2, Node 22, PostgreSQL 16 y Redis 7.

```bash
git clone https://github.com/Esteban-Codificador/xoxo.git ai-engineer-roadmap
cd ai-engineer-roadmap

cp .env.example .env
docker compose up -d          # PostgreSQL (con pgvector), Redis y Mailpit

composer install
npm install
php artisan key:generate
php artisan migrate --seed    # esquema, roles, usuarios demo y currículo inicial
npm run build

composer run dev              # servidor, cola, logs y Vite: http://localhost:8000
```

> **Docker:** `docker-compose.yml` levanta **solo la infraestructura**; la app corre en tu máquina. Este archivo **no se pudo validar** en el entorno donde se desarrolló (sin daemon de Docker). Todos los demás comandos sí están verificados. Si no usas Docker, crea la base `ai_roadmap` (y `ai_roadmap_testing` para los tests) con el usuario `ai_roadmap` y la contraseña `secret`, o ajusta `.env`.

### Usuarios de demostración

`php artisan migrate --seed` crea un usuario por rol (solo fuera de producción), todos con la contraseña `password`:

| Email | Rol |
|---|---|
| `admin@ai-roadmap.test` | ADMIN |
| `editor@ai-roadmap.test` | EDITOR |
| `instructor@ai-roadmap.test` | INSTRUCTOR |
| `student@ai-roadmap.test` | STUDENT |

Los correos (verificación, recuperación de contraseña) llegan a Mailpit: http://localhost:8025.

## Contenido

```bash
php artisan content:validate           # valida content/ai-engineer sin tocar la BD
php artisan content:import --dry-run   # muestra qué crearía o actualizaría
php artisan content:import             # importa (idempotente; respeta lo editado en el CMS)
php artisan content:verify-links       # comprueba que las URLs de los recursos existan
```

Cómo escribir contenido: [`docs/content-authoring.md`](docs/content-authoring.md).

## Testing y calidad

```bash
php artisan test          # Pest sobre PostgreSQL (base ai_roadmap_testing)
npm run test              # Vitest + Testing Library
composer lint:check       # Pint
npm run check             # oxlint + oxfmt
composer types:check      # PHPStan (nivel 7)
npm run types:check       # tsc
php artisan types:enums --check
```

Estrategia y convenciones: [`docs/testing.md`](docs/testing.md). CI ejecuta todo lo anterior en cada push ([`.github/workflows/ci.yml`](.github/workflows/ci.yml)).

## Documentación

| Documento | Contenido |
|---|---|
| [spec/master-spec.md](docs/spec/master-spec.md) | Especificación maestra (documento rector) |
| [product-discovery.md](docs/product-discovery.md) | Propósito, usuarios, alcance, riesgos y resolución de contradicciones de la spec |
| [architecture.md](docs/architecture.md) | Módulos, motor de progreso, publicación, seguridad y registro de decisiones (ADR) |
| [database.md](docs/database.md) | Modelo de datos: ERD, tablas, constraints e índices |
| [frontend-architecture.md](docs/frontend-architecture.md) | Estructura React, grafo, contenido enriquecido y design system |
| [content-architecture.md](docs/content-architecture.md) | Modelo de contenido, paquete, importador y contrato pedagógico |
| [content-authoring.md](docs/content-authoring.md) | Guía práctica para escribir contenido |
| [curriculum.md](docs/curriculum.md) | Currículo inicial: tracks, lecciones, skills y proyectos |
| [roadmap.md](docs/roadmap.md) | Versiones, fases, puerta de validación y permisos |
| [development.md](docs/development.md) | Entorno local, comandos y convenciones de código |
| [testing.md](docs/testing.md) | Estrategia de pruebas |
| [contributing.md](docs/contributing.md) | Flujo de trabajo y criterios para integrar cambios |
| [progress.md](docs/progress.md) | Estado actual, próximos pasos, known issues y decisiones |
