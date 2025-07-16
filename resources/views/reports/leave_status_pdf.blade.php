<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Status Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        th { background-color: #f2f2f2; }
        h2, h4 { margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>Leave Status Overview</h2>
    <h4>As of {{ \Carbon\Carbon::now()->format('F d, Y') }}</h4>

    @foreach ($statusCounts as $department => $employees)
        <h4>Department: {{ $department }}</h4>
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Pending</th>
                    <th>Approved</th>
                    <th>Rejected</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($employees as $employee => $counts)
                    <tr>
                        <td>{{ $employee }}</td>
                        <td>{{ $counts['Pending'] ?? 0 }}</td>
                        <td>{{ $counts['Approved'] ?? 0 }}</td>
                        <td>{{ $counts['Rejected'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

</body>
</html>
