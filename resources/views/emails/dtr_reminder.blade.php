<p>Dear {{ $employee->user->name }},</p>

<p>This is a reminder that your DTR for the period <strong>{{ $payrollPeriod->start_date->format('M d') }} to {{ $payrollPeriod->end_date->format('M d, Y') }}</strong> is currently marked as <strong>{{ $status }}</strong>.</p>

<p>Please make sure to submit it as soon as possible.</p>

<p>Thank you,<br>HR Department</p>
