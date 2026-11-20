#!/usr/bin/env bash
# Creates (or resets the password of) the store's admin account.
# The phone and password are only passed to this one command, never saved.
set -euo pipefail
cd "$(dirname "$0")"

read -rp "Admin phone (e.g. 0555123456): " PHONE
read -rp "Admin email (optional, press Enter to skip): " EMAIL
read -rsp "Admin password: " PASSWORD
echo

docker compose run --rm --no-deps \
  -e ADMIN_SEED_PHONE="$PHONE" \
  -e ADMIN_SEED_EMAIL="$EMAIL" \
  -e ADMIN_SEED_PASSWORD="$PASSWORD" \
  api php artisan db:seed --class='Database\Seeders\AdminUserSeeder' --force
