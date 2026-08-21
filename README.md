# CHABA (شابة)

Single-vendor e-commerce platform for the Algerian market. See [docs/CHABA_PRD.md](docs/CHABA_PRD.md) for the full product requirements document — vision, personas, functional requirements, database/API design, business rules, and roadmap.

Built phase by phase per the PRD's development roadmap (§25–26): Foundation → Authentication → Catalog → Cart & Checkout → Orders & Delivery → Payments → Admin Dashboard → Testing → Deployment → Launch.

## Repo layout

```
chaba/
├── apps/
│   ├── api/     # Laravel (PHP) backend — REST API, /api/v1/*
│   └── web/     # Next.js (TypeScript) frontend — ar (default, RTL) / fr / en
├── packages/
│   └── shared/  # Cross-cutting contract (OpenAPI) + static reference data (wilayas)
├── docs/
│   └── CHABA_PRD.md
└── docker-compose.yml
```

Laravel and Next.js don't share a language, so this isn't a JS-tool-managed monorepo (no Turborepo/Nx) — `packages/shared` only holds the API contract and static reference data both apps need to stay in sync on.

## Prerequisites

- PHP 8.2+ with `pdo_pgsql` enabled, [Composer](https://getcomposer.org)
- Node.js 22+, npm
- Docker + Docker Compose (for local Postgres/Redis/MinIO/Mailhog, or the full containerized stack)

## Local setup

1. **Infrastructure** (Postgres, Redis, MinIO, Mailhog):
   ```
   docker compose up -d postgres redis minio minio-init mailhog
   ```

2. **API** (`apps/api`):
   ```
   cd apps/api
   cp .env.example .env
   composer install
   php artisan key:generate
   php artisan migrate
   php artisan db:seed
   php artisan serve
   ```
   API runs at `http://localhost:8000`, health check at `GET /api/v1/health`.

3. **Web** (`apps/web`):
   ```
   cd apps/web
   cp .env.local.example .env.local
   npm install
   npm run dev
   ```
   Web runs at `http://localhost:3000` (redirects to `/ar` by default).

Alternatively, `docker compose up` brings up the entire stack (infra + api + web) in containers.

## Useful commands

| App | Command | Purpose |
|---|---|---|
| api | `php artisan test` | PHPUnit tests |
| api | `./vendor/bin/pint` | Lint/format (Laravel Pint) |
| web | `npm run lint` | ESLint |
| web | `npm run typecheck` | TypeScript check |
| web | `npm run build` | Production build |
