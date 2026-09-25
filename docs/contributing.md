# Contribuir

## Flujo

1. Crea una rama desde `main`: `feat/…`, `fix/…`, `docs/…` o `content/…`.
2. Haz commits pequeños con [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) (`feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`).
3. Antes de abrir el PR, pasa la puerta de validación local:

   ```bash
   php artisan test && npm run test
   composer lint:check && npm run check
   composer types:check && npm run types:check
   php artisan types:enums --check
   php artisan content:validate
   ```

4. Abre el PR con qué cambia, por qué, cómo se probó y qué riesgos tiene. CI debe estar en verde antes de pedir revisión.

## Criterios para integrar (Definition of Done, §74)

Una funcionalidad está terminada cuando funciona de punta a punta: tiene UI (si corresponde), backend, persistencia, validaciones, manejo de errores, estados de carga, permisos y tests, y está documentada. No se integran botones sin backend, datos simulados ni TODOs críticos.

## Decisiones de arquitectura

Si un cambio contradice un ADR de [architecture.md §14](architecture.md#14-registro-de-decisiones-adr), añade un ADR nuevo que lo reemplace y explique por qué. No cambies una decisión en silencio.

## Contenido

Sigue [content-authoring.md](content-authoring.md). Los PR de contenido pasan por el workflow `Content`, que valida el paquete y comprueba cada URL.
