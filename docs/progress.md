# Progreso del proyecto

> **Protocolo de sesión (§82):** 1) leer este archivo; 2) determinar la fase; 3) continuar desde ahí; 4) no rehacer trabajo correcto; 5) pasar la puerta de validación ([roadmap §2](roadmap.md#2-puerta-de-validación-obligatoria-al-cerrar-cada-fase)); 6) actualizar este archivo.

**Fase actual:** Fase 2 cerrada. **Siguiente: Fase 3, Fundación y modelo de datos**, pendiente de la revisión de decisiones por el dueño del producto.

Última actualización: 2026-09-25.

---

## Completed

| Fecha | Fase | Entregable |
|---|---|---|
| 2026-09-25 | F0 Inspección | Repositorio vacío verificado. Entorno inventariado (PHP 8.4, Node 22, Postgres 16, Redis 7, Chromium). Limitaciones de red y de Docker documentadas. Versiones vigentes del stack verificadas en Packagist y npm. Starter kit oficial inspeccionado |
| 2026-09-25 | F1 Discovery | `docs/product-discovery.md`: propósito, personas, 22 casos de uso, alcance por versión, fuera de alcance, 14 contradicciones de la spec resueltas, 11 riesgos, supuestos y métricas |
| 2026-09-25 | F2 Arquitectura | `architecture.md` (módulos, motor de progreso, publicación, seguridad, 22 ADR), `database.md` (ERD, tablas, constraints, índices, enums), `frontend-architecture.md`, `content-architecture.md` (paquete, importador, contrato pedagógico, verificación de enlaces), `curriculum.md` (17 tracks, 50 módulos, 178 lecciones planificadas, 72 skills y 10 proyectos, sin ciclos ni referencias rotas: verificado por script), `roadmap.md` (fases, puerta de validación, matriz de permisos) |
| 2026-09-25 | — | Spec maestra versionada en `docs/spec/master-spec.md`. `CLAUDE.md` con el protocolo de sesión |

## In Progress

Nada. A la espera de la validación de las decisiones de mayor impacto (ver *Decisions* abajo).

## Next (Fase 3, en este orden)

1. Instalar `laravel/react-starter-kit` en un directorio temporal y fusionarlo en la raíz, preservando `docs/`, `CLAUDE.md` y `README.md`.
2. Configurar PostgreSQL y Redis, `.env.example`, Pest 5, Vitest + RTL y Larastan.
3. Migraciones de la Fase 3, modelos, enums, factories, morph map y `types:enums`.
4. Roles y permisos (spatie) con su seeder. `AuditLogger`.
5. `DependencyGraph` con tests.
6. `content:validate` + `content:import` con un paquete de muestra y tests de idempotencia.
7. `docker-compose.yml`, GitHub Actions y hook SessionStart.
8. Puerta de validación y README con la instalación verificada.

## Known Issues

| # | Problema | Impacto | Plan |
|---|---|---|---|
| KI-1 | El contenedor de desarrollo **no tiene daemon de Docker** | `docker compose` no se puede validar aquí | Validar en CI o en la máquina del dueño. Se marca como "no verificado" en el README hasta entonces |
| KI-2 | El contenedor **no alcanza sitios externos** (salvo Packagist, npm y `git clone` de GitHub) | Las URLs de recursos y videos no se pueden verificar al escribirlas | Job `content` en CI + `content:verify-links` programado. Un 404 bloquea el merge |
| KI-3 | La descarga *dist* de Composer desde GitHub está bloqueada (403) | `composer install` más lento (cae a `git clone`) | Aceptable. Considerar `preferred-install: source` en este entorno si molesta |
| KI-4 | `pgvector` no está instalado en el Postgres local | Ninguno hasta V2 | docker-compose usa la imagen `pgvector/pgvector:pg16` |
| KI-5 | Chromium del entorno (build 1194) posiblemente distinto del que espera el Playwright más reciente | Riesgo en E2E | Validar en la Fase 3. *Fallback* a `@playwright/test` con `executablePath` |

## Technical Debt

Ninguna todavía (no hay código).

## Decisions

El registro completo está en [architecture.md §14](architecture.md#14-registro-de-decisiones-adr) (ADR-001 a ADR-024).

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

## Validation Log

| Fase | Tests | Lint | Types | Build | Migraciones | Contenido | Notas |
|---|---|---|---|---|---|---|---|
| F0–F2 | N/A | N/A | N/A | N/A | N/A | Script ad hoc: 72 skills sin referencias rotas ni ciclos; 178 lecciones, 20 A, 135 B y 23 backlog | Solo documentación |
