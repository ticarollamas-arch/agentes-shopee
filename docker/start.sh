#!/bin/sh
# Importa o schema (idempotente: CREATE TABLE IF NOT EXISTS) e sobe o servidor PHP.
php docker/import-schema.php || echo "[start] schema nao importado (verifique DB_*)"
exec php -S 0.0.0.0:${PORT:-8080} -t public public/index.php
