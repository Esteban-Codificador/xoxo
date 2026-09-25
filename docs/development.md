# Desarrollo

## Entorno local

Instalación: ver el [README](../README.md#instalación). Resumen de piezas:

| Pieza | Dónde corre | Configuración |
|---|---|---|
| App Laravel, Vite, cola y logs | Host (`composer run dev`) | `.env` |
| PostgreSQL 16 (+ pgvector), Redis 7, Mailpit | `docker compose up -d` | `docker-compose.yml` |
| Base de tests `ai_roadmap_testing` | La crea `docker/pgsql/create-testing-database.sql` al iniciar el volumen | `phpunit.xml` |

### Claude Code en la web

El hook `.claude/hooks/session-start.sh` prepara cada sesión en la nube: arranca PostgreSQL y Redis, crea las bases, instala dependencias, crea `.env`, migra y compila los assets. Particularidad de ese entorno: la API de GitHub está bloqueada, así que Composer instala desde git y el hook reconstruye desde su repositorio los paquetes que solo se publican como zip de GitHub (hoy, `phpstan/phpstan`).

## Comandos habituales

| Tarea | Comando |
|---|---|
| Levantar todo (servidor, cola, logs, Vite) | `composer run dev` |
| Migrar y cargar datos | `php artisan migrate --seed` |
| Rehacer la BD desde cero | `php artisan migrate:fresh --seed` |
| Regenerar tipos TS de los enums | `php artisan types:enums` |
| Regenerar rutas tipadas (Wayfinder) | `php artisan wayfinder:generate --with-form` (también lo hace el build) |
| Formatear PHP / JS | `composer lint` / `npm run check:fix` |
| Revisar el design system y una lección real (solo `APP_ENV=local`) | http://localhost:8000/_dev/design-system |

## Convenciones de código

- **Dónde va cada cosa:** reglas de negocio en `app/Domain/<Módulo>` (Actions, servicios y value objects); persistencia en `app/Models`; validación HTTP en FormRequests; autorización en Policies. Ver [architecture §5](architecture.md#5-estructura-de-directorios-backend).
- **Enums:** los enums PHP de `app/Enums` son la fuente de verdad. Si agregas o cambias un caso, ejecuta `php artisan types:enums`; CI falla si `resources/js/types/enums.ts` queda desactualizado.
- **Contenido:** nunca va texto curricular en código ni en seeders; va en `content/` o en el CMS.
- **Contenido enriquecido:** siempre `RichContent` (validado). No se guarda HTML.
- **Migraciones:** reversibles, con CHECKs e índices en la BD. No referencies enums PHP dentro de una migración (los valores se escriben literales para que la migración no cambie con el código).
- **Modelos:** modo estricto activo fuera de producción (lazy loading prohibido y atributos no rellenables rechazados). Usa `with()` o `loadMissing()`.
- **Idioma:** textos de UI y mensajes al usuario en español (y preparados para i18n); identificadores, commits y comentarios de código en inglés.
- **Textos de interfaz:** siempre con `t('clave')` desde `resources/js/i18n`. Una clave nueva va en `es.ts` y en `en.ts` (tsc y un test fallan si falta en uno). Si el orden de las palabras cambia entre idiomas, la clave es la frase completa con `{parámetros}`, no fragmentos concatenados.
- **Colores:** solo tokens de `resources/css/app.css` (`bg-state-completed-soft`, `text-callout-tip`…), nunca literales. Un token nuevo que se usa como texto se añade a `resources/js/test/contrast.test.ts`.
- **Modelos:** si una columna tiene `default` en la BD y el código la lee justo después de `create()`, refleja ese valor en `$attributes` del modelo; con el modo estricto, leer un atributo no cargado lanza una excepción.

## Estructura del paquete de contenido

Ver [content-architecture §6](content-architecture.md#6-formato-del-paquete-de-contenido) y [content-authoring.md](content-authoring.md).
