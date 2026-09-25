---
key: git.commits-y-staging
slug: git-commits-arbol-de-trabajo-y-staging
title: "Git: commits, árbol de trabajo y staging"
type: TUTORIAL
difficulty: BEGINNER
estimated_minutes: 40
status: PUBLISHED
last_reviewed: 2026-09-25
summary: >-
  El modelo mental de Git: tres áreas (árbol de trabajo, staging y
  repositorio) y commits como instantáneas encadenadas e identificadas por
  un hash.
why_it_matters: >-
  Sin un modelo mental correcto, Git se usa por memorización de comandos y
  cada error termina en pérdida de trabajo. Con el modelo claro, cualquier
  situación se razona: qué cambió, dónde está y cómo volver atrás.
objectives:
  - Explicar la función del árbol de trabajo, el área de staging y el repositorio.
  - Construir commits atómicos seleccionando cambios parciales con git add -p.
  - Excluir del repositorio secretos, datasets y artefactos pesados con .gitignore.
skills:
  - { key: git, weight: 3 }
depends_on: []
resources: [pro-git-book, git-reference-manual]
---

## Concepto

Git guarda la historia de un proyecto como una secuencia de **commits**. Cada
commit es una instantánea completa de los archivos versionados, con un autor,
un mensaje y un puntero a su commit padre. Su identificador es un hash (SHA)
calculado a partir del contenido: si algo cambia, el hash cambia. Por eso la
historia no se puede alterar sin que se note.

Los cambios pasan por tres áreas:

| Área | Qué contiene | Cómo se llega |
|---|---|---|
| Árbol de trabajo | Los archivos tal como los editas | Editando |
| Staging (índice) | Lo que irá en el próximo commit | `git add` |
| Repositorio | Los commits confirmados | `git commit` |

El staging es lo que permite hacer **commits atómicos**: aunque hayas editado
cinco archivos, eliges qué parte de esos cambios forma una unidad lógica.

## Cómo funciona

```bash
git init                      # crea el repositorio en la carpeta actual
git status                    # qué cambió y en qué área está cada cambio
git diff                      # cambios del árbol de trabajo aún no preparados
git add -p src/parser.py      # elige fragmento por fragmento qué preparar
git diff --staged             # revisa exactamente lo que irá en el commit
git commit -m "feat: parse dates in ISO 8601"
git log --oneline --graph     # historia compacta
git restore --staged archivo  # saca un archivo del staging sin perder cambios
```

La regla de trabajo es: **revisa `git diff --staged` antes de cada commit**.
Ese hábito evita la mayoría de los commits accidentales.

## Errores comunes

- **Commits gigantes** que mezclan una corrección, un refactor y una
  funcionalidad nueva: son imposibles de revisar y de revertir por partes.
- **Versionar secretos.** Un archivo `.env` con claves de API subido una vez
  queda en la historia aunque después se borre. Hay que rotar la clave: borrar
  el archivo no basta.
- Usar `git add .` sin mirar qué se está agregando.

> [!WARNING]
> Si versionaste un archivo que debía ignorarse, agrégalo a `.gitignore` y
> quítalo del índice con `git rm --cached archivo`. El archivo sigue en tu
> disco, pero deja de estar versionado desde ese commit.

## En AI Engineering

Los proyectos de IA tienen tres tipos de archivos que **no** deben ir al
repositorio: secretos (claves de proveedores de modelos), datasets y pesos de
modelos. Los dos últimos son grandes y cambian en forma binaria, así que Git
los maneja mal. Se ignoran en `.gitignore` y se versionan con herramientas
específicas, como Git LFS o DVC, que se verán en el track de MLOps. Los
notebooks también requieren cuidado: sus salidas incluyen datos y ensucian los
diffs, por lo que conviene limpiarlas antes del commit.

## Práctica

Crea un repositorio para un script de análisis. Agrega un `.gitignore` que
excluya `.env`, `data/` y `*.pt`. Haz tres commits atómicos: la estructura
inicial, una función y sus pruebas. En el segundo, edita dos funciones pero
usa `git add -p` para confirmar solo una. Verifica el resultado con
`git log --oneline` y `git show`.
