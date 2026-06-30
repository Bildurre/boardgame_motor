# Guía de componentes — BoardGame Motor (BGM)

Catálogo de todos los componentes, composables y utilidades que ofrece el motor.
Para cada uno se indica su **finalidad** (para qué se usa) y su **uso** (cómo se usa).

> Mantenimiento: **esta guía se actualiza siempre que se añade, cambia o elimina
> un componente.** Regla DC-28: antes de crear un componente nuevo se busca en
> kontuan; si existe se copia y adapta, si no se crea. Regla DC-27: nada de
> bloques `<style>` en los `.vue`; todo el SCSS vive en ficheros globales con
> clases BEM.

Paquetes:

- **`@bgm/ui`** — librería base de componentes, composables y tokens. La consume
  cualquier front (admin y app público).
- **`@bgm/admin-kit`** — layout del panel y scaffolding CRUD, construido sobre
  `@bgm/ui`. Solo lo consume el admin.

Los estilos se importan vía SCSS:

```scss
// en el main.scss de cada app
@use '@bgm/ui/scss/shared-components';      // tokens + componentes base
@use '@bgm/admin-kit/scss/admin-kit';       // solo en el admin
```

---

## `@bgm/ui` — Componentes

### BaseButton

- **Finalidad:** botón de acción principal del motor con variantes de color
  coherentes con los tokens.
- **Props:** `variant?: 'primary' | 'secondary' | 'danger' | 'success'` (def.
  `primary`), `type?: 'button' | 'submit'` (def. `button`).
- **Slot:** contenido del botón (texto/icono).
- **Uso:**

```vue
<BaseButton @click="save">Guardar</BaseButton>
<BaseButton variant="secondary" @click="cancel">Cancelar</BaseButton>
<BaseButton variant="danger" type="submit">Eliminar</BaseButton>
```

### IconButton

- **Finalidad:** botón compacto solo-icono para acciones de fila (editar,
  borrar, publicar…). En reposo usa el color del texto y al pasar el ratón tiñe
  el icono con el color de la variante.
- **Props:** `variant?: 'neutral' | 'accent' | 'danger' | 'success' | 'warning' | 'info'`
  (def. `neutral`), `title?: string` (sirve de tooltip y de `aria-label`),
  `type?: 'button' | 'submit'`.
- **Slot:** el icono (de `@lucide/vue`).
- **Uso:**

```vue
<IconButton variant="success" title="Editar" @click="edit(item)">
  <SquarePen :size="18" />
</IconButton>
<IconButton variant="danger" title="Borrar" @click="del(item)">
  <Trash2 :size="18" />
</IconButton>
```

### MotorBadge

- **Finalidad:** distintivo de marca del motor (logotipo textual con punto).
  Se usa en la cabecera/brand del layout.
- **Props:** `label?: string` (def. `BGM`), `version?: string` (si se pasa se
  muestra como ` · vX`).
- **Uso:**

```vue
<MotorBadge label="BGM Admin" />
<MotorBadge label="BGM" :version="motorVersion" />
```

### BaseTabs

- **Finalidad:** pestañas controladas. Se usan para filtrar listados por estado
  (Publicadas / Borrador / Papelera) o cualquier conmutación de vista.
- **Modelo:** `v-model` (string con la `key` de la pestaña activa).
- **Props:** `tabs: { key: string; label: string; count?: number; icon?: Component }[]`.
  El `icon` es opcional (componente de `@lucide/vue`); en pantallas estrechas las
  tabs con icono se apilan (icono + etiqueta pequeña).
- **Uso:**

```vue
<script setup>
import { CircleCheck, FilePen, Trash } from '@lucide/vue'
const status = ref('published')
const tabs = [
  { key: 'published', label: 'Publicadas', icon: CircleCheck },
  { key: 'draft', label: 'Borrador', icon: FilePen },
  { key: 'trashed', label: 'Papelera', icon: Trash },
]
</script>

<BaseTabs v-model="status" :tabs="tabs" />
```

### BaseModal

- **Finalidad:** ventana modal base (overlay + diálogo) con cierre por overlay,
  botón X y tecla `Escape`. Bloquea el scroll del body mientras está abierta.
  Es la base de `ConfirmDialog` y de cualquier formulario/aviso emergente.
- **Modelo:** `v-model` (boolean abierto/cerrado).
- **Props:** `title?: string`, `size?: 'sm' | 'md' | 'lg'` (def. `md`).
- **Slots:** por defecto (cuerpo), `header` (sustituye al título), `footer`.
- **Uso:**

```vue
<BaseModal v-model="open" title="Editar" size="md">
  <p>Contenido…</p>
  <template #footer>
    <BaseButton variant="secondary" @click="open = false">Cerrar</BaseButton>
  </template>
</BaseModal>
```

### ConfirmDialog

- **Finalidad:** diálogo de confirmación global que **sustituye al `confirm()`
  nativo** de JS. Se monta UNA sola vez en la raíz de la app y responde a las
  llamadas de `useConfirm()`.
- **Props/slots:** ninguno; se controla por el composable `useConfirm`.
- **Uso:** ver `useConfirm` más abajo. Montaje:

```vue
<!-- App.vue, una vez -->
<ConfirmDialog />
```

### BaseToast

- **Finalidad:** notificación individual (toast). Se cierra solo tras `duration`
  o con el botón X. Normalmente no se usa suelto, sino a través de
  `ToastContainer`.
- **Props:** `variant?: 'success' | 'warning' | 'danger' | 'info'` (def. `info`),
  `message: string`, `duration?: number` (ms, def. `4000`; `0` = no autocierra).
- **Emite:** `close`.

### ToastContainer

- **Finalidad:** contenedor que renderiza la pila de toasts activos (con
  transiciones). Se monta UNA vez en la raíz y muestra todo lo que se emita con
  `useToast()`.
- **Uso:**

```vue
<!-- App.vue, una vez -->
<ToastContainer />
```

### TranslatableInput

- **Finalidad:** campo de texto/textarea multi-idioma. Muestra pestañas por
  locale y edita un objeto `{ es: '', eu: '', en: '' }`. Para los campos
  traducibles de las entidades (nombre, descripción…).
- **Modelo:** `v-model` (`Record<string, string>`).
- **Props:** `locales: { code: string; name: string }[]`, `label?: string`,
  `type?: 'text' | 'textarea'` (def. `text`), `placeholder?: string`.
- **Uso:**

```vue
<TranslatableInput v-model="form.name" :locales="locales.locales" label="Nombre" />
<TranslatableInput v-model="form.description" :locales="locales.locales"
  label="Descripción" type="textarea" />
```

### ImageUpload

- **Finalidad:** subida de una imagen con previsualización. Devuelve un `File`
  (o `null`) para enviar por `FormData`; muestra la imagen actual si se le pasa
  `current-url`.
- **Modelo:** `v-model` (`File | null`).
- **Props:** `currentUrl?` (imagen ya guardada), `label?`, y los textos internos
  traducibles `emptyText?` / `chooseText?` / `clearText?`.
- **Uso:**

```vue
<ImageUpload v-model="image" :current-url="currentImage" :label="t('houses.fields.image')"
  :empty-text="t('houses.fields.imageEmpty')" :choose-text="t('houses.fields.imageChoose')" />
```

### ThemeSelector

- **Finalidad:** conmutador de tema claro / oscuro / sistema. Aplica
  `data-theme` al `<html>` y lo persiste en `localStorage`. Sin props: el estado
  es global (composable `useTheme`). Va en las preferencias del layout.
- **Uso:**

```vue
<ThemeSelector />
```

### LocaleSelector

- **Finalidad:** selector del idioma de la app. En el admin el idioma es único y
  controla TODO: la UI (`vue-i18n`), las rutas (segmentos de path traducidos) y
  el contenido (locale enviado a la API). Controlado: la lista de locales se pasa
  por props (la sirve la API del motor); el handler de cambio orquesta i18n +
  router + API (ver el store `locales`).
- **Modelo:** `v-model` (código de locale, p. ej. `'es'`).
- **Props:** `locales: { code: string; name: string }[]`.
- **Uso:**

```vue
<LocaleSelector :model-value="locales.current" :locales="locales.locales"
  @update:model-value="locales.setCurrent" />
```

### AppBreadcrumbs

- **Finalidad:** migas de pan. Cada ruta declara su rastro en
  `meta.breadcrumbs` (`{ label, to? }[]`); se antepone una miga "home". Sin
  `vue-i18n`. El `AdminLayout` ya la monta — normalmente no se usa suelta.
- **Props:** `home?: { label, to } | null` (def. `{ label: 'Inicio', to: { name: 'dashboard' } }`; `null` la oculta).
- **Uso (en la ruta):**

```ts
{ path: '/houses/new', name: 'house-new', meta: {
  breadcrumbs: [{ label: 'Houses', to: { name: 'houses' } }, { label: 'Nueva house' }],
} }
```

---

## `@bgm/ui` — Composables y utilidades

### useToast()

- **Finalidad:** emitir notificaciones desde cualquier componente. El estado es
  un singleton compartido con `ToastContainer`.
- **API:** `{ toasts, add, remove, success, warning, danger, info }`.
  - `success/warning/danger/info(message)` — atajos por variante.
  - `add(message, variant?, duration?)` — control total.
- **Uso:**

```ts
const toast = useToast()
toast.success('Casa creada correctamente.')
toast.danger('No se pudo guardar.')
```

### useTheme()

- **Finalidad:** estado global del tema (claro/oscuro/sistema). Lo usa
  `ThemeSelector`, pero cualquier componente puede leer/forzar el tema.
- **API:** `{ themeMode, setTheme }`. `themeMode: 'light' | 'dark' | 'system'`.
- **Uso:**

```ts
const { themeMode, setTheme } = useTheme()
setTheme('dark')
```

### useConfirm()

- **Finalidad:** pedir confirmación al usuario de forma asíncrona (Promise),
  pintada por `ConfirmDialog`. Reemplaza al `confirm()` nativo.
- **API:** `{ state, confirm, resolve }`. Lo habitual es usar solo `confirm`.
  - `confirm(options): Promise<boolean>`
  - `options`: `{ title?, message, confirmLabel?, cancelLabel?, variant? }`
    con `variant?: 'primary' | 'danger' | 'success'`.
- **Uso:**

```ts
const { confirm } = useConfirm()

const ok = await confirm({
  title: 'Enviar a la papelera',
  message: `¿Enviar "${name}" a la papelera?`,
  confirmLabel: 'Borrar',
  variant: 'danger',
})
if (!ok) return
// …continúa el borrado
```

### createApi(options)

- **Finalidad:** crea la instancia de axios del motor (base URL, token Sanctum,
  cabeceras, locale). Cada app la envuelve en su propio `lib/api`.
- **Uso:** ver `playground/admin/src/lib/api.ts`.

---

## `@bgm/admin-kit` — Componentes

### AdminLayout

- **Finalidad:** layout del panel — portado del `AppLayout` de kontuan (DC-28).
  Mobile-first: drawer a pantalla completa en móvil (`< bp-md`, hamburguesa),
  sidebar colapsable a barra de iconos en escritorio (estado en `localStorage`).
  Integra las preferencias (ThemeSelector + LocaleSelector) en la cabecera del
  sidebar, las migas de pan (`AppBreadcrumbs`) en el contenido y un pie de
  usuario. El contenido es un _container_ (`container-name: content`), así los
  componentes internos (tabs, listas) responden al ancho real, no al viewport.
- **Props:** `title?`, `brand?` (def. `'BGM Admin'`), `locales?` (lista para el
  selector), `locale?` (v-model:locale del idioma de la app),
  `homeCrumb?` (miga "home"; `null` la oculta), `breadcrumbs?` (migas ya
  traducidas que la app calcula con `t()`).
- **Slots:** `nav` (enlaces, usa la clase `nav-item` + `nav-label`),
  `actions` (zona derecha del navbar), `user` (pie del sidebar; recibe
  `{ collapsed }`), por defecto (cuerpo de la página).
- **Uso:**

```vue
<AdminLayout
  :title="title" brand="BGM Admin"
  :locales="locales.locales" :locale="locales.current"
  @update:locale="locales.setCurrent"
>
  <template #nav>
    <RouterLink class="nav-item" :to="{ name: 'dashboard' }">
      <LayoutDashboard class="nav-icon" :size="20" /><span class="nav-label">Dashboard</span>
    </RouterLink>
  </template>
  <template #user="{ collapsed }">
    <div class="who"><span class="who__avatar">A</span><span v-if="!collapsed" class="who__name">Admin</span></div>
    <button v-if="!collapsed" class="who-logout" @click="logout"><LogOut :size="20" /></button>
  </template>
  <RouterView />
</AdminLayout>
```

### ResourceList

- **Finalidad:** listado responsive de un recurso. En pantallas anchas (≥ bp-lg)
  pinta una tabla; por debajo, tarjetas (1 columna en móvil, 2 en bp-md). Incluye
  estados de carga/vacío y paginación.
- **Props:** `columns: { key: string; label: string }[]`, `items: any[]`,
  `meta?` (paginación de Laravel), `loading?: boolean`, y los textos
  traducibles `loadingText?` / `emptyText?`.
- **Emite:** `page` (número de página solicitada).
- **Slots dinámicos:** `cell-<key>` por columna (recibe `{ item }`), y
  `actions` (recibe `{ item }`) para los botones de fila.
- **Uso:**

```vue
<ResourceList :columns="columns" :items="items" :meta="meta" :loading="loading" @page="load">
  <template #cell-name="{ item }">{{ label(item.name) }}</template>
  <template #actions="{ item }">
    <IconButton variant="success" title="Editar" @click="edit(item)"><SquarePen :size="18" /></IconButton>
  </template>
</ResourceList>
```

### FiltersBar

- **Finalidad:** barra de filtros del listado (búsqueda con _debounce_ y, de
  forma opcional, un selector de estado). Emite los cambios ya consolidados.
- **Props:** `statusOptions?: { value: string; label: string }[]`,
  `searchPlaceholder?: string` (def. `Buscar…`).
- **Emite:** `change` con `{ search, status }` (250 ms de debounce).
- **Uso:** (con tabs para el estado, la barra se usa solo para la búsqueda)

```vue
<FiltersBar @change="onFilter" />
```

---

## `@bgm/admin-kit` — Composables

### useResource(api, basePath)

- **Finalidad:** CRUD genérico sobre la API REST del motor; evita reescribir el
  ir-y-venir con axios en cada entidad.
- **API devuelta:** `{ items, meta, loading, list, find, create, update,
  createForm, updateForm, remove, action }`.
  - `list(params)` — listar con filtros/página; rellena `items` y `meta`.
  - `find(id)` — obtener uno.
  - `create/update(payload)` — alta/edición JSON.
  - `createForm/updateForm(formData)` — alta/edición multipart (ficheros). En
    edición añade `_method=PUT` (PHP no parsea multipart en PUT).
  - `remove(id)` — borrado (soft-delete en el back).
  - `action(id, verb)` — acción POST extra: `/{id}/{verb}`
    (p. ej. `toggle-published`, `restore`).
- **Uso:**

```ts
const { items, meta, loading, list, remove, action } = useResource(api, '/admin/houses')

await list({ search, status: 'published', page: 1 })
await action(item.id, 'toggle-published')
await action(item.id, 'restore')
await remove(item.id)
```

---

## i18n y rutas traducibles (patrón kontuan)

El idioma de la app es **único** y lo gobierna el store `locales`
(`setCurrent(code)`): cambia a la vez la UI (`vue-i18n`), las rutas y el
contenido (locale a la API). Piezas:

- **`src/i18n/`** — `vue-i18n` + `locales/{es,eu,en}.json`. Cada fichero tiene
  una sección `routes` con los **segmentos de path traducidos**
  (`houses`→`casas`, `new`→`nueva`, `edit`→`editar`) y todas las cadenas de UI.
- **`src/router/i18n-paths.ts`** — `createLocalizedRoutes(locale)` construye las
  rutas con el segmento del locale actual como `path` y los de los demás locales
  como `alias`, de modo que una URL en cualquier idioma resuelve al mismo
  `name`. Las rutas de detalle usan `:slug`.
- **`src/router/index.ts`** — al cambiar de idioma, `onLocaleChange(locale)`
  reconstruye las rutas y hace `router.replace` a la misma ruta por nombre, así
  la URL pasa al nuevo idioma (`/casas/casa-stark/editar` → `/houses/casa-stark/edit`).
- **Slugs**, no ids: el listado enlaza con el slug del locale activo
  (`{ name: 'house-edit', params: { slug } }`). El backend resuelve por slug en
  cualquier locale (`ResolvesBySlug::whereSlug`), así que la URL siempre carga.
- **Breadcrumbs / título**: cada ruta declara `meta.titleKey` y
  `meta.breadcrumbs: [{ key, to? }]` (claves i18n); `App.vue` los traduce con
  `t()` y se los pasa al `AdminLayout`.

Para añadir una entidad nueva: añade sus segmentos a `routes` en los tres JSON,
su bloque de cadenas, y sus rutas en `i18n-paths.ts` con `:slug` + `alias`.

## Patrón de montaje raíz (App.vue)

`ToastContainer` y `ConfirmDialog` se montan **una sola vez** en la raíz de la
app, junto al layout/router:

```vue
<template>
  <AdminLayout title="BGM Admin">
    <template #nav>…</template>
    <RouterView />
  </AdminLayout>
  <ToastContainer />
  <ConfirmDialog />
</template>
```

A partir de ahí, cualquier vista llama a `useToast()` / `useConfirm()` sin
volver a montar nada.
