# Changelog — @edc-motor/ui

Componentes Vue 3 + SCSS para las webs públicas (y piezas compartidas con el
admin). Paquete **fuente** (se consume vía Vite). Versión de tren con
`edc-motor/core` y `@edc-motor/admin-kit`.

## [0.3.0] — 2026-07-07

- Sin cambios propios: versión de tren (logo traducible en `edc-motor/core`).

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
