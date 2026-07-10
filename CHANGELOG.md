# Changelog — EdC Motor

El motor se versiona **en tren**: `edc-motor/core`, `@edc-motor/ui` y `@edc-motor/admin-kit`
comparten número de versión y se etiquetan juntos con un tag `vX.Y.Z` en este
repositorio. Cada paquete tiene su propio `CHANGELOG.md` con el detalle:

- [`packages/core/CHANGELOG.md`](packages/core/CHANGELOG.md) — backend Laravel (`edc-motor/core`).
- [`packages/ui/CHANGELOG.md`](packages/ui/CHANGELOG.md) — componentes públicos (`@edc-motor/ui`).
- [`packages/admin-kit/CHANGELOG.md`](packages/admin-kit/CHANGELOG.md) — kit del admin (`@edc-motor/admin-kit`).

El formato sigue [Keep a Changelog](https://keepachangelog.com/es/) y el
versionado, [SemVer](https://semver.org/lang/es/) (mientras estemos en `0.x`,
los cambios de API pueden llegar en versiones menores).

## [Sin publicar]

- **El header crece con el logo**: la línea 1 de la cabecera pública ya no es
  fija (56px); mide el alto del logo + 22px en cada breakpoint, con el resto
  de elementos centrados verticalmente y la barra lateral móvil y el hueco
  del contenido siguiéndola (antes el logo ancho se salía del header). La
  escalera del logo se acota: 34 → 44 → **50px** como máximo (header 56 → 66
  → 72px).
- **Acciones del header más limpias**: fuera las barras separadoras y los
  grupos — elementos sueltos (admin · descargas · entrar/usuario · idioma ·
  tema) con un único gap. Patrón común: icono/texto en color de texto, sin
  caja ni padding, y color solo al hover (acento; rojo en salir).
  Entrar/usuario pierde los iconos (texto a secas). **Migración del
  cascarón**: copiar de `plantilla/`
  `app/src/assets/scss/components/_app-header.scss` y
  `app/src/components/AppHeader.vue`.
- **Scripts de mantenimiento en la plantilla**: los juegos nuevos nacen con
  `update-motor.sh <version>` (sube los paquetes composer/npm del motor, migra
  y limpia cachés), `copiar-plantilla.sh [-t vX.Y.Z] <rutas...>` (trae archivos
  del cascarón desde `plantilla/` del monorepo, para las notas de "migración
  del cascarón" del changelog) y `claude.sh --start/--finish <rama>` (flujo de
  ramas de Claude).
- **Fix de la plantilla (Vite)**: en los juegos que consumen los paquetes
  desde npm (no enlazados), el optimizador de dependencias de Vite
  pre-empaquetaba `@edc-motor/ui` y `@edc-motor/admin-kit` pero externalizaba
  sus `.vue`, duplicando los singletons de los composables (toast, confirm,
  panel derecho): el panel no se abría al seleccionar y los confirms/toasts no
  salían. Los `vite.config.ts` de la plantilla (admin y app) añaden
  `optimizeDeps.exclude` para servir los paquetes como fuente. **Migración de
  juegos existentes**: añadir a `admin/vite.config.ts`
  `optimizeDeps: { exclude: ['@edc-motor/admin-kit', '@edc-motor/ui'] }` y a
  `app/vite.config.ts` `optimizeDeps: { exclude: ['@edc-motor/ui'] }`.

## [0.4.0] — 2026-07-07

- **Bloques anidados** (un nivel) con índice automático **indentado** y
  tarjetas hijas sangradas en el admin.
- Imagen de los bloques texto/CTA: **modo de escalado** (contener / cubrir /
  rellenar, al alto del texto de al lado) y **reparto de columnas** (1:1 …
  4:3). Subidas hasta 10 MB.
- **Subtítulo en todos los bloques**; ningún título es obligatorio. Título h1
  solo en la cabecera (h2 en el resto, nunca justificado).
- Tipografía con tokens: texto 16 · subtítulo 20 · título 28 (cabecera
  32/24); anchura por defecto `wide` **~1200px**. Wysiwyg con márgenes entre
  elementos, escala h3–h6 y sin párrafos vacíos.
- El single de página recupera el **panel de la Página** en la barra derecha
  (acciones + flags + slugs) cuando no hay bloque seleccionado.
- El logo del header de la web crece con el ancho: 34px en estrecho y hasta
  el doble (68px) en pantallas anchas.

## [0.3.1] — 2026-07-07

- Fix: la subida de imágenes rechazaba los SVG ("debe ser una imagen");
  vuelven a admitirse y se guardan saneados (sin scripts ni handlers).

## [0.3.0] — 2026-07-07

- **Logo traducible** en la configuración de la web: uno por idioma desde el
  admin (`TranslatableImage`), con fallback al locale por defecto y el SVG
  inlineado por idioma (currentColor hereda el acento). El formato antiguo
  (URL única) se sigue aceptando.
- **Subidas de imagen sin huérfanos** (logo, favicon, fondos e imágenes de
  bloques): se guardan con el nombre original del fichero y al sustituir o
  quitar una imagen la anterior se borra del disco.

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
- **`plantilla/`**: esqueleto mínimo de juego (api + admin + app +
  `packages/shared`) con toda la infraestructura del motor funcionando y SIN
  entidades demo, cubierto por los gates del monorepo. `tools/crear-juego.sh`
  ahora genera los juegos desde ella, con dependencias por versión de los
  registros (sin carpeta `motor/` hermana).

## [0.1.0] — 2026-07-05

Primera versión etiquetada: el resultado de las Fases 0–7 del
[plan de acción](documentacion/02-plan-de-accion.md). Incluye auth y roles,
comportamientos de modelo + media + i18n, render de componentes a PNG,
generación de PDF (catálogo + colección de usuario/invitado), CRM de páginas
y bloques, configuración de la web, gestión de usuarios, backup de BBDD,
web pública (locale/SEO/sitemap/prerender, descargas) y panel de usuario
extensible. Ver el changelog de cada paquete.

Herramientas del monorepo:

- `tools/crear-juego.sh <destino> [ruta-al-motor]` — genera un proyecto de
  juego limpio a partir del playground (api + admin + app + packages/shared,
  tooling raíz y CI adaptados, solo las guías de documentación) que consume
  el motor por versión desde una carpeta hermana.
- `tools/consumo-externo/probar-consumo.sh` — prueba reproducible de consumo
  del motor por versión (Composer `path` + npm `file:`).
