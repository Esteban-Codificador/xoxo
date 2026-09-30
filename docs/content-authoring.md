# Guía de autoría de contenido

Cómo escribir o cambiar contenido del **paquete** (`content/ai-engineer`) y cómo llevar al paquete lo editado en el CMS (`/admin`). La referencia completa del formato y de las reglas está en [content-architecture.md](content-architecture.md).

## Flujo de trabajo

```bash
# 1. Escribe o edita archivos en content/ai-engineer
# 2. Valida: esquema, referencias, ciclos, Markdown y contrato pedagógico
php artisan content:validate

# 3. Mira qué cambiaría en la BD sin guardar nada
php artisan content:import --dry-run

# 4. Importa
php artisan content:import

# 5. Antes del PR, si agregaste recursos: comprueba sus URLs (CI lo repite y bloquea ante 404)
php artisan content:verify-links
```

El importador es **idempotente** y **no sobrescribe lo editado en el CMS**. Si una lección se cambió desde el admin después de la última importación, el paquete la salta y avisa. Para imponer la versión del paquete: `php artisan content:import --force`.

## Del CMS al paquete

Lo que se edita en `/admin` vive en la base de datos. Para llevarlo al repositorio:

```bash
php artisan content:export --dry-run   # qué archivos cambiarían
php artisan content:export             # escribe content/ai-engineer
git diff content/                      # solo cambia lo editado; revisa y haz commit
```

- Se exporta **lo que ven los estudiantes**: si una lección tiene cambios sin publicar, se exporta su versión publicada y el comando lo avisa. Publica antes de exportar si quieres llevarlos.
- Lo que no cambió conserva su texto, hasta el nivel de párrafo: en el cuerpo de una lección o en la descripción del roadmap, solo se reescriben los bloques editados. Lo editado se escribe en formato canónico: cada párrafo en una línea y los textos del front matter entre comillas cuando hace falta.
- Si un archivo cambió a mano y no se importó, el export se detiene para no pisarlo: impórtalo primero. Si cambió en los dos lados, decide cuál vale: `content:import --force` (gana el archivo) o `content:export --force` (gana la base de datos).
- Tras exportar, la base de datos y el paquete quedan sincronizados: `content:import` no cambia nada y `migrate:fresh --seed` reconstruye la base desde el paquete sin perder lo editado. El historial de versiones no viaja: cada lección vuelve como versión 1.
- Para un respaldo o una revisión en otro directorio: `php artisan content:export /ruta/copia --copy` (no altera la sincronización).

## Dónde va cada archivo

```text
content/ai-engineer/
├── roadmap.yaml
├── tracks/NN-<track>/track.md
├── tracks/NN-<track>/NN-<módulo>/module.md
├── tracks/NN-<track>/NN-<módulo>/NN-<lección>.md
├── skills/<skill>.md
└── resources/<área>.yaml
```

El prefijo `NN-` define el orden. La **clave** (`key`) del front matter es la identidad: renombrar o mover el archivo no crea un duplicado, pero **cambiar la `key` sí**, así que no la cambies.

## Plantilla de lección

```markdown
---
key: git.ramas-merge-rebase          # estable, nunca cambia
slug: ramas-merge-y-rebase           # URL pública
title: Ramas, merge y rebase
type: TUTORIAL                       # CONCEPT | TUTORIAL | READING | VIDEO | DOCUMENTATION | CHALLENGE
difficulty: INTERMEDIATE             # BEGINNER | INTERMEDIATE | ADVANCED | EXPERT
estimated_minutes: 45
status: PUBLISHED                    # DRAFT | REVIEW | PUBLISHED | ARCHIVED
last_reviewed: 2026-09-25
summary: >-
  Qué es la lección, en 1 a 3 frases (mínimo 80 caracteres).
why_it_matters: >-
  Para qué sirve profesionalmente (mínimo 80 caracteres).
objectives:                          # mínimo 2, con verbos observables
  - Explicar ...
  - Resolver ...
skills:
  - { key: git, weight: 3 }          # peso 1-5: cuánto desarrolla esa skill
depends_on:
  - { lesson: git.commits-y-staging, kind: REQUIRED }   # o RECOMMENDED
resources: [pro-git-book]            # claves de resources/*.yaml
---

## Concepto
## Cómo funciona
## En código
## Errores comunes
## En AI Engineering
## Práctica
```

Una lección `PUBLISHED` debe cumplir el contrato: resumen y "por qué importa" de 80 o más caracteres, al menos 2 objetivos, cuerpo de 1.500 o más caracteres de texto, al menos 1 skill y una sección `## Práctica` (o un ejercicio o laboratorio asociado, desde la Fase 6). Su módulo y su track deben estar publicados. `content:validate` indica qué falta.

## Sintaxis del cuerpo

Es Markdown compatible con GitHub, así que se ve igual en un PR. Se convierte a contenido enriquecido al importar.

| Quieres | Escribe |
|---|---|
| Código | ```` ```python ```` … ```` ``` ```` |
| Callout | `> [!TIP]`, `> [!NOTE]`, `> [!IMPORTANT]`, `> [!WARNING]`, `> [!CAUTION]` |
| Fórmula en línea / en bloque | `$a \cdot b$` / ```` ```math ```` |
| Diagrama | ```` ```mermaid ```` |
| Video de YouTube | ```` ```video ```` con las líneas `provider: youtube` e `id: <ID>` |
| Imagen | `![texto alternativo](media/archivo.png)`, sola en su párrafo |

No se admiten HTML crudo ni encabezados H1 (el título ya lo es).

**Imágenes.** Lo normal es subirlas desde el editor del CMS (botón "Imagen"): se guarda una copia limpia, sin metadatos, de hasta 2400 px por lado, y `content:export` la escribe en `media/` con un nombre sacado de su contenido (`media/3f2a9c1e4b5d6a7f.png`). Si la añades a mano al paquete:

- PNG, JPEG o WebP, hasta 5 MB y 4096 px por lado, en `media/` (sin subcarpetas; nombre con letras, números, `.`, `-` o `_`).
- La ruta en el Markdown es relativa a la **raíz del paquete**, no al archivo de la lección: `media/…` desde cualquier lección.
- El **texto alternativo es obligatorio**: describe lo que la imagen enseña para quien no puede verla ("Diagrama: los datos pasan por entrenamiento, evaluación y despliegue"), no "imagen" ni el nombre del archivo. Una sola línea, hasta 300 caracteres.
- Sin título (`![alt](ruta "título")` es un error).
- El importador guarda el archivo tal cual (no lo reencodifica): quítale tú los metadatos si es una foto. El siguiente `content:export` lo renombra por su contenido y actualiza la ruta.

**Lenguajes con resaltado:** `python`, `bash`, `javascript`, `typescript`, `tsx`, `jsx`, `json`, `yaml`, `toml`, `ini`, `sql`, `php`, `html`, `css`, `xml`, `markdown`, `dockerfile`, `diff`, `go`, `rust`, `java`, `c` y `cpp`, más alias comunes (`py`, `sh`, `shell`, `console`, `js`, `ts`, `yml`, `md`, `docker`). Cualquier otro se muestra como texto plano con su etiqueta.

**Diagramas legibles:** el lector reduce un diagrama ancho hasta el 75 % de su tamaño y, a partir de ahí, lo desplaza en horizontal. En móvil, un `flowchart LR` de más de 4 nodos obliga a desplazarse. Prefiere `flowchart TD` para procesos largos y deja `LR` para 2–4 nodos.

**Revisar cómo se ve:** en local, `/_dev/design-system?lesson=<slug>` renderiza cualquier lección publicada con el mismo componente que verá el estudiante, en claro, oscuro y móvil.

## Videos

Lo normal es añadirlos desde el CMS (**Videos → Nuevo video**): pegas el enlace de YouTube, "Buscar en YouTube" confirma que existe y trae el título y el canal, y escribes la duración, el idioma y para qué sirve. Después se adjuntan a una lección en su pestaña **Relaciones**. El estudiante solo ve los publicados que YouTube confirma como disponibles (se comprueban al guardarlos y cada noche).

En el paquete viven en `videos/*.yaml`, como los recursos:

```yaml
- key: redes-neuronales-intuicion
  url: 'https://www.youtube.com/watch?v=<ID>'   # o youtu.be/<ID>, o el ID de 11 caracteres
  title: 'But what is a neural network?'
  instructor: 3Blue1Brown
  duration: '18:40'                             # minutos:segundos; entre comillas
  language: en
  description: 'Qué aporta a la lección.'
```

La lección los lista en orden con `videos: [redes-neuronales-intuicion]` en su front matter. **Nunca escribas un ID de memoria**: cópialo de YouTube. CI (`content:verify-links`) comprueba cada video del paquete, y cada bloque ```video del texto, con oEmbed y bloquea el merge si no existe o no se puede insertar.

## Recursos externos

```yaml
- key: pro-git-book
  title: Pro Git (2.ª edición)
  url: https://git-scm.com/book/en/v2   # siempre https, nunca inventada
  type: BOOK                            # DOCUMENTATION | ARTICLE | TUTORIAL | COURSE | BOOK | PAPER | REPOSITORY | TOOL
  provider: Git (git-scm.com)
  is_official: true
  difficulty: BEGINNER
  language: en
  description: Por qué este recurso y para qué usarlo.
```

Prioriza documentación oficial. Nunca escribas una URL que no hayas abierto: CI la comprobará y un 404 bloquea el merge.

## Estilo

Español neutro, términos de la industria en inglés con su explicación, afirmaciones concretas y verificables, y los detalles volátiles de APIs enlazados a la documentación oficial en lugar de copiados. Guía completa: [content-architecture §10](content-architecture.md#10-guía-de-estilo).
