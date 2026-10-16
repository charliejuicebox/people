# JB People

Quarterly reviews, named feedback, values-based praise and an audit log for Juicebox AU and ID.
Standalone Laravel 13 app (PHP 8.3+, SQLite by default, Tailwind v4 via Vite).

> **Dummy data only until the CTO review.** The seeder uses fake people at `example-au.test` / `example-id.test`.
> Load the real roster with `people:import-roster` only after the review is signed off.

## Run it with Docker (easiest)

1. Install [Docker Desktop](https://www.docker.com/products/docker-desktop/) and open it.
2. In Terminal, go to this folder (type `cd `, drag the folder in from Finder, press Enter).
3. Run:

```sh
docker compose up --build
```

The first run takes a few minutes. When it says `Server running`, open http://localhost:8000 and pick someone from the demo login list.
To load the real team instead of the demo people, put the roster file at `roster/roster.csv` before the first run (or run `docker compose down -v` to start fresh). The `roster/` folder is never committed to git.
Ctrl+C stops it; `docker compose up` starts it again with your data kept. `docker compose down -v` wipes the data and reseeds next time.

## Run it without Docker

```sh
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
# in .env set PEOPLE_DEV_LOGIN=true (only works when APP_ENV=local)
php artisan serve
```

Open http://127.0.0.1:8000. The login page shows a **demo login** list so you can sign in as any seeded person
(director, HR, team lead, employee, ID staff) and see what each role sees.

Tests: `php artisan test` (28 feature tests).

## What's in the MVP

- **Quarterly reviews**: draft by the team lead, rated against the three values (Make it Matter, Work as One, Embrace Change)
  plus overall rating, concerns and expectations for next quarter. Shared to the employee, who adds a reflection and acknowledges.
  Lead fields lock once shared.
- **No-surprises check**: rating someone "below expectations" when last quarter's review didn't record a concern
  requires the lead to confirm the expectation was communicated, and flags it to HR.
- **Feedback**: named, visible to the recipient, their lead, HR and directors.
- **Praise**: tagged with a value, auto-posted to Slack `#juicebox`.
- **Access**: directors and HR see everyone across both entities; team leads see their reports; admin manages the app but can't read review content.
- **Audit log**: append-only (the model refuses updates/deletes), filterable, CSV export.
- **Bilingual UI**: EN/ID toggle; Bahasa strings in `lang/id.json` are a draft for ID HR to check.
- **Reminders**: weekday, business-hours (Perth/Bali time) Slack + email nudges for missing and unacknowledged reviews.
- **Retention**: `people:prune-records` deletes check-in feedback after 2 years and formal records 7 years after leaving.

## Configuration (.env)

| Key | Purpose |
|---|---|
| `PEOPLE_TIMEZONE` | Default `Australia/Perth` (same offset as Bali) |
| `PEOPLE_DEV_LOGIN` | Demo login, local only. Must be `false` anywhere else |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | AU sign-in |
| `MICROSOFT_CLIENT_ID` / `MICROSOFT_CLIENT_SECRET` / `MICROSOFT_TENANT_ID` | ID sign-in (Entra) |
| `SLACK_BOT_TOKEN` | Praise posts and DMs. Without it, praise is saved and marked "Slack not configured" |
| `SLACK_PRAISE_CHANNEL` | Default `#juicebox` |
| `MAIL_*` | Email notifications |

### Sign-in

Only people on the roster can sign in. Identities are linked to the internal user ID (not email), so moving AU to Microsoft in 2027 means each person just signs in once with Microsoft.

- **Google** (Cloud Console > APIs & Services > Credentials > OAuth client, Web): redirect URI `APP_URL/auth/google/callback`. Set the consent screen to Internal.
- **Microsoft Entra** (App registrations > New, single tenant): redirect URI `APP_URL/auth/microsoft/callback`, add a client secret, API permission `User.Read`.

### Slack app

Create an app in the Juicebox workspace with bot scopes `chat:write`, `users:read`, `users:read.email`. Install it, copy the bot token, and invite the bot to `#juicebox`.

## Roster import

```sh
php artisan people:import-roster roster.csv --dry-run
php artisan people:import-roster roster.csv
```

Columns follow `roster-template.csv` in the repo root (employee_id, full_name, work_email, entity, department, job_title, team_lead_email, role, employment_type, start_date, contract_end_date, sign_in, plus optional viewable_by_emails: extra people, separated by `;`, who can read that person's reviews).
Every row is validated first; nothing is written if any row fails.

## Production

- Cron: `* * * * * php artisan schedule:run` (reminders weekdays 09:00, prune monthly).
- Queue worker: `php artisan queue:work` (Slack posts and notifications).
- `APP_ENV=production`, `APP_DEBUG=false`, `PEOPLE_DEV_LOGIN=false`, HTTPS only, nightly encrypted DB backups.

## Not yet built

Annual 360 cycle, Slack `/praise` command, ID formal records (SP1-3, Perjanjian Bersama, PKWT contract-end reminders), goals and reporting.
