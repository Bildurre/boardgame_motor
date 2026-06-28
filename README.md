# Boardgame Motor (BGM)

Motor común (paquetes versionados) para construir webs de juegos de mesa: API REST
(Laravel) + admin y público (Vue 3 SPA, instalables como PWA), con generación de
PDF/PNG, CRM de páginas y bloques, i18n, auth y backup reutilizables. Cada juego
consume el motor y programa solo sus entidades.

> Diseño y decisiones en [`documentacion/`](documentacion/README.md).
> **Estado: Fase 0 (andamiaje) completada.** Plan en
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
│   └── app/         Vue SPA (5173) — web pública, usa @bgm/ui
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
cp .env.example .env          # ajusta DB_USERNAME / DB_PASSWORD si hace falta
php artisan key:generate
php artisan migrate
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
| `api`   | http://localhost:8000 | Laravel (`php artisan serve`) |

Las SPA llaman a la API por el proxy `/api` de Vite (sin CORS en dev).
Comprobación rápida del cableado: http://localhost:5173/api/motor/ping debe
devolver el JSON del motor.

Otros scripts: `npm run dev:front` (solo app+admin) · `npm run build` (build de
ambas SPA).
