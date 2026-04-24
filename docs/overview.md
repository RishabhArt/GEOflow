# GEOFlow Overview

## Runtime layers

- Web/Admin: frontend publishing and internal operations
- API/CLI: `/api/v1/`, `bin/cron.php`, `bin/worker.php`
- Domain services: AI, tasks, queue, articles, assets, settings
- Persistence: PostgreSQL in Docker, SQLite fallback for local development

## Default pipeline

1. Admin user configures site settings, models, and assets.
2. Task records hold prompt context, keyword target, language, and schedule.
3. Scheduler turns due tasks into queued jobs.
4. Worker consumes jobs and generates a draft or published article.
5. Frontend pages expose article content with metadata and JSON-LD.

## Security controls

- password hashing with `password_hash()`
- CSRF validation for POST forms
- escaped frontend output for untrusted strings
- PDO prepared statements throughout services
- baseline security headers in web requests

