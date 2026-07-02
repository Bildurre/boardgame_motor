# Boardgame Motor (BGM)

Motor común (paquetes versionados) para construir webs de juegos de mesa: API REST
(Laravel) + admin y público (Vue 3 SPA, instalables como PWA), con generación de
PDF/PNG, CRM de páginas y bloques, i18n, auth y backup reutilizables. Cada juego
consume el motor y programa solo sus entidades.

> Diseño y decisiones en [`documentacion/`](documentacion/README.md).
> **Estado: Fases 0–3 completadas** (andamiaje · auth con verificación de
> email · traits/media/i18n · render de componentes a PNG). Plan en
> [`documentacion/02-plan-de-accion.md`](documentacion/02-plan-de-accion.md).

## Estructura

```
boardgame_motor/                 # monorepo donde se desarrolla el motor
├── packages/
│   ├── core/        bgm/core          (Composer — backend Laravel)
│   ├── ui/          @bgm/ui           (npm — componentes Vue + tokens SCSS)
│   └── admin-kit/   @bgm/admin-kit    (npm — layout + CRUD admin)
├── playground/                  # juego-demo para desarrollar/probar el motor
│   ├── api/         Laravel (requiere bgm/core por path repository)
│   ├── admin/       Vue SPA (5174) — usa @bgm/admin-kit + @bgm/ui
│   ├── app/         Vue SPA (5173) — web pública, usa @bgm/ui
│   └── packages/
│       └── shared/  @playground/shared (lo del juego compartido admin/app:
│                    cartas para web + render PNG, tipos, SCSS)
└── documentacion/
```

Durante el desarrollo, el `playground` consume los paquetes por **enlace local**
(Composer `path` repository + npm workspaces). La publicación versionada (npm a
GitHub Packages + subtree-split del paquete Composer) llega en la Fase 7. Ver
`documentacion/03-decisiones-cerradas.md` (DC-02).

## Requisitos

- PHP >= 8.2 · Composer 2.x
- Node >= 20.19 o >= 22.12 · npm >= 10
- MySQL >= 8 (el playground usa una base de datos `bgm`)

## Puesta en marcha

```bash
# 0) Crear la base de datos (una vez)
mysql -u root -p -e "CREATE DATABASE bgm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 1) Backend del playground
cd playground/api
composer install
npm install                   # puppeteer (render a PNG)
npm run chrome:install        # descarga el Chrome de puppeteer (si npm no lo hizo)
cp .env.example .env          # ajusta DB_USERNAME / DB_PASSWORD si hace falta
php artisan key:generate
php artisan migrate
php artisan motor:install     # roles base (admin/editor/user)
php artisan storage:link
cd ../..

# 2) Frontends + paquetes (desde la raíz, instala todos los workspaces)
npm install
```

> El `.env.example` ya apunta a MySQL (`DB_DATABASE=bgm`, `DB_HOST=127.0.0.1`,
> `DB_USERNAME=root`, `DB_PASSWORD=` vacío). Cambia usuario/contraseña en tu `.env`
> según tu MySQL local.

## Levantar todo con un solo comando

```bash
npm run dev
```

Arranca en paralelo (estilo kontuan, vía `concurrently`):

| Proceso | URL | Descripción |
|---|---|---|
| `app`   | http://localhost:5173 | Web pública (Vite) |
| `admin` | http://localhost:5174 | Panel admin (Vite) |
| `api`   | http://localhost:8010 | Laravel (`php artisan serve --port=8010`) |
| `queue` | — | Worker de cola (`queue:listen`): renders de PNG, correo… |

> La API usa el puerto **8010** (no el 8000 típico de Laravel) para no chocar con
> otras webs que tengas levantadas. Si 8010 también te lo pisa otro proceso,
> cámbialo en `package.json` (`dev:api`) **y** en el `proxy` de los dos
> `vite.config.ts`.

Las SPA llaman a la API leyendo su URL de `VITE_API_URL` (en el `.env` de cada
front), patrón kontuan. Por defecto `http://localhost:8010/api`. Si necesitas otra
URL/puerto, créate un `.env.local` en `playground/app` y `playground/admin` con tu
`VITE_API_URL` (no se versiona).

Comprobación rápida del cableado: http://localhost:8010/api/motor/ping debe
devolver el JSON del motor; la web (5173) debe mostrar la versión y los locales.

Otros scripts: `npm run dev:front` (solo app+admin) · `npm run build` (build de
ambas SPA) · `npm run lint` / `lint:fix` / `format` (ESLint + Prettier) ·
`npm run type-check` (vue-tsc) · `npm run lint:php` / `fix:php` (Pint) ·
`npm run test:api` (Pest).

## Render a PNG (previews)

El PNG de cada entidad se captura desde la ruta `/_render` de la `app` con
Chromium headless (Browsershot). Por defecto usa el Chromium que descarga
`puppeteer` (`npm install` en `playground/api`); si prefieres el del sistema,
fija `MOTOR_CHROME_PATH` en el `.env`. Si los jobs fallan con *"Could not
find Chrome"*, la descarga del navegador no llegó a ejecutarse (p. ej.
`ignore-scripts` en npm): `cd playground/api && npm run chrome:install`.
Gestión desde el admin (sección **Imágenes**) o en lote:
`php artisan preview:manage status|generate|regenerate|delete|clean`.
Detalles en `documentacion/funcionalidades/01-render-png.md` y en la guía
de montar una web (§5).
