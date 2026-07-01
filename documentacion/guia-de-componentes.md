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
  Es la base de `ConfirmDialog`/`EditModal` y de cualquier formulario emergente.
- **Comportamiento:** cabecera con fondo, la X se tiñe de rojo en hover, y el
  modal nunca supera el 88% del alto; si el contenido no cabe, **scrollea solo el
  `.modal__body`** (cabecera y pie quedan fijos).
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

### EditModal

- **Finalidad:** modal de formulario (portado de kontuan): `BaseModal` + pie con
  Cancelar/Guardar. Las altas y ediciones de entidades son **modales** (no rutas),
  abiertas desde el listado. Agnóstico de i18n: las etiquetas van por props.
- **Props:** `modelValue` (v-model abierto/cerrado), `title`, `size?`, `loading?`,
  `submitLabel?` / `cancelLabel?`, `submitVariant?`.
- **Emite:** `update:modelValue`, `submit`. **Slot** por defecto = cuerpo del form.
- **Uso (un FormModal por entidad envuelve EditModal):**

```vue
<EditModal
  :model-value="open" :title="title" :loading="saving"
  :submit-label="t('common.save')" :cancel-label="t('common.cancel')"
  @update:model-value="(v) => emit('update:modelValue', v)" @submit="submit"
>
  <TranslatableInput v-model="form.name" :locales="locales.locales" :label="t('houses.fields.name')" />
  …
</EditModal>
```

El `FormModal` de cada entidad (p. ej. `HouseFormModal` en el playground) recibe
`modelValue` + `mode: 'create'|'edit'` + `targetSlug`, carga por slug al abrir en
edición, y emite `saved` para que el listado recargue. Patrón kontuan exacto.

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

### Elementos de formulario (portados de kontuan)

Comparten el estilo `.form-field` (label, error, hint) salvo el checkbox y el
color. Úsalos siempre en formularios (en modal o donde sea).

- **BaseInput** — input de texto. Props: `modelValue` (v-model), `label?`,
  `type?` (`text|email|password|number|tel|url|search|date|datetime-local`),
  `placeholder?`, `error?`, `hint?`, `required?`, `disabled?`.
- **BaseTextarea** — igual que BaseInput con `rows?`.
- **BaseSelect** — `options: { value, label }[]`, `placeholder?`, resto igual.
- **BaseCheckbox** — `modelValue` (boolean), `label?` (o slot).
- **PaletteColorPicker** — selector de color. `modelValue` (v-model, **hex**;
  a diferencia de kontuan que guarda la clave de paleta, aquí emite el hex),
  `label?`, `allowCustom?` (swatch con selector nativo, por defecto `true`).

```vue
<BaseInput v-model="form.email" :label="t('login.email')" type="email" required :error="errors.email" />
<BaseSelect v-model="form.role" :label="t('users.role')" :options="roleOptions" />
<PaletteColorPicker v-model="form.color" :label="t('houses.fields.color')" />
<BaseCheckbox v-model="form.is_published" :label="t('houses.fields.published')" />
```

### RichTextInput (WYSIWYG)

- **Finalidad:** editor de texto enriquecido basado en **TipTap** (DC-09). Barra
  con negrita, cursiva, tachado, título (H2), listas, deshacer/rehacer, un
  **toggle visual/HTML** (`<>`, editar el código directamente) y, si se pasan
  `icons`, un **selector de iconos del juego** (como en CDL) que los inserta en
  línea como `<img class="rt-icon">`. `v-model` = **HTML**. Se usa dentro de
  `TranslatableInput type="wysiwyg"` (carga diferida: TipTap solo se descarga si
  hay algún campo wysiwyg).
- **Props:** `modelValue` (HTML), `placeholder?`, `disabled?`,
  `icons?: { name, url }[]` (biblioteca de iconos; la sirve el motor en `GET /icons`).
- **Uso directo (no traducible):**

```vue
<RichTextInput v-model="html" :icons="icons" />
```

### TranslatableInput

- **Finalidad:** campo de texto/textarea multi-idioma (portado de kontuan). Usa
  el estilo `.form-field` y un **selector desplegable** de locale con contador de
  rellenados (p. ej. `ES 1/3`). Edita un objeto `{ es, eu, en }`.
- **Modelo:** `v-model` (`Record<string, string>`).
- **Props:** `locales: { code, name }[]`, `label?`,
  `type?: 'text' | 'textarea' | 'wysiwyg'` (def. `text`; `wysiwyg` usa
  `RichTextInput`/TipTap), `placeholder?`, `rows?`, `required?`,
  `icons?: { name, url }[]` (solo wysiwyg; se pasan al selector de iconos).
- **Uso:**

```vue
<TranslatableInput v-model="form.name" :locales="locales.locales" label="Nombre" />
<TranslatableInput v-model="form.description" :locales="locales.locales"
  label="Descripción" type="textarea" />
```

### ImageUpload

- **Finalidad:** subida de imagen con **arrastrar-y-soltar o clic** (portado de
  kontuan): zona _dropzone_ con previsualización y botón de quitar. Devuelve un
  `File` (o `null`) para enviar por `FormData`; muestra la imagen actual con
  `current-url`.
- **Modelo:** `v-model` (`File | null`). **Emite:** `remove`.
- **Props:** `currentUrl?`, `label?`, `accept?` (def. `image/*`), `maxSize?`
  (MB, def. 4), `error?`, y los textos traducibles `dragText?` / `hintText?`.
- **Uso:**

```vue
<ImageUpload v-model="image" :current-url="currentImage" :label="t('houses.fields.image')"
  :drag-text="t('houses.fields.imageDrag')" :hint-text="t('houses.fields.imageHint')" />
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

### Index de entidades (patrón sin tablas) — `FilterBar` + `BaseTabs` + `BaseGrid` + `EntityCard`

**DC-30: los index NUNCA usan tablas.** Siempre **grid de tarjetas**. El orden es
**filtros → tabs → grid**. Componentes (mezcla kontuan + CDL):

#### FilterBar

- **Finalidad:** barra de búsqueda estilo kontuan (caja con icono de lupa). Va
  **por encima de las tabs**. Slot por defecto para filtros extra a la derecha.
- **Modelo:** `v-model` (texto de búsqueda). **Props:** `placeholder?`.

#### BaseGrid

- **Finalidad:** grid responsive de tarjetas (portado de kontuan). Responde al
  ancho del _container_ `content`, no al viewport.
- **Props:** `cols?` (número u objeto `{ base, sm, md, lg }`),
  `gap?: 'sm'|'md'|'lg'`, `preset?` (`cards` = 1→2→3, `cards-wide`,
  `cards-narrow`, `cards-full`, `halves`, `thirds`).

#### EntityCard

- **Finalidad:** tarjeta de entidad. Mezcla kontuan (contenedor con borde/sombra,
  hover, slots) y CDL (cabecera título + acciones con divisoria, contenido con
  `badges` y `meta`). Franja `media` opcional arriba (emblema/imagen).
- **Props:** `title`, `clickable?`, `muted?`. **Emite:** `view` (si `clickable`).
- **Slots:** `media`, `actions`, `badges`, `meta`, por defecto.

#### EmptyState

- **Finalidad:** estado vacío de un listado. **Props:** `title`, `description?`.
  **Slots:** `icon`, `action`.

- **Uso (la vista compone todo):**

```vue
<FilterBar v-model="search" :placeholder="t('common.search')" />
<BaseTabs v-model="status" :tabs="tabs" />

<EmptyState v-if="!loading && !items.length" :title="t('common.empty')" />
<BaseGrid v-else preset="cards" gap="md">
  <EntityCard v-for="item in items" :key="item.id" :title="tr(item.name)" :muted="!!item.deleted_at">
    <template #media><div class="house-emblem" :style="{ '--c': item.color }">…</div></template>
    <template #actions><IconButton variant="success" @click="edit(item)"><SquarePen :size="18" /></IconButton></template>
    <template #badges><span class="chip chip--pub">{{ t('houses.state.published') }}</span></template>
    <template #meta><span><span class="swatch" :style="{ background: item.color }" />{{ item.color }}</span></template>
  </EntityCard>
</BaseGrid>
```

### ResourceList _(legacy)_ y FiltersBar _(legacy)_

`ResourceList` (tabla + tarjetas) y `FiltersBar` (select de estado) quedan
**obsoletos** por DC-30 (sin tablas). Se mantienen exportados de momento por
compatibilidad, pero los index nuevos usan el patrón de arriba.

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

## Errores y validación (convención)

Los formularios **nunca muestran el mensaje crudo del servidor** (SQL, trazas,
`validation.required`…). Patrón:

- **422 (validación):** se pintan los errores **por campo** (prop `:error` de
  `BaseInput`/`ImageUpload`), ya traducidos por el backend, más un **toast
  genérico**. Helper `fieldErrors(e)` en `playground/admin/src/lib/apiError.ts`
  extrae `{ campo: mensaje }` de `response.data.errors`.
- **Cualquier otro error:** solo un **toast genérico** ("No se pudo guardar."),
  sin exponer el detalle.

Backend: las validaciones se traducen con `playground/api/lang/{es,eu,en}/
validation.php` (con `attributes` para nombres de campo). El locale lo fija el
`SetLocale` del motor desde `?locale`; **por eso las rutas del motor van en el
grupo `api`** (`Route::prefix('api')->middleware('api')`), o no se localizarían.
