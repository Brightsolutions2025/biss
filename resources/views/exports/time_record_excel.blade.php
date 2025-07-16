<h3>Time Record for {{ $timeRecord->employee->user->name }}</h3>
<p>Employee Number: {{ $timeRecord->employee->employee_number }}</p>
<p>Payroll Period: {{ $timeRecord->payrollPeriod->start_date }} to {{ $timeRecord->payrollPeriod->end_date }}</p>
<p>Approval Date: {{ $timeRecord->approval_date }}</p>
<br>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Day</th>
            <th>Clock In</th>
            <th>Clock Out</th>
            <th>Late</th>
            <th>Undertime</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        @foreach($timeRecord->lines as $line)
            <tr>
                <td>{{ $line->date }}</td>
                <td>{{ \Carbon\Carbon::parse($line->date)->format('l') }}</td>
                <td>{{ $line->clock_in }}</td>
                <td>{{ $line->clock_out }}</td>
                <td>{{ $line->late_minutes }}</td>
                <td>{{ $line->undertime_minutes }}</td>
                <td>{{ $line->remarks }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
