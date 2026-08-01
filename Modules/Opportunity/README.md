# Opportunity Module

## Queue & Scheduler (Production)

`ExpireOpportunityJob` is dispatched onto the **`opportunities`** queue. That queue is
consumed by the shared **`ijaz-default-worker`** Supervisor program (not a dedicated
worker — volume is low: hourly expire sweeps only).

Canonical Supervisor config lives in the root [`README.md`](../../README.md)
(`--queue=default,opportunities`). Do **not** add a separate `ijaz-opportunities-worker`
unless volume/isolation needs change.

Scheduler (via cron `schedule:run` or `schedule:work`):

```
* * * * * cd /home/ijaz/project && php artisan schedule:run >> /dev/null 2>&1
```

## Expire command

The `opportunities:expire` command runs hourly via the scheduler. It dispatches
`ExpireOpportunityJob` to the `opportunities` queue for each opportunity past its
`expires_at` date with status `new` or `offer_accepted`.

```bash
php artisan opportunities:expire
```
