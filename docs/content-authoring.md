# Guía de autoría de contenido

Cómo escribir o cambiar contenido del **paquete** (`content/ai-engineer`). Cuando exista el CMS (Fase 5), el contenido también se editará desde `/admin`. La referencia completa del formato y de las reglas está en [content-architecture.md](content-architecture.md).

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

No se admiten HTML crudo, encabezados H1 (el título ya lo es) ni, hasta la Fase 6, imágenes.

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
