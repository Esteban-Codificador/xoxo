# CLAUDE.md

Plataforma educativa **AI Engineer Roadmap** (Laravel 13 + Inertia 3 + React 19 + PostgreSQL 16). La especificación rectora es `docs/spec/master-spec.md`.

## Al iniciar cada sesión

1. Lee `docs/progress.md`: fase actual, siguiente paso, known issues y decisiones.
2. Continúa desde ahí. No rehagas trabajo validado.
3. Antes de cambiar la arquitectura, lee el ADR correspondiente en `docs/architecture.md` §14. Si lo contradices, añade un ADR nuevo; no lo cambies en silencio.
4. Al cerrar una fase, pasa la puerta de validación (`docs/roadmap.md` §2) y actualiza `docs/progress.md`.

## Reglas no negociables

- **El contenido es dato.** Ningún texto curricular va en componentes, controladores ni seeders PHP. Va en `content/<paquete>/` (bootstrap) o se crea desde el CMS.
- **No inventar URLs ni IDs de video.** Todo recurso debe pasar `content:verify-links` (en CI; este contenedor no tiene acceso a sitios externos).
- **Nada de funcionalidades simuladas**: ni botones sin backend, ni autenticación o admin falsos, ni lorem ipsum.
- Las reglas de negocio (estados, progreso, desbloqueo) viven en PHP (`app/Domain`). React muestra lo que recibe y no recalcula.
- Los textos de UI pasan por i18n (`resources/js/i18n`). Contenido en español; identificadores de código en inglés.
- El contenido enriquecido es RichContent (JSON de ProseMirror validado en el servidor), nunca HTML guardado. Videos por proveedor e ID, nunca iframes.
- Toda escritura se valida en un FormRequest, se autoriza en una Policy y se ejecuta en una Action.
- Tests contra PostgreSQL, no SQLite.

## Entorno cloud (Claude Code on the web)

- PostgreSQL y Redis no arrancan solos: `service postgresql start` y `redis-server --daemonize yes`.
- Composer cae a `git clone` porque la descarga dist desde GitHub está bloqueada. Es lento pero funciona.
- No hay daemon de Docker: `docker compose` no se puede probar aquí.
- Chromium para E2E: `/opt/pw-browsers` (no ejecutes `playwright install`).

## Comandos

Se completarán en la Fase 3, cuando existan y estén verificados.
