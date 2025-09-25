@component('mail::message')
# Hi {{ $employee->user->name }},

@if ($reminderType === 'before')
This is a reminder that the payroll cutoff period will end on **{{ $period->end_date->format('F j, Y') }}**.

Please make sure all your **employee requests** are filed in preparation for the submission of your DTRs before the cutoff.
@else
The payroll cutoff period ended on **{{ $period->end_date->format('F j, Y') }}**.

If you haven’t already, please ensure all your **employee requests** and DTR submissions are completed immediately.
@endif

Thanks,  
{{ config('app.name') }}
@endcomponent
