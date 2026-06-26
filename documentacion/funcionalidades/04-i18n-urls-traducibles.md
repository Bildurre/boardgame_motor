# 04 · i18n y URLs traducibles

## Qué hace

Contenido multi-idioma de punta a punta: campos traducibles en los modelos,
**slugs traducibles** (cada entidad tiene URL distinta por idioma), resolución de
locale en la API, y edición de todos los idiomas en el admin. **Dolor explícito:
hoy las URLs traducibles no funcionan bien, y menos en el admin.**

## Qué hay hoy en choque (y qué duele)

- `spatie/laravel-translatable` para campos; `spatie/laravel-sluggable`
  (`HasTranslatableSlug`) para slugs; `mcamara/laravel-localization` para routing.
- `Providers\TranslatableSlugServiceProvider` resuelve el slug→modelo.

Lo que duele:
- El provider **hardcodea cada modelo** con su `Route::bind` (`page`, `hero`,
  `card`, `faction`, `faction_deck`) y un `whereRaw("JSON_EXTRACT(slug,...)")` por
  locale. Añadir una entidad traducible = tocar el provider. No es del motor.
- Acoplado a **route-model binding** de un servidor Blade. En API/SPA la resolución
  es otra (por query/Accept-Language), así que esto hay que rehacerlo para el motor.
- La edición en el admin de "el slug de cada idioma" es justo donde se rompe.

## Diseño nuevo

**Principio:** traducibilidad **declarativa y genérica** en el motor. Una entidad
declara qué campos son traducibles y cuáles son slugs; el motor aporta resolución,
validación de unicidad por locale, y soporte de edición multi-idioma en el admin.
Nada hardcodeado por modelo.

### Locales

- Lista de locales de contenido en `config/motor.php` (`locales`, `default`).
- El front (admin y app) los lee de un endpoint (`GET /api/v1/locales`) → no se
  duplican en cada juego.

### Campos traducibles (backend)

- Trait `HasTranslatableContent` (envuelve Spatie Translatable) + convención: el
  modelo declara `$translatable`. El motor ofrece helpers para leer/escribir por
  locale y para fallback.
- **API**: las respuestas devuelven el valor en el locale pedido **y** (en el admin)
  el objeto completo `{es, eu, en}` para editar todos los idiomas. Un parámetro o
  cabecera distingue "modo lectura pública" de "modo edición".

### Slugs traducibles (backend)

- Trait `HasTranslatableSlug` del motor: genera slug por locale desde un campo
  fuente, garantiza **unicidad por locale**, permite edición manual.
- **Resolución genérica** (sustituye el provider hardcodeado): un `SlugResolver`
  único que, dado un modelo y un slug, busca en todos los locales. Las entidades
  del juego se registran en un `TranslatableRouting` (o se resuelve vía contrato
  `ResolvableBySlug`) — **una línea por entidad**, no un `Route::bind` a mano:

```php
// en el provider del juego
TranslatableRouting::register('cards', Card::class);
TranslatableRouting::register('pages', Page::class);
```

- En la **API** la resolución es por endpoint: `GET /public/cards/{slug}` usa el
  `SlugResolver` para encontrar la carta por slug en cualquier locale y, si el slug
  pedido no es el del locale activo, responde con la URL canónica de ese locale
  (útil para redirecciones/SEO en la SPA).

### Frontend

- **app (público)**: Vue Router con prefijo de locale (`/es/...`, `/eu/...`). Cada
  vista pide datos por slug+locale; `motor-ui` trae un `useContentLocales` y un
  `LocaleSwitcher` que cambia de idioma **manteniendo la entidad** (resuelve el slug
  equivalente en el otro idioma vía API).
- **admin**: `TranslatableInput` (de `motor-ui`) para editar todos los idiomas de un
  campo, **incluido el slug**, con aviso de unicidad. Aquí es donde hoy se rompe;
  se trata como ciudadano de primera.
- **vue-i18n** para los textos de la propia interfaz (distinto de los datos).

## Frontera motor ↔ juego

| Motor | Juego |
|---|---|
| Traits traducibles + slug, `SlugResolver`, unicidad, API de locales | Declara `$translatable` y registra sus entidades resolubles (1 línea) |
| `TranslatableInput`, `LocaleSwitcher`, routing con prefijo | Usa los componentes en sus formularios y vistas |

## Pasos

1. Config de locales + `GET /api/v1/locales`.
2. `HasTranslatableContent` + serialización API (lectura pública vs. edición).
3. `HasTranslatableSlug` del motor + unicidad por locale.
4. `SlugResolver` genérico + `TranslatableRouting::register`.
5. Endpoints públicos por slug con canónica por locale.
6. `TranslatableInput` (con slug) + `LocaleSwitcher` en `motor-ui`; prefijo de
   locale en el router de `app`.
7. Playground: entidad traducible con slug por idioma, editable en admin y navegable
   en público en los dos idiomas.

## Hito de aceptación

- Una entidad demo tiene slug distinto por idioma, **editable en el admin** en
  todos los locales, con unicidad validada.
- En público, `/es/...` y `/eu/...` resuelven la misma entidad; el `LocaleSwitcher`
  salta entre idiomas conservando la entidad.
- Registrar una entidad traducible nueva = **una línea**, sin tocar el motor.

## Riesgos / decisiones abiertas

- **Consulta de slug en JSON**: el `whereRaw(JSON_EXTRACT)` por locale funciona pero
  conviene índice/columna generada para rendimiento si hay muchas entidades.
- **Locale por defecto y fallback**: definir política (¿mostrar default si falta
  traducción, u ocultar?). Afecta a público y a previews/PDF.
- **Localización de rutas en API**: decidir si el prefijo de locale vive solo en el
  front (SPA) y la API es locale-agnóstica con parámetro, que es lo más limpio.
