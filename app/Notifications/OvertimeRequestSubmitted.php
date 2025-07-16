<?php

namespace App\Notifications;

use App\Models\OvertimeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OvertimeRequestSubmitted extends Notification
{
    use Queueable;

    public $overtimeRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(OvertimeRequest $overtimeRequest)
    {
        $this->overtimeRequest = $overtimeRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $employeeName = $this->overtimeRequest->employee->user->name ?? 'An employee';

        return (new MailMessage)
            ->subject('New Overtime Request Submitted')
            ->greeting("Hello {$notifiable->name},")
            ->line("{$employeeName} submitted an overtime request.")
            ->line("Date: " . $this->overtimeRequest->date)
            ->line("From: " . $this->overtimeRequest->time_start . " to " . $this->overtimeRequest->time_end)
            ->line("Reason: " . $this->overtimeRequest->reason)
            ->action('View Request', url(route('overtime_requests.show', $this->overtimeRequest->id)))
            ->line('Please review and take action.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
