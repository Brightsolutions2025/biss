BISS cumulative VL/EL + Compensatory Overtime Pre-Approval email integration

Base: the latest biss.zip uploaded in this conversation.

VL / EL functionality included:
- Vacation Leave (VL) and Emergency Leave (EL) are selectable in Leave Requests.
- Separate annual credit buckets: VL 12, EL 3 (config/leave.php).
- Paid VL/EL credits are limited to Regular employees.
- VL and EL cannot borrow from each other's credits.
- Emergency Leave requires at least one supporting document.
- The final EL supporting document cannot be deleted while the request remains EL.
- Leave Balance CRUD supports separate leave_type records.
- Dashboard shows separate VL and EL balances.
- Leave Utilization, Leave Summary, PDF, Excel, and timeline output recognize leave type.
- Existing historical leave data is treated as Vacation Leave by the migration.

Compensatory Overtime Credit Pre-Approval email fix included:
- Submit -> email assigned approver.
- Approve -> email employee/requestor.
- Reject -> email employee/requestor.
- Email wording uses "Compensatory Overtime Credit Pre-Approval".

Deployment:
1. Copy these files over the same paths in BISS.
2. Run: php artisan migrate --force
3. Run: php artisan optimize:clear
4. Run: php artisan optimize

Mail note:
MAIL_MAILER=log only writes emails to storage/logs/laravel.log.
Use the server's actual SMTP mailer configuration to send real email.
