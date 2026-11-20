#!/usr/bin/env bash
# Build and (re)start CHABA from the code currently checked out.
# GitHub Actions runs this after every push to master; you can also run it
# by hand on the server as the deploy user.
set -euo pipefail
cd "$(dirname "$0")"

for f in .env api.env; do
  if [ ! -f "$f" ]; then
    echo "deploy/$f is missing. Run install.sh first." >&2
    exit 1
  fi
done

echo "==> Building images"
docker compose build

echo "==> Database and Redis"
docker compose up -d --wait postgres redis

echo "==> Migrations and reference data"
docker compose run --rm --no-deps api php artisan deploy:prepare

echo "==> Starting the new version"
docker compose up -d --remove-orphans --wait

docker image prune -f > /dev/null
echo "==> Deployed $(git -C .. rev-parse --short HEAD)"
