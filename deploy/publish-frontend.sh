#!/usr/bin/env bash
# Publica el frontend compilado (dist/ en .tar.gz por la entrada estándar). Lo llama ssh-gate.sh.
set -euo pipefail
TARGET=/var/www/maestros-frontend
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

tar -xz -C "$TMP"
# Una compilación sin index.html dejaría la aplicación en blanco: mejor no publicar nada.
test -f "$TMP/index.html" || { echo "El paquete no trae index.html" >&2; exit 1; }

# Primero los archivos nuevos y al final index.html, para que nunca apunte a assets que aún no están.
rsync -a --exclude index.html "$TMP/" "$TARGET/"
cp "$TMP/index.html" "$TARGET/index.html.new" && mv "$TARGET/index.html.new" "$TARGET/index.html"
rsync -a --delete "$TMP/" "$TARGET/"
echo "Frontend publicado ($(find "$TARGET" -type f | wc -l) archivos)"
