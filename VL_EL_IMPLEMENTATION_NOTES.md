# VL / EL Leave Enhancement

Implemented on 2026-09-09.

## Implemented policy

- Vacation Leave (VL): 12 paid-leave credits per configured year for Regular employees.
- Emergency Leave (EL): 3 paid-leave credits per configured year for Regular employees.
- VL and EL have independent balance records and independent approved-paid-leave usage calculations.
- Non-Regular employees may still file leave using the existing unpaid-leave behavior, but cannot consume VL/EL paid credits.
- Emergency Leave requires at least one supporting document.
- EL attachment enforcement is server-side on create/update and is rechecked before approval.
- The last remaining attachment on an EL request cannot be deleted while the request remains EL.
- Supported attachments preserve the existing project rules: up to 5 files; max 5 MB each; PDF, JPG/JPEG, PNG, DOC/DOCX, XLSX.

## Migration behavior

Migration: `database/migrations/2026_09_09_000001_add_vl_el_leave_types.php`

- Adds `leave_type` to `leave_requests` and `leave_balances`.
- Existing historical leave requests/balances are classified as Vacation Leave because the prior schema contains no data that can reliably identify historical Emergency Leave.
- For the migration year, existing employees whose `employment_type` is `Regular` or `Regular Employee` are initialized/normalized to:
  - VL: 12
  - EL: 3
- No automatic future annual reset/scheduler is added by this change.

## Deployment

From the BISS application root:

```bash
php artisan migrate
php artisan optimize:clear
```

Recommended focused verification in an environment with the project's required PHP test extensions:

```bash
php artisan test --filter=LeaveRequestTest
php artisan test --filter=LeaveBalanceTest
```

## QA performed while preparing this package

- PHP syntax validation: all first-party PHP files under `app`, `config`, `database`, `tests`, `routes`, and `bootstrap` passed (`227` files).
- Blade source compilation/lint check: all `188` Blade views compiled and the generated PHP passed syntax validation, with component-tag compilation disabled only for the standalone lint harness.
- Full PHPUnit/Artisan execution could not be completed in the packaging environment because its PHP CLI lacks required DOM, mbstring, XML/XMLWriter, ZIP and database-driver extensions. Run the focused verification commands above in the normal BISS development/server environment.
