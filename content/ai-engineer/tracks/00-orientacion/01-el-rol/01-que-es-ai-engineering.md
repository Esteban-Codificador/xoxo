---
key: orientacion.que-es-ai-engineering
slug: que-es-ai-engineering
title: Qué es AI Engineering
type: CONCEPT
difficulty: BEGINNER
estimated_minutes: 25
status: PUBLISHED
last_reviewed: 2026-09-25
summary: >-
  AI Engineering es la disciplina de construir productos confiables sobre
  modelos de IA, sobre todo modelos fundacionales, combinando ingeniería de
  software, datos, evaluación y operación.
why_it_matters: >-
  En la mayoría de las empresas el valor de la IA no viene de entrenar modelos
  nuevos, sino de integrarlos bien: con el contexto correcto, una evaluación
  medible, costos controlados y una operación segura. Ese es el trabajo del AI
  Engineer.
objectives:
  - Explicar qué distingue a un sistema de IA de un sistema de software determinista.
  - Identificar las cinco capas de un sistema de IA (modelo, contexto, orquestación, evaluación y operación) en un caso concreto.
  - Describir el ciclo de trabajo del AI Engineer, desde la línea base hasta producción.
skills:
  - { key: ai-lifecycle, weight: 2 }
depends_on: []
resources: [google-rules-of-ml]
---

## Concepto

En el software tradicional, el comportamiento lo define el código: con la
misma entrada, la función devuelve la misma salida y un test unitario lo
garantiza. En un sistema de IA, una parte del comportamiento la define un
**modelo** aprendido a partir de datos. Ese comportamiento es estadístico:
acierta en la mayoría de los casos, falla en otros y puede cambiar cuando
cambian los datos, el modelo o la forma de pedirle las cosas.

AI Engineering es la disciplina que convierte esa pieza probabilística en un
producto confiable. Con la llegada de los modelos fundacionales (LLMs y
modelos multimodales entrenados a gran escala), el trabajo se desplazó: ya no
empieza por entrenar un modelo desde cero, sino por **elegir, adaptar,
evaluar y operar** un modelo que ya existe, a través de una API o con pesos
abiertos.

## Cómo funciona

Un sistema de IA en producción tiene cinco capas. Cada una es responsabilidad
del AI Engineer, aunque en equipos grandes se reparta:

1. **Modelo.** Qué modelo usar y dónde corre: API de un proveedor o modelo
   propio. Se elige por capacidad para la tarea, costo por solicitud,
   latencia y restricciones de privacidad.
2. **Contexto y datos.** Qué información recibe el modelo en cada solicitud:
   documentos recuperados, registros de una base de datos o el historial de
   la conversación. Aquí viven técnicas como RAG.
3. **Orquestación.** Cómo se encadenan los pasos: prompts, llamadas a
   herramientas, agentes y validación de la salida.
4. **Evaluación.** Cómo se mide si el sistema funciona: un conjunto de casos
   con resultado esperado, métricas y revisión humana. Sin evaluación no hay
   forma de saber si un cambio mejora o empeora el sistema.
5. **Operación.** Observabilidad, control de costos, seguridad, versionado de
   prompts y modelos, y respuesta ante fallos.

El ciclo de trabajo típico es iterativo: definir la tarea y la métrica de
éxito, construir una línea base simple, armar un conjunto de evaluación,
iterar sobre prompts, contexto o modelo midiendo contra ese conjunto, y
desplegar con monitoreo.

> [!TIP]
> Si no puedes escribir cómo medirías el éxito de un sistema de IA antes de
> construirlo, todavía no entendiste el problema.

## En AI Engineering

Un caso concreto: clasificar tickets de soporte en categorías. La línea base
puede ser un conjunto de reglas por palabras clave. La alternativa con IA es
pedirle a un LLM que clasifique cada ticket con una salida estructurada. La
decisión no se toma por intuición. Se toman 200 tickets ya etiquetados y se
mide la exactitud por categoría, el costo por ticket y la latencia de ambas
opciones. Si el LLM mejora la exactitud del 72 % al 91 % a un costo
aceptable, se adopta. Después se monitorean en producción los casos que el
modelo clasifica con baja confianza.

## Errores comunes

- Empezar por el modelo más grande disponible sin haber definido una métrica.
- Evaluar mirando cinco ejemplos y concluir que "funciona bien".
- Ignorar el costo y la latencia hasta el momento de salir a producción.
- Tratar la salida del modelo como confiable sin validarla: formato, rango de
  valores y riesgos de seguridad.

## Práctica

Elige un proceso de tu trabajo que hoy sea manual, como el triage de correos
o la extracción de datos de documentos. Escribe una página con: la tarea, la
entrada y la salida esperadas, la métrica de éxito, 20 casos de prueba con su
resultado correcto, una línea base sin IA y los riesgos si el sistema se
equivoca. Ese documento es el punto de partida de cualquier proyecto de AI
Engineering.
