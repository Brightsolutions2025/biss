<!DOCTYPE html>
<html>
<head>
    <title>Outbase Request Summary Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; margin-bottom: 0; }
        .meta { margin-bottom: 20px; }
    </style>
</head>
<body>

    <h2>{{ $company }}</h2>
    <h3 style="text-align: center;">Outbase Request Summary Report</h3>

    <div class="meta">
        <p><strong>Period Covered:</strong> {{ $period }}</p>
        <p><strong>Generated at:</strong> {{ now()->format('F d, Y H:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Department</th>
                <th>Employee</th>
                <th>No. of Outbase</th>
                <th>Locations</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $row)
                <tr>
                    <td>{{ $row['department'] }}</td>
                    <td>{{ $row['employee'] }}</td>
                    <td>{{ $row['outbase_count'] }}</td>
                    <td>
                        @if (!empty($locations[$row['employee']]))
                            {{ implode(', ', $locations[$row['employee']]->toArray()) }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
