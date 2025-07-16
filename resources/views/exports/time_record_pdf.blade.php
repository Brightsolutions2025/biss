<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Time Record (Landscape)</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 3px; text-align: center; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h3>Time Record for {{ $timeRecord->employee->user->name }}</h3>
    <p>Employee Number: {{ $timeRecord->employee->employee_number }}</p>
    <p>Payroll Period: {{ $timeRecord->payrollPeriod->start_date }} to {{ $timeRecord->payrollPeriod->end_date }}</p>
    <p>Approval Date: {{ $timeRecord->approval_date }}</p>

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
            @foreach ($timeRecord->lines as $line)
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
</body>
</html>
