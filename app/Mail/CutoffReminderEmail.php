<?php

namespace App\Mail;

use App\Models\PayrollPeriod;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CutoffReminderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $period;
    public $employee;
    public $reminderType;

    public function __construct($period, $employee, $reminderType = 'before')
    {
        $this->period = $period;
        $this->employee = $employee;
        $this->reminderType = $reminderType;
    }

    public function build()
    {
        return $this->markdown('emails.cutoff.reminder')
            ->with([
                'period' => $this->period,
                'employee' => $this->employee,
                'reminderType' => $this->reminderType,
            ]);
    }
}
