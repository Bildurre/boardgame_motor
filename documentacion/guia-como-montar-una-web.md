# Guía: cómo montar una web nueva (juego) sobre el motor

Guía práctica y **paso a paso** para crear un juego nuevo con BoardGame Motor
(BGM) y para añadirle entidades (modelos), iconos y vistas. El ejemplo de
referencia es la entidad **House** del `playground`: cópiala y adáptala.

> Mantenimiento: **esta guía se actualiza cada vez que cambia el flujo de alta de
> un juego, los traits del motor o el scaffolding de vistas.** Va de la mano de
> [`guia-de-componentes.md`](guia-de-componentes.md) (los componentes) y del
> [registro de decisiones](03-decisiones-cerradas.md) (los porqués, DC-xx).

---

## 0. Modelo mental

- El **motor** (`bgm/core`, `@bgm/ui`, `@bgm/admin-kit`) aporta lo común: auth,
  media, i18n, traits, componentes, layout, biblioteca de iconos. **No se toca
  por juego.**
- Cada **juego** es su propio repo/monorepo con tres piezas que **consumen** el
  motor y programan **solo sus entidades**:
  - `api/` — Laravel + Sanctum (usa `bgm/core` vía Composer _path repository_).
  - `admin/` — SPA Vue (panel) que usa `@bgm/admin-kit` + `@bgm/ui`.
  - `app/` — SPA Vue (público) que usa `@bgm/ui`.
- Regla de oro (DC-28): si necesitas un componente/patrón, **míralo primero en
  kontuan** (y para cartas, en CDL); si existe, cópialo y adáptalo.

---

## 1. Crear el juego (una vez)

1. Estructura: un monorepo con `api/`, `admin/`, `app/` (mira el `playground/`).
2. En `api/composer.json`, añade el motor como _path repository_:

   ```json
   "repositories": [{ "type": "path", "url": "../../packages/core" }],
   "require": { "bgm/core": "*" }
   ```

3. `api/bootstrap/app.php`: registra el `SetLocale` del motor en el grupo `api`
   (imprescindible para que las respuestas se traduzcan por `?locale`):

   ```php
   ->withMiddleware(function (Middleware $middleware): void {
       $middleware->appendToGroup('api', \Bgm\Core\I18n\Http\Middleware\SetLocale::class);
   })
   ```

4. Front: cada SPA lee la API de `VITE_API_URL` (`.env`), sin proxy. El admin
   usa `createApi()` de `@bgm/ui` (token Sanctum + 401).
5. Instala y prepara:

   ```bash
   npm install
   cd api && php artisan migrate            # crea tablas del motor (icons, …) + las del juego
   php artisan storage:link                 # para servir imágenes en /storage
   php artisan db:seed                      # usuarios base (admin/editor/user)
   ```

6. Datos de prueba: **avisa antes de insertar datos** de entidades; el seeder
   base solo crea usuarios.

---

## 2. Backend — añadir un modelo

Sigue el patrón de `House`. Cuatro archivos: migración, modelo, controlador,
resource; y las rutas.

### 2.1 Migración (DC-24)

- Campos **traducibles** → columnas `json`. Fechas con `datetimes()` y, si hay
  papelera, `softDeletesDatetime()` (NO `timestamps()`; evita el año 2038).
- Nombres de tabla/columna en **inglés**.

```php
Schema::create('houses', function (Blueprint $table) {
    $table->id();
    $table->json('name');                 // traducible
    $table->json('description')->nullable();
    $table->json('slug');                 // slug traducible (uno por locale)
    $table->string('color', 7)->nullable();
    $table->boolean('is_published')->default(false);
    $table->datetimes();
    $table->softDeletesDatetime();        // si quieres papelera
});
```

### 2.2 Modelo + traits disponibles

```php
class House extends Model implements HasMedia
{
    use HasFilters, HasImage, HasPublishedState, HasTranslatableSlug,
        HasTranslations, ResolvesBySlug, SoftDeletes;

    protected $table = 'houses';
    protected $fillable = ['name', 'description', 'slug', 'color', 'is_published'];
    public array $translatable = ['name', 'description', 'slug'];   // Spatie Translatable
    protected array $searchable = ['name'];                         // columnas para el buscador
    protected function casts(): array { return ['is_published' => 'boolean']; }

    public function getSlugOptions(): SlugOptions {                 // Spatie Sluggable
        return SlugOptions::createWithLocales(array_keys(config('motor.locales', ['es' => []])))
            ->generateSlugsFrom('name')->saveSlugsTo('slug');
    }
}
```

**Traits del motor** (`Bgm\Core\…`) y qué aporta cada uno:

| Trait | Para qué | Requiere / expone |
|---|---|---|
| `Support\Concerns\HasFilters` | Buscador + filtro de estado del listado. | Propiedad `$searchable` (columnas). Scope `filter(['search','status'])` con `status = published\|draft\|trashed`. |
| `Support\Concerns\HasPublishedState` | Publicar / borrador. | Columna `is_published`. Scopes `published()` / `draft()` y método `togglePublished()`. |
| `Support\Concerns\ResolvesBySlug` | Resolver por slug en **cualquier** idioma (rutas por slug). | Columna `slug` (json). Scope `whereSlug($slug)`. |
| `Media\Concerns\HasImage` | Imagen única (emblema, portada…). | `implements HasMedia`. Métodos `imageUrl()` (URL sobre el host de la petición) y `setImageFromRequest($request)`. |

**Traits de Spatie** que se combinan:

| Trait | Para qué |
|---|---|
| `Spatie\Translatable\HasTranslations` | Campos traducibles (`$translatable`, columnas json). |
| `Spatie\Sluggable\HasTranslatableSlug` | Slug **por idioma** (con `getSlugOptions()`). |
| `Illuminate\Database\Eloquent\SoftDeletes` | Papelera (restaurar / borrar definitivo). |

### 2.3 Controlador (admin)

- `index`: `Model::query()->filter($request->only('search','status'))->orderByDesc('id')->paginate(15)`.
- `show/update/destroy/toggle`: resuelven por slug con `whereSlug($slug)->firstOrFail()`.
- `restore` y `forceDestroy`: por **id** con `withTrashed()` (actúan desde la papelera).
- Guarda traducciones con `setTranslations('name', array_filter($data['name'] ?? []))`.
- Imagen: `$model->setImageFromRequest($request)` (tras `save()`).

### 2.4 Resource

Devuelve las traducciones completas para editar y la URL de imagen:

```php
'name' => $this->getTranslations('name'),
'slug' => $this->getTranslations('slug'),
'image' => $this->imageUrl(),
```

### 2.5 Rutas (`api/routes/api.php`)

Público solo publicadas; admin bajo `auth:sanctum` + `motor.admin`. Detalle por
`{slug}`, papelera por `{id}`:

```php
Route::middleware(['auth:sanctum','motor.admin'])->prefix('admin')->group(function () {
    Route::get('houses', [HouseController::class, 'index']);
    Route::post('houses', [HouseController::class, 'store']);
    Route::get('houses/{slug}', [HouseController::class, 'show']);
    Route::put('houses/{slug}', [HouseController::class, 'update']);
    Route::delete('houses/{slug}', [HouseController::class, 'destroy']);
    Route::post('houses/{id}/restore', [HouseController::class, 'restore']);
    Route::delete('houses/{id}/force', [HouseController::class, 'forceDestroy']);
    Route::post('houses/{slug}/toggle-published', [HouseController::class, 'togglePublished']);
});
```

> Las rutas propias del juego van en `api/routes/api.php` (grupo `api`
> automático). Las del **motor** ya vienen dadas por `bgm/core` (auth, locales,
> iconos…) y usan `Route::prefix('api')->middleware('api')`.

### 2.6 Validación traducida

Provee `api/lang/{es,eu,en}/validation.php` (mensajes + `attributes` con los
nombres de campo). El locale lo fija `SetLocale` desde `?locale`.

---

## 3. Iconos (biblioteca del juego)

La biblioteca de iconos la aporta el **motor** (DC-32): no hay que programar
nada en el backend del juego.

- **Gestión:** en el admin, sección **Iconos** → subir SVG/PNG (nombre +
  imagen). Endpoints del motor: `GET /api/icons`, `POST`/`DELETE /api/admin/icons`.
- **Uso en el texto:** cualquier campo `TranslatableInput type="wysiwyg"` que
  reciba `:icons` muestra un selector que inserta el icono **en línea**
  (`<img class="rt-icon">`). En el front, cárgalos con el store de iconos y
  pásalos al editor:

  ```ts
  const icons = useIconsStore(); await icons.load()
  // <TranslatableInput type="wysiwyg" :icons="icons.icons.filter(i=>i.url)…" />
  ```

---

## 4. Frontend admin — añadir las vistas del modelo

Todo esto vive en el **juego** (`admin/src`), componiendo piezas de
`@bgm/admin-kit` + `@bgm/ui`. Nada de tablas: **grid de tarjetas** (DC-30), y
altas/ediciones en **modal** (DC-31).

### 4.1 i18n (`admin/src/i18n/locales/*.json`)

Añade por cada idioma: el segmento de ruta, las migas, el nav y la sección del
modelo (tabs, columnas, campos, acciones, toasts, confirmaciones):

```jsonc
"routes":      { "houses": "casas" },      // segmento traducido de la URL
"breadcrumbs": { "houses": "Casas" },
"nav":         { "houses": "Casas" },
"houses":      { "title": "...", "tabs": {…}, "fields": {…}, "actions": {…}, "toast": {…} }
```

### 4.2 Ruta localizada (`admin/src/router/i18n-paths.ts`)

Una ruta de listado por entidad; el `path` usa el segmento del locale actual y
`alias` los de los demás (para que la URL resuelva en cualquier idioma). El
detalle usa `:slug`. Las altas/ediciones **no** son rutas (son modales).

```ts
{ path: `/${p.houses}`, name: 'houses',
  component: () => import('@/views/houses/HousesListView.vue'),
  alias: buildAliases((t) => `/${t.houses}`, locale),
  meta: { admin: true, titleKey: 'houses.title', breadcrumbs: [{ key: 'houses' }] } }
```

### 4.3 Enlace en el menú (`admin/src/App.vue`)

```vue
<RouterLink class="nav-item" :to="{ name: 'houses' }">
  <Home class="nav-icon" :size="20" /><span class="nav-label">{{ t('nav.houses') }}</span>
</RouterLink>
```

### 4.4 Vista de listado (`admin/src/views/<modelo>/XListView.vue`)

Orden fijo: **filtros → tabs → grid de tarjetas**. Usa `useResource(api, '/admin/houses')`.

```vue
<FilterBar v-model="search" :placeholder="t('common.search')" />
<BaseTabs v-model="status" :tabs="tabs" />                <!-- published/draft/trashed -->
<EmptyState v-if="!loading && !items.length" :title="t('common.empty')" />
<BaseGrid v-else preset="cards" gap="md">
  <EntityCard v-for="item in items" :key="item.id" :title="tr(item.name)" :muted="!!item.deleted_at">
    <template #media>…emblema…</template>
    <template #actions>…IconButton editar/publicar/borrar (o restaurar/borrar-definitivo en papelera)…</template>
    <template #badges>…chip de estado…</template>
    <template #meta>…datos…</template>
  </EntityCard>
</BaseGrid>
<XFormModal v-model="formOpen" :mode="formMode" :target-slug="formSlug" @saved="onSaved" />
```

- `tr(obj)` = valor en el locale activo (`obj[locales.current] || fallback`).
- Editar → abre el modal con el **slug del locale activo**.
- Acciones por slug: `remove(slug)`, `action(slug, 'toggle-published')`;
  restaurar/borrado definitivo por id.

### 4.5 Modal de alta/edición (`admin/src/components/<modelo>/XFormModal.vue`)

Envuelve `EditModal`. Recibe `modelValue` + `mode: 'create'|'edit'` + `targetSlug`;
al abrir en edición carga por slug; emite `saved`.

```vue
<EditModal :model-value="modelValue" :title="title" :loading="saving"
  :submit-label="t('common.save')" :cancel-label="t('common.cancel')"
  @update:model-value="v => emit('update:modelValue', v)" @submit="submit">
  <TranslatableInput v-model="form.name" :locales="locales.locales" :label="t('…name')" required :error="errors.name" />
  <TranslatableInput v-model="form.description" :locales="locales.locales" type="wysiwyg" :icons="iconList" :label="t('…desc')" />
  <ImageUpload v-model="image" :current-url="currentImage" :label="t('…image')"
    :too-large-text="t('common.fileTooLarge')" :invalid-type-text="t('common.fileType')" />
  <PaletteColorPicker v-model="form.color" :label="t('…color')" />
  <BaseCheckbox v-model="form.is_published" :label="t('…published')" />
</EditModal>
```

Elementos de formulario disponibles (todos de `@bgm/ui`, ver la guía de
componentes): `BaseInput`, `BaseTextarea`, `BaseSelect`, `BaseCheckbox`,
`TranslatableInput` (text/textarea/**wysiwyg**), `RichTextInput`, `ImageUpload`
(drag&drop), `PaletteColorPicker` (hex).

Envío con `FormData` (para la imagen); en edición, `updateForm` añade
`_method=PUT`.

### 4.6 Validación en cliente + errores

- Comprueba en cliente lo requerido **antes** de enviar (buena UX y evita 422
  innecesarios); marca los campos con `:error`.
- En el `catch`: **nunca** muestres el mensaje crudo del servidor. Usa
  `fieldErrors(e)` (`admin/src/lib/apiError.ts`) para pintar errores por campo
  (422, ya traducidos) + un **toast genérico**.

### 4.7 Estilos (DC-27)

Nada de `<style>` en los `.vue`. Crea `admin/src/assets/scss/views/_<modelo>.scss`
con clases BEM y decláralo en `views/_index.scss`.

---

## 5. Checklist para una entidad nueva

Backend:
- [ ] Migración (json traducibles, `is_published`, `datetimes()`/`softDeletesDatetime()`).
- [ ] Modelo con los traits que necesite (`HasFilters`, `HasPublishedState`, `HasImage`, `ResolvesBySlug`, `HasTranslations`, `HasTranslatableSlug`, `SoftDeletes`).
- [ ] `$fillable`, `$translatable`, `$searchable`, `casts`, `getSlugOptions`.
- [ ] Controlador (index con `filter`, CRUD por slug, restore/force por id, toggle).
- [ ] Resource.
- [ ] Rutas admin (+ públicas si aplica).
- [ ] `php artisan migrate`.

Frontend admin:
- [ ] i18n en los 3 locales (routes, breadcrumbs, nav, sección del modelo).
- [ ] Ruta localizada en `i18n-paths.ts`.
- [ ] Enlace en `App.vue`.
- [ ] `XListView.vue` (FilterBar + BaseTabs + BaseGrid + EntityCard + EmptyState).
- [ ] `XFormModal.vue` (EditModal + campos).
- [ ] Validación cliente + `fieldErrors` + toast genérico.
- [ ] SCSS de la vista en `views/_<modelo>.scss`.

---

## 6. Convenciones que aplican siempre

- **DC-24** — código/tablas en inglés; `datetimes()`/`softDeletesDatetime()`.
- **DC-25** — iconos de UI con `@lucide/vue`.
- **DC-26** — mobile-first; nada más ancho que el 100%.
- **DC-27** — SCSS en ficheros globales (BEM), nada de `<style>` en `.vue`.
- **DC-28** — buscar antes en kontuan (y CDL para cartas); copiar y adaptar.
- **DC-29** — i18n de toda la app + rutas y slugs traducibles.
- **DC-30** — index sin tablas: grid de tarjetas.
- **DC-31** — altas/ediciones en modal (no rutas).
- **DC-32** — WYSIWYG TipTap + biblioteca de iconos del motor.

Detalle de cada componente en [`guia-de-componentes.md`](guia-de-componentes.md).
