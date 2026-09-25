# Arquitectura frontend

| | |
|---|---|
| Estado | v1.0: diseño (se implementa desde la Fase 4) |
| Base | `laravel/react-starter-kit` (Inertia 3, React 19, TS, Tailwind 4, shadcn/ui, Wayfinder) |
| Relacionados | [architecture](architecture.md) · [content-architecture](content-architecture.md) |

---

## 1. Principios

1. **El servidor es dueño del estado del dominio.** Las páginas reciben props de Inertia ya calculadas: estados de nodos, porcentajes y recomendaciones. **React no recalcula reglas de negocio.** Si una regla existiera en PHP y en TS, divergiría.
2. **Sin gestor de estado global** (ni Redux ni React Query). El estado del servidor son las props de Inertia, que se refrescan con recargas parciales. El estado de UI es local. El estado compartible (filtros, pestañas, búsqueda) va en la URL.
3. **Contenido fuera de los componentes.** Ningún componente contiene texto curricular. Los textos de interfaz pasan por i18n.
4. **Las bibliotecas pesadas se cargan solo donde se usan**: React Flow, Mermaid, KaTeX, Shiki y CodeMirror van como `import()` dinámico.
5. **Accesibilidad antes que estética.** El estado de un nodo se comunica con icono y texto, no solo con color.

## 2. Estructura de directorios

```text
resources/js/
├── app.tsx                    # Bootstrap de Inertia (starter kit)
├── pages/                     # Una página Inertia por ruta; componentes delgados que componen features
│   ├── dashboard.tsx
│   ├── roadmap/show.tsx
│   ├── tracks/show.tsx
│   ├── lessons/show.tsx
│   ├── skills/{index,show}.tsx
│   ├── admin/
│   │   ├── dashboard.tsx
│   │   ├── lessons/{index,create,edit}.tsx
│   │   ├── tracks/… modules/… skills/… resources/… users/… audit-logs/index.tsx
│   ├── auth/… settings/…      # Del starter kit
│   └── errors/error.tsx       # 403 / 404 / 419 / 500 / 503
├── features/                  # Lógica y UI por dominio (lo que hace cada página)
│   ├── roadmap-graph/         # Canvas, nodos, aristas, layout dagre, panel lateral, vista de lista
│   ├── progress/              # ProgressBar, StateBadge, CompleteLessonButton, TrackProgressList
│   ├── lesson/                # Secciones de la lección: Overview, WhyItMatters, Prerequisites, NextSteps…
│   ├── markdown/              # MarkdownRenderer y plugins (callout, video, mermaid, math, code)
│   ├── recommendations/       # RecommendationCard y RecommendationList
│   ├── dependencies/          # DependencyEditor reutilizable (track, skill, lección)
│   ├── admin/
│   │   ├── data-table/        # Tabla dirigida por el servidor (orden, filtros, paginación)
│   │   ├── lesson-editor/     # Editor Markdown + preview + checklist de publicación
│   │   └── publishing/        # StatusBadge, PublishActions, VersionHistory
│   └── search/                # CommandPalette (Fase 7)
├── components/
│   ├── ui/                    # shadcn/ui (copiados; no se editan salvo con motivo)
│   └── …                      # Compartidos: EmptyState, ErrorState, PageHeader, Breadcrumbs…
├── layouts/                   # AppLayout (estudiante), AdminLayout, AuthLayout, PublicLayout
├── i18n/                      # es.ts (fuente), en.ts (debe satisfacer el tipo de es), t()
├── hooks/  lib/
├── types/
│   ├── enums.ts               # GENERADO por `php artisan types:enums`. No se edita a mano
│   └── models.ts              # Formas de los props (espejo de los JsonResource)
├── actions/  routes/  wayfinder/   # GENERADOS por Wayfinder
```

Regla de dependencia: `pages → features → components`. Una feature no importa otra feature; si dos la necesitan, lo común baja a `components/` o `lib/`.

## 3. Contrato servidor → cliente

- Los controladores devuelven `Inertia::render('lessons/show', [...])` con **JsonResource**, nunca con modelos crudos. Eso evita filtrar columnas (borradores, respuestas correctas).
- Los props costosos van como **deferred props** de Inertia (p. ej., recomendaciones y actividad en el dashboard) y la página muestra *skeletons* mientras llegan.
- Las **recargas parciales** (`router.reload({ only: ['progress'] })`) refrescan el progreso tras una acción sin reconstruir la página.
- Las rutas y acciones se invocan con **Wayfinder** (funciones TS tipadas generadas desde Laravel), nunca con URLs escritas a mano.
- Los enums se importan de `types/enums.ts`. Un test de PHP comprueba que el archivo generado coincide con los enums actuales, así que un cambio sin regenerar rompe CI.

## 4. Layouts y navegación

| Layout | Uso | Elementos |
|---|---|---|
| `AppLayout` | Estudiante | Sidebar (Dashboard, Roadmap, Skills, Proyectos, Buscar), breadcrumbs, menú de usuario, selector de tema, paleta de comandos (⌘K, Fase 7) |
| `AdminLayout` | `/admin` | Sidebar por recurso (Dashboard, Usuarios, Roadmaps, Tracks, Módulos, Lecciones, Skills, Ejercicios, Quizzes, Proyectos, Videos, Recursos, Auditoría), breadcrumbs y enlace "ver como estudiante" |
| `AuthLayout` | Autenticación | Del starter kit |
| `PublicLayout` | Landing, roadmap público y portfolio (Fases 7–8) | Metadatos SEO y Open Graph |

Dark y light mode vienen del starter kit (`use-appearance`), con preferencia del sistema y persistencia por cookie.

## 5. Design system (Fase 4)

- **Tokens:** variables CSS de shadcn/ui (espacio de color oklch), extendidas con tokens semánticos del dominio. Nunca se usan colores literales en componentes.
- **Estados de nodo:** color **más** icono **más** etiqueta. Contraste AA verificado en ambos temas.

| Estado | Token | Icono (lucide) | Etiqueta |
|---|---|---|---|
| LOCKED | `--state-locked` (neutro atenuado) | `Lock` | Bloqueado |
| AVAILABLE | `--state-available` (borde primario) | `Circle` | Disponible |
| IN_PROGRESS | `--state-in-progress` (azul) | `CircleDot` | En curso |
| COMPLETED | `--state-completed` (verde) | `CircleCheck` | Completado |
| MASTERED | `--state-mastered` (ámbar) | `Award` | Dominado |

- **Tipografía:** la sans del starter kit para la UI y una monoespaciada para el código. Escala de Tailwind y ancho de lectura ≤ 72ch en las lecciones.
- **Restricciones de la spec (§52):** sin gradientes decorativos, glassmorphism ni sombras exageradas. Las animaciones son cortas (≤ 200 ms) y se respeta `prefers-reduced-motion`.
- **Estados de pantalla obligatorios** en toda vista con datos: *loading* (skeleton), *empty* (explica qué hacer), *error* (mensaje con acción de reintento) y *forbidden*.

## 6. Roadmap visual (ADR-010)

**Librerías:** `@xyflow/react` 12 para el canvas (zoom, pan, nodos React, minimapa y foco por teclado) y `@dagrejs/dagre` para el layout jerárquico automático. El layout se calcula en el cliente a partir del grafo, así que **añadir contenido desde el CMS nunca exige reposicionar nodos a mano**.

### Dos niveles

1. **Macro:** unos 17 nodos de track. Aristas REQUIRED continuas y RECOMMENDED punteadas (se pueden ocultar con un filtro).
2. **Detalle bajo demanda:** al expandir un track aparecen sus módulos como nodos hijos y se colapsan al contraer. Las lecciones **no** se dibujan como nodos; se listan en el panel lateral del módulo seleccionado. Así se mantiene la legibilidad con cientos de lecciones.

### Interacciones exigidas por §27

| Requisito | Implementación |
|---|---|
| Zoom y pan | Integrados en React Flow, con controles, minimapa (escritorio) y "ajustar a la vista" |
| Expandir y contraer | Clic en el chevron del nodo track; el estado de expansión se guarda en la URL |
| Buscar | Campo que resalta coincidencias, atenúa el resto y encuadra la primera |
| Filtrar | Por estado, dificultad y tipo de arista |
| Ver progreso | Barra y porcentaje dentro del nodo, más el badge de estado |
| Prerequisitos y dependencias | Al pasar o seleccionar, se resaltan las aristas entrantes (prerequisitos) y salientes (dependientes). El panel lateral muestra, por ejemplo, "Requiere Python: llevas 45 %, se requieren 80 %" |
| Clic en nodo | Abre el panel lateral con el resumen, el progreso, los requisitos y un CTA ("Continuar", "Ver track") |
| Hover | Tooltip con título, estado y progreso |
| Responsive | Por debajo de 768 px se muestra la **vista de lista**: acordeón por nivel (rango topológico calculado), con los mismos datos y acciones |
| Teclado | Nodos enfocables (Tab), Enter abre el panel y Esc lo cierra. La vista de lista es la alternativa accesible completa |

### Componentes

`RoadmapCanvas` (layout y React Flow) · `TrackNode` · `ModuleNode` · `DependencyEdge` · `GraphToolbar` (búsqueda y filtros) · `NodeDetailPanel` (Sheet de shadcn) · `RoadmapListView` (móvil y accesibilidad) · `useGraphLayout` (función pura: grafo → posiciones, con test unitario).

## 7. Renderizado de contenido Markdown (ADR-005)

Un único `MarkdownRenderer` sirve para la página de la lección y para el preview del editor. **Lo que ve el editor es exactamente lo que verá el estudiante.**

```text
markdown ──remark-parse──▶ mdast
  ├─ remark-gfm             tablas, listas de tareas, autolinks
  ├─ remark-math            $…$ y $$…$$
  └─ remark-directive       :::tip / :::warning / :::note  ·  ::video{provider=youtube id=…}
──remark-rehype (SIN allowDangerousHtml)──▶ hast
  ├─ rehype-sanitize        esquema restrictivo (defensa en profundidad)
  ├─ rehype-katex           fórmulas (después de sanitizar: su salida es de confianza)
  └─ componentes React      code → CodeBlock (Shiki, carga diferida, tema dual claro/oscuro)
                            code[lang=mermaid] → MermaidDiagram (import dinámico, securityLevel 'strict')
                            directiva video → VideoEmbed (youtube-nocookie, solo IDs validados)
                            a[href] externo → rel="noopener noreferrer", icono de enlace externo
```

- El HTML crudo del Markdown **se descarta** porque no se incluye `rehype-raw`.
- Los encabezados reciben un `id` y se genera una tabla de contenidos para lecciones largas.
- Tests de Vitest cubren: payloads XSS (`<script>`, `javascript:` en enlaces, `onerror` en imágenes), directivas válidas e inválidas y Mermaid con contenido malicioso.

## 8. Editor de lecciones (admin)

- **CodeMirror 6** (`@uiw/react-codemirror`) con el lenguaje Markdown, en pantalla dividida con el preview en vivo (`MarkdownRenderer` con *debounce*).
- Una barra de inserción para callout, bloque de código, fórmula, Mermaid, video e imagen (esta última desde la Fase 6).
- El **formulario estructurado** cubre título, slug, resumen, por qué importa, objetivos (lista editable), tipo, dificultad, minutos estimados, skills con peso, dependencias (con `DependencyEditor`), recursos, videos, estado y nota de cambio. Cubre §54 completo.
- **Checklist de publicación en vivo** (`PublishReadiness` del backend): muestra qué falta antes de habilitar "Publicar".
- **Historial de versiones:** lista de `lesson_versions`, vista de una versión y diff contra la copia de trabajo.
- Protección de cambios sin guardar: un aviso al navegar con el formulario modificado.

## 9. Formularios, tablas y feedback

- Formularios con el componente `<Form>` y `useForm` de Inertia, más las *form variants* de Wayfinder. La validación la hacen los FormRequest del servidor; los errores se muestran por campo (`InputError`) y los botones indican el envío en curso.
- Tablas del admin **dirigidas por el servidor**: orden, filtros, búsqueda y paginación en la query string, con Laravel paginator y `router.get` con `preserveState`. No se usa TanStack Table: la tabla no ordena en el cliente.
- Las acciones destructivas o de publicación usan un `AlertDialog` de confirmación.
- Los toasts (`sonner`) salen de los mensajes flash del servidor (`use-flash-toast` del starter kit).

## 10. Internacionalización de la UI

```ts
// i18n/es.ts: fuente de verdad de las claves
export const es = { roadmap: { states: { LOCKED: 'Bloqueado', /* … */ } } } as const;
// i18n/en.ts: debe cumplir la misma forma; una clave faltante es error de compilación
export const en: Messages = { /* … */ };
```

`t('roadmap.states.LOCKED')` tiene claves tipadas (template literal types). La interpolación es simple (`{name}`). Los textos que vienen del servidor (razones de las recomendaciones) llegan como `reasonKey` + `params` y se traducen en el cliente.

## 11. Rendimiento

- Las páginas se resuelven de forma diferida con `import.meta.glob`, así que cada página es un *chunk*.
- React Flow y dagre solo cargan en `roadmap/show`. Mermaid, KaTeX y Shiki solo en las lecciones y el editor. CodeMirror solo en el admin.
- El React Compiler viene activado en el starter kit y evita la memoización manual.
- Las imágenes de lecciones usan `loading="lazy"` y dimensiones declaradas (desde `media_assets`).

## 12. Testing frontend

| Nivel | Herramienta | Qué se prueba |
|---|---|---|
| Unitario | Vitest | `useGraphLayout`, filtros del grafo, `t()` y sanitización del Markdown |
| Componente | Vitest + React Testing Library | `ProgressBar`, `StateBadge` (texto accesible), `CompleteLessonButton` (estado de envío), `DependencyEditor`, formulario del editor de lecciones y `RecommendationCard` |
| E2E | Pest Browser (Playwright) | Flujo del estudiante (login → dashboard → roadmap → lección → completar → progreso actualizado) y flujo editorial (admin crea lección → publica → el estudiante la ve) |
| Estático | `tsc --noEmit`, `vp check` (oxlint + oxfmt) | Todo el código de `resources/js` |
