#!/bin/sh
# Postgres init scripts only run once, on first container startup with an
# empty data volume. Creates the separate test DB (phpunit.xml, CI workflow)
# alongside the main dev DB (POSTGRES_DB=chaba) so RefreshDatabase in tests
# never touches real dev data.
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE DATABASE chaba_test;
EOSQL
