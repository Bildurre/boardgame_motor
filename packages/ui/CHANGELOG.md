# Changelog — @edc-motor/ui

Componentes Vue 3 + SCSS para las webs públicas (y piezas compartidas con el
admin). Paquete **fuente** (se consume vía Vite). Versión de tren con
`edc-motor/core` y `@edc-motor/admin-kit`.

## [0.4.4] — 2026-07-12

### Añadido

- **`PreviewGrid`**: rejilla presentacional de previews del catálogo público
  (`GET /api/catalog/{key}`). Props `items` (con `to` opcional → RouterLink),
  `loading`, `page`/`pages` (paginación prev/next con emit `page`) y variante
  `compact`; slots `item` y `actions` (scoped `{ item }`) y `empty`. Fallback
  con el nombre y proporción de carta (5/7) cuando la preview no está
  generada. Textos por prop (DC-29). SCSS en `components/_preview-grid.scss`.
- **Bloque `related`** (`BlockRelated`, clave `related` en
  `motorBlockComponents`): título/subtítulo + PreviewGrid compacta con los
  ítems de `data` y botón opcional al índice (`with_button`/`button_label`;
  texto por defecto por prop, DC-29). Los enlaces se resuelven con el mapa
  que la app provee vía **`catalogRoutesKey`** (nuevo
  `src/blocks/catalogRoutes.ts`, exporta también los tipos `CatalogItem`,
  `CatalogRouteEntry` y `CatalogRoutes`); sin mapa, los ítems se pintan sin
  enlace. SCSS en `components/_block-related.scss`.

## [0.4.3] — 2026-07-11

- Sin cambios propios: versión de tren.

## [0.4.2] — 2026-07-10

### Cambiado

- **Bloque CTA**: los botones suben a cuerpo 18 (`$fs-18`); la tarjeta es un
  poco más transparente (50% de superficie, antes 65%, como el resto de
  tarjetas de bloque); y la **imagen sangra hasta el borde de la tarjeta**
  según su posición — en columnas (izquierda/derecha) toca arriba, abajo y su
  lateral (el título y subtítulo pasan a la columna de texto), y
  arriba/abajo toca los laterales (arriba solo si no hay título encima).
  Las esquinas siguen el radio de la tarjeta.
- **Anchura `narrow` de los bloques**: sube de 680px a **800px**.

## [0.4.0] — 2026-07-07

### Añadido

- Token `$fs-32`; parcial público **`_rich-content.scss`** (márgenes entre
  párrafos/listas/títulos del wysiwyg y escala h2 28 · h3 24 · h4 20 · h5 18
  · h6 16).
- Bloques: subtítulo en todos; marco de imagen en columnas con
  `image_fit`/`image_columns`; índice con sangría por nivel.

### Cambiado

- Tipografía de bloque: texto 16 · subtítulo 20 · título 28 (cabecera
  32/24). El título es h1 SOLO en la cabecera (h2 en el resto) y nunca se
  justifica (justificado → izquierda). Anchura `wide` a **1200px**.

## [0.3.1] — 2026-07-07

- Sin cambios propios: versión de tren (fix de subida de SVG en `edc-motor/core`).

## [0.3.0] — 2026-07-07

### Cambiado

- `TranslatableImage`: la prop `upload` recibe también la URL a la que
  sustituye (`(file, replaces?)`) y hay una prop opcional `removeFile` para
  borrar el fichero al pulsar "quitar".

## [0.2.0] — 2026-07-06

### Cambiado

- **Renombrado del vendor/scope a `edc-motor`** (DC-21 revisada): el paquete
  Composer pasa de `bgm/core` a **`edc-motor/core`** (namespace PHP
  `Edc\Core`) y los npm a **`@edc-motor/ui`** y **`@edc-motor/admin-kit`**.
  Migración de un juego existente: actualizar `composer.json`/`package.json`,
  los imports (`@bgm/` → `@edc-motor/`), el namespace en `config/motor.php` y
  las clases propias, y las clases CSS `bgm-*` → `edc-*`.
- **Licencia GPL-3.0-only** y publicación en registros públicos: Packagist
  (`edc-motor/core`, vía el repo split `bildurre/edc-core`) y npmjs
  (org `edc-motor`). El consumo por clon hermano deja de ser necesario.

## [0.1.0] — 2026-07-05

Primera versión etiquetada (Fases 0–7 del plan).

### Añadido

- **Base**: `BaseButton` (variantes primary/secondary/danger/success/text,
  con icono), `BaseInput`, `BaseSelect`, `BaseTextarea`, `BaseModal`,
  `ConfirmDialog`, toasts (`useToast` + `ToastHost`), `IconButton`,
  `ThemeSelector` (claro/oscuro/sistema) y `LocaleDropdown`.
- **Contenido**: editor WYSIWYG propio con TipTap (`RichTextEditor`, DC-09)
  con toggle visual/HTML, `PageBackground`, `BlockRenderer` + bloques del
  catálogo (hero, texto, imagen, cita con fuente *especial*, CTA con
  `.block-button` de hover cruzado, columnas, índice, FAQ…), envoltorio
  `BlockShell` (align/width/background).
- **SEO**: `useHead` sin dependencias (title, description, canonical,
  hreflang) apto para el prerender (DC-18).
- **SCSS**: tokens (`tokens.scss` con fuentes/colores/espaciado/radios),
  temas claro/oscuro, parciales de componentes y utilidades
  (`rich-content`, formularios).
- `RichTextInput` se exporta **diferido** (defineAsyncComponent): TipTap
  (~450 KB) no entra en el bundle de la web pública y el admin lo trocea en
  su propio chunk.
- `BaseButton` con variantes `info` y `warning`; `.block-button` cruza sus
  estados sobre la SUPERFICIE ($surface), no sobre el fondo puro.
- **Chip único** (`.chip`): contorno con esquinas poco redondeadas
  ($radius-sm), acento por defecto (nunca gris), `$fs-12`, con estados
  `is-ok/is-info/is-missing/is-failed` — lo usan app y admin (sustituye a
  `.locale-chip` y a los chips por vista).
- i18n por props (DC-29): el paquete no lleva textos; la app los inyecta.
