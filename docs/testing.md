# Testing

## Principios

1. **PostgreSQL real en los tests** (ADR-003). El esquema depende de CHECKs, índices parciales, jsonb y FKs con `RESTRICT`; SQLite no las probaría. La base es `ai_roadmap_testing` (`phpunit.xml`) y cada test corre en una transacción (`RefreshDatabase`).
2. **Se prueba la regla, no el mock.** Las restricciones de integridad se prueban contra la BD. Los flujos (importar, publicar) se prueban de punta a punta con datos reales.
3. **Sin llamadas de red** en los tests: `Http::preventStrayRequests()` en los que usan HTTP, con `Http::fake()`.
4. **Un test que falla es un bug hasta que se demuestre lo contrario.** No se desactiva ni se marca como *flaky* para pasar CI.

## Niveles

| Nivel | Herramienta | Ubicación | Qué cubre hoy |
|---|---|---|---|
| Unitario (PHP) | Pest | `tests/Unit` | `DependencyGraph`, `RichContentValidator`, conversor Markdown → RichContent |
| Feature (PHP) | Pest + PostgreSQL | `tests/Feature` | Autenticación (starter), restricciones del esquema, roles y permisos, auditoría, publicación y versiones, validador e importador de paquetes, comandos `content:*`, verificación de enlaces, sincronía de enums |
| Frontend | Vitest + React Testing Library | `resources/js/**/*.test.tsx` | Harness activo (hook y componente del starter). Los componentes de dominio se prueban desde la Fase 4 |
| E2E | Pest Browser (Playwright) | `tests/Browser` (Fase 5) | Flujos de §78: estudiante y editorial |

## Comandos

```bash
php artisan test                                   # toda la suite PHP
./vendor/bin/pest tests/Feature/Content            # un directorio
./vendor/bin/pest --filter="is idempotent"         # un test por nombre
npm run test                                       # Vitest una vez
npx vp test watch                                  # Vitest en modo watch
```

## Datos de prueba

- **Factories** (`database/factories`) para modelos. El texto que generan es ficticio y solo vive en los tests; nunca es contenido del producto. Las URLs usan el dominio reservado `example.test`.
- **`Tests\Support\ContentPackageFixture`** escribe un paquete de contenido válido en un directorio temporal para romper exactamente una cosa por test.

## CI

`.github/workflows/ci.yml` ejecuta, en este orden: build, Pint, oxlint/oxfmt, PHPStan, tsc, sincronía de enums, migraciones (fresh + seed + rollback + migrate), Pest y Vitest, con servicios PostgreSQL 16 y Redis 7.

`.github/workflows/content.yml` valida el paquete y verifica las URLs de los recursos cuando cambia `content/` (bloquea ante 404/410) y cada semana (solo informa).
