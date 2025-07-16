<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Offset Request Summary PDF</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge { padding: 2px 6px; border-radius: 4px; color: #fff; font-size: 10px; }
        .approved { background-color: #28a745; }
        .pending { background-color: #6c757d; }
        .rejected { background-color: #dc3545; }
    </style>
</head>
<body>
    <h2>Offset Request Summary</h2>
    <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Time Start</th>
                <th>Time End</th>
                <th>Hours</th>
                <th>Reason</th>
                <th>Project/Event</th>
                <th>Status</th>
                <th>Approver</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($offsetRequests as $req)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($req->date)->toFormattedDateString() }}</td>
                    <td>{{ \Carbon\Carbon::parse($req->time_start)->format('h:i A') }}</td>
                    <td>{{ \Carbon\Carbon::parse($req->time_end)->format('h:i A') }}</td>
                    <td>{{ $req->number_of_hours }}</td>
                    <td>{{ $req->reason ?? '-' }}</td>
                    <td>{{ $req->project_or_event_description }}</td>
                    <td>
                        <span class="badge {{ $req->status }}">
                            {{ ucfirst($req->status) }}
                        </span>
                    </td>
                    <td>{{ $req->approver?->name ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No offset requests found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
