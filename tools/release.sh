#!/usr/bin/env bash
#
# Release del tren del motor: sube la versión en todos los sitios, cierra los
# changelogs, commitea, taguea, pushea y publica los paquetes npm.
#
# Uso: ./tools/release.sh 0.4.4 [-y]
#   -y  no pide confirmación antes de pushear/publicar
#
# Qué toca:
#   packages/core/composer.json        "version"
#   packages/core/src/Motor.php        const VERSION (lo que responde el ping)
#   packages/ui/package.json           "version"
#   packages/admin-kit/package.json    "version" + dependencia @edc-motor/ui
#   CHANGELOG.md y packages/*/CHANGELOG.md  "[Sin publicar]" → "[X.Y.Z] — fecha"
#   (si un paquete no tiene sección pendiente, se le añade "versión de tren")
#
# Después: commit "Release X.Y.Z", tag vX.Y.Z, push main --tags y npm publish
# de ui y admin-kit. El core llega a Packagist vía la CI del split al pushear.

set -euo pipefail

VERSION="${1:-}"
ASSUME_YES="${2:-}"

if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
  echo "Uso: $0 <version> [-y]   (ej: $0 0.4.4)" >&2
  exit 1
fi

# Siempre desde la raíz del monorepo
cd "$(dirname "$0")/.."

# --- Comprobaciones previas (no tocan nada) ---------------------------------
[[ "$(git branch --show-current)" == "main" ]] || { echo "✖ Debes estar en main" >&2; exit 1; }
[[ -z "$(git status --porcelain)" ]] || { echo "✖ Working tree sucio: commitea antes de lanzar la release" >&2; exit 1; }

git fetch origin main --tags
[[ "$(git rev-parse HEAD)" == "$(git rev-parse origin/main)" ]] || {
  echo "✖ Tu main no está al día con origin/main (haz git pull)" >&2; exit 1; }
if git rev-parse "v$VERSION" >/dev/null 2>&1; then
  echo "✖ El tag v$VERSION ya existe" >&2; exit 1
fi
npm whoami >/dev/null 2>&1 || { echo "✖ Sin sesión npm (haz npm login)" >&2; exit 1; }

FECHA="$(date +%F)"

# --- Versiones ---------------------------------------------------------------
sed -i -E "s/^(\s*\"version\": \")[0-9]+\.[0-9]+\.[0-9]+(\",?)/\1$VERSION\2/" \
  packages/core/composer.json packages/ui/package.json packages/admin-kit/package.json
sed -i -E "s/(\"@edc-motor\/ui\": \")\^[0-9]+\.[0-9]+\.[0-9]+(\")/\1^$VERSION\2/" \
  packages/admin-kit/package.json
sed -i -E "s/(const VERSION = ')[0-9]+\.[0-9]+\.[0-9]+(')/\1$VERSION\2/" \
  packages/core/src/Motor.php

# Comprobar que los 5 cambios han entrado de verdad
for par in "packages/core/composer.json" "packages/ui/package.json" \
           "packages/admin-kit/package.json" "packages/core/src/Motor.php"; do
  grep -q "$VERSION" "$par" || { echo "✖ No pude subir la versión en $par" >&2; exit 1; }
done

# --- Changelogs ----------------------------------------------------------------
for f in CHANGELOG.md packages/core/CHANGELOG.md packages/ui/CHANGELOG.md packages/admin-kit/CHANGELOG.md; do
  if grep -q '^## \[Sin publicar\]' "$f"; then
    sed -i "s/^## \[Sin publicar\]/## [$VERSION] — $FECHA/" "$f"
  else
    # Sin sección pendiente: entrada de "versión de tren" antes de la primera.
    awk -v v="$VERSION" -v d="$FECHA" '
      /^## \[/ && !done { print "## [" v "] — " d "\n\n- Sin cambios propios: versión de tren.\n"; done=1 }
      { print }
    ' "$f" > "$f.tmp" && mv "$f.tmp" "$f"
    echo "ℹ $f no tenía [Sin publicar]: añadida entrada de versión de tren"
  fi
done

# --- Resumen y confirmación -----------------------------------------------------
echo
git --no-pager diff --stat
echo
if [[ "$ASSUME_YES" != "-y" ]]; then
  read -r -p "¿Commitear, taguear v$VERSION, pushear y publicar en npm? [s/N] " ok
  [[ "$ok" == "s" || "$ok" == "S" ]] || { git checkout -- .; echo "Cancelado (cambios revertidos)"; exit 1; }
fi

# --- Commit + tag + push ---------------------------------------------------------
git add -A
git commit -m "Release $VERSION"
git tag "v$VERSION"
git push origin main --tags

# --- Publicación npm (el core lo publica la CI del split en Packagist) -----------
for pkg in packages/ui packages/admin-kit; do
  if ! (cd "$pkg" && npm publish); then
    echo "✖ npm publish falló en $pkg. El commit y el tag YA están pusheados:" >&2
    echo "  arregla la causa y relanza a mano: cd $pkg && npm publish" >&2
    exit 1
  fi
done

echo
echo "✔ Release $VERSION completa: tag v$VERSION pusheado y npm publicado."
echo "  Packagist (edc-motor/core) se actualiza solo vía la CI del split:"
echo "  comprueba que la action ha pasado. En cada juego: ./update-motor.sh $VERSION"
