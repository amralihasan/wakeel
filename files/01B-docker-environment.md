# PROMPT 01 B — Dockerized local environment

> Re-paste `00-MASTER-CONTEXT.md` before running this prompt.
> Run this **immediately after prompt 01** (the scaffold) and **before prompt 02**.
> After this prompt, every later prompt assumes the app runs inside Docker and that
> `php artisan` / `composer` / `npm` commands are executed inside the app container.

> **Update the master context:** add Docker to §2 as the locked local/dev environment
> (custom `docker-compose`, not Sail), and note that all artisan/composer/npm commands
> in later prompts run inside the `app` container (e.g. `docker compose exec app php
> artisan migrate`).

---

**Goal:** A complete, reproducible containerized environment so MySQL, Redis, the
Laravel app, the queue worker (Horizon), the websocket server (Reverb), and an
S3-compatible store all come up with one command — and the local setup closely mirrors
production (the prompt-17 deploy target).

**Assumption (stated explicitly):** we use a **custom `docker-compose`** for
production parity rather than Laravel Sail. If you'd rather use Sail, this prompt can be
swapped — but the rest of the series only needs "MySQL + Redis + app reachable", which
both satisfy.

---

**Do this:**

1. **`docker-compose.yml`** at the project root with these services:

   - **`app`** — PHP 8.3-FPM (build from a local `docker/php/Dockerfile`). Installs the
     PHP extensions Laravel + this stack need: `pdo_mysql`, `redis` (via PECL),
     `bcmath`, `gd`, `zip`, `intl`, `pcntl` (Horizon/Reverb need it), `opcache`.
     Installs Composer. Mounts the project source as a volume. Exposes nothing directly
     (nginx fronts it).
   - **`nginx`** — serves the app, proxies PHP to `app:9000`. Config in
     `docker/nginx/default.conf`. Publish on host port `8080` → app at
     `http://localhost:8080`.
   - **`mysql`** — MySQL 8.x. Env: database `wakeel`, user/password from compose env.
     Named volume `mysql-data` for persistence. Publish `3306` (host) for local GUI
     access. Add a healthcheck (`mysqladmin ping`).
   - **`redis`** — Redis 7.x. Named volume `redis-data`. Publish `6379`. Healthcheck
     (`redis-cli ping`).
   - **`horizon`** — same build as `app`, command runs `php artisan horizon`. Depends on
     `mysql` + `redis` (healthy). Shares the source volume. This is the queue worker.
   - **`reverb`** — same build as `app`, command runs `php artisan reverb:start
     --host=0.0.0.0`. Publish the Reverb port (e.g. `8081`). Depends on `redis`.
   - **`scheduler`** — same build as `app`, runs the Laravel scheduler loop
     (`php artisan schedule:work`) so prompt 14's follow-up jobs fire in dev too.
   - **`minio`** — S3-compatible object storage for unit media (so prompt 11's uploads
     work locally without real AWS). Publish console + API ports; named volume
     `minio-data`. Add a one-shot `minio-init` (mc) service that creates the
     `wakeel-media` bucket and sets it readable for the URLs the WhatsApp send jobs use.
   - *(optional, dev convenience)* **`mailpit`** — catch outbound mail (billing
     receipts, etc.) on a local web UI.

2. **Networking & dependencies:** put everything on one user-defined bridge network.
   Use `depends_on` with `condition: service_healthy` so `app`/`horizon`/`reverb` wait
   for MySQL and Redis to be ready.

3. **`docker/php/Dockerfile`** — base `php:8.3-fpm`, install system deps + the PHP
   extensions above + Composer, set a non-root user matching the host UID to avoid
   permission issues on the mounted volume, set `WORKDIR /var/www/html`.

4. **Env alignment:** update `.env.example` (and document in `README`) so the in-cluster
   service names are the hosts:
   ```
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=wakeel
   DB_USERNAME=wakeel
   DB_PASSWORD=secret
   REDIS_HOST=redis
   REDIS_PORT=6379
   CACHE_STORE=redis
   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis
   # S3-compatible (MinIO) for unit media
   AWS_ACCESS_KEY_ID=wakeel
   AWS_SECRET_ACCESS_KEY=wakeel-secret
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=wakeel-media
   AWS_ENDPOINT=http://minio:9000
   AWS_USE_PATH_STYLE_ENDPOINT=true
   FILESYSTEM_DISK=s3
   # Reverb
   REVERB_HOST=reverb
   REVERB_PORT=8081
   ```
   Note for the developer: the **app↔services** hosts are the compose service names
   (`mysql`, `redis`, `minio`, `reverb`); the **browser↔app** URL is
   `http://localhost:8080` and MinIO/GUI access uses the published host ports.

5. **`.dockerignore`** — exclude `vendor`, `node_modules`, `.git`, `.env`, build
   artifacts.

6. **Convenience tooling:**
   - A `Makefile` (or `composer` scripts) with: `up`, `down`, `build`, `restart`,
     `migrate`, `fresh` (migrate:fresh --seed), `tinker`, `test`, `horizon-logs`,
     `bash` (shell into `app`). Each wraps the right `docker compose exec app ...`.
   - A short **`README` "Getting started"** section:
     ```
     cp .env.example .env
     docker compose up -d --build
     docker compose exec app composer install
     docker compose exec app php artisan key:generate
     docker compose exec app php artisan migrate
     docker compose exec app npm install && docker compose exec app npm run build
     # app: http://localhost:8080  | MinIO console: http://localhost:9001
     ```

7. **Healthcheck the setup:** ensure `docker compose up -d --build` brings all services
   to healthy, the app responds at `http://localhost:8080` (the prompt-01 smoke route
   → 200), Horizon shows the worker running, MinIO has the `wakeel-media` bucket, and
   `docker compose exec app vendor/bin/pest` runs green against the containerized
   MySQL/Redis.

---

**Done when:**
- `docker compose up -d --build` starts every service healthy.
- `http://localhost:8080` returns the prompt-01 smoke response.
- `docker compose exec app php artisan migrate` succeeds against the `mysql` container.
- Horizon (queue), Reverb (websockets), and the scheduler are all running as their own
  containers.
- Unit-media uploads (later, prompt 11) will land in MinIO with working public URLs.
- The Pest suite passes inside the container.

---

**Note for later prompts:** wherever a prompt says "run `php artisan ...`" or
"`composer ...`" or "`npm ...`", run it as `docker compose exec app <command>`. The
production `DEPLOY.md` in prompt 17 should mirror these services (MySQL, Redis, app,
Horizon worker, Reverb, scheduler cron, real S3 in place of MinIO).
