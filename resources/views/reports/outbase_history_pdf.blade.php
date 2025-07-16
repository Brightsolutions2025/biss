<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Outbase Request Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        h2, .subtitle {
            text-align: center;
            margin: 0;
            padding: 0;
        }

        .subtitle {
            font-size: 13px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #999;
            padding: 6px 8px;
            font-size: 11px;
        }

        th {
            background-color: #f2f2f2;
            text-align: left;
        }

        .badge {
            padding: 2px 6px;
            font-size: 10px;
            border-radius: 4px;
            color: #fff;
        }

        .approved { background-color: #28a745; }
        .pending { background-color: #6c757d; }
        .rejected { background-color: #dc3545; }
    </style>
</head>
<body>

    <h2>Outbase Request Report</h2>
    <div class="subtitle">
        Period: {{ \Carbon\Carbon::parse($startDate)->toFormattedDateString() }} 
        to {{ \Carbon\Carbon::parse($endDate)->toFormattedDateString() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Department</th>
                <th>Time Start</th>
                <th>Time End</th>
                <th>Location</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Approver</th>
            </tr>
        </thead>
        <tbody>
            @foreach($outbaseRequests as $req)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($req->date)->toFormattedDateString() }}</td>
                    <td>{{ $req->employee->department->name ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($req->time_start)->format('h:i A') }}</td>
                    <td>{{ \Carbon\Carbon::parse($req->time_end)->format('h:i A') }}</td>
                    <td>{{ $req->location }}</td>
                    <td>{{ $req->reason }}</td>
                    <td>
                        <span class="badge {{ $req->status }}">
                            {{ ucfirst($req->status) }}
                        </span>
                    </td>
                    <td>{{ $req->approver?->name ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
