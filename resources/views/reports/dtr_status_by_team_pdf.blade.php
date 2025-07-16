<!DOCTYPE html>
<html>
<head>
    <title>DTR Status Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2, .header h4 { margin: 0; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ auth()->user()->preference->company->name ?? 'Company Name' }}</h2>
        <h4>DTR Status Report by Team</h4>
        <p>Period Covered: 
            {{ \Carbon\Carbon::parse($payrollPeriod->start_date)->format('F d, Y') }}
            -
            {{ \Carbon\Carbon::parse($payrollPeriod->end_date)->format('F d, Y') }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Team</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $row)
                <tr>
                    <td>{{ $row['employee']->first_name }} {{ $row['employee']->last_name }}</td>
                    <td>{{ $row['department'] }}</td>
                    <td>{{ $row['team'] }}</td>
                    <td>{{ $row['status'] ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
