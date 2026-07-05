# Changelog — @bgm/ui

Componentes Vue 3 + SCSS para las webs públicas (y piezas compartidas con el
admin). Paquete **fuente** (se consume vía Vite). Versión de tren con
`bgm/core` y `@bgm/admin-kit`.

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
- i18n por props (DC-29): el paquete no lleva textos; la app los inyecta.
