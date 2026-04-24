CREATE TABLE IF NOT EXISTS migrations (
    id BIGSERIAL PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    username VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS settings (
    id BIGSERIAL PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS ai_models (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    provider_name VARCHAR(120) NOT NULL,
    api_url TEXT NOT NULL,
    model_id VARCHAR(200) NOT NULL,
    api_key TEXT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS assets (
    id BIGSERIAL PRIMARY KEY,
    asset_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    meta_json JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS tasks (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    model_id BIGINT NULL,
    asset_ids_json JSONB NOT NULL DEFAULT '[]'::jsonb,
    title_seed VARCHAR(255) NOT NULL,
    primary_keyword VARCHAR(255) NOT NULL,
    language_code VARCHAR(20) NOT NULL,
    audience VARCHAR(255) NOT NULL,
    category_name VARCHAR(120) NOT NULL,
    prompt_template TEXT NOT NULL,
    publish_mode VARCHAR(50) NOT NULL,
    schedule_at TIMESTAMP NOT NULL,
    status VARCHAR(50) NOT NULL,
    article_id BIGINT NULL,
    last_error TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS jobs (
    id BIGSERIAL PRIMARY KEY,
    task_id BIGINT NOT NULL,
    job_type VARCHAR(50) NOT NULL,
    payload_json JSONB NOT NULL DEFAULT '{}'::jsonb,
    status VARCHAR(50) NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    available_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS articles (
    id BIGSERIAL PRIMARY KEY,
    task_id BIGINT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    category_name VARCHAR(120) NOT NULL,
    category_slug VARCHAR(120) NOT NULL,
    summary TEXT NOT NULL,
    body_html TEXT NOT NULL,
    status VARCHAR(50) NOT NULL,
    seo_title VARCHAR(255) NOT NULL,
    seo_description TEXT NOT NULL,
    open_graph_title VARCHAR(255) NOT NULL,
    open_graph_description TEXT NOT NULL,
    structured_data_json JSONB NOT NULL DEFAULT '{}'::jsonb,
    source_model VARCHAR(150) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    published_at TIMESTAMP NULL
);

CREATE INDEX IF NOT EXISTS idx_tasks_status_schedule ON tasks(status, schedule_at);
CREATE INDEX IF NOT EXISTS idx_jobs_status_available ON jobs(status, available_at);
CREATE INDEX IF NOT EXISTS idx_articles_status_published ON articles(status, published_at);

INSERT INTO migrations (migration_name) VALUES ('001_init') ON CONFLICT (migration_name) DO NOTHING;
