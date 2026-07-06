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
