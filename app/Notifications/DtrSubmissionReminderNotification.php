<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\PayrollPeriod;

class DtrSubmissionReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public PayrollPeriod $period, public string $status) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('DTR Submission Overdue')
            ->greeting('Hello ' . $notifiable->name)
            ->line("Your DTR for the period {$this->period->start_date->format('M d')} to {$this->period->end_date->format('M d, Y')} is marked as '{$this->status}' and the due date has already passed.")
            ->line('Please take appropriate action immediately.')
            ->action('Log in to the HRIS', url('/')) // adjust the URL
            ->line('Thank you for your attention.');
    }
}
