<?php

namespace App\Console\Commands;

use App\Mail\DtrReminderEmail;
use App\Models\{Employee, PayrollPeriod, TimeRecord};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDtrReminders extends Command
{
    protected $signature   = 'dtr:send-reminders';
    protected $description = 'Send DTR reminder emails to employees with Not Submitted or Submitted status';

    public function handle()
    {
        $now = now();

        $payrollPeriods = PayrollPeriod::whereNotNull('dtr_submission_due_at')
            ->where('dtr_submission_due_at', '<=', $now)
            ->whereNull('reminder_sent_at') // prevent duplicate emails
            ->get();

        foreach ($payrollPeriods as $payrollPeriod) {
            $employees = Employee::where('company_id', $payrollPeriod->company_id)->get();

            foreach ($employees as $employee) {
                $timeRecord = TimeRecord::where('employee_id', $employee->id)
                    ->where('payroll_period_id', $payrollPeriod->id)
                    ->first();

                // Skip if the record exists and is approved
                if ($timeRecord && $timeRecord->status === 'approved') {
                    continue;
                }

                // Determine status for email
                $status = 'Not Submitted';
                if ($timeRecord) {
                    if ($timeRecord->status === 'rejected') {
                        continue; // optionally skip rejected
                    } elseif (!is_null($timeRecord->status)) {
                        $status = 'Submitted';
                    }
                }

                // Send reminder only if not approved
                Mail::to($employee->user->email)->send(new DtrReminderEmail($employee, $status, $payrollPeriod));
                $this->info("Reminder sent to {$employee->user->email} with status: $status");
            }

            // Optional: move this outside if you want per-employee tracking instead of per-period
            $payrollPeriod->update(['reminder_sent_at' => $now]);
        }
    }
}
