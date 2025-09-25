<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PayrollPeriod;
use App\Mail\CutoffReminderEmail;
use App\Models\Employee;
use Illuminate\Support\Facades\Mail;

class SendCutoffReminder extends Command
{
    protected $signature = 'payroll:send-cutoff-reminder';
    protected $description = 'Send email reminders 2 days before cutoff ends';

    public function handle()
    {
        \Log::info("Running cutoff reminder at " . now());
        $today = now()->startOfDay();

        // Find payroll periods ending in exactly 2 days
        $payrollPeriods = PayrollPeriod::whereDate('end_date', $today->copy()->addDays(2))
            ->orWhereDate('end_date', $today->copy()->subDay())
            ->get();

        foreach ($payrollPeriods as $period) {
            // Determine if this is a before-cutoff or after-cutoff reminder
            $reminderType = $period->end_date->isSameDay($today->copy()->addDays(2))
                ? 'before'
                : 'after';

            // Get employees linked to this company
            $employees = Employee::where('company_id', $period->company_id)->get();

            foreach ($employees as $employee) {
                if ($employee->user && $employee->user->email) {
                    Mail::to($employee->user->email)
                        ->send(new CutoffReminderEmail($period, $employee, $reminderType));
                }
            }
        }

        $this->info("Cutoff reminder emails sent for payroll periods ending in 2 days.");
    }
}
