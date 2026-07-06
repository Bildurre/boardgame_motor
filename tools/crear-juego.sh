#!/usr/bin/env bash
# Crea un PROYECTO DE JUEGO limpio a partir del playground del monorepo:
# api/ + admin/ + app/ + packages/shared/ con el tooling raíz y la CI, las
# GUÍAS de documentación (sin los docs de desarrollo del motor) y sin nada
# del playground como tal. El proyecto consume el motor por versión desde
# una carpeta hermana (clon/submódulo al tag), según DC-33.
#
# Uso:
#   tools/crear-juego.sh <directorio-destino> [ruta-al-motor]
#
#   <directorio-destino>  dónde crear el juego (p. ej. ../mi-juego).
#                         El nombre del juego se toma del basename.
#   [ruta-al-motor]       ruta al clon del motor RELATIVA A LA RAÍZ del
#                         juego (o absoluta). Por defecto: ../motor
#
# Ejemplo (motor y juego como carpetas hermanas):
#   git clone --branch v0.1.0 --depth 1 <url-del-motor> motor
#   motor/tools/crear-juego.sh mi-juego
#   cd mi-juego/api && composer install && cp .env.example .env \
#     && php artisan key:generate && php artisan migrate --seed \
#     && php artisan motor:install
#   cd .. && npm install && npm run dev
set -euo pipefail

MOTOR_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ $# -lt 1 ]]; then
  sed -n '2,20p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
  exit 1
fi

DEST="$1"
MOTOR_REL="${2:-../motor}"

if [[ -e "$DEST" && -n "$(ls -A "$DEST" 2>/dev/null)" ]]; then
  echo "ERROR: $DEST existe y no está vacío; no piso nada." >&2
  exit 1
fi

mkdir -p "$DEST"
DEST="$(cd "$DEST" && pwd)"

# Nombre del juego (para package.json/composer.json): basename saneado.
NOMBRE="$(basename "$DEST" | tr '[:upper:]' '[:lower:]' | tr -cs 'a-z0-9' '-' | sed 's/^-//; s/-$//')"

# Versión de tren del motor (la exige el composer.json del juego).
VERSION="$(php -r 'echo json_decode(file_get_contents($argv[1]))->version;' "$MOTOR_DIR/packages/core/composer.json")"

# Ruta al motor DESDE api/, app/ y admin/ (un nivel por debajo de la raíz).
if [[ "$MOTOR_REL" = /* ]]; then
  MOTOR_DESDE_HIJO="$MOTOR_REL"
else
  MOTOR_DESDE_HIJO="../$MOTOR_REL"
fi

echo "== Motor:   $MOTOR_DIR (v$VERSION)"
echo "== Juego:   $DEST (nombre: $NOMBRE)"
echo "== El juego buscará el motor en: <raíz del juego>/$MOTOR_REL"

# ---------------------------------------------------------------- plantilla --
# Copia SOLO lo versionado en git (fuera vendor/, node_modules/, dist/,
# .env de la api, sqlite…), remapeando playground/<x> → <x>. El composer.lock
# se excluye: su repositorio path apunta a la ruta del monorepo.
copiar() {
  local origen="$1" destino="$2" f rel
  while IFS= read -r -d '' f; do
    rel="${f#"$origen"/}"
    case "$rel" in
      composer.lock) continue ;;
    esac
    mkdir -p "$DEST/$destino/$(dirname "$rel")"
    cp "$MOTOR_DIR/$f" "$DEST/$destino/$rel"
  done < <(git -C "$MOTOR_DIR" ls-files -z -- "$origen")
}

echo
echo "== 1/5 · Copiando la plantilla (playground versionado)"
copiar playground/api api
copiar playground/admin admin
copiar playground/app app
copiar playground/packages/shared packages/shared

# ------------------------------------------------------- rutas hacia el motor
echo "== 2/5 · Apuntando composer y vite al motor ($MOTOR_REL)"

# api/composer.json: nombre propio, edc-motor/core por VERSIÓN y path repo al motor.
sed -i \
  -e "s|\"name\": \"laravel/laravel\"|\"name\": \"$NOMBRE/api\"|" \
  -e "s|\"edc-motor/core\": \"\*\"|\"edc-motor/core\": \"$VERSION\"|" \
  -e "s|\"url\": \"../../packages/core\"|\"url\": \"$MOTOR_DESDE_HIJO/packages/core\"|" \
  "$DEST/api/composer.json"

# vite.config.ts de app y admin: loadPaths de SCSS hacia el motor (los
# paquetes son fuente) y fs.allow para servir sus módulos en dev (viven
# fuera de la raíz del juego). '../packages/shared/scss' no cambia.
for spa in app admin; do
  sed -i \
    -e "s|'../../packages/ui/scss'|'$MOTOR_DESDE_HIJO/packages/ui/scss'|" \
    -e "s|'../../packages/admin-kit/scss'|'$MOTOR_DESDE_HIJO/packages/admin-kit/scss'|" \
    -e "s|server: { port: \([0-9]*\) },|server: { port: \1, fs: { allow: ['..', '$MOTOR_DESDE_HIJO'] } },|" \
    "$DEST/$spa/vite.config.ts"
done

# Marca del juego en vez de la del playground (manifest PWA y nombres npm).
sed -i "s|EdC Playground|$NOMBRE|g" "$DEST/app/vite.config.ts" "$DEST/admin/vite.config.ts"
sed -i "s|\"name\": \"playground-app\"|\"name\": \"$NOMBRE-app\"|" "$DEST/app/package.json"
sed -i "s|\"name\": \"playground-admin\"|\"name\": \"$NOMBRE-admin\"|" "$DEST/admin/package.json"

# ------------------------------------------------------------- tooling raíz --
echo "== 3/5 · Tooling raíz (package.json, eslint, prettier, gitignore, CI)"

# Workspaces del juego + @edc-motor/* por file: (los hijos declaran "*" y npm lo
# resuelve contra estos enlaces). Mismos gates que el monorepo.
cat > "$DEST/package.json" <<JSON
{
  "name": "$NOMBRE",
  "version": "0.1.0",
  "private": true,
  "type": "module",
  "workspaces": [
    "packages/shared",
    "admin",
    "app"
  ],
  "dependencies": {
    "@edc-motor/ui": "file:$MOTOR_REL/packages/ui",
    "@edc-motor/admin-kit": "file:$MOTOR_REL/packages/admin-kit"
  },
  "scripts": {
    "dev": "concurrently -n app,admin,api,queue -c blue,green,red,yellow \"npm run dev -w app\" \"npm run dev -w admin\" \"npm run dev:api\" \"npm run dev:queue\"",
    "dev:front": "concurrently -n app,admin -c blue,green \"npm run dev -w app\" \"npm run dev -w admin\"",
    "dev:api": "cd api && php artisan serve --port=8010",
    "dev:queue": "cd api && php artisan queue:listen --tries=1",
    "build": "npm run build -w app && npm run build -w admin",
    "type-check": "npm run type-check -w app && npm run type-check -w admin",
    "lint": "eslint .",
    "lint:fix": "eslint . --fix",
    "format": "prettier --write \"admin/src/**/*.{ts,vue}\" \"app/src/**/*.{ts,vue}\" \"packages/shared/src/**/*.{ts,vue}\"",
    "lint:php": "cd api && ./vendor/bin/pint --test",
    "fix:php": "cd api && ./vendor/bin/pint",
    "test:api": "cd api && php artisan test"
  },
  "devDependencies": {
    "@eslint/js": "^10.0.1",
    "@vue/eslint-config-prettier": "^10.2.0",
    "@vue/eslint-config-typescript": "^14.9.0",
    "concurrently": "^9.1.0",
    "eslint": "^10.6.0",
    "eslint-plugin-vue": "^10.9.2",
    "prettier": "^3.9.4"
  },
  "engines": {
    "node": "^20.19.0 || >=22.12.0"
  }
}
JSON

cp "$MOTOR_DIR/.prettierrc" "$DEST/.prettierrc"
sed "s|'playground/api/\*\*'|'api/**'|" "$MOTOR_DIR/eslint.config.js" > "$DEST/eslint.config.js"

cat > "$DEST/.gitignore" <<'GITIGNORE'
# Dependencies
node_modules/
vendor/

# Builds
dist/
api/public/build/

# Env
.env
.env.*
!.env.example
# Los .env de los frontends solo llevan VITE_* (no secretos): se versionan.
# Overrides locales en .env.local (ignorado).
!app/.env
!admin/.env

# Laravel runtime
api/storage/*.key
api/storage/framework/cache/data/*
api/storage/framework/sessions/*
api/storage/framework/views/*
api/storage/logs/*
api/bootstrap/cache/*.php
api/database/*.sqlite

# Editor / OS
.DS_Store
*.log
.vscode/
.idea/
GITIGNORE

# CI: la del monorepo con las rutas del juego; sin el Pint del core (el
# motor se lintea en su propio repo).
mkdir -p "$DEST/.github/workflows"
sed \
  -e 's|working-directory: playground/api|working-directory: api|' \
  -e "s|hashFiles('playground/api/composer.lock')|hashFiles('api/composer.lock')|" \
  -e 's|Pint (api + core)|Pint|' \
  -e '/pint --test --config/d' \
  "$MOTOR_DIR/.github/workflows/ci.yml" > "$DEST/.github/workflows/ci.yml"

# --------------------------------------------------------------------- docs --
echo "== 4/5 · Documentación: solo las guías"
mkdir -p "$DEST/documentacion"
cp "$MOTOR_DIR"/documentacion/guia-*.md "$DEST/documentacion/"

cat > "$DEST/README.md" <<README
# $NOMBRE

Proyecto de juego sobre el **EdC Motor** (edc-motor/core \`$VERSION\`,
@edc-motor/ui y @edc-motor/admin-kit por \`file:\`). Generado con
\`tools/crear-juego.sh\` a partir de la plantilla del motor: trae una API
Laravel, un panel de administración y una web pública funcionando con
entidades de ejemplo (casas, personajes…) para sustituir por las del juego.

## Estructura

\`\`\`
$MOTOR_REL          # clon/submódulo del motor al tag v$VERSION (carpeta hermana)
api/               # Laravel + edc-motor/core
admin/             # SPA de administración (@edc-motor/admin-kit)
app/               # web pública (@edc-motor/ui)
packages/shared/   # cartas y tipos compartidos entre admin, app y render PNG
\`\`\`

## Arranque

\`\`\`bash
# 0. El motor tiene que estar en $MOTOR_REL (visto desde esta carpeta):
#    git clone --branch v$VERSION --depth 1 <url-del-motor> $MOTOR_REL

# 1. Backend
cd api
composer install
npm install                 # puppeteer (render de PNG con Browsershot)
cp .env.example .env        # revisa BBDD, colas, URLs y MOTOR_CHROME_PATH
php artisan key:generate
php artisan migrate --force
php artisan motor:install   # roles y permisos base

# 2. Frontends (desde la raíz del juego)
cd ..
npm install
npm run dev                 # app :5173 · admin :5174 · api :8010 · queue

# 3. Datos demo (con los servers levantados: los previews PNG se generan
#    cargando la web pública en Chrome headless)
cd api && php artisan db:seed
\`\`\`

> Si puppeteer no descarga Chrome (o quieres usar el del sistema), fija
> \`MOTOR_CHROME_PATH\` en \`api/.env\` (hay un \`npm run chrome:install\`
> dentro de \`api/\`).

Credenciales demo del seeder: \`admin@edc.test\` / \`editor@edc.test\` /
\`user@edc.test\`, contraseña \`password\`.

## Guías

- \`documentacion/guia-como-montar-una-web.md\` — cómo montar cada pieza
  (entidades, bloques, PNG, PDF, SEO…).
- \`documentacion/guia-de-componentes.md\` — catálogo de componentes de
  @edc-motor/ui y @edc-motor/admin-kit.
- \`documentacion/guia-arrancar-un-juego-nuevo.md\` — distribución del motor
  por versión y cómo actualizar de tag.

## Gates (desde la raíz)

\`npm run lint\` · \`npm run type-check\` · \`npm run build\` ·
\`npm run lint:php\` · \`npm run test:api\`
README

# --------------------------------------------------------------------- fin --
echo "== 5/5 · Comprobaciones"
if [[ "$MOTOR_REL" = /* ]]; then MOTOR_ESPERADO="$MOTOR_REL"; else MOTOR_ESPERADO="$DEST/$MOTOR_REL"; fi
if [[ -d "$MOTOR_ESPERADO/packages/core" ]]; then
  echo "   motor encontrado en $MOTOR_REL ✓"
else
  echo "   AVISO: aún no hay motor en <juego>/$MOTOR_REL — clónalo antes de instalar:"
  echo "     git clone --branch v$VERSION --depth 1 <url-del-motor> \"$DEST/$MOTOR_REL\""
fi

echo
echo "== OK: proyecto creado en $DEST"
echo "   Siguientes pasos: los del README.md del juego."
