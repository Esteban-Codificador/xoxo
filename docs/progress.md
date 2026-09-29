# Progreso del proyecto

> **Protocolo de sesión (§82):** 1) leer este archivo; 2) determinar la fase; 3) continuar desde ahí; 4) no rehacer trabajo correcto; 5) pasar la puerta de validación ([roadmap §2](roadmap.md#2-puerta-de-validación-obligatoria-al-cerrar-cada-fase)); 6) actualizar este archivo.

**Fase actual:** Fase 5b (CMS) en curso: pasos 1 a 3 hechos (lecciones, tracks, módulos y relaciones). **Siguiente: crear contenido nuevo y el flujo de revisión (paso 4).**

Última actualización: 2026-09-29.

---

## Completed

| Fecha | Fase | Entregable |
|---|---|---|
| 2026-09-25 | F0 Inspección | Repositorio vacío verificado. Entorno inventariado. Limitaciones de red y de Docker documentadas. Versiones del stack verificadas |
| 2026-09-25 | F1 Discovery | `product-discovery.md` |
| 2026-09-25 | F2 Arquitectura | `architecture.md`, `database.md`, `frontend-architecture.md`, `content-architecture.md`, `curriculum.md` y `roadmap.md` |
| 2026-09-25 | Decisiones | D1–D7 confirmadas por el dueño del producto. D2 y D3 cambiadas (ADR-023 y ADR-024) |
| 2026-09-25 | **F3 Fundación** | Starter kit oficial (Laravel 13, Inertia 3, React 19, Fortify con 2FA y passkeys) sobre PostgreSQL y Redis. 13 migraciones con CHECKs, FKs e índices parciales. Modelos, enums, factories y *morph map* forzado. Roles y permisos (matriz de roadmap §4) sincronizados en una migración. `AuditLogger` con observers. `DependencyGraph`. RichContent (esquema, validador y conversor Markdown → RichContent). `LessonReadiness` + `PublishLesson` (versiones inmutables). Paquete de contenido: lector, validador e importador idempotente que respeta el CMS. Comandos `content:validate`, `content:import`, `content:verify-links` y `types:enums`. Paquete de muestra real: 2 tracks, 2 módulos, **6 lecciones de nivel B**, 3 skills y 7 recursos oficiales. `docker-compose.yml`, workflows `CI` y `Content`, hook de sesión y documentación (README, development, testing, content-authoring y contributing) |
| 2026-09-25 | **F4 Design system** | Tokens de estado y de callout en claro y oscuro con **test de contraste AA** que lee `app.css`. i18n tipado (`es` fuente, `en` con la misma forma) guiado por `<html lang>`, middleware `SetLocale` y `lang/es` (validación incluida). UI del starter traducida. `AppLayout` y `AdminLayout` con navegación real según permisos. Página de error de Inertia (403/404/419/500/503). Dashboard del estudiante (tracks publicados con lecciones visibles) y resumen del admin (contenido por estado, enlaces y actividad). Componentes de dominio con tests. **`RichContentRenderer`** con Shiki, KaTeX, Mermaid, video, tablas, callouts y enlaces seguros. Galería local `/_dev/design-system` con lecciones reales. Revisión visual con Playwright en claro, oscuro y móvil |
| 2026-09-25 | **F5a · camino de lectura** | Páginas de track y de lección sobre la versión publicada, dashboard enlazado, visibilidad con 404, `TrackOutline` para el orden de estudio. El dueño del producto ya puede revisar las 6 lecciones dentro de la plataforma |
| 2026-09-29 | **F5a · E2E y roadmap visual** | Suite `tests/Browser` con Pest Browser sobre el paquete real (registro, recorrido completo con progreso y desbloqueo, roadmap) y en CI. Roadmap visual: grafo de tracks (React Flow + dagre) con estados, progreso y dependencias, panel lateral con requisitos, módulos, lecciones y "Continuar", vista de lista por niveles (móvil y accesibilidad) y acceso por teclado. "Roadmap" en el menú lateral y en el dashboard |
| 2026-09-29 | **F5b · relaciones, slug y estado de la lección** | Pestañas **Contenido / Relaciones** en la lección. **Relaciones** (sin versiones, se ven de inmediato): skills con peso 1–5, prerrequisitos del mismo roadmap con `DependencyEditor` (rechaza ciclos con la ruta) y recursos ordenados (conserva la nota al reordenar). **Slug** editable con redirección a la nueva URL. **Archivar y restaurar** la lección con confirmación; re-publicar una lección sin cambios reutiliza su versión en lugar de duplicarla, y una archivada se restaura antes de publicarse. Auditoría única por guardado con solo lo que cambió. E2E de relaciones |
| 2026-09-29 | **F5b · tracks y módulos** | `/admin/tracks` (por roadmap: estado, módulos, lecciones y prerrequisitos) y edición del track: datos, slug, dificultad, horas y descripción en TipTap (opcional). **Estado** de tracks y módulos (publicar, pasar a borrador, archivar, restaurar) con confirmación que explica qué verán los estudiantes y la acción de auditoría correcta. **Prerrequisitos** con `DependencyEditor` (necesario/recomendado y progreso mínimo), rechazando ciclos con la ruta legible. **Módulos**: edición en diálogo, estado y orden. `TrackPolicy` y `ModulePolicy` (instructor: solo lo suyo; publicar y archivar según permiso). E2E de tracks |
| 2026-09-29 | **F5b · editar y publicar lecciones** | `/admin/lessons` (lista por track con estado, versión y "cambios sin publicar") y edición de la copia de trabajo: formulario estructurado (título, resumen, por qué importa, objetivos, tipo, dificultad, minutos) y **cuerpo con TipTap** limitado a la lista blanca de RichContent, con barra de herramientas, callouts, tablas, fórmulas, diagramas Mermaid y videos por ID. `UpdateLessonRequest` + `LessonPolicy::update` (instructor: solo las suyas) + `UpdateLesson`, con auditoría. Checklist de publicación, **publicar** con nota de cambio (`PublishLessonRequest` + `LessonPolicy::publish`), historial de versiones y "Ver como estudiante". Aviso de cambios sin guardar. Paridad editor ↔ servidor probada en ambos lados (TD-5). E2E editorial |
| 2026-09-29 | **F5a · progreso** | `RoadmapStateResolver` (estados de lección y track, % de track, requisitos pendientes, ADVISORY/STRICT) en 5 consultas fijas. Acciones `StartLesson`, `CompleteLesson` y `UncompleteLesson` con FormRequest, Policy y registro en `learning_activities`. Lección: estado, aviso de requisitos, inicio al abrirla, botón completar/desmarcar y siguiente lección. Track: barra de progreso, estado por lección y "Continuar". Dashboard: "Continúa donde lo dejaste" y progreso por track. Contenido: diagrama del ciclo de vida en vertical (versión 2 publicada) |

## In Progress

**Fase 5a, ruta del estudiante.** Orden cambiado el 2026-09-25 a pedido del dueño del producto: revisa el contenido **dentro de la plataforma**, así que el camino de lectura va antes que el motor de estados.

- [x] **Camino de lectura:** tarjetas del dashboard enlazadas; página de track (temario por módulo en orden de estudio, prerrequisitos, "por qué importa", descripción y botón para empezar); página de lección (versión publicada, objetivos, prerrequisitos, índice con la sección activa, skills, recursos con aviso de enlace roto y navegación anterior/siguiente). Tests de backend y frontend. Recorrido en navegador real en claro, oscuro y móvil.
- [x] Motor de estados y política ADVISORY/STRICT (ADR-029 para MASTERED).
- [x] Acciones de progreso (iniciar, completar, desmarcar) y su botón en la lección.
- [x] Dashboard y track con progreso.
- [x] E2E del estudiante (Pest Browser, en CI).
- [x] Roadmap visual y vista de lista.
- [ ] Skills y recomendaciones 1–3 → **después del CMS** (decisión del dueño del producto, 2026-09-29).

**Fase 5b, CMS.** El dueño del producto pidió trabajar ya el lado de administración: con D8 el editor es la herramienta para mejorar las 6 lecciones sin pasar por archivos del repositorio.

- [x] 1. Editar una lección existente (formulario estructurado + cuerpo con TipTap, paridad de esquema, FormRequest, Policy, Action y auditoría).
- [x] 2. Checklist de publicación, publicar con nota de cambio, "cambios sin publicar" e historial de versiones.
- [x] 3a. Tracks y módulos: datos, estado, prerrequisitos (`DependencyEditor`) y orden de módulos.
- [x] 3b. Relaciones de la lección (skills, prerrequisitos, recursos), slug y archivar/restaurar.

## Next (Fase 5b: CMS, en este orden)

4. **Crear** lecciones, módulos y tracks nuevos, y el flujo de revisión (INSTRUCTOR envía, EDITOR publica). CRUD de skills y recursos (hoy solo se asocian los existentes). Vista de una versión y diff de texto.
5. Usuarios y roles; vista de auditoría.

Después: skills y recomendaciones 1–3 (5a pendiente). La 5c queda redefinida por D8: pulir las 6 lecciones, no agregar más; desde ahora se hace en `/admin/lessons` (usuario `editor@ai-roadmap.test` en local).

**Pendiente del dueño del producto:** observaciones de la revisión de las 6 lecciones dentro de la plataforma. El diagrama de "El ciclo de vida de un sistema de IA" ya está en vertical.

## Known Issues

| # | Problema | Impacto | Plan |
|---|---|---|---|
| KI-1 | El contenedor de desarrollo no tiene daemon de Docker | `docker-compose.yml` escrito, sin validar | Validar en la máquina del dueño. El README lo marca como no verificado |
| KI-2 | El contenedor no alcanza sitios externos | `content:verify-links` da "no concluyente" localmente | La verificación real ocurre en el workflow `Content` de CI |
| KI-3 | ~~La descarga dist de Composer desde GitHub está bloqueada~~ | — | **Resuelto:** el hook instala desde git y reconstruye phpstan desde su repositorio |
| KI-4 | `pgvector` no está instalado en el Postgres local | Ninguno hasta V2 | docker-compose y CI usan `pgvector/pgvector:pg16` |
| KI-5 | ~~Integración de Pest Browser con el Chromium del entorno sin probar~~ | — | **Resuelto:** Pest Browser 5 exige Playwright 1.62 (Chromium 1234) y la imagen trae Chromium 1194; `scripts/test-browser.sh` mapea la revisión con un *shim* de rutas y corre la suite. En CI se instala el navegador correcto |
| KI-6 | ~~`cloud.google.com/architecture/mlops-…` redirige a `docs.cloud.google.com`~~ | — | **Resuelto:** CI confirmó el destino (HTTP 200) y la URL del paquete ahora es la canónica |
| KI-8 | ~~`dump.rdb` (volcado de Redis) versionado desde la Fase 3: el hook arrancaba Redis con la raíz del repositorio como directorio~~ | — | **Resuelto:** fuera del índice, en `.gitignore`, y el hook arranca Redis sin snapshots (`--save ''`, `--dir /tmp`). Solo contenía caché y sesiones locales de desarrollo |
| KI-7 | En el tema oscuro de Mermaid, las etiquetas de los commits de un `gitGraph` tienen poco contraste | Cosmético; el diagrama se entiende | Ajustar `themeVariables` de Mermaid en la Fase 8, junto con la auditoría axe |

## Technical Debt

| # | Deuda | Cuándo se paga |
|---|---|---|
| TD-1 | ~~Los mensajes de validación del framework salen en inglés~~ | **Pagada en la Fase 4** (`lang/es`) |
| TD-2 | `axllent/mailpit:latest` sin versión fijada en docker-compose | Al validar KI-1 |
| TD-3 | Tooling pre-1.0 heredado del starter: vite-plus 0.3 y Wayfinder 0.1 | Revisar en cada actualización. El lockfile las fija |
| TD-4 | Borrar un archivo del paquete no elimina la entidad de la BD. Es intencional (la BD es la fuente de verdad), pero no se avisa | Fase 7, con `content:export`, que mostrará las diferencias |
| TD-5 | ~~El esquema RichContent se valida solo en el servidor: aún no existe el editor TipTap~~ | **Pagada en la Fase 5b:** `allowlist.json` con paridad probada en Pest (contra `RichContentSchema`) y en Vitest (contra el esquema del editor), más ida y vuelta de un documento con todos los nodos |
| TD-6 | Las relaciones de una lección (skills, recursos, dependencias) no se versionan (R10) | Aceptado para V1 |
| TD-7 | `overrides.lodash-es: ^4.18.1` en `package.json`: Mermaid 12 arrastra `lodash-es@4.17.23` (vía chevrotain), con dos avisos altos (GHSA-r5fr-rjxr-66jc y GHSA-f23m-r3pf-42rh). El "arreglo" de `npm audit` era bajar a Mermaid 11 | Quitar el override cuando Mermaid publique una versión con `lodash-es` ≥ 4.18; revisar en cada actualización |
| TD-8 | El iframe de YouTube carga el reproductor completo (con `loading="lazy"`); no hay fachada con miniatura | Fase 8 (rendimiento y CSP) |
| TD-9 | El script de capturas de la revisión visual vive fuera del repositorio | Fase 5, al integrar Pest Browser |
| TD-10 | La caché de estados por `progress_version` (architecture §6) no está implementada: cada página recalcula (5 consultas) | Cuando el roadmap crezca o una medición lo pida |
| TD-11 | Roadmap visual sin buscador, filtros, minimapa ni expansión a módulos (§27): con 2 tracks no aportan (D8). El panel lateral ya lista módulos y lecciones | Cuando haya más de ~8 tracks |
| TD-12 | Pest deja huérfano su servidor de Playwright; `scripts/test-browser.sh` lo cierra | Revisar en cada actualización de `pestphp/pest-plugin-browser` |
| TD-13 | El editor no conserva la alineación de columnas de tabla (TipTap 3.31 la ofrece; RichContent no la modela) | Si el contenido la necesita: ampliar esquema, validador, lector y `allowlist.json` a la vez |

## Decisions

El registro completo está en [architecture.md §14](architecture.md#14-registro-de-decisiones-adr) (ADR-001 a ADR-029).

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
| D8 | (2026-09-29) "No quiero más lecciones sino mejorar lo que ya hay" | ✅ **La V1 se cierra con las 6 lecciones actuales pulidas.** La 5c ya no busca ≥ 100 lecciones. Las metas de volumen de la Fase 6 (155 lecciones) quedan en suspenso hasta que el dueño decida |

Decisiones técnicas tomadas durante la Fase 5b (sin impacto de producto):

- **Estado de una lección:** su estado describe la copia de trabajo y, con una versión publicada, sigue visible en DRAFT o REVIEW (architecture §7). Por eso el CMS solo la **archiva** (se oculta) o la **restaura** (vuelve a verse su última versión); "pasar a borrador" no ocultaría nada y confundiría. Se publica solo con `PublishLesson`; una archivada se restaura antes.
- **Re-publicar sin cambios reutiliza la versión:** `PublishLesson` ya no crea una versión duplicada cuando el contenido coincide con la vigente y la lección no estaba publicada; solo la vuelve a PUBLISHED. El panel muestra la versión que realmente quedará activa.
- **Relaciones en su propia pestaña:** se guardan aparte porque no tienen versiones (TD-6) y se ven de inmediato; el contenido se publica. Una sola acción (`SyncLessonRelations`) valida ciclos en el grafo de lecciones, sincroniza las tres relaciones y audita una vez con solo lo que cambió. Los prerrequisitos se limitan al roadmap de la lección.
- **Recursos: solo asociar y ordenar.** Crear o editar un recurso (con su URL, que verifica `content:verify-links`) llega con el paso 4. Reordenar conserva la nota de cada recurso.
- **Slug de la lección editable:** es su URL pública; el formulario lo advierte y, al cambiarlo, el editor redirige a la nueva dirección.

- **Tracks y módulos sin versiones:** lo guardado es lo que ven los estudiantes (solo las lecciones tienen versiones, ADR-006). La UI lo dice junto a cada formulario.
- **Transiciones de estado explícitas** (`StatusTransition`): borrador ↔ publicado, cualquiera → archivado, archivado → borrador (restaurar). A REVIEW solo se llegará con el flujo de revisión (paso 4). Publicar o retirar exige `content.publish`; archivar o restaurar, `content.archive`. La transición inválida es un 422; la falta de permiso, un 403. La auditoría registra PUBLISHED, UNPUBLISHED, ARCHIVED o RESTORED.
- **URLs del admin por id para tracks y módulos:** su slug solo es único dentro del padre y ahora es editable. Las lecciones siguen por slug (único global).
- **Prerrequisitos como conjunto:** se guarda la lista completa; si una arista cerraría un ciclo se rechaza todo y el error muestra la ruta con títulos ("A → B → A"). Los cambios de relaciones (pivotes sin eventos) y el orden de módulos se auditan una vez sobre el track, con antes y después.
- **Instructor y módulos:** los módulos no tienen autor; puede editarlos quien puede editar su track.
- **Insignias y acciones de estado en `components/publishing/`:** las usan varias features (CMS, lista de módulos), y una feature no importa otra.

- **La lista blanca se comparte como JSON** (`resources/js/features/rich-content/allowlist.json`) en lugar de generarla: Pest la ata a `RichContentSchema` y Vitest al esquema del editor, así que cambiar un lado sin el otro rompe un test.
- **Lo que TipTap añade y el servidor no acepta se elimina en el editor** (`title` de enlaces, `align` de celdas, `target`/`rel`/`class` pegados, lenguajes de código inválidos), no se amplía el esquema.
- **El checklist se calcula sobre la copia guardada**, no mientras se escribe: las reglas de publicación viven solo en PHP. "Publicar" exige guardar antes.
- **Guardar sin editar el cuerpo no lo reescribe:** el formulario envía el cuerpo del servidor hasta que el editor informa una edición real.
- **Instructor:** ve la lista, edita solo las lecciones que creó y no publica (`content.update_own`, sin `content.publish`).
- **"Ver como estudiante" abre la versión publicada**, que es lo que leen los estudiantes; el panel explica si hay cambios pendientes.

Decisiones técnicas tomadas durante la Fase 5a (sin impacto de producto):

- **Abrir una lección solo la inicia si está AVAILABLE** (`RoadmapState::startsOnOpen`). Lo destapó el E2E: mirar una lección bloqueada la marcaba en curso y "Continuar" mandaba al estudiante a una lección sin sus requisitos. Reemplaza la decisión anterior de iniciar cualquier lección abierta.
- **El E2E también encontró que los nodos del grafo no eran clicables**: React Flow desactiva los eventos de puntero en nodos no seleccionables ni arrastrables. Se usa `onNodeClick`; el botón dentro del nodo mantiene Tab + Enter.
- **E2E en su propia suite** (`tests/Browser`, fuera de `php artisan test`), sobre el paquete de contenido real y no sobre factories. Se ejecuta con `composer test:browser`.
- **En móvil el roadmap siempre es la lista**; el grafo (React Flow, cargado de forma diferida) solo se descarga en escritorio.

- **ADR-029:** un track solo llega a MASTERED con al menos una evidencia publicada. La regla literal se cumplía por vacío.
- **Abrir una lección la inicia** (`POST /lessons/{slug}/start` que lanza la página, sin escribir en un GET). En ADVISORY eso incluye lecciones con requisitos pendientes, que muestran el aviso. "Continúa donde lo dejaste" es la última lección en curso visitada; si no hay, la primera disponible en orden de estudio.
- **Completar guarda la versión leída** (`completed_version_id`) y es idempotente; desmarcar vuelve a IN_PROGRESS y deja la entrada del feed (append-only). MASTERED no se puede desmarcar.
- **XP = 0** en `learning_activities` hasta la Fase 7; el feed ya se escribe.
- **STRICT** responde 403 en las acciones de progreso sobre lecciones LOCKED; la lectura sigue permitida (ADR-009).

- **URLs:** `/roadmaps/{roadmap}/tracks/{track}` (el slug de un track es único dentro de su roadmap, con bindings encadenados) y `/lessons/{lesson}` (el slug de una lección es único global; coincide con `POST /lessons/{slug}/complete` de architecture §8).
- **El estudiante lee la versión publicada** (`lesson_versions`), nunca la copia de trabajo: título, resumen, cuerpo, objetivos y minutos salen del snapshot. Las relaciones (skills, recursos, prerrequisitos) no se versionan (TD-6) y se filtran a lo publicado.
- **Una sola regla de visibilidad:** `Lesson::visibleToLearners()` (ahora también exige roadmap publicado). `LessonPolicy::view` consulta ese mismo scope y responde **404**, no 403, para no revelar borradores. `TrackPolicy::view` exige track y roadmap publicados.
- **`TrackOutline`** (`app/Domain/Curriculum/Queries`) define el orden de estudio: módulos publicados por posición y lecciones visibles por posición, sin módulos vacíos. El temario del track y la navegación anterior/siguiente de la lección salen de ahí, así que no pueden discrepar.

Decisiones técnicas tomadas durante la Fase 4 (sin impacto de producto):

- **ADR-028:** KaTeX y Mermaid escriben su propio DOM (excepción acotada a "sin HTML inyectado", con `trust: false` y `securityLevel: 'strict'`). Todo lo demás del lector son elementos React; Shiki entrega tokens, no HTML.
- **KaTeX fijado en 0.16** (no 0.18) para compartir una sola copia con Mermaid: evita descargar dos veces 78 kB gzip.
- **Shiki "fine-grained":** núcleo + motor de expresiones regulares en JS (sin WASM) y una gramática por lenguaje cargada al primer uso, en lugar del bundle completo.
- **Tokens del starter corregidos por el test de contraste:** `--muted-foreground` en claro, `--destructive` en oscuro (valor de shadcn v4; el botón destructivo lo atenúa con `dark:bg-destructive/60`) y el logo en oscuro.
- **Idioma por `<html lang>`** en lugar de un contexto de React: el servidor lo fija por usuario y `t()` lo lee siempre del mismo sitio que los lectores de pantalla.
- **Galería `/_dev/design-system` solo en `APP_ENV=local`:** herramienta de revisión, no una función del producto.
- **Dos bugs encontrados por los tests nuevos:** `User` no reflejaba los defaults de columna (con el modo estricto, `SetLocale` fallaba justo después de `create()`), y el scope `visibleToLearners` no calificaba sus columnas (ambiguas en el `has-many-through` de `Track::visibleLessons()`).

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
| F3 | **Pest 150/150** (349 aserciones) sobre PostgreSQL 16 · **Vitest 5/5** | Pint ✅ · oxlint + oxfmt ✅ | PHPStan nivel 7: 0 errores ✅ · tsc ✅ · enums sincronizados ✅ | Vite ✅ | fresh + seed + rollback + migrate ✅ · importación idempotente comprobada | `content:validate`: 0 problemas · enlaces: 7 no concluyentes (sin red) → CI | Hook validado desde frío (servicios detenidos, sin vendor de phpstan ni `.env`): 18 s. Prueba de humo HTTP: `/up` y `/login` 200, fuente servida localmente, `lang="es"`. `composer run dev` verificado. **CI en GitHub Actions:** primer run con todo en verde salvo Vitest (el plugin de Laravel se niega a arrancar con `CI=true`); corregido cargando solo React bajo Vitest. **Run #2 (`eab9002`): CI ✅ y Content ✅.** Workflow Content: 7/7 URLs verificadas (6 OK y 1 redirigida, luego actualizada) |
| F4 | **Pest 170/170** (547 aserciones) · **Vitest 97/97** | Pint ✅ · oxlint + oxfmt ✅ | PHPStan nivel 7: 0 errores ✅ · tsc ✅ · enums sincronizados ✅ | Vite ✅ (Shiki, KaTeX y Mermaid en chunks diferidos; el bundle principal no los incluye) | fresh + seed + rollback + migrate ✅ | `content:validate`: 0 problemas · importación ✅ | `npm audit`: 0 vulnerabilidades (tras TD-7). **Revisión visual con Playwright** (Chromium del entorno): bienvenida, login (con error), dashboard, admin, 403, 404 y las 6 lecciones en la galería, en claro, oscuro y móvil (390 px). Sin errores de consola ni desbordamiento horizontal. Defectos encontrados y corregidos en la revisión: logo invisible en oscuro, texto de error ilegible en oscuro, frases de actividad agramaticales ("publicó lecciones «X»") y diagramas anchos ilegibles en móvil (reducidos al 24 %) |
| F5a · lectura | **Pest 188/188** (755 aserciones) · **Vitest 108/108** | Pint ✅ · oxlint + oxfmt ✅ | PHPStan nivel 7: 0 errores ✅ · tsc ✅ · enums ✅ | Vite ✅ | fresh + seed + rollback ✅ | `content:validate`: 0 problemas | `npm audit`: 0. **Recorrido en navegador real** con el usuario estudiante: clic en la tarjeta del dashboard, "Empezar" y "Siguiente" hasta el final del track, en claro, oscuro y móvil (390 px). Sin errores de consola, sin desbordamiento horizontal, una petición por navegación y 404 para una lección inexistente |
| F5a · progreso | **Pest 209/209** (918 aserciones) · **Vitest 115/115** | Pint ✅ · oxlint + oxfmt ✅ | PHPStan nivel 7: 0 errores ✅ · tsc ✅ · enums ✅ | Vite ✅ | fresh + seed + rollback ✅ | `content:validate`: 0 problemas · importación publicó la versión 2 del ciclo de vida | `npm audit`: 0. **Recorrido real** con el estudiante demo: dashboard → Empezar → la lección pasa a En curso → Marcar como completada → Desmarcar → volver a completar; lección con requisito pendiente muestra el aviso ADVISORY; track al 33 %; dashboard con "Continúa donde lo dejaste". Claro, oscuro y móvil sin errores de consola ni desbordamiento. Defecto encontrado y corregido: el botón "Siguiente: <título largo>" se salía de la tarjeta en móvil |
| F5b · relaciones | **Pest 247/247** (1318 aserciones) · **Vitest 148/148** · **E2E 9/9** | Pint ✅ · oxlint + oxfmt ✅ | PHPStan nivel 7: 0 errores ✅ · tsc ✅ · enums ✅ | Vite ✅ | fresh + seed ✅ | `content:validate`: 0 problemas | **Recorrido real** con el editor demo: pestaña Relaciones, intento de ciclo entre lecciones de Git rechazado con la ruta, archivar con confirmación, restaurar y publicar de nuevo sin versión nueva. Claro, oscuro y móvil sin errores de consola ni desbordamiento. Defecto encontrado y corregido: con la lección publicada y sin cambios, el botón decía "Publicar versión 1"; ahora dice "Publicar" (desactivado, con el motivo) |
| F5b · tracks y módulos | **Pest 239/239** (1227 aserciones) · **Vitest 146/146** · **E2E 8/8** | Pint ✅ · oxlint + oxfmt ✅ | PHPStan nivel 7: 0 errores ✅ · tsc ✅ · enums ✅ | Vite ✅ | fresh + seed ✅ | `content:validate`: 0 problemas | **Recorrido real** con el editor demo: lista de tracks, edición, intento de ciclo (Orientación ← Fundamentos) rechazado con la ruta, módulo renombrado en diálogo, confirmación de "Pasar a borrador", guardar horas. Claro, oscuro y móvil sin errores de consola ni desbordamiento. Defectos encontrados y corregidos: campos desalineados en filas de dos columnas (el grid estiraba las filas), editor de la descripción demasiado alto y el error de ciclo que seguía visible tras quitar la fila |
| F5b · lecciones | **Pest 229/229** (1126 aserciones) · **Vitest 139/139** · **E2E 7/7** | Pint ✅ · oxlint + oxfmt ✅ | PHPStan nivel 7: 0 errores ✅ · tsc ✅ · enums ✅ | Vite ✅ (editor en un chunk diferido de 151 kB gzip) | fresh + seed ✅ | `content:validate`: 0 problemas | `npm audit`: 0. **CI #11 ✅** en GitHub (incluye el E2E editorial). **Recorrido real** con el editor demo: lista, edición, escribir en el cuerpo, fórmula por diálogo, callout y tabla, guardar (el servidor aceptó la salida del editor), publicar v2 con nota y leerla como estudiante; aviso de cambios sin guardar al salir. Claro, oscuro y móvil sin errores de consola ni desbordamiento. Defectos encontrados y corregidos: dentro del editor, el código de los bloques heredaba el estilo de código en línea (ilegible) y las celdas tenían márgenes de párrafo; la paridad detectó `title` en enlaces y `align` en celdas |
| F5a · E2E y roadmap | **Pest 214/214** (960 aserciones) · **Vitest 123/123** · **E2E 5/5** (Pest Browser, Chromium real) | Pint ✅ · oxlint + oxfmt ✅ | PHPStan nivel 7: 0 errores ✅ · tsc ✅ · enums ✅ | Vite ✅ (React Flow en un chunk diferido) | fresh + seed + rollback ✅ | `content:validate`: 0 problemas | `npm audit`: 0. CI #9 con el E2E en verde en GitHub. Revisión visual del roadmap en claro, oscuro y móvil; teclado (Tab + Enter abre el panel, Esc lo cierra). Dos defectos encontrados por el E2E y corregidos: inicio al mirar lecciones bloqueadas y nodos no clicables |

