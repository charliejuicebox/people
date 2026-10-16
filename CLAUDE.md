# JB People

Laravel 13 app for Juicebox quarterly reviews, feedback, praise and audit. See README.md.

- Access rules live in `User::canSeeRecordsOf()` and `app/Policies/QuarterlyReviewPolicy.php`. Admin must never see review/feedback content.
- Every read of a person's records and every change goes through `App\Services\Audit::log()`. `AuditLog` is append-only.
- Records are keyed on `users.id`, never email (sign-in providers will change in 2027).
- UI strings use `__()`; add the Bahasa version to `lang/id.json`.
- Seed data is dummy only. Don't add real names or emails.
- Run `php artisan test` before committing.
