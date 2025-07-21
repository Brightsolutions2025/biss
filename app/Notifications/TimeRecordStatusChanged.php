<?php

namespace App\Notifications;

use App\Models\TimeRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimeRecordStatusChanged extends Notification
{
    use Queueable;

    public $timeRecord;
    public $status;

    public function __construct(TimeRecord $timeRecord, $status)
    {
        $this->timeRecord = $timeRecord;
        $this->status     = $status;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $statusLabel   = ucfirst($this->status);
        $statusMessage = match ($this->status) {
            'approved' => 'has been approved. Great job!',
            'rejected' => 'was rejected. Please contact your supervisor.',
            default    => 'was updated.'
        };

        $mail = (new MailMessage())
            ->subject("Time Record {$statusLabel}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your time record for payroll period '{$this->timeRecord->payrollPeriod->name}' {$statusMessage}");

        if ($this->status === 'rejected' && $this->timeRecord->rejection_reason) {
            $mail->line("Rejection Reason: {$this->timeRecord->rejection_reason}");
        }

        return $mail
            ->action('View Time Record', route('time_records.show', $this->timeRecord->id))
            ->line('Thank you for using our system!');
    }
}
