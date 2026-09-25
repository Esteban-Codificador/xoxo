# Product Discovery — AI Engineer Roadmap

| | |
|---|---|
| Estado | v1.0 — Fase 1 completada |
| Fecha | 2026-09-25 |
| Documento rector | [`docs/spec/master-spec.md`](spec/master-spec.md) |
| Documentos relacionados | [architecture](architecture.md) · [database](database.md) · [frontend](frontend-architecture.md) · [content](content-architecture.md) · [curriculum](curriculum.md) · [roadmap](roadmap.md) · [progress](progress.md) |

Convención de este documento: **[H]** hecho verificado, **[I]** inferencia, **[O]** opinión/decisión, **[S]** supuesto.

---

## 0. Punto de partida (Fase 0 — inspección)

### Repositorio

- **[H]** `Esteban-Codificador/xoxo` está vacío: sin commits, sin archivos, sin Docker, sin configuración. Rama de trabajo `claude/ai-engineer-roadmap-discovery-cprntd`.
- **[H]** No existe stack previo que preservar, así que aplica el stack propuesto en §48 de la spec, actualizado a las versiones vigentes.

### Entorno de desarrollo (contenedor cloud de Claude Code)

| Herramienta | Versión | Estado |
|---|---|---|
| PHP | 8.4.19 | OK. Extensiones: `pdo_pgsql`, `pgsql`, `redis`, `intl`, `gd`, `mbstring`, `sodium`, `zip` |
| Composer | 2.x | OK, con matices: la descarga *dist* desde GitHub está bloqueada (403) y Composer cae a `git clone` (*source*). Funciona, pero es más lento |
| Node / npm / pnpm / bun | 22.22 / 10.9 / 10.33 / 1.3 | OK. Registro npm accesible |
| PostgreSQL | 16.13 | OK; hay que arrancarlo manualmente con `service postgresql start`. Extensiones: `pg_trgm`, `unaccent`, `citext`. **pgvector no está instalado** |
| Redis | 7.0.15 | OK; se arranca con `redis-server --daemonize yes` |
| Docker | cliente 29.3 | **Sin daemon.** `docker compose` no se puede validar en este entorno |
| Chromium (Playwright) | build 1194 en `/opt/pw-browsers` | OK para E2E |
| Red externa | — | Packagist y npm: accesibles. `git clone` desde GitHub: accesible. **Cualquier otra URL (docs.python.org, youtube.com, pytorch.org…) está bloqueada** |

### Consecuencias de la inspección

1. **[H]** Las URLs de recursos y los IDs de video **no se pueden verificar desde aquí**. La spec prohíbe inventar URLs (§33, §72), así que la verificación se traslada a un job de CI (GitHub Actions sí tiene internet) y a un comando programado en producción. Ver [content-architecture §8](content-architecture.md#8-verificación-de-enlaces-y-videos).
2. **[H]** Docker Compose se escribirá, pero **su validación queda pendiente** hasta ejecutarlo en una máquina con daemon o en CI. Se registra como *Known Issue* en `progress.md`.
3. **[H]** Versiones vigentes verificadas en Packagist/npm el 2026-09-25: Laravel 13.33, `inertiajs/inertia-laravel` 3.4, `@inertiajs/react` 3.7, React 19.3, Tailwind 4.3, Vite 8.3, Pest 5.2 (exige PHP ≥ 8.4), `laravel/fortify` 1.40, `spatie/laravel-permission` 8.3, `larastan` 3.12, `@xyflow/react` 12.12 y `laravel/ai` 1.0 (SDK oficial de IA para Laravel, publicado el 2026-09-23).
4. **[H]** El starter kit oficial `laravel/react-starter-kit` (commit `717b8f5`, 2026-09-21) ya trae Laravel 13, Fortify (con 2FA y passkeys), Inertia 3, React 19, shadcn/ui, Wayfinder, Larastan, Pint y `vite-plus` (oxlint + oxfmt). Es la base (ADR-002).

---

## 1. Propósito

Construir una plataforma educativa que lleve a una persona **desde fundamentos de programación hasta diseñar y operar sistemas de IA en producción**, con evidencia verificable de competencia (proyectos, evaluaciones y progreso) y no solo con contenido consumido.

La plataforma separa dos capas:

- **Plataforma**: el roadmap visual, el grafo de conocimiento, el LMS, el progreso, las evaluaciones, los proyectos, el CMS, los usuarios, la búsqueda y la gamificación. Es código.
- **Contenido**: el currículo, las lecciones, los ejercicios, los quizzes, los proyectos, los recursos y los videos. Son **datos** que se administran desde el CMS, se guardan en PostgreSQL y crecen sin tocar el código.

La prueba de éxito (§86) no es "¿se ve bonito?", sino "¿esto sirve para aprender AI Engineering y llevarlo a proyectos reales?".

### Diferenciación [O]

Existen alternativas gratuitas y maduras, como roadmap.sh (que tiene su propio roadmap de AI Engineer), cursos de Hugging Face y documentación oficial. Un roadmap más con enlaces no tiene razón de existir. Esta plataforma se justifica solo si cumple estas cuatro condiciones:

1. **Aprendizaje basado en evidencia.** Un estado MASTERED exige una evaluación o un proyecto, no un clic.
2. **Español técnico de calidad**, con los términos de la industria en inglés cuando corresponde.
3. **Rutas para perfiles reales.** Un software engineer con experiencia no debería repetir "variables y bucles" para llegar a RAG.
4. **Proyectos de portfolio con contexto empresarial**, como OCR de facturas, clasificación de tickets o agentes de automatización de procesos.

Si alguna de estas cuatro se pierde en la ejecución, el producto pierde su razón de ser.

---

## 2. Usuarios

| Persona | Descripción | Necesidad principal | Versión |
|---|---|---|---|
| **P1 — Ingeniero en transición** (primaria) | Software engineer con 2–6 años que quiere pasar a AI Engineering. **[S]** Coincide con el perfil del dueño del producto | Llegar a LLM/RAG/Agents rápido y rellenar fundamentos (matemáticas, ML) con criterio. Construir un portfolio creíble | V1 |
| **P2 — Estudiante desde cero / junior** | Poca experiencia en programación | Ruta guiada con fundamentos primero y desbloqueo estricto | V1 |
| **P3 — Editor de contenido** | Escribe, revisa y publica contenido | CMS fiable, con preview idéntico a lo que ve el estudiante y un flujo de publicación claro | V1 |
| **P4 — Instructor** | Crea contenido sin permiso para publicarlo y sigue el avance de estudiantes | Enviar a revisión y ver analíticas | V1 (contenido), V1.2 (analíticas) |
| **P5 — Administrador** | Opera la plataforma | Usuarios, roles, auditoría | V1 |
| **P6 — Visitante / reclutador** | Ve el portfolio público de un estudiante | URL pública con proyectos y skills verificables | V1.2 |

---

## 3. Casos de uso

| ID | Actor | Caso de uso | Versión |
|---|---|---|---|
| UC-01 | P1, P2 | Registrarse, verificar email e iniciar sesión (2FA y passkeys opcionales) | V1 |
| UC-02 | P1, P2 | Ver el roadmap completo como grafo, con estado y progreso por track | V1 |
| UC-03 | P1, P2 | Expandir un track, ver sus módulos y lecciones, prerequisitos y dependientes | V1 |
| UC-04 | P1, P2 | Abrir una lección y ver qué es, por qué importa, prerequisitos, teoría, recursos y siguientes pasos | V1 |
| UC-05 | P1, P2 | Marcar una lección en curso o completada (y deshacerlo); el progreso y los desbloqueos se recalculan | V1 |
| UC-06 | P1, P2 | Ver el dashboard: progreso general y por track, skills, próximos contenidos, recomendaciones y actividad | V1 |
| UC-07 | P1, P2 | Recibir la recomendación "qué sigue" y, si falta algo, "antes de continuar, completa X" | V1 |
| UC-08 | P1, P2 | Explorar skills, su nivel, prerequisitos y el contenido asociado | V1 |
| UC-09 | P3, P4 | Crear y editar tracks, módulos, lecciones, skills y recursos en `/admin` | V1 |
| UC-10 | P3, P4 | Editar una lección en Markdown con preview en vivo idéntico a la vista del estudiante | V1 |
| UC-11 | P3 | Publicar, despublicar y archivar contenido. Editar una lección publicada sin afectar a los estudiantes hasta republicarla | V1 |
| UC-12 | P3 | Definir dependencias (track, skill, lección) con validación de ciclos | V1 |
| UC-13 | P5 | Gestionar usuarios y roles, y consultar el log de auditoría | V1 |
| UC-14 | P1, P2 | Resolver ejercicios y quizzes; ver la explicación, el puntaje y las preguntas falladas | V1.1 |
| UC-15 | P1, P2 | Seguir un laboratorio paso a paso y marcar la validación | V1.1 |
| UC-16 | P1, P2 | Trabajar un proyecto por milestones y registrar el repositorio y la demo | V1.1 |
| UC-17 | P1, P2 | Ver videos (YouTube) asociados a una lección | V1.1 |
| UC-18 | P1, P2 | Buscar con la paleta de comandos (skills, lecciones, proyectos, recursos…) | V1.2 |
| UC-19 | P1, P2 | Guardar marcadores y notas por lección | V1.2 |
| UC-20 | P1, P2 | Ganar XP, rachas e insignias y obtener un certificado interno al completar un track | V1.2 |
| UC-21 | P1, P6 | Publicar y ver un portfolio público (`/u/{username}`) | V1.2 |
| UC-22 | P1, P2 | Preguntar al tutor de IA sobre el contenido publicado | V2 |

---

## 4. Funcionalidades y alcance por versión

La correspondencia con las fases de implementación está en [roadmap.md](roadmap.md).

### V1 — MVP de punta a punta (Fases 3–5)

- **Autenticación y roles**: registro, login, verificación de email, 2FA y passkeys (vienen del starter kit), y los roles ADMIN, EDITOR, INSTRUCTOR y STUDENT con permisos granulares.
- **Roadmap visual**: grafo macro de tracks (zoom, pan, búsqueda, filtros, hover y clic), expansión de módulos, panel lateral de detalle y **vista de lista alternativa** para móvil y accesibilidad.
- **Página de track**: módulos y lecciones con su estado, progreso y prerequisitos.
- **Página de lección**: overview, por qué importa, objetivos, prerequisitos, teoría (Markdown con código, math y Mermaid), recursos oficiales y siguientes pasos.
- **Progreso**: marcar en curso, completada y deshacer. Los estados LOCKED/AVAILABLE/IN_PROGRESS/COMPLETED/MASTERED se calculan a partir de las dependencias.
- **Skills**: listado, detalle y progreso por skill (calculado).
- **Dashboard**: progreso general y por track, skills completadas y pendientes, próximos contenidos, recomendaciones por reglas, actividad y tiempo estimado completado.
- **Recursos externos**: asociados a lecciones, skills y tracks, con estado de verificación del enlace.
- **CMS `/admin`**: CRUD de roadmaps, tracks, módulos, lecciones, skills y recursos; editor Markdown con preview; dependencias; flujo DRAFT → REVIEW → PUBLISHED → ARCHIVED; versionado de lecciones; usuarios y roles; log de auditoría. Todas las tablas con filtros, búsqueda, paginación, validación, confirmaciones y toasts.
- **Contenido inicial**: 17 tracks, alrededor de 60 skills y **100 o más lecciones publicadas** como ficha pedagógica completa, más un núcleo de lecciones en profundidad (ver [curriculum.md](curriculum.md)).
- **Paquete de contenido e importador idempotente** (`content:validate`, `content:import`).

### V1.1 — Práctica y evaluación (Fase 6)

Ejercicios (7 tipos), quizzes (intentos, tiempo, puntaje y preguntas falladas), laboratorios, proyectos con milestones, videos de YouTube (metadatos vía oEmbed), subida segura de imágenes para lecciones, 20 lecciones en profundidad con ejercicios y quiz, y los 10 proyectos de portfolio.

### V1.2 — Experiencia avanzada (Fase 7)

XP, rachas, insignias, búsqueda global con paleta de comandos, marcadores, notas, estadísticas, perfil, portfolio público, certificados internos, recomendaciones ampliadas, glosario de conceptos (topics) y tecnologías.

### Fases 8–9 — Calidad y pulido

Endurecimiento de seguridad (CSP, rate limits), rendimiento, auditoría de accesibilidad, SSR y SEO para páginas públicas, Dockerfile de producción, guía de despliegue y pulido de UX.

### V2 — IA dentro de la plataforma

Tutor de IA con RAG sobre el contenido publicado, recomendaciones asistidas por IA, revisión de código de ejercicios, asistente de proyectos, ejercicios Python ejecutables en el navegador (Pyodide), rutas de aprendizaje curadas e internacionalización del contenido.

**[O]** El tutor de V2 es, literalmente, el proyecto final del track de RAG aplicado a la propia plataforma. Construirlo sirve a la vez de funcionalidad y de caso de estudio publicable (*dogfooding*).

---

## 5. Fuera de alcance (explícito)

| Excluido | Motivo | ¿Cuándo? |
|---|---|---|
| Ejecución de código del estudiante en el servidor | Exige sandboxing serio (aislamiento, límites y abuso). Es un producto en sí mismo | Nunca en servidor sin un proveedor dedicado. Pyodide en navegador en V2 |
| Editor WYSIWYG / rich text | Un solo formato (Markdown) es versionable, diferenciable y seguro. Ver ADR-005 | Reevaluar si hay editores no técnicos |
| Alojamiento propio de video (subida y transcodificación) | Costoso. La arquitectura queda preparada (disco S3/R2/Azure/GCS + URLs firmadas) | Cuando exista contenido propio en video |
| Pagos y suscripciones | No es necesario para validar el aprendizaje | Sin fecha |
| Foros, comentarios y comunidad | Requiere moderación | Sin fecha |
| App móvil nativa | La web responsive cubre el caso | Sin fecha |
| Multi-tenant de la plataforma | Un solo tenant. (El proyecto final *enseña* multi-tenant, que es otra cosa) | Sin fecha |
| Traducción del contenido al inglés | La UI queda lista para `es`/`en`; el contenido es solo en español en V1 | V2 |
| Elasticsearch/OpenSearch | Se usa PostgreSQL FTS detrás de una interfaz | Cuando el volumen lo justifique (medido) |
| SCORM/xAPI e integración con LMS externos | No es necesario | Sin fecha |
| Certificaciones oficiales | Los certificados son internos y se rotulan así (§46) | — |

---

## 6. Contradicciones de la spec y su resolución

La spec es el documento rector, pero tiene tensiones internas. Cada una se resuelve con una decisión explícita y trazable.

| # | Tensión | Resolución [O] | ADR |
|---|---|---|---|
| C1 | "Contenido desacoplado, no hardcodeado" (§2) frente a "seed con 100+ lecciones" (§50) | El seed vive como **paquete de contenido** (Markdown + YAML), fuera del código de la app, y se carga con un importador idempotente. Tras importarlo, **la BD es la fuente de verdad** y el importador no sobrescribe lo editado en el CMS | ADR-004 |
| C2 | "No meter todo en un JSON gigante" (§72) | Un archivo por entidad (lección, skill, proyecto), con claves estables y relaciones por clave | ADR-004 |
| C3 | "No inventar URLs" (§33) frente a un contenedor sin salida a internet | Cada recurso guarda su estado de verificación. Un job de CI verifica cada URL y cada ID de YouTube (oEmbed) y **bloquea el merge si hay 404**. Los enlaces rotos se ocultan al estudiante y se marcan en el admin | — |
| C4 | Cadena lineal Python → … → Transformers → LLMs → RAG (§25) frente a "rutas alternativas" (§4.14) y el perfil real del AI Engineer | Las dependencias tienen tipo **REQUIRED** o **RECOMMENDED**. LLM Engineering *requiere* Python y *recomienda* Transformers. Las lecciones avanzadas (fine-tuning, LoRA) sí requieren DL y Transformers a nivel de lección. Eso habilita la ruta "aplicaciones primero" sin duplicar contenido | ADR-008 |
| C5 | LOCKED estricto (§26) frente a estudiantes que ya saben parte del contenido | Política de desbloqueo por roadmap: **ADVISORY** (por defecto: se ve el estado y un aviso, pero no se bloquea) o **STRICT** (bloquea las acciones de progreso) | ADR-009 |
| C6 | Grafo completo con zoom y pan (§27) frente a "no cargar un roadmap enorme" (§79) | Dos niveles: grafo macro de unos 17 tracks más detalle bajo demanda (módulos al expandir, lecciones en el panel lateral) | ADR-010 |
| C7 | Editor con rich text **y** Markdown (§31) frente a XSS (§77) y versionado (§56) | Markdown extendido como único formato (GFM, KaTeX, Mermaid, callouts, directiva de video). Preview con el mismo renderer que ve el estudiante. **HTML crudo prohibido** | ADR-005 |
| C8 | "Horas estudiadas" (§28) sin una forma fiable de medirlas | Se muestra **"tiempo estimado completado"** (la suma de `estimated_minutes` de lo completado) con esa etiqueta exacta. La medición activa (heartbeat) queda diferida. Mostrar otra cosa sería inventar un dato | — |
| C9 | MASTERED sin definir (§26) | MASTERED exige **evidencia**: un quiz aprobado con umbral de maestría (90 % por defecto) o un proyecto completado. El contenido sin evaluación llega como máximo a COMPLETED | ADR-007 |
| C10 | "Laravel Fortify/Breeze" (§48) | Breeze dejó de ser el camino oficial. Se usa el **starter kit oficial de React** (Fortify, Inertia 3, Laravel 13) | ADR-002 |
| C11 | Hoja de ruta del producto (§60): recursos y videos en V1.2 | Los **recursos pasan a V1**: una lección sin fuentes oficiales pierde gran parte de su valor y el costo es bajo. Los videos pasan a V1.1 | — |
| C12 | Tabla `student_skill_progress` (§49) | **No se materializa en V1.** El progreso por skill se calcula desde `lesson_progress` (una sola fuente de verdad, sin datos desactualizados cuando se publica contenido nuevo). Se materializa solo si una métrica lo exige | ADR-020 |
| C13 | "Preparar `AiProviderInterface`" (§59) frente a "no sobrearquitecturar" (§72) | `laravel/ai` 1.0 ya abstrae OpenAI, Anthropic, Gemini y modelos locales. Duplicar esa abstracción en V1, sin consumidores, sería especulativo. En V2 se definen puertos de dominio estrechos (`TutorService`, `CodeReviewer`) implementados sobre `laravel/ai`. El punto de extensión con consumidor real en V1 es `RecommendationEngine` | ADR-014 |
| C14 | Ejercicios de tipo "Código" (§34) frente a seguridad (§77) | V1.1: enunciado, código inicial, pistas, solución y rúbrica de autoevaluación. V2: ejecución en navegador (Pyodide) con casos de prueba y revisión por IA | — |

---

## 7. Riesgos

| # | Riesgo | Prob. | Impacto | Mitigación |
|---|---|---|---|---|
| R1 | **Producir contenido es el cuello de botella**, no la plataforma. 178 lecciones planificadas × ficha de calidad ≈ 70.000 palabras técnicas | Alta | Alto | Niveles de profundidad A/B ([content-architecture §9](content-architecture.md#9-niveles-de-profundidad-y-plan-del-seed)). El backlog vive como DRAFT en el CMS (no publicado). El contenido avanza en paralelo a las fases de plataforma |
| R2 | **Veracidad técnica** del contenido generado con asistencia de IA en un campo que cambia cada mes (APIs de LLM, frameworks de agentes) | Alta | Alto | Flujo REVIEW con revisión humana. `last_reviewed_at` visible por lección. Ciclo de revisión de 6 meses en los tracks volátiles (LLM, RAG, Agents, Security). El texto explica conceptos estables y **enlaza la documentación oficial** para los detalles de API |
| R3 | Enlaces o IDs de video inventados o rotos | Media | Alto | Verificación en CI que bloquea el merge, más verificación semanal en producción; los rotos se ocultan |
| R4 | Alcance de la spec (86 secciones ≈ meses de trabajo de un equipo) | Alta | Alto | Fases con criterios de salida. V1 recortada. Nada pasa a la fase siguiente con validaciones en rojo |
| R5 | El entorno no puede validar Docker; Playwright y Chromium pueden no coincidir en versión | Media | Medio | CI (GitHub Actions) como validador de Docker y E2E. Si falla Pest Browser, *fallback* a `@playwright/test` con `executablePath` |
| R6 | Tooling pre-1.0 en el starter oficial (`vite-plus` 0.3, Wayfinder 0.1) | Media | Medio | Versiones fijadas con lockfile. Actualizar a propósito, no por arrastre |
| R7 | XSS por contenido, fuga de respuestas de quiz al cliente, subidas maliciosas | Media | Alto | Sin HTML crudo más `rehype-sanitize`. Las respuestas correctas nunca se serializan antes de enviar. Validación MIME real (finfo), límites de tamaño y disco privado con URLs firmadas |
| R8 | Grafo ilegible en móvil o con mucho contenido | Media | Medio | Vista de lista alternativa y grafo de dos niveles |
| R9 | Importar sobrescribe cambios hechos en el CMS | Media | Alto | Hash del contenido importado. Si la entidad cambió en el CMS, el importador la salta salvo con `--force` |
| R10 | Versionado parcial: las relaciones de una lección (skills, recursos) no se versionan en V1 | Baja | Medio | Documentado. Cambiar relaciones es poco frecuente y queda auditado |
| R11 | Métrica de progreso engañosa (clic en "completar" sin aprender) | Alta | Medio | Separar COMPLETED (autodeclarado) de MASTERED (con evidencia). Los certificados exigen evidencia |

---

## 8. Decisiones principales

El registro completo, con contexto y alternativas, está en [architecture.md §14](architecture.md#14-registro-de-decisiones-adr). En resumen:

1. Monolito modular **Laravel 13 + Inertia 3 + React 19 + TypeScript** sobre el **starter kit oficial**.
2. **PostgreSQL 16 en todos los entornos, tests incluidos** (se usan jsonb, índices parciales, CHECK y FTS).
3. **El contenido es dato**: la BD es la fuente de verdad, el paquete de contenido solo arranca el sistema y el CMS lo hace crecer.
4. **Markdown extendido** como único formato de contenido, sin HTML crudo.
5. **Lecciones versionadas** con snapshots inmutables al publicar.
6. **Estados calculados** (LOCKED/AVAILABLE) y **estados persistidos** (IN_PROGRESS/COMPLETED/MASTERED).
7. **Dependencias con tipo** (REQUIRED/RECOMMENDED y umbral) en tres niveles (track, skill, lección), validadas como DAG.
8. **React Flow + dagre** en un grafo de dos niveles, con vista de lista para móvil.
9. **Búsqueda** en la tabla `search_documents`, que solo contiene lo publicado, con FTS de Postgres detrás de una interfaz.
10. **Recomendaciones por reglas** detrás de `RecommendationEngine`, e IA en V2 sobre `laravel/ai`.

## 9. Supuestos (a validar por el dueño del producto)

- **[S1]** Producto multiusuario con registro abierto (no una herramienta personal de un solo usuario).
- **[S2]** En V1 el contenido requiere login. La lectura pública con SSR y SEO llega en la Fase 8; las policies aceptan `?User` desde el principio para no rehacerlas.
- **[S3]** Un roadmap activo ("AI Engineer"). El modelo soporta varios.
- **[S4]** Escala V1 de hasta unos 10.000 usuarios. No hay requisitos de alta disponibilidad.
- **[S5]** Destino de despliegue sin definir. Se documenta un despliegue genérico con Docker; Laravel Cloud o Forge serían alternativas.
- **[S6]** El dueño del producto actúa como EDITOR y revisa el contenido antes de publicarlo.

## 10. Métricas de éxito

Estas métricas miden si la plataforma sirve para aprender, no si se usa por inercia.

| Métrica | Definición | Objetivo inicial [O] |
|---|---|---|
| Activación | % de registrados que completan su primera lección en 48 h | ≥ 40 % |
| Retención semanal | % de usuarios activos en la semana 4 respecto de la semana 1 | ≥ 25 % |
| Evidencia | % de lecciones COMPLETED que llegan a MASTERED cuando tienen quiz | ≥ 50 % |
| Proyectos | % de usuarios que completan al menos un proyecto de portfolio en 60 días | ≥ 15 % |
| Salud del contenido | % de recursos con enlace verificado en los últimos 30 días | ≥ 98 % |
