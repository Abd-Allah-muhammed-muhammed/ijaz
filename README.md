# Ijaz

Ijaz is a multi-actor marketplace platform built with Laravel and Inertia React.
It supports users, providers, and admins with features for orders, offers, chat,
wallets, payments, guarantee requests, advisements, and support tickets.

## Tech Stack

- PHP 8.2+
- Laravel 13
- Inertia.js 3 + React 19
- Tailwind CSS 4
- Sanctum authentication
- Reverb broadcasting

## Quick Start

1. Install PHP dependencies:
   - `composer install`
2. Install Node dependencies:
   - `npm install`
3. Configure environment:
   - `cp .env.example .env`
   - Update database and service credentials in `.env`
4. Generate app key:
   - `php artisan key:generate`
5. Run migrations and seeders:
   - `php artisan migrate --seed`
6. Start frontend build/dev process:
   - `npm run dev`

Note: In this environment, Laravel Herd serves the app; no manual `php artisan serve` is required.

## Documentation Map

Start with:

- [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md)

Detailed references:

- [docs/API_INVENTORY.md](docs/API_INVENTORY.md)
- [docs/MODELS_REFERENCE.md](docs/MODELS_REFERENCE.md)
- [docs/ENUMS_REFERENCE.md](docs/ENUMS_REFERENCE.md)
- [docs/REFACTOR_NOTES.md](docs/REFACTOR_NOTES.md)

## Common Commands

- Run tests: `php artisan test --compact`
- Format PHP: `vendor/bin/pint --dirty --format agent`
- List routes: `php artisan route:list --except-vendor`
- Generate Wayfinder routes/actions: `php artisan wayfinder:generate`

## Notes

- API responses should use the `HasApiResponse` helpers and Resources.
- Controllers should stay thin and delegate business logic to Services/Actions.
- Status values should use enums from `app/Enums`.

## Server Setup & Deployment

### Requirements
- PHP 8.2+
- MySQL
- Supervisor
- Laravel Queue Worker
- Laravel Reverb (WebSocket)

---

### First-Time Deployment

#### 1. Clone & Install
```bash
git clone {repo} /home/ijaz/project
cd /home/ijaz/project
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

#### 2. Environment (.env)
```env
# Important: use 'localhost' not '127.0.0.1' for DB_HOST on cPanel servers
# Using 127.0.0.1 causes MySQL socket connection errors in Supervisor workers
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ijaz_main
DB_USERNAME=ijaz_main
DB_PASSWORD=
```

#### 3. Database
```bash
php artisan migrate --force
php artisan db:seed --force
php artisan db:seed --class=Modules\\Guarantor\\Database\\Seeders\\GuarantorPermissionSeeder --force
```

#### 4. Cache
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 5. Frontend Assets
Build locally (no npm on server):
```bash
# On local machine:
npm run build

# Upload public/build/ to server
rsync -avz public/build/ user@server:/home/ijaz/project/public/build/
# or via FTP/cPanel File Manager
```

---

### Supervisor Setup

Create a single config file for all workers:

```bash
nano /etc/supervisor/conf.d/ijaz.conf
```

Paste this content:

```ini
[program:ijaz-default-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/ijaz/project/artisan queue:work --sleep=3 --tries=3 --queue=default --timeout=0
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=ijaz
numprocs=4
redirect_stderr=true
stdout_logfile=/home/ijaz/project/storage/logs/default-worker.log
stopwaitsecs=3600

[program:ijaz-guarantor-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/ijaz/project/artisan queue:work --sleep=3 --tries=3 --queue=guarantor --timeout=0
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=ijaz
numprocs=2
redirect_stderr=true
stdout_logfile=/home/ijaz/project/storage/logs/guarantor-worker.log
stopwaitsecs=3600

[program:ijaz-online-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/ijaz/project/artisan app:online-listen
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=ijaz
numprocs=1
redirect_stderr=true
stdout_logfile=/home/ijaz/project/storage/logs/online-worker.log
stopwaitsecs=3600

[program:ijaz-reverb-socket]
process_name=%(program_name)s_%(process_num)02d
command=php /home/ijaz/project/artisan reverb:start
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=ijaz
numprocs=1
redirect_stderr=true
stdout_logfile=/home/ijaz/project/storage/logs/reverb.log
stopwaitsecs=3600
```

Apply config:
```bash
supervisorctl reread
supervisorctl update
supervisorctl start all
supervisorctl status
```

Expected output:
```
ijaz-default-worker:ijaz-default-worker_00    RUNNING
ijaz-default-worker:ijaz-default-worker_01    RUNNING
ijaz-default-worker:ijaz-default-worker_02    RUNNING
ijaz-default-worker:ijaz-default-worker_03    RUNNING
ijaz-guarantor-worker:ijaz-guarantor-worker_00 RUNNING
ijaz-guarantor-worker:ijaz-guarantor-worker_01 RUNNING
ijaz-online-worker:ijaz-online-worker_00      RUNNING
ijaz-reverb-socket:ijaz-reverb-socket_00      RUNNING
```

---

### Cron Job (Laravel Scheduler)

```bash
crontab -u ijaz -e
```

Add this line:
```
* * * * * cd /home/ijaz/project && php artisan schedule:run >> /dev/null 2>&1
```

Verify:
```bash
crontab -l -u ijaz
```

The scheduler runs:
- `guarantor:check-overdue` — daily at midnight (checks overdue installments)

---

### Regular Deployment (After First Setup)

```bash
cd /home/ijaz/project

# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Run migrations
php artisan migrate --force

# 4. Clear & rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart workers
supervisorctl restart ijaz-default-worker:*
supervisorctl restart ijaz-guarantor-worker:*

# 6. Upload public/build/ from local machine
```

---

### Adding a New Module (Queue)

If a new module uses a dedicated queue, add a new worker block to `/etc/supervisor/conf.d/ijaz.conf`:

```ini
[program:ijaz-{module}-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/ijaz/project/artisan queue:work --sleep=3 --tries=3 --queue={module} --timeout=0
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=ijaz
numprocs=2
redirect_stderr=true
stdout_logfile=/home/ijaz/project/storage/logs/{module}-worker.log
stopwaitsecs=3600
```

Then:
```bash
supervisorctl reread
supervisorctl update
supervisorctl status
```

---

### Troubleshooting

#### Worker FATAL / BACKOFF error
```bash
# Check logs
tail -100 /home/ijaz/project/storage/logs/default-worker.log
tail -100 /home/ijaz/project/storage/logs/guarantor-worker.log
tail -100 /home/ijaz/project/storage/logs/online-worker.log
```

#### MySQL connection refused in workers
Change `DB_HOST=127.0.0.1` to `DB_HOST=localhost` in `.env`
(cPanel servers use Unix socket, not TCP on 127.0.0.1)

#### Restart all workers
```bash
supervisorctl restart all
```

#### Check schedule is running
```bash
php artisan schedule:list
```
