---
key: git.pull-requests
slug: github-pull-requests-y-code-review
title: "GitHub: pull requests y code review"
type: TUTORIAL
difficulty: INTERMEDIATE
estimated_minutes: 35
status: PUBLISHED
last_reviewed: 2026-09-25
summary: >-
  El flujo de colaboración con pull requests: preparar un cambio revisable,
  pasar las verificaciones automáticas, recibir y dar code review, e
  integrarlo con la estrategia adecuada.
why_it_matters: >-
  El pull request es donde un cambio se vuelve responsabilidad del equipo. Un
  PR bien preparado se revisa rápido y con menos errores. Además, tu historial
  de PRs públicos es evidencia directa de cómo trabajas.
objectives:
  - Preparar un pull request pequeño con descripción, contexto y forma de probarlo.
  - Aplicar un checklist de code review que priorice corrección, pruebas y legibilidad.
  - Elegir entre merge commit, squash y rebase al integrar según la historia que se quiere conservar.
skills:
  - { key: git, weight: 2 }
  - { key: technical-communication, weight: 2 }
depends_on:
  - { lesson: git.ramas-merge-rebase, kind: REQUIRED }
resources: [github-docs-pull-requests, conventional-commits]
---

## Concepto

Un **pull request** (PR) es una propuesta para integrar una rama en otra,
acompañada de una conversación: descripción, verificaciones automáticas (CI)
y revisiones de otras personas. En GitHub, el PR es la unidad de trabajo en
equipo. Ahí se decide si un cambio es correcto antes de que llegue a la rama
principal.

## Cómo funciona

1. **Preparar el cambio.** Una rama con un propósito único. Si el cambio
   supera unas 400 líneas, conviene dividirlo: la calidad de la revisión cae
   rápido con el tamaño.
2. **Abrir el PR** con una descripción que responda qué cambia, por qué,
   cómo se probó y qué riesgos tiene. Los mensajes de commit pueden seguir
   Conventional Commits (`feat:`, `fix:`, `refactor:`), lo que permite
   generar changelogs de forma automática.
3. **Verificaciones automáticas.** El CI ejecuta tests, lint y build. Un PR
   en rojo no se revisa: primero se arregla.
4. **Code review.** Quien revisa lee buscando, en este orden: corrección
   (¿hace lo que dice, en todos los casos?), pruebas (¿un test fallaría si el
   código estuviera mal?), diseño y legibilidad. Los comentarios se formulan
   como observaciones o preguntas concretas, con el motivo.
5. **Integrar.** GitHub ofrece tres estrategias: *merge commit* conserva todos
   los commits más un commit de integración; *squash* combina el PR en un
   solo commit; *rebase* reaplica los commits en línea. Squash es una buena
   opción por defecto cuando los commits intermedios no aportan información.

> [!NOTE]
> Las reglas de protección de ramas en GitHub pueden exigir CI en verde y al
> menos una aprobación antes de integrar. Actívalas incluso en proyectos
> personales: te obligan a trabajar con el mismo proceso que un equipo.

## Errores comunes

- PRs de 2.000 líneas que reciben un "LGTM" sin revisión real.
- Descripciones vacías que obligan al revisor a adivinar la intención.
- Comentarios de revisión sobre estilo que un linter debería detectar
  automáticamente.
- Integrar con el CI en rojo porque "el fallo no es mío".

## En AI Engineering

En sistemas con LLMs, **un cambio de prompt es un cambio de código** y se
revisa igual. La descripción del PR debe incluir el resultado de la
evaluación antes y después del cambio. Por ejemplo: "exactitud en el conjunto
de evaluación: 84 % → 88 %; costo medio por solicitud sin cambios". Sin ese
dato, el revisor no puede juzgar si el cambio mejora el sistema. Muchos
equipos ejecutan la evaluación en el CI y publican la tabla como comentario
en el PR.

## Práctica

En un repositorio propio, agrega una plantilla de PR en
`.github/pull_request_template.md` con las secciones Qué, Por qué, Cómo se
probó y Riesgos. Abre un PR pequeño usando la plantilla y activa la
protección de la rama principal. Al día siguiente, revisa tu propio PR con el
checklist de esta lección y deja al menos dos comentarios concretos.
