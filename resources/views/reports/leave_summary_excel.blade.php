<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Leave Summary Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2, h4, p { margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        .text-center { text-align: center; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-3 { margin-bottom: 1rem; }
    </style>
</head>
<body>

    <div class="text-center mb-3">
        <h2>{{ $companyName }}</h2>
        <h4>Leave Summary Report</h4>
        <p class="mb-2">
            @if ($year)
                Period Covered: Jan 1, {{ $year }} to Dec 31, {{ $year }}
            @else
                As of {{ \Carbon\Carbon::now()->format('F d, Y') }}
            @endif
        </p>
    </div>

    <h4 class="mb-2">Summary</h4>
    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Team</th>
                <th>Approver</th>
                <th>Beginning Balance</th>
                <th>Used</th>
                <th>Remaining</th>
                <th>Utilization (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($leaveBalances as $row)
                <tr>
                    <td>{{ $row['employee_name'] }}</td>
                    <td>{{ $row['department_name'] ?? '—' }}</td>
                    <td>{{ $row['team_name'] ?? '—' }}</td>
                    <td>{{ $row['approver_name'] ?? '—' }}</td>
                    <td>{{ number_format($row['beginning_balance'], 2) }}</td>
                    <td>{{ number_format($row['used'], 2) }}</td>
                    <td>{{ number_format($row['remaining'], 2) }}</td>
                    <td>{{ number_format($row['utilization'], 1) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h4 class="mb-2" style="margin-top: 30px;">Approved Leave Requests</h4>
    <table>
        <thead>
            <tr>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Number of Days</th>
                <th>Reason</th>
                <th>Approval Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($leaveDetails as $leave)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($leave->start_date)->toFormattedDateString() }}</td>
                    <td>{{ \Carbon\Carbon::parse($leave->end_date)->toFormattedDateString() }}</td>
                    <td>{{ number_format($leave->number_of_days, 2) }}</td>
                    <td>{{ $leave->reason }}</td>
                    <td>{{ $leave->approval_date ? \Carbon\Carbon::parse($leave->approval_date)->toFormattedDateString() : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">No approved leave records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
