# Testing

## Principios

1. **PostgreSQL real en los tests** (ADR-003). El esquema depende de CHECKs, índices parciales, jsonb y FKs con `RESTRICT`; SQLite no las probaría. La base es `ai_roadmap_testing` (`phpunit.xml`) y cada test corre en una transacción (`RefreshDatabase`).
2. **Se prueba la regla, no el mock.** Las restricciones de integridad se prueban contra la BD. Los flujos (importar, publicar) se prueban de punta a punta con datos reales.
3. **Sin llamadas de red** en los tests: `Http::preventStrayRequests()` en los que usan HTTP, con `Http::fake()`.
4. **Un test que falla es un bug hasta que se demuestre lo contrario.** No se desactiva ni se marca como *flaky* para pasar CI.

## Niveles

| Nivel | Herramienta | Ubicación | Qué cubre hoy |
|---|---|---|---|
| Unitario (PHP) | Pest | `tests/Unit` | `DependencyGraph`, `RichContentValidator`, conversor Markdown → RichContent, paridad de `allowlist.json` con `RichContentSchema` (y el documento con todos los nodos es válido) |
| Feature (PHP) | Pest + PostgreSQL | `tests/Feature` | Autenticación (starter), restricciones del esquema, roles y permisos, auditoría, publicación y versiones, validador e importador de paquetes, comandos `content:*`, verificación de enlaces, sincronía de enums, dashboard del estudiante (solo contenido visible), motor de estados (reglas de desbloqueo, porcentajes, ADVISORY/STRICT, MASTERED con evidencia y número fijo de consultas), acciones de progreso (idempotencia, versión leída, feed, 403 en STRICT), páginas de track y de lección (versión publicada, orden de estudio, anterior/siguiente, prerrequisitos, skills y recursos publicados, 404 para todo lo no visible), resumen del admin y acceso por rol, CMS de lecciones (lista, edición y publicación por rol: el instructor solo edita las suyas y no publica; guardar no cambia lo que lee el estudiante; RichContent fuera de la lista blanca rechazado; requisitos incumplidos; publicar sin cambios no crea versión), CMS de tracks y módulos (roles, slug único por roadmap, transiciones de estado y su auditoría, prerrequisitos con ciclo, auto-referencia u otro roadmap rechazados, orden de módulos, módulo retirado oculta sus lecciones), relaciones de la lección (skills, prerrequisitos del mismo roadmap, recursos en orden con su nota, ciclos rechazados, auditoría con solo lo que cambió), slug con redirección, archivar/restaurar y re-publicar sin versión duplicada, skills y recursos (roles y autoría, https y URL única, verificación del enlace al crear o cambiar la URL y bajo demanda sin auditarla, estado visible para el estudiante, filtros, ciclos de skills), `content:verify-resources` y su programación nocturna, páginas de error de Inertia, idioma por usuario y galería local |
| Frontend | Vitest + React Testing Library | `resources/js/**/*.test.{ts,tsx}` | `t()` y paridad de diccionarios, contraste AA de los tokens (lee `app.css`), `StateBadge`, `ProgressBar`, estados de pantalla, frases de auditoría `RichContentRenderer` (29 casos: marcas, enlaces peligrosos, nodos desconocidos, tablas, callouts, video, Shiki y KaTeX reales, Mermaid simulado) `DependencyEditor`, `SkillsEditor`, `ResourcesEditor`, `StatusActions` (confirmación antes de enviar) y el editor TipTap (esquema idéntico a `allowlist.json`, ida y vuelta del documento con todos los nodos, lenguajes de código, IDs de YouTube, `onChange` solo tras una edición real) |
| E2E | Pest Browser (Playwright) | `tests/Browser` | Registro; recorrido del estudiante sobre el paquete real (empezar desde el dashboard, completar, progreso del track, desbloqueo, desmarcar); roadmap (grafo, clic en nodo, panel, vista de lista); 404; flujo editorial (un editor cambia un campo y el cuerpo con TipTap usando el teclado, guarda, publica la versión 2 y un estudiante la lee) estudiante sin acceso al CMS y tracks (editar un campo, ciclo de prerrequisitos rechazado, el estudiante ve el cambio) relaciones (añadir una skill que el estudiante ve sin publicar) y recursos (crear, publicar, asociar y verlo como estudiante; la verificación del enlace usa `Http::fake`) |

## Comandos

```bash
php artisan test                                   # toda la suite PHP
./vendor/bin/pest tests/Feature/Content            # un directorio
./vendor/bin/pest --filter="is idempotent"         # un test por nombre
npm run test                                       # Vitest una vez
composer test:browser                              # E2E (Pest Browser): scripts/test-browser.sh
npx vp test watch                                  # Vitest en modo watch
```

## Datos de prueba

- **Factories** (`database/factories`) para modelos. El texto que generan es ficticio y solo vive en los tests; nunca es contenido del producto. Las URLs usan el dominio reservado `example.test`.
- **`Tests\Support\ContentPackageFixture`** escribe un paquete de contenido válido en un directorio temporal para romper exactamente una cosa por test.
- **`publishableLesson()`** (en `tests/Pest.php`) crea una lección que cumple el contrato de publicación, con su módulo y track publicados, o dentro del módulo que se le pase.
- Una ruta que solo existe en local (la galería) se prueba registrando su controlador en una ruta temporal dentro del test.

## Revisión visual

La puerta de validación incluye capturas con Playwright (Chromium de `/opt/pw-browsers` en la nube) de las pantallas tocadas, en claro, oscuro y móvil. El script de la Fase 4 inicia sesión con los usuarios de ejemplo, recorre bienvenida, login, dashboard, admin, 403, 404 y las 6 lecciones en la galería, y comprueba que no haya errores de consola ni desbordamiento horizontal en móvil. Se versionará en `tests/Browser` cuando llegue Pest Browser (Fase 5).

## E2E

`composer test:browser` (o `scripts/test-browser.sh`) corre la suite `Browser`, que **no** forma parte de `php artisan test` (`defaultTestSuite` en `phpunit.xml`). El script resuelve dos problemas del entorno: cierra el servidor de Playwright que Pest deja huérfano (si no, cualquier tubería o CI queda esperando) y, cuando el Chromium preinstalado es de otra revisión que la que espera Playwright (la imagen de Claude Code en la nube), crea un *shim* de rutas en `/tmp`. En una máquina normal basta con `npx playwright install --only-shell chromium`. Si un test falla, la captura queda en `tests/Browser/Screenshots` (ignorado por git).

## CI

`.github/workflows/ci.yml` ejecuta, en este orden: build, Pint, oxlint/oxfmt, PHPStan, tsc, sincronía de enums, migraciones (fresh + seed + rollback + migrate), Pest, Vitest y E2E (instala Chromium headless y corre `scripts/test-browser.sh`), con servicios PostgreSQL 16 y Redis 7.

`.github/workflows/content.yml` valida el paquete y verifica las URLs de los recursos cuando cambia `content/` (bloquea ante 404/410) y cada semana (solo informa).
