#!/usr/bin/env bash
# Prueba de consumo del motor desde un proyecto EXTERNO (Fase 7):
# monta en un directorio temporal un consumidor Composer (edc-motor/core por
# versión, repositorio path) y un consumidor Vite (@edc-motor/ui y @edc-motor/admin-kit
# por file:), y comprueba que instalan y compilan. No toca el monorepo.
#
# Uso: tools/consumo-externo/probar-consumo.sh [directorio-de-trabajo]
set -euo pipefail

MOTOR_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
WORK="${1:-$(mktemp -d)}"
VERSION="$(php -r 'echo json_decode(file_get_contents($argv[1]))->version;' "$MOTOR_DIR/packages/core/composer.json")"

echo "== Motor: $MOTOR_DIR (edc-motor/core $VERSION)"
echo "== Consumidores en: $WORK"

# ---------------------------------------------------------------- Composer --
echo
echo "== 1/2 · Composer: instalar edc-motor/core $VERSION desde un proyecto externo"
mkdir -p "$WORK/consumidor-api"
cat > "$WORK/consumidor-api/composer.json" <<JSON
{
    "name": "juego/consumidor-api",
    "type": "project",
    "require": {
        "edc-motor/core": "$VERSION"
    },
    "repositories": {
        "edc-core": { "type": "path", "url": "$MOTOR_DIR/packages/core" }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
JSON

(cd "$WORK/consumidor-api" && composer install --no-interaction --prefer-dist --no-progress --quiet)

php -r '
require $argv[1] . "/consumidor-api/vendor/autoload.php";
$clases = [
    "Edc\\Core\\MotorServiceProvider",
    "Edc\\Core\\Auth\\MotorAuth",
    "Edc\\Core\\Pdf\\PdfService",
    "Edc\\Core\\Content\\Fields\\Field",
];
foreach ($clases as $c) {
    if (!class_exists($c)) { fwrite(STDERR, "FALTA $c\n"); exit(1); }
}
echo "   edc-motor/core autocarga OK (" . count($clases) . " clases)\n";
' "$WORK"

INSTALADA="$(cd "$WORK/consumidor-api" && composer show edc-motor/core --format=json | php -r 'echo json_decode(stream_get_contents(STDIN))->versions[0];')"
echo "   versión instalada: $INSTALADA"

# --------------------------------------------------------------------- npm --
echo
echo "== 2/2 · Vite: compilar una app externa con @edc-motor/ui y @edc-motor/admin-kit"
APP="$WORK/consumidor-app"
mkdir -p "$APP/src"

cat > "$APP/package.json" <<JSON
{
  "name": "juego-consumidor-app",
  "private": true,
  "type": "module",
  "scripts": { "build": "vite build" },
  "dependencies": {
    "@edc-motor/ui": "file:$MOTOR_DIR/packages/ui",
    "@edc-motor/admin-kit": "file:$MOTOR_DIR/packages/admin-kit",
    "@lucide/vue": "^1.0.0",
    "vue": "^3.5.38",
    "vue-router": "^5.0.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^6.0.7",
    "sass-embedded": "^1.83.0",
    "vite": "^8.1.0"
  }
}
JSON

cat > "$APP/vite.config.js" <<'JS'
import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: '@use "tokens" as *;\n',
        loadPaths: [
          fileURLToPath(new URL('./node_modules/@edc-motor/ui/scss', import.meta.url)),
        ],
      },
    },
  },
})
JS

cat > "$APP/index.html" <<'HTML'
<!doctype html>
<html><body><div id="app"></div><script type="module" src="/src/main.js"></script></body></html>
HTML

cat > "$APP/src/main.js" <<'JS'
import { createApp, h } from 'vue'
import { BaseButton, useHead } from '@edc-motor/ui'
import { EmptyState } from '@edc-motor/admin-kit'
import './estilos.scss'

// Consumo real: componentes de los dos paquetes + un composable.
useHead({ title: 'Consumidor externo' })
createApp({
  render: () => [h(BaseButton, () => 'Hola'), h(EmptyState, { title: 'Vacío' })],
}).mount('#app')
JS

cat > "$APP/src/estilos.scss" <<'SCSS'
@use "components/base-button";
body { background: $bg; color: $text-1; }
SCSS

(cd "$APP" && npm install --no-audit --no-fund --loglevel=error && npm run build)

echo
echo "== OK: consumo externo verificado (composer + vite) en $WORK"
