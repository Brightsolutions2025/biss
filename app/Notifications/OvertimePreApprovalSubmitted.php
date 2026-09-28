<?php

namespace App\Notifications;

use App\Models\OvertimePreApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OvertimePreApprovalSubmitted extends Notification
{
    use Queueable;

    public function __construct(
        public OvertimePreApproval $overtimePreApproval
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employeeName = $this->overtimePreApproval->employee->user->name
            ?? trim(
                ($this->overtimePreApproval->employee->first_name ?? '') . ' ' .
                ($this->overtimePreApproval->employee->last_name ?? '')
            )
            ?: 'An employee';

        $date = $this->overtimePreApproval->date?->format('F j, Y')
            ?? (string) $this->overtimePreApproval->date;

        $mail = (new MailMessage())
            ->subject(
                'New Compensatory Over Time Credit Pre-Approval Submitted'
            )
            ->greeting("Hello {$notifiable->name},")
            ->line(
                "{$employeeName} submitted a Compensatory Over Time Credit Pre-Approval."
            )
            ->line("Date: {$date}")
            ->line(
                'Planned Time: ' .
                $this->overtimePreApproval->planned_time_start .
                ' to ' .
                $this->overtimePreApproval->planned_time_end
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

        if ($this->overtimePreApproval->planned_tasks) {
            $mail->line(
                'Planned Tasks: ' .
                $this->overtimePreApproval->planned_tasks
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
            ->line('Please review and take action.');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}