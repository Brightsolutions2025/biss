<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Leave Utilization Summary</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2, h4 { margin: 0; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background-color: #eee; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>

    <h2>{{ $company->name }}</h2>
    <h4>Leave Utilization Summary</h4>
    <p class="text-center">Period Covered: {{ $periodCovered }}</p>

    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Year</th>
                <th class="text-right">Beginning Balance</th>
                <th class="text-right">Used</th>
                <th class="text-right">Remaining</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($leaveBalances as $row)
                <tr>
                    <td>{{ $row['employee_name'] }}</td>
                    <td>{{ $row['department'] }}</td>
                    <td class="text-center">{{ $row['year'] }}</td>
                    <td class="text-right">{{ number_format($row['beginning'], 2) }}</td>
                    <td class="text-right">{{ number_format($row['used'], 2) }}</td>
                    <td class="text-right">{{ number_format($row['remaining'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
