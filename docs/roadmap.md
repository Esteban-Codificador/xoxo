# Roadmap del producto y plan de implementación

| | |
|---|---|
| Estado | v1.0 |
| Fecha | 2026-09-25 |
| Estado vivo | [progress.md](progress.md) |

---

## 1. Versiones del producto

```text
V1   (Fases 3–5)  Autenticación · Roadmap · Lecciones · Progreso · Skills · Recursos · Dashboard · Admin/CMS · Importador de contenido
V1.1 (Fase 6)     Ejercicios · Quizzes · Laboratorios · Proyectos · Videos · Subida de imágenes · 20 lecciones [A] · 10 proyectos
V1.2 (Fase 7)     Gamificación · Búsqueda + paleta de comandos · Marcadores · Notas · Estadísticas · Perfil/Portfolio · Certificados · Glosario
V1.3 (Fases 8–9)  Seguridad endurecida · Rendimiento · Accesibilidad · SSR/SEO · Despliegue · Pulido
V2                Tutor IA (RAG sobre el contenido) · Recomendaciones IA · Revisión de código IA · Asistente de proyectos · Pyodide · Rutas curadas · i18n del contenido
```

Diferencias con §60 de la spec: los **recursos** pasan de V1.2 a V1 y los **videos** de V1.2 a V1.1. Motivo: sin fuentes oficiales una lección pierde gran parte de su valor y su costo es bajo (ver discovery C11).

## 2. Puerta de validación (obligatoria al cerrar cada fase)

Ninguna fase se cierra con algo en rojo. La puerta es la misma para todas:

| # | Paso | Comando (desde la Fase 3) |
|---|---|---|
| 1 | Tests backend | `php artisan test` (Pest, PostgreSQL) |
| 2 | Tests frontend | `npm run test` (Vitest) |
| 3 | Lint y formato | `composer lint:check` (Pint) + `npm run check` (oxlint + oxfmt) |
| 4 | Type checking | `composer types:check` (PHPStan) + `npm run types:check` (tsc) |
| 5 | Build | `npm run build` |
| 6 | Migraciones | `php artisan migrate:fresh --seed` + `migrate:rollback` |
| 7 | Contenido | `php artisan content:validate content/ai-engineer` |
| 8 | Revisión de experiencia | Capturas con Playwright de las pantallas tocadas, en claro, oscuro y móvil, revisadas contra el design system |
| 9 | Documentación | `progress.md` actualizado (completado, deuda, decisiones) y docs afectados |

Los comandos exactos se fijan en la Fase 3 y se documentan en el README **después de comprobar que funcionan** (§76).

## 3. Fases

Las estimaciones van en **sesiones de trabajo de Claude Code** (bloques de trabajo continuo con validación). **[O] Son estimaciones gruesas, no compromisos**: la incertidumbre mayor está en el contenido, no en el código.

### Fase 0 — Inspección ✅
Repositorio vacío y entorno inspeccionado. Hallazgos en [product-discovery §0](product-discovery.md#0-punto-de-partida-fase-0--inspección).

### Fase 1 — Discovery ✅
[product-discovery.md](product-discovery.md).

### Fase 2 — Arquitectura ✅
[architecture.md](architecture.md), [database.md](database.md), [frontend-architecture.md](frontend-architecture.md), [content-architecture.md](content-architecture.md) y [curriculum.md](curriculum.md).

### Fase 3 — Fundación y modelo de datos ✅

**Alcance:**
1. Instalar el starter kit oficial de React (Laravel 13) en la raíz del repositorio sin perder `docs/`. Funciones de autenticación: registro, verificación de email, 2FA, passkeys y confirmación de contraseña.
2. Configurar PostgreSQL (dev y test) y Redis (caché, colas y sesiones). Crear `.env.example` completo.
3. Migrar el starter kit a Pest 5 (los tests existentes siguen pasando), añadir Vitest + RTL y ajustar el nivel de PHPStan.
4. Migraciones de la Fase 3 ([database §7](database.md#7-orden-de-migraciones)), modelos, enums, factories, morph map y `types:enums`.
5. spatie/laravel-permission con los roles ADMIN, EDITOR, INSTRUCTOR y STUDENT, la matriz de permisos (§4 abajo) y un seeder.
6. `AuditLogger` + observers.
7. `DependencyGraph` (ciclos y orden topológico) con tests unitarios.
8. RichContent: esquema, validador, texto plano y conversor Markdown → RichContent. `content:validate` y `content:import` con un **paquete mínimo de muestra** (2 tracks y 6 lecciones reales) y tests de idempotencia y protección de ediciones del CMS.
9. `docker-compose.yml` (Postgres con pgvector, Redis y Mailpit). No se valida aquí; se deja como Known Issue.
10. GitHub Actions: workflow `CI` (lint, tipos, build, migraciones y tests) y workflow `Content` (validación del paquete y verificación de enlaces).
11. Hook SessionStart para que las sesiones cloud futuras arranquen Postgres y Redis e instalen dependencias.

**Criterio de salida:** `migrate:fresh --seed` crea el esquema y los usuarios de ejemplo por rol. Puerta de validación en verde local. CI en verde en GitHub. README con la instalación verificada.

### Fase 4 — Design system · estimación: 1 sesión

Tokens semánticos (estados de nodo), tipografía, `AppLayout` y `AdminLayout` (sidebar y breadcrumbs), componentes base de dominio (`ProgressBar`, `StateBadge`, `EmptyState`, `ErrorState`, skeletons, `PageHeader`), página de error de Inertia, i18n tipado (`es`/`en`) y `RichContentRenderer` con sus nodos compartidos y tests.

**Criterio de salida:** los componentes tienen tests (Vitest + RTL), pasan contraste AA en ambos temas y **no hay componentes huérfanos**: cada uno tiene un consumidor planificado en la Fase 5.

### Fase 5 — MVP de punta a punta · estimación: 4–5 sesiones

- **5a · Ruta del estudiante:** `RoadmapStateResolver`, `CompleteLesson`, `StartLesson`, `UncompleteLesson`, política de desbloqueo, recomendaciones por reglas (1–3), dashboard, roadmap (canvas + lista), track, lección y skills. Recursos visibles con su estado de enlace.
- **5b · CMS:** CRUD de roadmaps, tracks, módulos, lecciones, skills y recursos. Editor TipTap con nodos de dominio y checklist. `DependencyEditor`. Flujo de publicación y versiones. Usuarios y roles. Vista de auditoría.
- **5c · Contenido:** paquete completo con 17 tracks, 50 módulos, 72 skills y dependencias, recursos oficiales y **≥ 100 lecciones publicadas**. Job de CI de verificación de enlaces.
- **E2E:** los dos flujos de §78.

**Criterio de salida:** un estudiante nuevo puede registrarse, recorrer el roadmap, estudiar, completar lecciones y ver cómo cambian el progreso y los desbloqueos. Un editor puede crear y publicar una lección que el estudiante ve de inmediato. Todo cubierto por tests. **Aquí queda cerrada la V1.**

### Fase 6 — Práctica y evaluación · estimación: 3–4 sesiones

Motor de tipos de pregunta, ejercicios (7 tipos), quizzes (intentos, tiempo en el servidor, puntaje, preguntas falladas y MASTERED), laboratorios, proyectos con milestones, videos (oEmbed y verificación), subida segura de imágenes, 20 lecciones [A] con quiz y ejercicio, 10 proyectos completos y las 155 lecciones A+B publicadas. Reglas de recomendación 4 y 5.

### Fase 7 — Experiencia avanzada · estimación: 3 sesiones

`learning_activities` completo, XP, rachas, insignias, certificados internos (con la leyenda "certificado interno, no académico"), `search_documents` + paleta de comandos, marcadores, notas, estadísticas del estudiante y del instructor, perfil y portfolio público (`/u/{username}`), glosario de topics, tecnologías y `content:export`.

### Fase 8 — Calidad · estimación: 2 sesiones

CSP y cabeceras de seguridad, revisión de rate limits, auditoría con axe en los tests de navegador, `EXPLAIN` de las consultas críticas, lectura pública con SSR y SEO (Open Graph), Dockerfile de producción, `deployment.md` y revisión de seguridad (`/security-review`).

### Fase 9 — Pulido · estimación: 1 sesión

Microinteracciones sutiles, revisión de todos los estados vacíos, de carga y de error, y una pasada de UX como estudiante real sobre las rutas A y B.

**Total estimado para V1.3:** unas 17–19 sesiones, de las cuales alrededor del 40 % es producción de contenido.

## 4. Matriz de permisos inicial

| Permiso | ADMIN | EDITOR | INSTRUCTOR | STUDENT |
|---|:-:|:-:|:-:|:-:|
| `admin.access` | ✓ | ✓ | ✓ | |
| `content.view_any` | ✓ | ✓ | ✓ | |
| `content.create` | ✓ | ✓ | ✓ | |
| `content.update_any` | ✓ | ✓ | | |
| `content.update_own` | ✓ | ✓ | ✓ | |
| `content.submit_review` | ✓ | ✓ | ✓ | |
| `content.publish` | ✓ | ✓ | | |
| `content.archive` | ✓ | ✓ | | |
| `content.delete` | ✓ | | | |
| `users.view` | ✓ | | | |
| `users.manage` | ✓ | | | |
| `roles.assign` | ✓ | | | |
| `audit.view` | ✓ | ✓ | | |
| `analytics.view` (Fase 7) | ✓ | ✓ | ✓ | |

Las acciones de aprendizaje (progreso, intentos, notas) están disponibles para cualquier usuario autenticado y verificado. Las restringe la política de desbloqueo, no un permiso.
