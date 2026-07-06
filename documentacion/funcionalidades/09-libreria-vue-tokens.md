# 09 · Librería Vue + tokens SCSS

## Qué hace

La base visual compartida entre `admin` y `app` de cada juego: componentes Vue 3
genéricos (sin lógica de negocio) y un sistema de **design tokens** en SCSS.
Equivalente a `@kontuan/shared`, pero para juegos.

## Qué hay hoy en choque

Estilos SCSS server-side + librerías sueltas (Choices.js, SortableJS, TinyMCE,
Chart.js) sobre Blade. No hay componentes Vue reutilizables. → se construye nuevo,
inspirado en los ~30 componentes base de `@kontuan/shared`.

## Diseño nuevo

**Paquete `@edc-motor/ui`:**

```
src/
├── components/
│   ├── base/      BaseButton, BaseInput, BaseSelect, BaseTextarea, BaseCheckbox,
│   │              BaseModal, BaseCard, BaseTable, BaseTabs, BaseDropdown, BaseGrid…
│   ├── form/      TranslatableInput, ImageUpload (simple + multilingüe),
│   │              SortableList, NumericInput, IconPicker, ColorPicker…
│   └── feedback/  Toast/ToastContainer, LoadingSpinner, EmptyState, PageHeader
├── composables/   useTheme, useToast, useContentLocales, useHead (SEO), useApi
├── lib/           axios factory configurable, cliente REST tipado
├── i18n/          helpers de locale
└── index.ts       barrel export
└── scss/
    ├── _tokens.scss   colores, tipografía, espaciado (grid 4px), radios, breakpoints
    ├── _theme.scss    light/dark
    ├── _base.scss · _forms.scss · _fonts.scss
    └── components/     SCSS por componente
```

- **Build en modo librería** (Vite) → consumible por `admin` y `app`.
- **Tokens** como única fuente de verdad visual; cada juego puede sobre-escribir
  un puñado (color de marca, fuente) sin tocar el paquete.
- Componentes **sin negocio**: nada de "Card del juego" aquí; eso es del juego.

## Frontera motor ↔ juego

| Motor | Juego |
|---|---|
| Componentes base + tokens + composables + cliente API | Sus componentes visuales (entidades) y su tema (override de tokens) |

## Pasos

1. Andamiaje del paquete (Vite library, exports, tokens base).
2. Componentes base (botón, input, modal, tabla, card, grid, tabs…).
3. Componentes de formulario (TranslatableInput, ImageUpload, SortableList…).
4. Composables (useToast, useTheme, useContentLocales, useHead, useApi).
5. Documentar tokens y cómo un juego aplica su tema.

## Hito de aceptación

- `admin` y `app` del playground consumen `@edc-motor/ui`; cambiar un token de marca
  re-tematiza ambos.

## Decisiones (cerradas)

- **Texto rico** → **DC-09**: TipTap. **Drag & drop** → **DC-17**: vue-draggable-plus.
- **PWA** → **DC-01**: `@edc-motor/ui` aporta el `useHead` y el andamiaje que admin/app
  usan para ser instalables.

## Riesgos

- Empezar **mínimo** (lo que pidan admin-kit y las primeras vistas) y crecer; no
  portar los ~30 componentes de kontuan de golpe.
