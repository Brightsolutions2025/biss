<?php

namespace App\Notifications;

use App\Models\OvertimePreApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OvertimePreApprovalStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public OvertimePreApproval $overtimePreApproval,
        public string $status
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = ucfirst($this->status);

        $date = $this->overtimePreApproval->date?->format('F j, Y')
            ?? (string) $this->overtimePreApproval->date;

        $statusMessage = match ($this->status) {
            'approved' => 'has been approved.',
            'rejected' => 'has been rejected.',
            default => 'has been updated.',
        };

        $mail = (new MailMessage())
            ->subject(
                "Compensatory Over Time Credit Pre-Approval {$statusLabel}"
            )
            ->greeting("Hello {$notifiable->name},")
            ->line(
                "Your Compensatory Over Time Credit Pre-Approval for {$date} {$statusMessage}"
            )
            ->line(
                'Estimated Credit Hours: ' .
                number_format(
                    (float) $this->overtimePreApproval
                        ->estimated_number_of_hours,
                    2
                )
            )
            ->line(
                'Reason: ' .
                $this->overtimePreApproval->reason
            );

        if (
            $this->status === 'rejected' &&
            $this->overtimePreApproval->rejection_reason
        ) {
            $mail->line(
                'Rejection Reason: ' .
                $this->overtimePreApproval->rejection_reason
            );
        }

        return $mail
            ->action(
                'View Pre-Approval',
                route(
                    'overtime_pre_approvals.show',
                    $this->overtimePreApproval->id
                )
            )
            ->line('Thank you for using BISS.');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}