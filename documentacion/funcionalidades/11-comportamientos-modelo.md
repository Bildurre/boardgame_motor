# 11 · Comportamientos de modelo

## Qué hace

Los **cimientos** que casi toda entidad de juego va a reutilizar: publicar/borrador,
soft-delete + restore, filtros de listado, y utilidades (color, coste). Son traits
del motor que recortan el boilerplate de cada entidad y alimentan el CRUD del admin.

## Qué hay hoy en choque

Traits en `app/Models/Traits/`: `HasPublishedAttribute`, `HasFilters`,
`HasColorAttribute`, `HasCostAttribute` (+ los de imagen/preview, docs 07/01).
Rutas `toggle-published` y `restore` por entidad. Se conserva la idea, se generaliza.

## Diseño nuevo

**`core/src/Support/Concerns/`:**
- **`HasPublishedState`** — `is_published`, scopes `published()`/`draft()`,
  `togglePublished()`; integra con el CRUD del admin (acción publicar/despublicar).
- **`SoftDeletesWithRestore`** — soft-delete estándar + endpoint/acción de restore
  estandarizada; scopes `withTrashed`/`onlyTrashed` expuestos en la API de admin.
- **`HasFilters`** — declara filtros (por campo, búsqueda, rango, booleano) que el
  endpoint index entiende y que `FiltersBar` (admin-kit) pinta solo.
- **`HasColorAttribute`** / **`HasCostAttribute`** — utilidades (color con derivados
  RGB/contraste como hoy; coste con formato).
- Convención de **ordenación** (`order` + reorder) reutilizable por entidades
  listables/arrastrables.

Estos traits son lo que hace que el **CRUD dirigido por DSL** del admin-kit (doc 08)
"sepa" qué puede hacer cada entidad: publicar, restaurar, filtrar, reordenar.

## Frontera motor ↔ juego

| Motor | Juego |
|---|---|
| Traits + scopes + integración con API/CRUD | `use` los traits que necesite cada entidad y declara sus filtros |

## Pasos

1. `HasPublishedState` + scopes + toggle.
2. `SoftDeletesWithRestore` + restore estandarizado.
3. `HasFilters` (declarativo) ligado al index de la API y a `FiltersBar`.
4. `HasColorAttribute` / `HasCostAttribute` + ordenación/reorder.

## Hito de aceptación

- Una entidad demo que usa los traits obtiene publish/draft, soft-delete+restore,
  filtros y reorder funcionando en la API y en el admin **sin código a medida**.

## Decisiones (cerradas)

- **Composición y filtros** → **DC-20**: traits **opt-in** por entidad; formato de
  filtros **unificado** entre `HasFilters`, `FiltersBar` y `defineResource`.

## Riesgos

- Que añadir un trait nuevo no rompa el contrato del CRUD declarativo (doc 08).
