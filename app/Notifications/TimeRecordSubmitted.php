<?php

namespace App\Notifications;

use App\Models\TimeRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimeRecordSubmitted extends Notification
{
    use Queueable;

    public $timeRecord;

    public function __construct(TimeRecord $timeRecord)
    {
        $this->timeRecord = $timeRecord;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $employeeName = $this->timeRecord->employee->user->name ?? 'An employee';
        return (new MailMessage())
            ->subject('New Time Record Submitted')
            ->greeting("Hello {$notifiable->name},")
            ->line("{$employeeName} submitted a time record.")
            ->line('Payroll Period: ' . $this->timeRecord->payrollPeriod->name)
            ->action('View Time Record', route('time_records.show', $this->timeRecord->id))
            ->line('Please review and take action.');
    }
}
