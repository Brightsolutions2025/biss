<?php

namespace App\Notifications;

use App\Models\DayOffChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DayOffChangeRequestSubmitted extends Notification
{
    use Queueable;

    public $dayOffChangeRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(DayOffChangeRequest $dayOffChangeRequest)
    {
        $this->dayOffChangeRequest = $dayOffChangeRequest;
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
        $employeeName = $this->dayOffChangeRequest->employee->user->name ?? 'An employee';

        return (new MailMessage())
            ->subject('New Day Off Change Request Submitted')
            ->greeting("Hello {$notifiable->name},")
            ->line("{$employeeName} submitted a day off change request.")
            ->line('Current Day Off: ' . $this->dayOffChangeRequest->old_day_off)
            ->line('Requested Day Off: ' . $this->dayOffChangeRequest->new_day_off)
            ->when($this->dayOffChangeRequest->reason, function ($mail) {
                $mail->line('Reason: ' . $this->dayOffChangeRequest->reason);
            })
            ->action(
                'View Request',
                url(route('day_off_change_requests.show', $this->dayOffChangeRequest->id))
            )
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
            'day_off_change_request_id' => $this->dayOffChangeRequest->id,
            'type' => 'day_off_change',
        ];
    }
}
