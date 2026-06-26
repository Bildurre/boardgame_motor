# 08 · Admin kit (layout + CRUD)

## Qué hace

Evita rehacer el panel de administración en cada juego. Aporta el **layout** del
admin (sidebar, topbar, breadcrumbs) y el **scaffolding de CRUD** (listados,
formularios, filtros, orden, soft-delete/restore, publicar/despublicar), más los
gestores ya descritos (PDF, previews, usuarios, CRM).

## Qué hay hoy en choque

Admin server-side (Blade) con `Route::resource` para cada entidad
(hero-classes, cards, factions, pages, counters…), `toggle-published`, `restore`,
`reorder`. Mucho patrón repetido por entidad. → se moderniza a Vue + dirigido por
configuración.

## Diseño nuevo

**Paquete `motor-admin-kit` (Vue, sobre `motor-ui`):**

```
src/
├── layout/    AdminLayout, AdminSidebar, AdminTopbar, Breadcrumbs
├── crud/      ResourceList, ResourceForm, FiltersBar, useResource(), columns/fields DSL
├── content/   PageEditor, BlockPalette, BlockEditor      (doc 03)
├── pdf/       PdfManager, PreviewManager                  (doc 01/02)
├── users/     UsersAdmin, RolesAdmin                      (doc 05)
├── stores/    auth, locales (Pinia)
└── index.ts
```

- **`useResource(endpoint)`**: composable que habla con la API REST estándar del
  motor (index con filtros/paginación, store, update, destroy, restore, toggle).
- **`ResourceList`**: tabla con filtros, orden (drag para reordenables), acciones
  (editar/borrar/restaurar/publicar), paginación — configurada por un **DSL de
  columnas**.
- **`ResourceForm`**: formulario configurado por un **DSL de campos** (incluye
  `TranslatableInput`, `ImageUpload`, selects, etc.). Mismo motor que usa el
  `BlockEditor` (doc 03).
- **`AdminLayout`**: el juego registra sus secciones de menú y monta sus rutas; las
  pantallas estándar salen del DSL, las muy específicas las escribe el juego con
  `motor-ui`.

**Cómo lo usa un juego:**
```ts
// admin del juego: declarar un recurso
defineResource('cards', {
  columns: [...],            // → ResourceList
  fields: [...],             // → ResourceForm
  features: ['publish','soft-delete','reorder'],
})
```

## Frontera motor ↔ juego

| Motor | Juego |
|---|---|
| Layout, CRUD dirigido por DSL, gestores PDF/previews/usuarios/CRM | Declara sus recursos (columns/fields) y pantallas específicas |

## Pasos

1. `AdminLayout` + navegación registrable + breadcrumbs.
2. `useResource` + cliente REST estándar.
3. `ResourceList` (DSL columnas) + `FiltersBar` + reorder.
4. `ResourceForm` (DSL campos) compartido con BlockEditor.
5. Integrar gestores (PDF, previews, usuarios, CRM) ya construidos.

## Hito de aceptación

- Declarar un recurso del playground con `defineResource` da index+form+filtros+
  publish+soft-delete+reorder **sin escribir pantallas a mano**.

## Decisiones (cerradas)

- **Magia del DSL** → **DC-19**: declarativo para lo estándar, **con slots/overrides
  y escape a componentes a mano** para pantallas especiales.
- **Alineación con el CRM** → **DC-08**: el `ResourceForm` usa el **mismo DSL de
  campos y renderer** que el `BlockEditor` (doc 03).
- **PWA** → **DC-01**: el admin es instalable (vite-plugin-pwa).

## Riesgos

- No convertir el DSL en un framework propio difícil de mantener.
