# AI Engineer Roadmap

Plataforma educativa para formar **AI Engineers** de forma progresiva, desde los fundamentos de programación hasta LLM Engineering, RAG, agentes, MLOps y AI System Design. Reúne un roadmap visual, un grafo de conocimiento, un LMS, aprendizaje basado en proyectos, seguimiento de progreso con evidencia y un CMS educativo.

El nombre es provisional.

## Objetivo

Llevar a una persona desde "sé programar" hasta "diseño, evalúo y opero sistemas de IA en producción", con **evidencia verificable** (evaluaciones y proyectos) y no solo con contenido consumido. El detalle está en [`docs/product-discovery.md`](docs/product-discovery.md).

## Estado

**V1 completa (Fase 5); Fase 6 en curso.** El estudiante se registra, recorre el roadmap (grafo o lista), estudia lecciones con código resaltado, fórmulas, diagramas y video, las completa y ve su progreso, los desbloqueos, recomendaciones de qué seguir y sus skills. El equipo editorial trabaja en `/admin`: crea, edita, revisa y publica tracks, módulos, lecciones (con TipTap, imágenes, versiones y diff), skills y recursos, gestiona roles y consulta la auditoría; `php artisan content:export` lleva lo editado al repositorio, imágenes incluidas. En curso: Fase 6 (práctica y evaluación); hecha la subida de imágenes, sigue video con oEmbed. En local, `/_dev/design-system` muestra los componentes y las lecciones reales.

El estado vivo está en [`docs/progress.md`](docs/progress.md). Capturas: pendientes (Fase 9, pulido).

## Principio de diseño

La **plataforma** (código) y el **contenido** (datos) están desacoplados. El currículo vive en PostgreSQL y se administra desde el CMS. El directorio `content/` es un paquete de arranque que se importa con `php artisan content:import`: añadir tracks o lecciones no requiere tocar código.

## Stack

Laravel 13 · Inertia 3 · React 19 + TypeScript · Tailwind CSS 4 · shadcn/ui · TipTap (editor) · PostgreSQL 16 · Redis 7 · Pest 5 · Vitest · GitHub Actions. Base: el starter kit oficial de React para Laravel.

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

> **Docker:** `docker-compose.yml` levanta **solo la infraestructura**; la app corre en tu máquina. Este archivo **no se pudo validar** en el entorno donde se desarrolló (sin daemon de Docker). Todos los demás comandos sí están verificados.

### Sin Docker

La única pieza obligatoria es **PostgreSQL 16**. Redis y Mailpit son opcionales en desarrollo: Laravel guarda sesiones, caché y cola en la misma base de datos (las tablas `sessions`, `cache` y `jobs` ya vienen en las migraciones) y los correos pueden ir al log.

Instala PostgreSQL:

| Sistema | Comando |
|---|---|
| Windows | `winget install PostgreSQL.PostgreSQL.16` |
| macOS | `brew install postgresql@16 && brew services start postgresql@16` |
| Debian/Ubuntu | `sudo apt install postgresql-16` |

Crea el usuario y las dos bases (la de tests es obligatoria para `php artisan test`):

```sql
-- psql -U postgres
CREATE USER ai_roadmap WITH PASSWORD 'secret' CREATEDB;
CREATE DATABASE ai_roadmap OWNER ai_roadmap;
CREATE DATABASE ai_roadmap_testing OWNER ai_roadmap;
```

Y ajusta estas cuatro líneas del `.env` para no depender de Redis ni de un servidor SMTP:

```dotenv
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER=log          # los correos quedan en storage/logs/laravel.log
```

El resto de la instalación es idéntico, saltándote `docker compose up -d`.

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
php artisan content:export             # lleva lo editado en el CMS a content/ (revisa el diff y haz commit)
php artisan content:verify-links       # comprueba las URLs del paquete de archivos (lo corre CI)
php artisan content:verify-resources   # comprueba las URLs guardadas en la BD (incluidas las del CMS) y registra su estado
```

En producción hacen falta dos procesos además de la web: el *scheduler* (`* * * * * php artisan schedule:run` en cron; verifica los recursos cada noche a las 03:30) y un worker de cola (`php artisan queue:work`), que atiende "Verificar ahora" y la comprobación al guardar un recurso. En local, `composer run dev` ya levanta el worker.

Cómo escribir contenido: [`docs/content-authoring.md`](docs/content-authoring.md).

## Testing y calidad

```bash
php artisan test          # Pest sobre PostgreSQL (base ai_roadmap_testing)
npm run test              # Vitest + Testing Library
composer test:browser     # E2E en un navegador real (npx playwright install --only-shell chromium la primera vez)
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
