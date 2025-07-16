<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Filed Overtime Report</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }
        h2, h4, p {
            margin: 0;
            padding: 0;
        }
        .text-center {
            text-align: center;
        }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-3 { margin-bottom: 1rem; }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .table th, .table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
        }
        .badge-success { background-color: #28a745; color: #fff; }
        .badge-warning { background-color: #ffc107; color: #000; }
        .badge-danger { background-color: #dc3545; color: #fff; }
    </style>
</head>
<body>

    <div class="text-center mb-3">
        <h2>{{ $companyName }}</h2>
        <h4>Filed Overtime Report</h4>
        <p class="mb-2">
            @if (!empty($filters['start_date']) && !empty($filters['end_date']))
                Period Covered: {{ \Carbon\Carbon::parse($filters['start_date'])->format('F d, Y') }} to {{ \Carbon\Carbon::parse($filters['end_date'])->format('F d, Y') }}
            @else
                As of {{ \Carbon\Carbon::now()->format('F d, Y') }}
            @endif
        </p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Time Start</th>
                <th>Time End</th>
                <th>Hours</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Approval Date</th>
                <th>Expiration Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($overtimeRequests as $ot)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($ot->date)->toFormattedDateString() }}</td>
                    <td>{{ \Carbon\Carbon::parse($ot->time_start)->format('h:i A') }}</td>
                    <td>{{ \Carbon\Carbon::parse($ot->time_end)->format('h:i A') }}</td>
                    <td>{{ number_format($ot->number_of_hours, 2) }}</td>
                    <td>{{ $ot->reason }}</td>
                    <td>
                        @if ($ot->status === 'approved')
                            <span class="badge badge-success">Approved</span>
                        @elseif ($ot->status === 'pending')
                            <span class="badge badge-warning">Pending</span>
                        @else
                            <span class="badge badge-danger">Rejected</span>
                        @endif
                    </td>
                    <td>{{ $ot->approval_date ? \Carbon\Carbon::parse($ot->approval_date)->toFormattedDateString() : '—' }}</td>
                    <td>{{ $ot->expires_at ? \Carbon\Carbon::parse($ot->expires_at)->toFormattedDateString() : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">No overtime records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
