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
6. Create the initial root admin (interactive — password is never stored in code):
   - `php artisan admin:create`
   - Confirm **yes** when asked "Is this a root account?"
7. Start frontend build/dev process:
   - `npm run dev`

### Creating additional admins

```bash
# After RolePermissionSeeder has run (included in db:seed):
php artisan admin:create
```

Answer the prompts for name, email, phone, and password (masked). Choose **no** for
root, then select an existing admin-guard role (`super-admin`, `operations`, `finance`,
`support`, `content-manager`, `viewer-monitor`, `developer`).

If no roles exist yet, the command fails with guidance to run:

```bash
php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder
```

Note: In this environment, Laravel Herd serves the app; no manual `php artisan serve` is required.

## Documentation Map

Start with:

- [docs/PROJECT_CONTEXT.md](docs/PROJECT_CONTEXT.md)

Detailed references:

- [docs/API_INVENTORY.md](docs/API_INVENTORY.md)
- [docs/MODELS_REFERENCE.md](docs/MODELS_REFERENCE.md)
- [docs/ENUMS_REFERENCE.md](docs/ENUMS_REFERENCE.md)

Architecture rules live in `.cursor/rules/layered-architecture.mdc` (Claude Skill:
`.claude/skills/layered-architecture/`).

## Common Commands

- Run tests (fast parallel default): `composer test`
- Run the serial race-condition test only: `composer test:serial`
- Full/CI-equivalent coverage (parallel + serial): `composer test:all`
- Format PHP: `vendor/bin/pint --dirty --format agent`
- List routes: `php artisan route:list --except-vendor`
- Generate Wayfinder routes/actions: `php artisan wayfinder:generate`
- Create admin accounts: `php artisan admin:create`

### Test suite modes

| Command | What it runs | When to use |
|---|---|---|
| `composer test` | Pest Parallel (~8 processes), **excludes** the `serial` group | Everyday development — the fast path |
| `composer test:serial` | Only tests tagged `->group('serial')` | After wallet/concurrency changes, or when you need the race proof |
| `composer test:all` | `composer test` then `composer test:serial` | Pre-push / CI-equivalent full coverage |

The sole `serial` test today is `Modules/Wallet/tests/Feature/WithdrawConcurrentBalanceTest.php`.
It spawns its own multi-process `Process::pool` against a shared file SQLite DB to prove
concurrent withdrawals cannot overdraw available balance. That nested process pool is
unsafe to schedule alongside other ParaTest workers, so it is quarantined from the
parallel pool and run alone via `composer test:serial`.

### Mobile auth — multi-device sessions & push tokens

| Endpoint | Behavior |
|---|---|
| OTP verify (`player_id` optional) | Creates a **new** Sanctum token **without** revoking other devices; registers/upserts the FCM token on `device_tokens` |
| `POST /api/v1/user/auth/logout` | Revokes **only** the current Sanctum token and clears the FCM `device_tokens` row linked to that session (no request body) |
| `POST /api/v1/user/auth/logout-all` | Revokes **all** Sanctum tokens and clears **all** `device_tokens` for the user |

Push notifications fan out to every registered `device_tokens` row. Mobile still sends `player_id` on verify/logout (legacy field name) — no mobile schema change.

### Production deploy — `device_tokens` (3 deliberate steps)

Do **not** treat create + backfill + drop as one automatic migrate. Sequence:

1. **Deploy + migrate** — creates `device_tokens` only (`2026_08_06_064514_create_device_tokens_table`).
2. **Backfill** — `php artisan device-tokens:backfill-from-player-id --dry-run` first, review counts, then run without `--dry-run`. Idempotent; safe to re-run.
3. **Verify** — compare non-null `users.player_id` count vs matching `device_tokens` rows (command report + spot-check).
4. **Drop column** — only then run `php artisan migrate` again (or `--path=database/migrations/2026_08_06_064515_drop_player_id_from_users_table.php`). That migration **aborts** if any unmigrated `player_id` remains.

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

# Firebase Cloud Messaging (config/services.php → services.firebase)
# Place the Google service-account JSON at:
#   storage/app/firebase/ijaz.json
# (gitignored; not web-accessible). Or set an absolute path:
FIREBASE_AUTH_FILE_PATH=
# Default when empty: {project}/storage/app/firebase/ijaz.json
```

Push notifications use Laravel’s Notification system (`firebase` channel →
`App\Services\Firebase\FirebaseService`). The sender is **stateless** (one
`OutgoingFirebaseMessage` DTO per send) and talks to FCM/OAuth via the HTTP
client. See `docs/PROJECT_CONTEXT.md` for the APNs/Android decision point.

#### 3. Database
```bash
php artisan migrate --force
php artisan db:seed --force
php artisan db:seed --class=Modules\\Guarantor\\Database\\Seeders\\GuarantorPermissionSeeder --force
php artisan admin:create   # interactive: create root admin (no hardcoded password in seeders)
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
; Listen to default + opportunities (hourly ExpireOpportunityJob — low volume,
; no dedicated worker needed). Queue order = priority: default first.
command=php /home/ijaz/project/artisan queue:work --sleep=3 --tries=3 --queue=default,opportunities --timeout=0
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

#### Production hotfix — `opportunities` queue was never consumed

`ExpireOpportunityJob` is dispatched to the `opportunities` queue (hourly via
`opportunities:expire`), but older Supervisor configs only listened to `default`
and `guarantor`. Apply this on the **live server** (edit the real file, not only
this README):

```bash
# 1. Edit Supervisor program command
sudo nano /etc/supervisor/conf.d/ijaz.conf
# Change the ijaz-default-worker command line from:
#   --queue=default
# to:
#   --queue=default,opportunities

# 2. Reload Supervisor and restart only the default workers
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart ijaz-default-worker:*

# 3. Confirm the new command is live
sudo supervisorctl status ijaz-default-worker:*
ps aux | grep 'queue:work' | grep opportunities

# 4. Drain any jobs that piled up while nobody was listening
cd /home/ijaz/project
php artisan tinker --execute="echo DB::table('jobs')->where('queue','opportunities')->count().' pending'; echo PHP_EOL; echo DB::table('failed_jobs')->where('queue','opportunities')->count().' failed'; echo PHP_EOL;"
# Pending jobs will be picked up automatically by the restarted workers.
# Retry failed ones if needed:
# php artisan queue:retry all
# (or retry specific UUIDs from queue:failed)
```

---

### Migrating to Horizon on Production

Horizon replaces the dedicated `queue:work` Supervisor programs with a single
`php artisan horizon` process that auto-balances workers across Redis queues.
**Do not cut over until Redis is installed and verified** (same Redis instance
already used / planned for cache). This section is copy-paste-ready for the
live server; do **not** apply it until you are deliberately migrating.

Horizon is installed in the app and **opt-in** via `.env`. Until you change
`QUEUE_CONNECTION`, production (and local) keep using the `database` queue
driver and the existing `ijaz-default-worker` / `ijaz-guarantor-worker` programs.

#### 1. Prerequisites

- Redis installed and reachable (`REDIS_HOST` / `REDIS_PORT` / `REDIS_PASSWORD`)
- PhpRedis (`ext-redis`) or Predis configured via `REDIS_CLIENT`
- Logical DB layout (same Redis instance, isolated keyspaces):
  - DB `0` — general / Horizon meta (`config/horizon.php` → `'use' => 'default'`)
  - DB `1` — cache (`REDIS_CACHE_DB=1`)
  - DB `2` — queue jobs (`REDIS_QUEUE_DB=2`)
- Code with `laravel/horizon` deployed (`composer install` on Linux needs
  `ext-pcntl` + `ext-posix`; Windows local installs may need
  `--ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix`)
- Confirm `config/queue.php` redis connection points at the `queue` Redis
  connection (`REDIS_QUEUE_CONNECTION=queue` → `REDIS_QUEUE_DB=2`)

#### 2. Drain the database queue before cutover

Keep the old workers running until the MySQL `jobs` table is empty (new jobs
must not pile up mid-cutover — briefly pause traffic or put the app in
maintenance if needed).

```bash
cd /home/ijaz/project

# Pending + reserved jobs still on the database driver
php artisan tinker --execute="echo 'jobs='.DB::table('jobs')->count().PHP_EOL; echo 'failed_jobs='.DB::table('failed_jobs')->count().PHP_EOL;"

# Optional: break down by queue name
php artisan tinker --execute="echo DB::table('jobs')->select('queue', DB::raw('count(*) as c'))->groupBy('queue')->get();"
```

Wait until `jobs=0`. Retry or clear `failed_jobs` separately if you care about
them (`php artisan queue:retry all` / `queue:flush`).

#### 3. `.env` changes (production)

```env
QUEUE_CONNECTION=redis

# Dedicated Redis DB for jobs (do not reuse cache DB 1)
REDIS_QUEUE_DB=2
REDIS_QUEUE_CONNECTION=queue

# Ensure Redis itself is configured (already required for cache if CACHE_STORE=redis)
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Then rebuild config cache:

```bash
php artisan config:cache
```

#### 4. Supervisor — comment out queue workers, add Horizon

Edit `/etc/supervisor/conf.d/ijaz.conf`:

```bash
sudo nano /etc/supervisor/conf.d/ijaz.conf
```

**Comment out** the queue worker programs only (`ijaz-default-worker` and
`ijaz-guarantor-worker`). Keep `ijaz-online-worker` (`app:online-listen`) and
`ijaz-reverb-socket` — they are not queue consumers and must stay running.

```ini
; --- Replaced by [program:ijaz-horizon] (Redis + Horizon) ---
;[program:ijaz-default-worker]
;process_name=%(program_name)s_%(process_num)02d
;; Listen to default + opportunities (hourly ExpireOpportunityJob — low volume,
;; no dedicated worker needed). Queue order = priority: default first.
;command=php /home/ijaz/project/artisan queue:work --sleep=3 --tries=3 --queue=default,opportunities --timeout=0
;autostart=true
;autorestart=true
;stopasgroup=true
;killasgroup=true
;user=ijaz
;numprocs=4
;redirect_stderr=true
;stdout_logfile=/home/ijaz/project/storage/logs/default-worker.log
;stopwaitsecs=3600

;[program:ijaz-guarantor-worker]
;process_name=%(program_name)s_%(process_num)02d
;command=php /home/ijaz/project/artisan queue:work --sleep=3 --tries=3 --queue=guarantor --timeout=0
;autostart=true
;autorestart=true
;stopasgroup=true
;killasgroup=true
;user=ijaz
;numprocs=2
;redirect_stderr=true
;stdout_logfile=/home/ijaz/project/storage/logs/guarantor-worker.log
;stopwaitsecs=3600

[program:ijaz-horizon]
process_name=%(program_name)s
command=php /home/ijaz/project/artisan horizon
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=ijaz
numprocs=1
redirect_stderr=true
stdout_logfile=/home/ijaz/project/storage/logs/horizon.log
stopwaitsecs=3600

; Keep these unchanged:
; [program:ijaz-online-worker]  — app:online-listen
; [program:ijaz-reverb-socket]  — reverb:start
```

Horizon supervisors in `config/horizon.php` already cover `default`,
`opportunities`, and `guarantor` (same split as the old Worker programs).

#### 5. Apply Supervisor changes

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl stop ijaz-default-worker:*
sudo supervisorctl stop ijaz-guarantor-worker:*
sudo supervisorctl start ijaz-horizon
sudo supervisorctl status
```

Expected (names may vary slightly after `update` removes old programs):

```
ijaz-horizon                            RUNNING
ijaz-online-worker:ijaz-online-worker_00 RUNNING
ijaz-reverb-socket:ijaz-reverb-socket_00 RUNNING
```

Verify Horizon:

```bash
cd /home/ijaz/project
php artisan horizon:status
# Dashboard (admin with "view monitoring tools"): https://{your-domain}/horizon
```

#### 6. Post-deploy restarts (after Horizon is live)

Replace the old worker restarts in the regular deploy script with:

```bash
php artisan horizon:terminate
# Supervisor autorestarts Horizon; or:
# supervisorctl restart ijaz-horizon
```

#### 7. Rollback (if something goes wrong)

1. Revert `.env`:
   ```env
   QUEUE_CONNECTION=database
   ```
   (comment out or leave `REDIS_QUEUE_*` — unused while on `database`)
2. `php artisan config:cache`
3. In Supervisor: uncomment `ijaz-default-worker` / `ijaz-guarantor-worker`,
   comment out or remove `ijaz-horizon`
4. Apply:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl stop ijaz-horizon
   sudo supervisorctl start ijaz-default-worker:*
   sudo supervisorctl start ijaz-guarantor-worker:*
   sudo supervisorctl status
   ```
5. Confirm: `php artisan tinker --execute="echo config('queue.default');"` → `database`

Any jobs already pushed to Redis while `QUEUE_CONNECTION=redis` will not be
visible to the database workers. Drain Redis first if needed
(`php artisan horizon:clear` / `queue:clear redis`) before rolling back, or
temporarily keep Horizon running until Redis queues are empty.

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
- `opportunities:expire` — hourly (dispatches `ExpireOpportunityJob` onto the `opportunities` queue; consumed by `ijaz-default-worker`, or by Horizon after the Redis cutover)
- `guarantor:check-overdue` — daily at midnight (checks overdue installments)
- `auth:prune-expired-otp-sessions` — hourly
- `telescope:prune --hours=48` — daily at midnight (Telescope is off by default via `TELESCOPE_ENABLED`; when enabled, entries older than 48 hours are pruned)
- Pulse has **no** prune artisan command — retention is config-driven (`PULSE_STORAGE_KEEP` / `PULSE_INGEST_KEEP`, default **7 days**) with lottery-based trim on ingest

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
# Before Horizon cutover:
supervisorctl restart ijaz-default-worker:*
supervisorctl restart ijaz-guarantor-worker:*
# After Horizon cutover (see "Migrating to Horizon on Production"):
# php artisan horizon:terminate
# supervisorctl restart ijaz-horizon

# 6. Upload public/build/ from local machine
```

---

### Adding a New Module (Queue)

**Prefer the simple path:** low-volume module queues (like `opportunities`) should be
appended to the default worker list — e.g. `--queue=default,opportunities,{module}` —
then `supervisorctl reread && supervisorctl update && supervisorctl restart ijaz-default-worker:*`.

Only add a **dedicated** Supervisor program (like `guarantor`) when the queue needs
isolation: high volume, long-running jobs, or failure blast-radius separation.

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
