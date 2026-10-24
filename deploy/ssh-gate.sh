#!/usr/bin/env bash
# Único comando que puede ejecutar la llave SSH de GitHub Actions (comando forzado en
# ~/.ssh/authorized_keys). Así, si la llave se filtra, solo sirve para desplegar.
#   ssh servidor backend    -> despliega el backend (deploy.sh)
#   ssh servidor frontend   -> publica el frontend compilado que llega por la entrada estándar (.tar.gz)
set -euo pipefail
DIR=/var/www/maestros-backend/deploy

case "${SSH_ORIGINAL_COMMAND:-}" in
  backend) exec bash "$DIR/deploy.sh" ;;
  frontend) exec bash "$DIR/publish-frontend.sh" ;;
  *) echo "Comando no permitido: '${SSH_ORIGINAL_COMMAND:-}'" >&2; exit 1 ;;
esac
