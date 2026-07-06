#!/usr/bin/env bash
# Crea el PROYECTO DE UN JUEGO nuevo a partir de la plantilla mínima del
# monorepo (plantilla/): api + admin + app + packages/shared SIN entidades
# demo, con el tooling raíz y la CI, las GUÍAS de documentación y un README
# con el arranque. El proyecto consume el motor por los REGISTROS PÚBLICOS
# (DC-33): edc-motor/core desde Packagist y @edc-motor/* desde npmjs — no
# necesita el monorepo al lado.
#
# Uso:
#   tools/crear-juego.sh <directorio-destino>
#
#   <directorio-destino>  dónde crear el juego (p. ej. ../mi-juego).
#                         El nombre del juego se toma del basename.
# Es un script de BASH (sh/dash no valen): ejecútalo directamente o con bash.
if [ -z "${BASH_VERSION:-}" ]; then
  echo "ERROR: ejecútame con bash (o directamente: tools/crear-juego.sh), no con sh." >&2
  exit 1
fi

set -euo pipefail

MOTOR_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# El script copia la plantilla DESDE el clon del monorepo: no vale copiarlo
# suelto a otro directorio.
if [[ ! -d "$MOTOR_DIR/plantilla" ]] || ! git -C "$MOTOR_DIR" rev-parse --git-dir >/dev/null 2>&1; then
  echo "ERROR: no encuentro la plantilla del motor junto al script." >&2
  echo "  Ejecútalo desde dentro del clon del monorepo:" >&2
  echo "    git clone https://github.com/bildurre/boardgame_motor.git" >&2
  echo "    boardgame_motor/tools/crear-juego.sh <directorio-destino>" >&2
  exit 1
fi

if [[ $# -lt 1 ]]; then
  sed -n '2,13p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
  exit 1
fi

DEST="$1"

if [[ -e "$DEST" && -n "$(ls -A "$DEST" 2>/dev/null)" ]]; then
  echo "ERROR: $DEST existe y no está vacío; no piso nada." >&2
  exit 1
fi

mkdir -p "$DEST"
DEST="$(cd "$DEST" && pwd)"

# Nombre del juego (para package.json/composer.json): basename saneado.
NOMBRE="$(basename "$DEST" | tr '[:upper:]' '[:lower:]' | tr -cs 'a-z0-9' '-' | sed 's/^-//; s/-$//')"

# Versión de tren del motor publicada con este monorepo.
VERSION="$(php -r 'echo json_decode(file_get_contents($argv[1]))->version;' "$MOTOR_DIR/packages/core/composer.json")"

echo "== Motor:  edc-motor v$VERSION (Packagist + npmjs)"
echo "== Juego:  $DEST (nombre: $NOMBRE)"

# ---------------------------------------------------------------- plantilla --
echo
echo "== 1/4 · Copiando la plantilla mínima"
while IFS= read -r -d '' f; do
  rel="${f#plantilla/}"
  mkdir -p "$DEST/$(dirname "$rel")"
  cp "$MOTOR_DIR/$f" "$DEST/$rel"
done < <(git -C "$MOTOR_DIR" ls-files -z -- plantilla)

# ------------------------------------------------------------------- nombres --
echo "== 2/4 · Nombre y versión del motor"

# composer.json: nombre propio y versión del motor de ESTE monorepo.
sed -i \
  -e "s|\"name\": \"juego/api\"|\"name\": \"$NOMBRE/api\"|" \
  -e "s|\"edc-motor/core\": \"[^\"]*\"|\"edc-motor/core\": \"^$VERSION\"|" \
  "$DEST/api/composer.json"

# Marca del juego (manifests PWA) y nombres npm.
sed -i "s|'Plantilla EdC Admin'|'$NOMBRE Admin'|; s|short_name: 'Plantilla Admin'|short_name: '$NOMBRE Admin'|" "$DEST/admin/vite.config.ts"
sed -i "s|'Plantilla EdC'|'$NOMBRE'|; s|short_name: 'Plantilla'|short_name: '$NOMBRE'|" "$DEST/app/vite.config.ts"
sed -i "s|\"name\": \"plantilla-app\"|\"name\": \"$NOMBRE-app\"|" "$DEST/app/package.json"
sed -i "s|\"name\": \"plantilla-admin\"|\"name\": \"$NOMBRE-admin\"|" "$DEST/admin/package.json"

# ------------------------------------------------------------- tooling raíz --
echo "== 3/4 · Tooling raíz (package.json, eslint, prettier, gitignore, CI)"

# Workspaces del juego + @edc-motor/* por versión desde npmjs (los hijos
# declaran "*" y npm lo resuelve contra estos). Mismos gates que el monorepo.
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
    "@edc-motor/ui": "^$VERSION",
    "@edc-motor/admin-kit": "^$VERSION"
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

# Las SPA compilan los paquetes fuente desde node_modules: loadPaths del SCSS
# hacia allí (en el monorepo la plantilla los lee de packages/, por eso el sed).
for spa in app admin; do
  sed -i \
    -e "s|'../../packages/ui/scss'|'../node_modules/@edc-motor/ui/scss'|" \
    -e "s|'../../packages/admin-kit/scss'|'../node_modules/@edc-motor/admin-kit/scss'|" \
    "$DEST/$spa/vite.config.ts"
done

cp "$MOTOR_DIR/.prettierrc" "$DEST/.prettierrc"
sed -e "s|'playground/api/\*\*'|'api/**'|" -e "/'plantilla\/api\/\*\*',/d" \
  "$MOTOR_DIR/eslint.config.js" > "$DEST/eslint.config.js"

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
echo "== 4/4 · Documentación (solo las guías) y README"
mkdir -p "$DEST/documentacion"
cp "$MOTOR_DIR"/documentacion/guia-*.md "$DEST/documentacion/"

cat > "$DEST/README.md" <<README
# $NOMBRE

Web del juego, montada sobre **EdC Motor** (\`edc-motor/core\` ^$VERSION de
Packagist; \`@edc-motor/ui\` y \`@edc-motor/admin-kit\` de npmjs). Generado con
\`tools/crear-juego.sh\` del motor: nace con la infraestructura funcionando
(auth, páginas, configuración, PDF, copias, usuarios) y **sin entidades**:
las de tu juego se crean siguiendo las guías.

## Estructura

\`\`\`
api/               # Laravel + edc-motor/core
admin/             # SPA de administración (@edc-motor/admin-kit)
app/               # web pública (@edc-motor/ui)
packages/shared/   # tus cartas y tipos, compartidos entre admin, app y render PNG
\`\`\`

## Arranque

\`\`\`bash
# 1. Backend
cd api
composer install
npm install                 # puppeteer (render de PNG con Browsershot)
cp .env.example .env        # revisa BBDD, colas, URLs y MOTOR_CHROME_PATH
php artisan key:generate
php artisan migrate --force
php artisan motor:install   # roles y permisos base
php artisan db:seed         # usuarios demo + home mínima + configuración

# 2. Frontends (desde la raíz del juego)
cd ..
npm install
npm run dev                 # app :5173 · admin :5174 · api :8010 · queue
\`\`\`

Credenciales demo del seeder: \`admin@edc.test\` / \`editor@edc.test\` /
\`user@edc.test\`, contraseña \`password\`.

> Si puppeteer no descarga Chrome (o quieres usar el del sistema), fija
> \`MOTOR_CHROME_PATH\` en \`api/.env\` (hay un \`npm run chrome:install\`
> dentro de \`api/\`).

## Primera entidad

Sigue \`documentacion/guia-como-montar-una-web.md\` (§7: checklist de una
entidad completa — migración, modelo, API, carta en \`packages/shared\`,
vistas de admin y app, previews/PDF y seeder). El ejemplo vivo completo es
el \`playground/\` del monorepo del motor.

## Actualizar el motor

Lee antes los \`CHANGELOG.md\` del motor.

- **Parche** (p. ej. 0.3.0 → 0.3.1) — la horquilla \`^\` lo coge sola:

  \`\`\`bash
  cd api && composer update edc-motor/core
  cd .. && npm update @edc-motor/ui @edc-motor/admin-kit
  \`\`\`

- **Minor de la serie 0** (p. ej. 0.3 → 0.4) — puede romper API: sube la
  horquilla a mano y revisa la sección de migración del changelog:

  \`\`\`bash
  cd api && composer require edc-motor/core:^0.4.0
  cd .. && npm install @edc-motor/ui@^0.4.0 @edc-motor/admin-kit@^0.4.0
  \`\`\`

  Si el cambio toca el **cascarón** (archivos generados que viven en este
  repo), el changelog lo dice; el diff exacto:
  \`git -C <monorepo> diff vX.Y.0 vX.Z.0 -- plantilla\`.

## Gates (desde la raíz)

\`npm run lint\` · \`npm run type-check\` · \`npm run build\` ·
\`npm run lint:php\` · \`npm run test:api\`
README

echo
echo "== OK: proyecto creado en $DEST"
echo "   Siguientes pasos: los del README.md del juego."
