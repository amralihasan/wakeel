# Wakeel — Deployment Guide

## Server Requirements

- PHP 8.4+
- MySQL 8.0+ or MariaDB 10.6+
- Redis 7+ (for queues, cache, sessions, Horizon)
- Composer 2.x
- Node.js 20+ with npm (for frontend assets)
- Supervisor (for queue workers)

## Environment Variables

```bash
# App
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=wakeel
DB_USERNAME=
DB_PASSWORD=

# Redis (Horizon, Cache, Sessions, Broadcasting)
REDIS_HOST=
REDIS_PASSWORD=
REDIS_PORT=6379
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
BROADCAST_DRIVER=redis

# AI Provider (Prism/Anthropic)
PRISM_DEFAULT_PROVIDER=anthropic
ANTHROPIC_API_KEY=

# WhatsApp via 360dialog
DIALOG360_API_KEY=
DIALOG360_BASE_URL=https://waba-v2.360dialog.io
WHATSAPP_WEBHOOK_VERIFY_TOKEN=
WHATSAPP_APP_SECRET=

# Stripe (Cashier billing)
STRIPE_KEY=
STRIPE_SECRET=
CASHIER_CURRENCY=usd
CASHIER_CURRENCY_LOCALE=en

# S3 File Storage
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
FILESYSTEM_DISK=s3

# Reverb (WebSocket broadcasting)
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=
REVERB_PORT=443
REVERB_SCHEME=https
```

## S3 & File Storage

Set `FILESYSTEM_DISK=s3` in production. Run `php artisan storage:link` after deploy if using the `public` disk.

## Reverb (WebSockets)

Reverb requires a Supervisor process. Configuration is in `config/reverb.php`.

```ini
[program:reverb]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/artisan reverb:start --host=0.0.0.0 --port=8080
user=forge
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/reverb.log
```

## Cron Schedule

Add this entry to the server crontab:

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler handles:
- `billing:rollover` — daily billing cycle rollover
- `horizon:snapshot` — Horizon metrics

## Queue Processing (Horizon)

Use Supervisor to keep Horizon running:

```ini
[program:horizon]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/artisan horizon
user=forge
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/horizon.log
```

Queue priorities (in order): `inbound` → `outbound` → `default`

## Database Migrations

```bash
php artisan migrate --force
```

## Frontend Assets

```bash
npm ci --production && npm run build
```

## Go-Live Checklist

1. [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
2. [ ] Generate `APP_KEY`: `php artisan key:generate`
3. [ ] Configure database credentials
4. [ ] Configure Redis connection
5. [ ] Run `php artisan migrate --force`
6. [ ] Configure S3 credentials and set `FILESYSTEM_DISK=s3`
7. [ ] Set Stripe keys for billing
8. [ ] Set 360dialog API key and verify token
9. [ ] Configure Reverb for real-time broadcasting
10. [ ] Build assets: `npm ci --production && npm run build`
11. [ ] Set up Supervisor for Horizon
12. [ ] Set up Supervisor for Reverb (if using WebSockets)
13. [ ] Add cron entry for `php artisan schedule:run`
14. [ ] Verify `/health` endpoint returns `healthy`
15. [ ] Seed super-admin account: `php artisan tinker --execute 'User::create([...])'`
