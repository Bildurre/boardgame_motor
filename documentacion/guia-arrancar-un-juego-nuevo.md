# Guía: cómo arrancar un juego nuevo sobre el motor

Objetivo de la Fase 7 (hito): crear un repo de juego nuevo que **instale el
motor por versión** y tenga admin + web pública funcionando **en menos de un
día**. Esta guía cubre el arranque del repo y el consumo por versión; el
detalle de cada pieza (entidades, bloques, PNG, PDF…) está en
`guia-como-montar-una-web.md` y `guia-de-componentes.md`.

## 1. Cómo se distribuye el motor (DC-33)

El motor vive en **un monorepo etiquetado**: cada release es un tag `vX.Y.Z`
y los tres paquetes comparten esa versión (versión *de tren*):

| Paquete | Qué es | Se instala con |
|---|---|---|
| `bgm/core` | Backend Laravel (provider, migraciones, API) | Composer (repositorio `path`) |
| `@bgm/ui` | Componentes públicos Vue + SCSS (paquete **fuente**) | npm (`file:`) |
| `@bgm/admin-kit` | Kit del admin (paquete **fuente**, depende de `@bgm/ui`) | npm (`file:`) |

Los paquetes npm son **fuente** (`main: src/index.ts`): los compila la app
del juego con Vite (por eso necesitan `sass-embedded` y
`@vitejs/plugin-vue` en el consumidor; no hay paso de build en el motor).

**El juego fija la versión clonando el motor a un tag** junto al repo del
juego (carpeta hermana o submódulo git):

```bash
# opción A: carpeta hermana fijada al tag
git clone --branch v0.1.0 --depth 1 <url-del-motor> motor

# opción B: submódulo (queda registrado en el repo del juego)
git submodule add <url-del-motor> motor
git -C motor checkout v0.1.0
```

**Actualizar de versión** = mover el clon/submódulo al tag nuevo y refrescar
dependencias (leer antes los `CHANGELOG.md` del motor):

```bash
git -C motor fetch --tags && git -C motor checkout v0.2.0
cd api && composer update bgm/core
cd .. && npm install
```

> Un registry privado (Packagist/Satis para Composer, Verdaccio/npm para JS)
> puede sustituir el clon más adelante sin tocar el código del juego: solo
> cambian `repositories` y los especificadores de dependencia.

## 2. Esqueleto del repo del juego

La estructura de referencia es la del `playground/` de este monorepo — de
hecho, **la forma más rápida de arrancar es copiarlo** y renombrar:

```
mi-juego/
├── motor/                  # clon/submódulo del motor al tag elegido
├── api/                    # Laravel (bgm/core)
├── admin/                  # SPA de administración (@bgm/admin-kit)
├── app/                    # web pública (@bgm/ui)
├── packages/shared/        # cartas y tipos compartidos entre admin y app
└── package.json            # workspaces npm: admin, app, packages/shared
```

`packages/shared` existe porque **las cartas se pintan igual** en el admin,
en la web y en el render a PNG (doc 01): componentes como `CharacterCard` +
los tipos TS de las entidades viven una sola vez.

## 3. Backend (`api/`)

```bash
composer create-project laravel/laravel api
cd api
```

En `composer.json`, apunta al motor clonado y exige la versión:

```json
{
  "require": { "bgm/core": "0.1.0" },
  "repositories": {
    "bgm-core": { "type": "path", "url": "../motor/packages/core" }
  }
}
```

```bash
composer update bgm/core        # instala core + sus dependencias (Sanctum, Spatie…)
php artisan vendor:publish --tag=motor-config   # config/motor.php
php artisan migrate             # migraciones del motor + las tuyas
php artisan motor:install       # roles base (admin, editor, user) y permisos
```

En `config/motor.php` configura locales de contenido, colas, PDF (layouts),
backup y `frontend` (URLs de app/admin para correos y render). `.env` mínimo
para desarrollo: SQLite, `QUEUE_CONNECTION=sync`, Mailpit
(`MAIL_MAILER=smtp`, puerto 1025) — ver §1.7 de la guía de la web para
colas en producción.

Lo que aporta el juego (con el playground de plantilla):

- **Modelos/migraciones/controladores/resources** de sus entidades
  (checklist §7 de `guia-como-montar-una-web.md`).
- **Registros** en un provider propio: `Previews::register(...)` (PNG),
  `Pdfs::register(...)` (exports), `Sitemap::add(...)` (SEO) y sus bloques
  propios (`BlockTypes::register(...)`).
- **Seeder demo** con datos de todas las piezas (regla de la casa: todo lo
  que se genere, al seeder).

## 4. Frontends (`admin/` y `app/`)

Ambos son apps Vite + Vue 3 + TS normales. En el `package.json` raíz del
juego (workspaces `admin`, `app`, `packages/shared`):

```json
{
  "dependencies": {
    "@bgm/ui": "file:../motor/packages/ui",
    "@bgm/admin-kit": "file:../motor/packages/admin-kit"
  }
}
```

y en cada `vite.config.ts`, los `loadPaths` de SCSS hacia los tokens del
motor (los paquetes son fuente):

```ts
css: {
  preprocessorOptions: {
    scss: {
      additionalData: '@use "tokens" as *;\n',
      loadPaths: [
        fileURLToPath(new URL('../motor/packages/ui/scss', import.meta.url)),
        fileURLToPath(new URL('../packages/shared/scss', import.meta.url)),
        fileURLToPath(new URL('./src/assets/scss', import.meta.url)),
      ],
    },
  },
},
```

Qué rellena el juego (todo con el playground de espejo):

- **admin**: rutas localizadas (`i18n-paths.ts`), vistas de listado/single
  por entidad, modales de formulario, i18n ×3; los gestores (Imágenes, PDF,
  Páginas, Configuración, Usuarios, Copias) son del kit y solo se montan.
- **app**: `entityRegistry` (secciones públicas), componentes de tarjeta y
  detalle, `router/downloads.ts`, i18n ×3, prerender (`npm run prerender`).
- **packages/shared**: componentes de carta + `render/registry.ts` (PNG).

## 5. Checklist del primer día

- [ ] Repo del juego con el motor clonado/submódulo al tag (`v0.1.0`).
- [ ] `api/` instala `bgm/core` por versión; `migrate` + `motor:install` OK.
- [ ] `admin/` y `app/` compilan (`npm run build`) con `@bgm/ui` y
      `@bgm/admin-kit` por `file:`.
- [ ] Primera entidad de juego completa (checklist §7 de la guía de la web).
- [ ] Seeder demo; login admin y web pública navegable en los 3 locales.
- [ ] CI copiada de `.github/workflows/ci.yml` (ajustando rutas).

## 6. Versionar el motor (para quien lo mantiene)

1. Actualiza los `CHANGELOG.md` (raíz + 3 paquetes) con lo nuevo.
2. Sube la versión **en los 4 sitios** (raíz `package.json`,
   `packages/ui`, `packages/admin-kit`, `packages/core/composer.json`).
3. Gates en verde (`lint`, `type-check`, `build`, `lint:php`, `test:api`).
4. Tag y push: `git tag v0.2.0 && git push origin v0.2.0` (el CI corre
   también sobre tags).

Mientras estemos en `0.x`, una versión **menor** puede romper API (SemVer);
los juegos actualizan leyendo el changelog. La prueba de consumo externa
(§ Fase 7 del plan) vive en `tools/consumo-externo/` y puede correrse antes
de etiquetar.
