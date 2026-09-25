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
| Feature (PHP) | Pest + PostgreSQL | `tests/Feature` | Autenticación (starter), restricciones del esquema, roles y permisos, auditoría, publicación y versiones, validador e importador de paquetes, comandos `content:*`, verificación de enlaces, sincronía de enums, dashboard del estudiante (solo contenido visible), páginas de track y de lección (versión publicada, orden de estudio, anterior/siguiente, prerrequisitos, skills y recursos publicados, 404 para todo lo no visible), resumen del admin y acceso por rol, páginas de error de Inertia, idioma por usuario y galería local |
| Frontend | Vitest + React Testing Library | `resources/js/**/*.test.{ts,tsx}` | `t()` y paridad de diccionarios, contraste AA de los tokens (lee `app.css`), `StateBadge`, `ProgressBar`, estados de pantalla, frases de auditoría y `RichContentRenderer` (29 casos: marcas, enlaces peligrosos, nodos desconocidos, tablas, callouts, video, Shiki y KaTeX reales, Mermaid simulado) |
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
- **`publishableLesson()`** (en `tests/Pest.php`) crea una lección que cumple el contrato de publicación, con su módulo y track publicados, o dentro del módulo que se le pase.
- Una ruta que solo existe en local (la galería) se prueba registrando su controlador en una ruta temporal dentro del test.

## Revisión visual

La puerta de validación incluye capturas con Playwright (Chromium de `/opt/pw-browsers` en la nube) de las pantallas tocadas, en claro, oscuro y móvil. El script de la Fase 4 inicia sesión con los usuarios de ejemplo, recorre bienvenida, login, dashboard, admin, 403, 404 y las 6 lecciones en la galería, y comprueba que no haya errores de consola ni desbordamiento horizontal en móvil. Se versionará en `tests/Browser` cuando llegue Pest Browser (Fase 5).

## CI

`.github/workflows/ci.yml` ejecuta, en este orden: build, Pint, oxlint/oxfmt, PHPStan, tsc, sincronía de enums, migraciones (fresh + seed + rollback + migrate), Pest y Vitest, con servicios PostgreSQL 16 y Redis 7.

`.github/workflows/content.yml` valida el paquete y verifica las URLs de los recursos cuando cambia `content/` (bloquea ante 404/410) y cada semana (solo informa).
