# GEOFlow

GEOFlow is a PHP-based GEO / SEO content production system with:

- multi-model AI configuration via OpenAI-style APIs
- centralized asset management
- task scheduling and queue processing
- draft, review, and publish workflow
- frontend article delivery with SEO metadata
- Docker Compose deployment with PostgreSQL

## Local quick start

This repository includes a ready-to-run `.env` configured for SQLite because the current machine does not have `pdo_pgsql` enabled in local PHP. That lets you run the project immediately:

```powershell
php bin/seed_demo.php
php bin/cron.php
php bin/worker.php --once
php -S localhost:8080 router.php
```

Open:

- Frontend: `http://localhost:8080`
- Admin: `http://localhost:8080/geo_admin/`
- API: `http://localhost:8080/api/v1/`

Default admin login:

- Username: `admin`
- Password: `admin888`

## Docker quick start

For the PostgreSQL runtime:

```powershell
docker compose --profile scheduler up -d --build
```

Open:

- Frontend: `http://localhost:18080`
- Admin: `http://localhost:18080/geo_admin/`

## Core workflow

1. Add an AI model in the admin panel, or use the built-in fallback generator.
2. Create reusable assets such as prompts and knowledge base entries.
3. Create a task and schedule it.
4. Run the scheduler and worker.
5. Review the generated article and publish it.

## Project structure

- `index.php`, `article.php`, `category.php`, `archive.php`: frontend pages
- `geo_admin/`: admin dashboard
- `api/v1/`: REST-style API
- `bin/cron.php`, `bin/worker.php`: scheduler and worker
- `includes/`: domain services and shared runtime
- `database/migrations/`: schema bootstrap
- `docker/`: container runtime files

