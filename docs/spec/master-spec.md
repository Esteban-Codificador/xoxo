# AI Engineer Roadmap — Prompt Maestro para Claude Code

## Propósito

Este documento contiene el prompt maestro para construir una plataforma educativa completa orientada a formar **AI Engineers**, junto con las decisiones de diseño fundamentales que deben mantenerse durante la implementación.

La idea no es construir una landing page ni un README interactivo. El objetivo es crear un **producto educativo técnico real**, extensible y administrable, que combine:

- Roadmap visual.
- Grafo de conocimientos y skills.
- LMS.
- Aprendizaje basado en proyectos.
- Ejercicios y laboratorios.
- Quizzes.
- Gestión de progreso.
- Recursos externos.
- Videos.
- CMS administrativo.
- Gamificación moderada.
- Portfolio.
- Arquitectura preparada para incorporar IA como tutor y recomendador.

---

# 1. PRINCIPIO FUNDAMENTAL

La plataforma debe diseñarse como un **grafo de conocimiento y aprendizaje**, no como una lista lineal de cursos.

Ejemplo conceptual:

```text
PROGRAMACIÓN
     │
     ▼
PYTHON
     │
     ├───────────────┐
     ▼               ▼
ESTRUCTURAS       POO
     │               │
     └───────┬───────┘
             ▼
       NUMPY / PANDAS
             │
             ▼
      MATEMÁTICAS PARA IA
             │
      ┌──────┴──────┐
      ▼             ▼
 MACHINE LEARNING   DATOS
      │             │
      └──────┬──────┘
             ▼
       DEEP LEARNING
             │
       ┌─────┴──────┐
       ▼            ▼
       NLP         CV
       │            │
       └─────┬──────┘
             ▼
         LLM / GENAI
             │
             ▼
      AI ENGINEERING
             │
       ┌─────┴─────────┐
       ▼               ▼
   MLOps           LLMOps
       │               │
       └───────┬───────┘
               ▼
       PRODUCCIÓN / CLOUD
```

La plataforma debe representar relaciones entre:

- Skills.
- Lecciones.
- Conceptos.
- Prerequisitos.
- Ejercicios.
- Laboratorios.
- Proyectos.
- Tecnologías.

---

# 2. DECISIÓN CLAVE: DOS CAPAS

## Capa 1 — Plataforma

La aplicación debe proporcionar:

- Roadmap.
- Grafo.
- LMS.
- Admin.
- Progreso.
- Ejercicios.
- Quizzes.
- Proyectos.
- Videos.
- Recursos.
- Usuarios.
- Roles.
- Búsqueda.
- Gamificación.
- Portfolio.

## Capa 2 — Contenido

El contenido debe ser independiente de la aplicación:

- Currículo.
- Lecciones.
- Ejercicios.
- Laboratorios.
- Proyectos.
- Recursos.
- Videos.
- Evaluaciones.

### Regla

**No escribir cientos de lecciones completas directamente en componentes del frontend.**

El contenido debe poder administrarse desde el CMS y almacenarse correctamente en la base de datos.

Esto permite que la plataforma crezca sin modificar el código fuente.

---

# 3. ROL PARA CLAUDE CODE

Actúa como:

- Staff/Principal Software Engineer.
- Solution Architect.
- Full Stack Engineer senior.
- UX/UI Engineer.
- Learning Experience Designer.
- AI Engineer.
- MLOps Engineer.
- DevOps Engineer.
- QA Engineer.
- Technical Writer.
- Product Manager técnico.

Debes pensar como si estuvieras construyendo un producto que posteriormente pudiera convertirse en una plataforma educativa real.

No construyas una demo superficial.

---

# 4. OBJETIVO DEL PRODUCTO

Construir una plataforma llamada provisionalmente:

**AI Engineer Roadmap**

El nombre debe poder cambiarse posteriormente.

El usuario debe poder:

1. Ver el roadmap completo.
2. Entender qué debe aprender.
3. Saber en qué orden.
4. Comprender por qué necesita cada conocimiento.
5. Estudiar teoría.
6. Consultar recursos.
7. Ver videos.
8. Resolver ejercicios.
9. Realizar laboratorios.
10. Completar proyectos.
11. Registrar progreso.
12. Identificar prerequisitos.
13. Ver skills dominadas.
14. Seguir rutas alternativas.
15. Construir portfolio.
16. Prepararse progresivamente para trabajo profesional.

---

# 5. PRINCIPIOS PEDAGÓGICOS

Cada contenido debe responder:

### ¿Qué es?

Explicación clara.

### ¿Por qué importa?

Aplicación profesional.

### ¿Qué debo saber antes?

Prerequisitos.

### ¿Qué debo aprender después?

Siguiente paso.

### ¿Cómo se practica?

Ejercicio o laboratorio.

### ¿Cómo sé que lo aprendí?

Evaluación o evidencia.

### ¿Qué proyecto puedo construir?

Aplicación práctica.

---

# 6. ROADMAP CURRICULAR

El roadmap inicial debe ser amplio y coherente.

## Nivel 0 — Orientación

- Qué es AI Engineering.
- AI Engineer vs ML Engineer.
- AI Engineer vs Data Scientist.
- AI Engineer vs Data Engineer.
- AI Engineer vs MLOps Engineer.
- AI Engineer vs Software Engineer especializado en IA.
- Cómo estudiar.
- Cómo medir progreso.
- Cómo construir portfolio.
- GitHub.
- Documentación técnica.

## Nivel 1 — Fundamentos de computación

### Programación

- Variables.
- Tipos.
- Condicionales.
- Bucles.
- Funciones.
- Recursividad.
- Excepciones.
- Modularidad.
- Paquetes.
- Entornos virtuales.
- Dependencias.

### Estructuras de datos

- Arrays.
- Lists.
- Tuples.
- Sets.
- Dictionaries.
- Stacks.
- Queues.
- Trees.
- Graphs.

### Algoritmos

- Big O.
- Complejidad temporal.
- Complejidad espacial.
- Búsqueda.
- Ordenamiento.
- Recursividad.
- Grafos.

### Git

- Git.
- GitHub.
- Branches.
- Pull Requests.
- Merge.
- Rebase.
- Conventional Commits.

---

# 7. PYTHON PARA AI ENGINEERING

Debe ser una ruta especialmente completa:

- Sintaxis.
- POO.
- Decorators.
- Generators.
- Iterators.
- Context managers.
- Typing.
- Dataclasses.
- Async.
- Concurrency.
- Testing.
- Pytest.
- Logging.
- Packaging.
- Poetry/uv/pip.
- Virtual environments.
- CLI.
- APIs.

Bibliotecas:

- NumPy.
- Pandas.
- Matplotlib.
- Polars.
- Jupyter.

---

# 8. MATEMÁTICAS PARA IA

Enseñar matemáticas orientadas a aplicaciones de IA, evitando abstracción innecesaria.

## Álgebra lineal

- Vectores.
- Matrices.
- Producto punto.
- Multiplicación matricial.
- Transpuesta.
- Inversa.
- Eigenvalues.
- Eigenvectors.
- Transformaciones.

## Cálculo

- Derivadas.
- Gradientes.
- Derivadas parciales.
- Regla de la cadena.
- Gradient Descent.

## Probabilidad

- Variables aleatorias.
- Distribuciones.
- Esperanza.
- Varianza.
- Bayes.
- Probabilidad condicional.

## Estadística

- Media.
- Mediana.
- Desviación.
- Correlación.
- Covarianza.
- Inferencia.

Cada concepto debe conectarse con Machine Learning.

---

# 9. DATA ENGINEERING PARA IA

- SQL.
- PostgreSQL.
- Modelado.
- ETL.
- ELT.
- Data pipelines.
- CSV.
- JSON.
- Parquet.
- APIs.
- Web scraping.
- Data validation.
- Data cleaning.
- Feature engineering.
- Data warehouses.
- Data lakes.
- Vector databases.

---

# 10. MACHINE LEARNING

## Fundamentos

- Supervised Learning.
- Unsupervised Learning.
- Semi-supervised Learning.
- Reinforcement Learning.

## Algoritmos

- Linear Regression.
- Logistic Regression.
- Decision Trees.
- Random Forest.
- Gradient Boosting.
- XGBoost.
- LightGBM.
- KNN.
- SVM.
- K-Means.
- PCA.

## Conceptos

- Train/Test.
- Validation.
- Cross Validation.
- Overfitting.
- Underfitting.
- Bias/Variance.
- Regularization.
- Feature selection.
- Hyperparameter tuning.

## Evaluación

- Accuracy.
- Precision.
- Recall.
- F1.
- ROC-AUC.
- Confusion Matrix.
- Regression metrics.

---

# 11. DEEP LEARNING

- Neural Networks.
- Perceptron.
- Activation Functions.
- Forward Propagation.
- Backpropagation.
- Loss Functions.
- Optimizers.
- Batch.
- Epoch.
- Learning Rate.

Frameworks:

- PyTorch.
- TensorFlow/Keras.

Arquitecturas:

- CNN.
- RNN.
- LSTM.
- GRU.
- Autoencoders.
- Transformers.

---

# 12. COMPUTER VISION

- Images.
- Pixels.
- Convolutions.
- Classification.
- Object Detection.
- Segmentation.
- OCR.
- Embeddings visuales.
- Transfer Learning.
- Vision Transformers.

Proyectos:

- Clasificador de imágenes.
- Detector de objetos.
- OCR.
- Inspección visual.

---

# 13. NLP

- Tokenization.
- Stemming.
- Lemmatization.
- TF-IDF.
- Word Embeddings.
- Word2Vec.
- Transformers.
- Attention.
- Text Classification.
- NER.
- Sentiment Analysis.
- Summarization.
- Translation.

---

# 14. TRANSFORMERS

Explicar profundamente:

- Attention.
- Self-Attention.
- Multi-Head Attention.
- Positional Encoding.
- Encoder.
- Decoder.
- Token embeddings.
- Context window.
- Transformer architecture.

Incluir visualizaciones y ejemplos.

---

# 15. LLM ENGINEERING

Esta debe ser una de las rutas principales.

- LLM fundamentals.
- Tokens.
- Context windows.
- Temperature.
- Sampling.
- Prompt engineering.
- System prompts.
- Structured outputs.
- Function calling.
- Tool calling.
- Streaming.
- Embeddings.
- Fine-tuning.
- LoRA.
- QLoRA.
- Quantization.
- Inference.
- Model selection.
- Model evaluation.

Ecosistema:

- Hugging Face.
- OpenAI APIs.
- Anthropic APIs.
- Google Gemini APIs.
- Ollama.
- vLLM.

No acoplar la plataforma a un único proveedor.

---

# 16. RAG

Implementar una ruta completa:

```text
Documentos
   ↓
Parsing
   ↓
Chunking
   ↓
Embeddings
   ↓
Vector Database
   ↓
Retrieval
   ↓
Reranking
   ↓
Prompt Construction
   ↓
LLM
   ↓
Response
   ↓
Evaluation
```

Temas:

- Naive RAG.
- Advanced RAG.
- Chunking.
- Metadata.
- Hybrid search.
- Semantic search.
- Reranking.
- Query rewriting.
- Context compression.
- Retrieval evaluation.
- Hallucination mitigation.

Vector databases:

- pgvector.
- Qdrant.
- Pinecone.
- Weaviate.

---

# 17. AI AGENTS

- Qué es un agente.
- Tool use.
- Function calling.
- Planning.
- Memory.
- State.
- Multi-agent systems.
- Agent loops.
- Human-in-the-loop.
- Agent evaluation.
- Agent security.

Frameworks:

- LangChain.
- LangGraph.
- LlamaIndex.
- MCP.

Construir agentes reales.

---

# 18. MLOPS / LLMOPS

- Experiment tracking.
- Model registry.
- Dataset versioning.
- Model versioning.
- Monitoring.
- Logging.
- Tracing.
- Evaluation.
- Drift.
- Data quality.
- Model quality.
- Prompt versioning.

Herramientas:

- MLflow.
- Weights & Biases.
- Docker.
- Kubernetes.
- GitHub Actions.

---

# 19. CLOUD

Introducción práctica a:

- AWS.
- Azure.
- Google Cloud.

Conceptos:

- Compute.
- Storage.
- Networking.
- Containers.
- Serverless.
- GPUs.
- Managed ML services.
- Secrets.
- IAM.
- Observability.

No enseñar todos los servicios existentes. Priorizar lo relevante para AI Engineering.

---

# 20. AI SYSTEM DESIGN

Casos:

- Chatbot empresarial.
- Sistema RAG.
- Recomendador.
- OCR.
- Plataforma de agentes.
- Sistema de inferencia.
- Plataforma AI multi-tenant.

Incluir:

- Arquitecturas.
- Trade-offs.
- Escalabilidad.
- Costos.
- Latencia.
- Seguridad.
- Disponibilidad.
- Observabilidad.

---

# 21. AI SECURITY

- Prompt injection.
- Jailbreaking.
- Data leakage.
- Model abuse.
- RAG poisoning.
- Insecure tool calling.
- Secrets.
- PII.
- Access control.
- Rate limiting.
- Authentication.
- Authorization.
- Audit logs.

---

# 22. PRODUCCIÓN

- Docker.
- CI/CD.
- Testing.
- Monitoring.
- Logging.
- Tracing.
- Scaling.
- Caching.
- Queues.
- Background jobs.
- API design.
- Rate limits.
- Cost optimization.

---

# 23. PORTFOLIO

Crear proyectos progresivos:

1. Python CLI.
2. Data analysis.
3. Machine Learning API.
4. Deep Learning application.
5. Computer Vision.
6. NLP application.
7. RAG.
8. AI Agent.
9. Production AI system.
10. Proyecto final de AI Engineering.

Cada proyecto debe incluir:

- Problema.
- Objetivos.
- Requisitos.
- Arquitectura.
- Tecnologías.
- Dataset.
- Implementación.
- Tests.
- Docker.
- README.
- Deployment.
- Métricas.
- Mejoras.
- Preguntas de entrevista.

---

# 24. NIVELES Y TIPOS DE CONTENIDO

Dificultad:

```text
BEGINNER
INTERMEDIATE
ADVANCED
EXPERT
```

Tipos:

```text
CONCEPT
TUTORIAL
EXERCISE
LAB
PROJECT
QUIZ
READING
VIDEO
DOCUMENTATION
CHALLENGE
```

---

# 25. PREREQUISITOS

Cada contenido debe poder indicar:

- Prerequisitos.
- Contenidos relacionados.
- Siguiente contenido.
- Skills desarrolladas.

Ejemplo:

```text
Python
  ↓
NumPy
  ↓
Linear Algebra
  ↓
Machine Learning
  ↓
Deep Learning
  ↓
Transformers
  ↓
LLMs
  ↓
RAG
  ↓
Agents
```

El sistema debe utilizar estas dependencias para desbloquear contenidos.

---

# 26. ESTADOS DEL ROADMAP

```text
LOCKED
AVAILABLE
IN_PROGRESS
COMPLETED
MASTERED
```

---

# 27. ROADMAP VISUAL

Debe permitir:

- Zoom.
- Pan.
- Expandir nodos.
- Contraer ramas.
- Buscar.
- Filtrar.
- Ver progreso.
- Ver prerequisitos.
- Ver dependencias.
- Click en nodo.
- Hover.
- Responsive.

Utilizar una librería madura para grafos cuando sea apropiado. No reinventar el sistema desde cero.

En móvil debe existir una experiencia alternativa si el grafo completo no es usable.

---

# 28. DASHBOARD DEL ESTUDIANTE

Mostrar:

- Progreso general.
- Progreso por categoría.
- Horas estudiadas.
- Skills completadas.
- Skills pendientes.
- Proyectos.
- Racha.
- Próximos contenidos.
- Recomendaciones.
- Actividad.

Ejemplo:

```text
AI ENGINEER
████████████████░░░░ 78%

Programming       ██████████████████ 90%
Math              ███████████░░░░░░ 65%
ML                █████████████░░░░ 72%
Deep Learning     ██████████░░░░░░░ 55%
LLM Engineering   ███████████████░░ 80%
MLOps             ██████░░░░░░░░░░░ 40%
```

---

# 29. PÁGINA DE LECCIÓN

Cada tema puede contener:

```text
Overview
Why it matters
Prerequisites
Theory
Examples
Code
Exercises
Lab
Quiz
Project
Resources
Videos
Next steps
```

---

# 30. CMS ADMINISTRATIVO

Crear `/admin`.

Debe poder gestionar:

- Roadmaps.
- Tracks.
- Modules.
- Lessons.
- Topics.
- Skills.
- Exercises.
- Projects.
- Quizzes.
- Questions.
- Resources.
- Videos.
- References.
- Code examples.

---

# 31. EDITOR RICO

Debe permitir:

- Markdown.
- Rich text.
- Código.
- Imágenes.
- Tablas.
- Links.
- Callouts.
- Mermaid.
- Fórmulas matemáticas.
- Videos.
- Embeds.

Código con syntax highlighting.

---

# 32. VIDEOS

Debe soportar:

## YouTube

Guardar:

- URL.
- Video ID.
- título.
- descripción.
- duración.
- thumbnail.
- instructor.
- nivel.

## Video propio

Preparar arquitectura para almacenamiento futuro mediante:

- S3.
- Cloudflare R2.
- Azure Blob.
- Google Cloud Storage.

No acoplar la aplicación al almacenamiento local.

---

# 33. RECURSOS EXTERNOS

Cada recurso:

```text
title
url
type
provider
description
difficulty
is_official
```

Priorizar documentación oficial y fuentes confiables.

No inventar URLs.

---

# 34. EJERCICIOS

Tipos:

- Multiple choice.
- Código.
- Conceptual.
- Ordenamiento.
- Relación.
- Debugging.
- Arquitectura.

---

# 35. QUIZZES

Cada quiz debe soportar:

- Preguntas.
- Opciones.
- Respuesta correcta.
- Explicación.
- Dificultad.
- Puntaje.
- Intentos.
- Tiempo.
- Preguntas falladas.

---

# 36. LABORATORIOS

Estructura:

```text
Objetivo
Prerequisitos
Contexto
Setup
Steps
Expected result
Validation
Challenge
Solution
```

---

# 37. PROYECTOS

Cada proyecto:

```text
Problem
Context
Requirements
Architecture
Stack
Dataset
Milestones
Tasks
Evaluation
Deliverables
Expected skills
```

El usuario puede marcar milestones.

---

# 38. SISTEMA DE SKILLS

Ejemplos:

```text
Python
SQL
Git
Docker
Linear Algebra
Statistics
Machine Learning
PyTorch
Transformers
RAG
Agents
MLOps
Cloud
System Design
```

Cada skill tiene:

- Nivel.
- Descripción.
- Contenidos asociados.
- Proyectos.
- Ejercicios.
- Prerequisitos.

---

# 39. PERFIL

Crear:

- Perfil.
- Skills.
- Progreso.
- Proyectos.
- Certificados internos.
- Actividad.
- Estadísticas.

Preparar arquitectura para una futura URL pública de portfolio.

---

# 40. SISTEMA DE RECOMENDACIÓN

Inicialmente utilizar reglas deterministas.

Ejemplo:

Si:

```text
Python >= 80%
Math >= 70%
```

entonces:

```text
Machine Learning → AVAILABLE
```

Si falta un prerrequisito:

> Antes de continuar, completa Python Fundamentals.

Diseñar una interfaz que posteriormente permita incorporar recomendaciones mediante IA.

---

# 41. ROLES

Inicialmente:

```text
ADMIN
EDITOR
INSTRUCTOR
STUDENT
```

Preparar permisos granulares.

---

# 42. PUBLICACIÓN

Estados:

```text
DRAFT
REVIEW
PUBLISHED
ARCHIVED
```

El contenido no debe aparecer públicamente hasta estar publicado.

---

# 43. AUDITORÍA

Registrar:

- Usuario.
- Acción.
- Entidad.
- Cambios.
- Fecha.
- Publicación.

---

# 44. BÚSQUEDA

Buscar:

- Skills.
- Lessons.
- Projects.
- Exercises.
- Resources.
- Videos.

Preparar arquitectura para PostgreSQL Full Text y posteriormente Elasticsearch/OpenSearch si fuera necesario.

---

# 45. GAMIFICACIÓN

Implementar moderadamente:

- XP.
- Streak.
- Badges.
- Milestones.
- Completion percentage.

La gamificación debe reforzar el aprendizaje, no convertirse en ruido visual.

---

# 46. CERTIFICADOS

Preparar certificados internos cuando se complete una ruta.

No presentarlos como títulos académicos ni certificaciones oficiales.

---

# 47. ARQUITECTURA

Priorizar:

- Clean Architecture cuando aporte valor.
- SOLID.
- Separación de responsabilidades.
- Modularidad.
- Testing.
- Seguridad.
- Escalabilidad.

Evitar:

- God classes.
- God components.
- Duplicación.
- Hardcoding.
- Acoplamiento innecesario.
- Archivos gigantes.

---

# 48. STACK PROPUESTO

Si el repositorio está vacío:

### Backend

Laravel.

### Frontend

React + TypeScript.

### Bridge

Inertia.js.

### UI

Tailwind CSS.

### Componentes

shadcn/ui.

### Database

PostgreSQL.

### Cache / Queues

Redis.

### Authentication

Laravel Fortify/Breeze o equivalente apropiado.

### Testing

Pest + PHPUnit.

Frontend:

- Vitest.
- React Testing Library.

E2E:

- Playwright.

### Infraestructura

Docker.

### CI/CD

GitHub Actions.

Si ya existe un stack, analizarlo primero y no reemplazarlo automáticamente.

---

# 49. MODELO DE DATOS

Como mínimo considerar:

```text
users
roles
permissions

roadmaps
tracks
modules
lessons
topics
skills

skill_dependencies

lesson_skills
lesson_dependencies

exercises
exercise_attempts

quizzes
quiz_questions
quiz_attempts

projects
project_milestones

resources
videos

student_progress
student_skill_progress

bookmarks
notes

badges
user_badges

audit_logs
```

Normalizar correctamente y utilizar índices y constraints apropiados.

---

# 50. CONTENIDO INICIAL

La aplicación no debe quedar vacía.

Crear seed inicial con contenido suficiente para demostrar la plataforma.

Como mínimo:

- Roadmap.
- 15+ tracks/módulos.
- 50+ skills.
- 100+ lessons/topics.
- Ejercicios.
- Quizzes.
- Proyectos.
- Recursos.
- Videos de ejemplo.

No llenar artificialmente la base de datos con texto mediocre.

La arquitectura debe soportar mucho más contenido posteriormente.

---

# 51. CALIDAD DEL CONTENIDO

Evitar contenido genérico.

Malo:

> Python es un lenguaje de programación muy popular.

Bueno:

```text
Objetivo:
Construir funciones Python mantenibles usando typing,
manejo de excepciones y composición.

Prerequisitos:
- Variables
- Condicionales
- Funciones

Práctica:
Construir un parser de archivos CSV.

Resultado:
El estudiante debe poder explicar por qué
escogió determinada estructura y cómo probarla.
```

---

# 52. EXPERIENCIA VISUAL

Debe verse como un producto profesional.

Características:

- Responsive.
- Dark mode.
- Light mode.
- Sidebar.
- Breadcrumbs.
- Command palette.
- Search.
- Cards.
- Progress bars.
- Badges.
- Tabs.
- Modals.
- Tooltips.
- Empty states.
- Loading states.
- Error states.

Evitar abuso de:

- Gradientes.
- Animaciones.
- Glassmorphism.
- Sombras exageradas.
- Colores chillones.

Priorizar claridad.

---

# 53. ADMIN UX

Debe incluir:

- Sidebar.
- Breadcrumbs.
- Tables.
- Filters.
- Search.
- Pagination.
- Forms.
- Validation.
- Confirmation dialogs.
- Toast notifications.
- Draft/publish workflow.

No crear un admin falso donde los botones no hacen nada.

---

# 54. EDITOR DE LECCIONES

Debe permitir configurar:

```text
Lesson title
Learning objectives
Prerequisites
Estimated time
Difficulty
Content
Code example
Exercise
Resources
Videos
Related skills
Dependencies
Publish
```

---

# 55. MULTIMEDIA

Preparar modelo para:

```text
TEXT
IMAGE
CODE
VIDEO
DIAGRAM
AUDIO
EMBED
FILE
```

No es obligatorio implementar todo inmediatamente.

---

# 56. VERSIONADO DE CONTENIDO

Preparar estrategia para evolución de lecciones:

```text
Lesson v1
Lesson v2
Lesson v3
```

No hace falta construir un Git completo para contenido.

---

# 57. INTERNACIONALIZACIÓN

Preparar:

```text
es
en
```

Primera versión en español.

No hardcodear innecesariamente textos de UI dentro de componentes.

---

# 58. IA DENTRO DE LA PLATAFORMA

Preparar extensiones futuras:

### AI Tutor

Resolver dudas.

### AI Mentor

Analizar progreso.

### AI Recommendation Engine

Recomendar contenidos.

### AI Code Reviewer

Revisar ejercicios.

### AI Project Assistant

Ayudar con proyectos.

No es obligatorio implementar IA real en la primera versión.

---

# 59. ABSTRACCIÓN DE PROVEEDORES IA

Preparar una interfaz similar a:

```text
AiProviderInterface
```

con posibilidad de:

```text
OpenAIProvider
AnthropicProvider
GeminiProvider
LocalModelProvider
```

No acoplar todo el sistema a un único proveedor.

---

# 60. ROADMAP DEL PROPIO PRODUCTO

Mantener un roadmap interno:

```text
V1
├── Authentication
├── Roadmap
├── Lessons
├── Progress
└── Admin

V1.1
├── Exercises
├── Quizzes
└── Projects

V1.2
├── Gamification
├── Videos
└── Resources

V2
├── AI Tutor
├── AI Recommendations
└── AI Code Review
```

---

# 61. FASE 0 — INSPECCIÓN

Antes de modificar archivos:

- Analizar directorio.
- Detectar stack.
- Revisar package managers.
- Revisar versiones.
- Revisar Git.
- Revisar Docker.
- Revisar configuración.
- Revisar base de datos si existe.

No destruir archivos existentes.

---

# 62. FASE 1 — DISCOVERY

Crear:

```text
docs/product-discovery.md
```

Debe incluir:

- Propósito.
- Usuarios.
- Casos de uso.
- Funcionalidades.
- Alcance.
- Fuera de alcance.
- Riesgos.
- Decisiones.

---

# 63. FASE 2 — ARQUITECTURA

Crear:

```text
docs/architecture.md
docs/database.md
docs/frontend-architecture.md
docs/content-architecture.md
```

Utilizar Mermaid cuando aporte valor.

---

# 64. FASE 3 — MODELO DE DATOS

Diseñar:

- ERD.
- Tablas.
- Relaciones.
- Índices.
- Constraints.
- Enums.

Después implementar migrations.

---

# 65. FASE 4 — DESIGN SYSTEM

Definir:

- Layout.
- Colores.
- Typography.
- Spacing.
- Components.
- States.

---

# 66. FASE 5 — MVP

Implementar primero:

```text
Auth
+
Dashboard
+
Roadmap
+
Skills
+
Lessons
+
Progress
+
Admin
```

Debe funcionar end-to-end.

---

# 67. FASE 6 — CONTENIDO

Después:

- Exercises.
- Quizzes.
- Projects.
- Resources.
- Videos.

---

# 68. FASE 7 — EXPERIENCIA AVANZADA

Después:

- Gamification.
- Search.
- Bookmarks.
- Notes.
- Recommendations.
- Statistics.

---

# 69. FASE 8 — CALIDAD

Después:

- Tests.
- Security.
- Performance.
- Accessibility.
- Responsive.
- Documentation.

---

# 70. FASE 9 — POLISH

Finalmente:

- UX.
- Animaciones sutiles.
- Empty states.
- Loading states.
- Error states.
- Microinteractions.

---

# 71. VALIDACIÓN POR FASE

Después de cada fase:

1. Ejecutar tests.
2. Ejecutar lint.
3. Ejecutar type checking.
4. Ejecutar build.
5. Revisar errores.
6. Corregir.
7. Actualizar documentación.

No acumular errores para el final.

---

# 72. NO HACER

No:

- Crear demo estática.
- Hardcodear todo el roadmap.
- Meter todo en un JSON gigante.
- Crear componentes monolíticos.
- Usar lorem ipsum.
- Inventar URLs.
- Crear autenticación falsa.
- Crear admin falso.
- Crear botones sin funcionalidad.
- Dejar TODOs críticos.
- Crear funcionalidades puramente visuales.
- Instalar librerías innecesarias.
- Sobrearquitecturar sin necesidad.

---

# 73. PRINCIPIO DE REALISMO

Es preferible:

```text
10 funcionalidades reales
```

a:

```text
40 funcionalidades falsas
```

Pero la arquitectura debe permitir crecer.

---

# 74. DEFINITION OF DONE

Una funcionalidad está terminada cuando:

- Funciona.
- Tiene UI.
- Tiene backend cuando corresponde.
- Tiene persistencia cuando corresponde.
- Tiene validaciones.
- Tiene manejo de errores.
- Tiene loading states.
- Tiene permisos.
- Tiene tests cuando corresponde.
- Está documentada.

---

# 75. DOCUMENTACIÓN DEL PROYECTO

Crear:

```text
README.md

docs/
├── architecture.md
├── roadmap.md
├── database.md
├── development.md
├── deployment.md
├── content-authoring.md
├── admin.md
├── testing.md
└── contributing.md
```

README debe incluir:

- Qué es.
- Objetivo.
- Screenshots.
- Stack.
- Instalación.
- Configuración.
- Docker.
- Base de datos.
- Seeds.
- Testing.
- Desarrollo.
- Arquitectura.
- Roadmap.

---

# 76. DEVELOPER EXPERIENCE

El proyecto debe poder levantarse fácilmente.

Idealmente:

```bash
git clone ...
cd ai-engineer-roadmap

cp .env.example .env

docker compose up -d

composer install
npm install

php artisan key:generate
php artisan migrate --seed

npm run dev
```

Ajustar los comandos al stack real.

No documentar comandos que no funcionen.

---

# 77. SEGURIDAD

Implementar:

- Authentication.
- Authorization.
- CSRF.
- XSS protection.
- Validation.
- Rate limiting donde corresponda.
- Secure file upload.
- MIME validation.
- File size limits.
- Secure video URLs.
- Audit logs.

Nunca guardar secretos en Git.

---

# 78. TESTING

Backend:

- Models.
- Services.
- Authorization.
- Progress calculation.
- Dependencies.
- Recommendation rules.

Frontend:

- Components críticos.
- Forms.
- Progress UI.

E2E:

```text
Login
↓
Dashboard
↓
Open roadmap
↓
Open lesson
↓
Mark lesson completed
↓
Progress updates
```

Y:

```text
Admin login
↓
Create lesson
↓
Publish
↓
Student sees lesson
```

---

# 79. PERFORMANCE

Considerar:

- Pagination.
- Eager loading.
- Indexes.
- Caching.
- Lazy loading.
- Code splitting.
- Image optimization.
- Query optimization.

No cargar un roadmap enorme innecesariamente.

---

# 80. ACCESIBILIDAD

Implementar:

- Keyboard navigation.
- Semantic HTML.
- ARIA cuando corresponda.
- Contraste.
- Focus states.
- Accessible forms.

---

# 81. SEO

Para páginas públicas:

- Metadata.
- Title.
- Description.
- Open Graph.
- Semantic structure.

---

# 82. PROGRESO DEL PROYECTO

Crear:

```text
docs/progress.md
```

Mantener:

```text
Completed
In Progress
Next
Known Issues
Technical Debt
Decisions
```

En cada sesión:

1. Leer el estado actual.
2. Determinar fase.
3. Continuar desde allí.
4. No rehacer trabajo correcto.
5. Validar.
6. Actualizar documentación.

---

# 83. AUTONOMÍA DE CLAUDE CODE

Claude puede tomar decisiones técnicas razonables.

Cuando existan alternativas:

1. Evaluarlas.
2. Explicar brevemente el trade-off.
3. Elegir una.
4. Documentar la decisión.

No preguntar por decisiones triviales.

Preguntar solamente cuando una decisión pueda cambiar significativamente la arquitectura o el objetivo.

---

# 84. CHECKPOINT INICIAL

Antes de implementar:

1. Inspeccionar repositorio.
2. Crear discovery.
3. Crear arquitectura.
4. Diseñar modelo de datos.
5. Presentar/registrar plan.
6. Comenzar implementación incremental.

No crear cientos de componentes inmediatamente.

---

# 85. RESULTADO FINAL

La experiencia debe terminar pareciéndose conceptualmente a:

```text
AI ENGINEER ROADMAP

Dashboard
│
├── My Progress
├── Continue Learning
├── Skills
├── Projects
└── Activity

ROADMAP
│
├── Foundations
├── Python
├── Mathematics
├── Data
├── Machine Learning
├── Deep Learning
├── Computer Vision
├── NLP
├── Transformers
├── LLM Engineering
├── RAG
├── AI Agents
├── MLOps
├── Cloud
├── AI Security
└── AI System Design

ADMIN
│
├── Dashboard
├── Users
├── Roadmaps
├── Skills
├── Lessons
├── Exercises
├── Quizzes
├── Projects
├── Videos
├── Resources
└── Audit Logs
```

---

# 86. CRITERIO FINAL DE CALIDAD

Evaluar continuamente el producto como:

- Staff Engineer.
- UX Designer.
- Product Manager.
- Security Engineer.
- QA Engineer.
- Estudiante real.

La pregunta principal no es:

> ¿Se ve bonito?

La pregunta es:

> ¿Esto realmente sirve para aprender AI Engineering y llevar ese conocimiento a proyectos reales?

Prioridad:

```text
CORRECCIÓN
>
ARQUITECTURA
>
EXPERIENCIA DE APRENDIZAJE
>
FUNCIONALIDAD
>
UX/UI
>
ESTÉTICA
```

---

# 87. INSTRUCCIÓN FINAL PARA CLAUDE CODE

Construye un **producto educativo técnico real**, no una landing page.

La plataforma debe poder evolucionar desde:

```text
Roadmap educativo
```

hasta:

```text
Learning Management System
+
Knowledge Graph
+
Project Based Learning
+
AI Engineering Curriculum
+
AI-powered Learning Platform
```

Trabaja incrementalmente.

No intentes completar todo en una sola pasada.

Primero inspecciona el repositorio actual.

No modifiques nada destructivamente.

Después crea discovery y arquitectura.

Luego implementa por fases.

Después de cada fase valida, corrige y documenta.

El resultado debe ser funcional, mantenible, extensible y preparado para crecer.
