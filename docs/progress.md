# Progreso del proyecto

> **Protocolo de sesión (§82):** 1) leer este archivo; 2) determinar la fase; 3) continuar desde ahí; 4) no rehacer trabajo correcto; 5) pasar la puerta de validación ([roadmap §2](roadmap.md#2-puerta-de-validación-obligatoria-al-cerrar-cada-fase)); 6) actualizar este archivo.

**Fase actual:** Fase 3 cerrada. **Siguiente: Fase 4, Design system.**

Última actualización: 2026-09-25.

---

## Completed

| Fecha | Fase | Entregable |
|---|---|---|
| 2026-09-25 | F0 Inspección | Repositorio vacío verificado. Entorno inventariado. Limitaciones de red y de Docker documentadas. Versiones del stack verificadas |
| 2026-09-25 | F1 Discovery | `product-discovery.md` |
| 2026-09-25 | F2 Arquitectura | `architecture.md`, `database.md`, `frontend-architecture.md`, `content-architecture.md`, `curriculum.md` y `roadmap.md` |
| 2026-09-25 | Decisiones | D1–D7 confirmadas por el dueño del producto. D2 y D3 cambiadas (ADR-023 y ADR-024) |
| 2026-09-25 | **F3 Fundación** | Starter kit oficial (Laravel 13, Inertia 3, React 19, Fortify con 2FA y passkeys) sobre PostgreSQL y Redis. 13 migraciones con CHECKs, FKs e índices parciales. Modelos, enums, factories y *morph map* forzado. Roles y permisos (matriz de roadmap §4) sincronizados en una migración. `AuditLogger` con observers. `DependencyGraph`. RichContent (esquema, validador y conversor Markdown → RichContent). `LessonReadiness` + `PublishLesson` (versiones inmutables). Paquete de contenido: lector, validador e importador idempotente que respeta el CMS. Comandos `content:validate`, `content:import`, `content:verify-links` y `types:enums`. Paquete de muestra real: 2 tracks, 2 módulos, **6 lecciones de nivel B**, 3 skills y 7 recursos oficiales. `docker-compose.yml`, workflows `CI` y `Content`, hook de sesión y documentación (README, development, testing, content-authoring y contributing) |

## In Progress

Nada.

## Next (Fase 4: Design system, en este orden)

1. Tokens semánticos de estado (LOCKED…MASTERED) en `resources/css/app.css`, en claro y oscuro, con contraste AA verificado.
2. i18n tipado (`resources/js/i18n/es.ts`, `en.ts`, `t()`) y `lang/es` del backend, incluida la traducción de `validation.php` (hoy los mensajes del validador del paquete salen en inglés).
3. `AppLayout` y `AdminLayout` (sidebar y breadcrumbs) sobre los del starter; página de error de Inertia (403, 404, 419, 500 y 503).
4. Componentes de dominio con tests: `ProgressBar`, `StateBadge`, `EmptyState`, `ErrorState`, skeletons y `PageHeader`.
5. `RichContentRenderer` con los nodos compartidos (`Callout`, `CodeBlock` con Shiki, `MathBlock`/`MathInline` con KaTeX, `MermaidDiagram`, `VideoEmbed`) y sus tests, incluidos XSS y nodos desconocidos.
6. Puerta de validación y actualización de este archivo.

## Known Issues

| # | Problema | Impacto | Plan |
|---|---|---|---|
| KI-1 | El contenedor de desarrollo no tiene daemon de Docker | `docker-compose.yml` escrito, sin validar | Validar en la máquina del dueño. El README lo marca como no verificado |
| KI-2 | El contenedor no alcanza sitios externos | `content:verify-links` da "no concluyente" localmente | La verificación real ocurre en el workflow `Content` de CI |
| KI-3 | ~~La descarga dist de Composer desde GitHub está bloqueada~~ | — | **Resuelto:** el hook instala desde git y reconstruye phpstan desde su repositorio |
| KI-4 | `pgvector` no está instalado en el Postgres local | Ninguno hasta V2 | docker-compose y CI usan `pgvector/pgvector:pg16` |
| KI-5 | Integración de Pest Browser con el Chromium del entorno sin probar | Riesgo en E2E | Se valida en la Fase 5, cuando existan los flujos E2E. *Fallback*: `@playwright/test` con `executablePath` |
| KI-6 | ~~`cloud.google.com/architecture/mlops-…` redirige a `docs.cloud.google.com`~~ | — | **Resuelto:** CI confirmó el destino (HTTP 200) y la URL del paquete ahora es la canónica |

## Technical Debt

| # | Deuda | Cuándo se paga |
|---|---|---|
| TD-1 | Los mensajes de validación del framework salen en inglés (falta `lang/es/validation.php`) | Fase 4 |
| TD-2 | `axllent/mailpit:latest` sin versión fijada en docker-compose | Al validar KI-1 |
| TD-3 | Tooling pre-1.0 heredado del starter: vite-plus 0.3 y Wayfinder 0.1 | Revisar en cada actualización. El lockfile las fija |
| TD-4 | Borrar un archivo del paquete no elimina la entidad de la BD. Es intencional (la BD es la fuente de verdad), pero no se avisa | Fase 7, con `content:export`, que mostrará las diferencias |
| TD-5 | El esquema RichContent se valida solo en el servidor: aún no existe el editor TipTap | Fase 5b: un test de paridad entre los nombres de nodo del editor y la lista blanca del servidor |
| TD-6 | Las relaciones de una lección (skills, recursos, dependencias) no se versionan (R10) | Aceptado para V1 |

## Decisions

El registro completo está en [architecture.md §14](architecture.md#14-registro-de-decisiones-adr) (ADR-001 a ADR-027).

Decisiones confirmadas por el dueño del producto el 2026-09-25:

| # | Decisión | Resultado |
|---|---|---|
| D1 | Desbloqueo ADVISORY por defecto | ✅ Aceptada |
| D2 | Ruta "aplicaciones primero" | ❌ **Cambiada: cadena lineal estricta del §25** (ADR-024). El esquema conserva REQUIRED/RECOMMENDED |
| D3 | Markdown como único formato | ❌ **Cambiada: TipTap como editor principal sobre un documento estructurado y extensible** (ADR-023, reemplaza a ADR-005). El Markdown queda como formato de autoría del paquete |
| D4 | Contenido tras login en V1; SSR y SEO en la Fase 8 | ✅ Aceptada |
| D5 | Progreso de skill y track calculado | ✅ Aceptada |
| D6 | Sin `AiProviderInterface` en V1 | ✅ Aceptada |
| D7 | Dos niveles de profundidad del contenido (A/B) | ✅ Aceptada |

Decisiones técnicas tomadas durante la Fase 3 (sin impacto de producto):

- **ADR-025:** fuentes autoalojadas con `@fontsource`. El build ya no depende de fonts.bunny.net.
- **ADR-026:** roles y permisos sincronizados en una migración. Registrarse nunca depende de `db:seed`.
- **ADR-027:** `content:verify-links` (modo archivos) adelantado a la Fase 3 y ejecutado en CI.
- El modelo de recursos externos se llama `ExternalResource` (tabla `resources`): "Resource" chocaba con el tipo `resource` de PHP y Pint lo reescribía.
- `users.username` usa `varchar` con un CHECK de minúsculas en lugar de `citext` (una extensión menos).

## Validation Log

| Fase | Tests | Lint | Types | Build | Migraciones | Contenido | Notas |
|---|---|---|---|---|---|---|---|
| F0–F2 | N/A | N/A | N/A | N/A | N/A | Script ad hoc: 72 skills sin referencias rotas ni ciclos | Solo documentación. 10/10 diagramas Mermaid y 50/50 enlaces internos válidos |
| F3 | **Pest 150/150** (349 aserciones) sobre PostgreSQL 16 · **Vitest 5/5** | Pint ✅ · oxlint + oxfmt ✅ | PHPStan nivel 7: 0 errores ✅ · tsc ✅ · enums sincronizados ✅ | Vite ✅ | fresh + seed + rollback + migrate ✅ · importación idempotente comprobada | `content:validate`: 0 problemas · enlaces: 7 no concluyentes (sin red) → CI | Hook validado desde frío (servicios detenidos, sin vendor de phpstan ni `.env`): 18 s. Prueba de humo HTTP: `/up` y `/login` 200, fuente servida localmente, `lang="es"`. `composer run dev` verificado. **CI en GitHub Actions:** primer run con todo en verde salvo Vitest (el plugin de Laravel se niega a arrancar con `CI=true`); corregido cargando solo React bajo Vitest. **Workflow Content: 7/7 URLs verificadas** (6 OK y 1 redirigida, luego actualizada) |
