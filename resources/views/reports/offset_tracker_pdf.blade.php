<!DOCTYPE html>
<html>
<head>
    <title>Offset Usage and Expiry Tracker</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2, h4 { margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 10px; }
        .subheader { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $companyName ?? 'Company Name' }}</h2>
        <h4>Offset Usage and Expiry Tracker</h4>
    </div>
    <div class="subheader">
        <strong>Period Covered:</strong> {{ $periodText ?? 'All Dates' }}
    </div>


    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Overtime Date</th>
                <th>Approved Hours</th>
                <th>Used Hours</th>
                <th>Remaining</th>
                <th>Expiry Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($offsetData as $row)
                <tr>
                    <td>{{ $row['employee_name'] }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ number_format($row['approved_hours'], 2) }}</td>
                    <td>{{ number_format($row['used_hours'], 2) }}</td>
                    <td>{{ number_format($row['remaining_hours'], 2) }}</td>
                    <td>{{ $row['expires_at'] ?? 'N/A' }}</td>
                    <td>
                        @if ($row['expired'] && $row['remaining_hours'] > 0)
                            Expired
                        @elseif ($row['remaining_hours'] <= 0)
                            Fully Used
                        @else
                            Active
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
