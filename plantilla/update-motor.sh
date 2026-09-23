#!/usr/bin/env bash
#
# Actualiza los paquetes del motor (edc-motor/core, @edc-motor/ui,
# @edc-motor/admin-kit) a la versión indicada.
#
# Uso: ./update-motor.sh 0.4.0
#
# Espera a que los registros SIRVAN la versión antes de instalar: la action
# «Publicar» del motor tarda unos minutos tras el tag y npm/Packagist algo
# más en propagarla; sin la espera, un `--finish --motor` recién releaseado
# se partía por la mitad con ETARGET (nos pasó en 0.5.51 y 0.5.52).

set -euo pipefail

VERSION="${1:-}"

if [[ -z "$VERSION" ]]; then
  echo "Uso: $0 <version>   (ej: $0 0.4.0)" >&2
  exit 1
fi

if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
  echo "Versión no válida: '$VERSION' (formato esperado: X.Y.Z, ej. 0.4.0)" >&2
  exit 1
fi

# Ejecutar siempre desde la raíz del repo (donde vive este script)
cd "$(dirname "$0")"

# Cuánto esperar como máximo a cada registro (segundos) y cada cuánto mirar.
ESPERA_MAX="${MOTOR_ESPERA_MAX:-600}"
ESPERA_PASO=15

# esperar "<qué>" <comando que sale 0 cuando la versión está disponible>
esperar() {
  local que="$1"; shift
  local t=0
  until "$@" >/dev/null 2>&1; do
    if (( t >= ESPERA_MAX )); then
      echo "✖ $que no sirve la $VERSION tras ${ESPERA_MAX}s. ¿Ha terminado en verde la action «Publicar» del motor?" >&2
      exit 1
    fi
    (( t == 0 )) && echo "   esperando a que $que sirva la $VERSION (hasta ${ESPERA_MAX}s)..."
    sleep "$ESPERA_PASO"; t=$((t + ESPERA_PASO))
  done
}

packagist_tiene() {
  # `composer show -a` consulta el registro (no el lock); --format=json lista
  # todas las versiones. Se fuerza la caché de metadatos con COMPOSER_CACHE_DIR
  # vacío para no leer una lista rancia.
  ( cd api && COMPOSER_CACHE_DIR="$(mktemp -d)" composer show -a edc-motor/core --format=json 2>/dev/null \
      | grep -q "\"$VERSION\"" )
}

npm_tiene() {
  [[ "$(npm view "$1@$VERSION" version --prefer-online 2>/dev/null)" == "$VERSION" ]]
}

echo "==> Backend: edc-motor/core ^$VERSION"
esperar "Packagist" packagist_tiene
(
  cd api
  composer require "edc-motor/core:^$VERSION" --update-with-all-dependencies
  php artisan migrate
  php artisan optimize:clear
)

echo
echo "==> Frontend: @edc-motor/ui y @edc-motor/admin-kit ^$VERSION"
esperar "npm (@edc-motor/ui)" npm_tiene "@edc-motor/ui"
esperar "npm (@edc-motor/admin-kit)" npm_tiene "@edc-motor/admin-kit"
# --prefer-online: la caché de metadatos de npm puede seguir sin la versión
# aunque el registro ya la sirva (ETARGET con la versión recién publicada).
npm install --prefer-online "@edc-motor/ui@^$VERSION" "@edc-motor/admin-kit@^$VERSION"

echo
echo "✔ Paquetes actualizados a ^$VERSION"
echo
echo "Recuerda revisar el CHANGELOG del motor por si esta versión trae"
echo "'migración del cascarón' (archivos a copiar de plantilla/)."
echo "Reinicia el dev server o lanza 'npm run build'."
