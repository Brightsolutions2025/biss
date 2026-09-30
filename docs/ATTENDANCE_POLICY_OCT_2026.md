# BISS Attendance Policy — Effective October 1, 2026

## Rules

From October 1, 2026 onward:

- **Warehouseman** — fixed attendance using the employee's assigned shift.
- **Non-director employees** — flexible arrival up to **11:00 AM**. Arrival after 11:00 AM is late by the exact excess minutes.
- **Directors** — unrestricted flexible arrival; arrival time does not generate late minutes.
- **Break time is flexible** — employees may take their break at any time. BISS does not require Break Out / Break In logs.
- For flexible employees, BISS deducts the configured **standard break duration** (default **60 minutes**) from the total Time In to Time Out span.
- Flexible employees must complete **480 working minutes (8 hours)** after the standard break deduction. Missing minutes are undertime.

Attendance dates before October 1, 2026 continue to use the existing `employees.flexible_time` rule and the legacy break-window computation. This preserves September and earlier DTR behavior if an older record is edited.

## Examples

- Non-director, 11:05 AM time in: **5 minutes late**.
- Non-director, 7:00 AM–4:00 PM: 9 hours elapsed − 60-minute standard break = 8 working hours → **0 late, 0 undertime**.
- Non-director, 10:00 AM–6:30 PM: 8.5 hours elapsed − 60-minute standard break = 7.5 working hours → **0 late, 30 minutes undertime**.
- Director, 1:00 PM–10:00 PM: 9 hours elapsed − 60-minute standard break = 8 working hours → **0 late, 0 undertime**.
- Warehouseman: late and undertime follow the assigned fixed shift. From October 1 onward, no fixed 12:00–1:00 break window is assumed for the late/undertime comparison.

## Configuration

The policy is centralized in `config/attendance.php` and may be overridden in `.env`:

```env
ATTENDANCE_POLICY_EFFECTIVE_DATE=2026-10-01
ATTENDANCE_FLEXIBLE_CUTOFF_TIME=11:00
ATTENDANCE_REQUIRED_WORK_MINUTES=480
ATTENDANCE_BREAK_MINUTES=60
ATTENDANCE_FIXED_POSITION_KEYWORDS=warehouseman,warehouse man
ATTENDANCE_UNRESTRICTED_POSITION_KEYWORDS=director
```

The legacy break settings below are only used for attendance dates before October 1, 2026:

```env
ATTENDANCE_LEGACY_BREAK_START=12:00
ATTENDANCE_LEGACY_BREAK_END=13:00
```

Position matching is case-insensitive and uses keyword containment. Examples such as `Operations Director` and `Account Director` therefore use unrestricted flexible arrival.

After changing attendance environment variables, run:

```bash
php artisan optimize:clear
```

## Terminology

User-facing terminology uses:

- **Compensatory Overtime Credit**
- **Compensatory Overtime Credit Pre-Approval**
- **Compensatory Time-Off**

Internal Laravel identifiers such as models, routes, table names, variables, and permission keys retain their existing technical names for backward compatibility.

## Deployment

This change does **not** require a database migration. After pulling/deploying the source:

```bash
php artisan optimize:clear
php artisan optimize
```

Existing shift assignments remain in place. They continue to control fixed-shift employees and are retained for compatibility with existing time-log handling.
