# Opportunity Module

## Queue & Scheduler (Production)

Run queue workers and the scheduler via Supervisor:

```ini
[program:ijaz-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ijaz/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/ijaz/storage/logs/worker.log
stopwaitsecs=3600

[program:ijaz-opportunities-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ijaz/artisan queue:work database --queue=opportunities --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/ijaz/storage/logs/opportunities-worker.log

[program:ijaz-scheduler]
command=php /var/www/ijaz/artisan schedule:work
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/www/ijaz/storage/logs/scheduler.log
```

### Cron alternative (if no Supervisor)

```
* * * * * cd /var/www/ijaz && php artisan schedule:run >> /dev/null 2>&1
```

## Expire command

The `opportunities:expire` command runs hourly via the scheduler. It dispatches `ExpireOpportunityJob` to the `opportunities` queue for each opportunity past its `expires_at` date with status `new` or `offer_accepted`.

```bash
php artisan opportunities:expire
```
