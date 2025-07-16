<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Late and Undertime Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2>Late and Undertime Report</h2>
    @if($date_from && $date_to)
        <p><strong>Period:</strong> {{ $date_from }} to {{ $date_to }}</p>
    @endif

    @forelse($grouped as $department => $employees)
        <h4>{{ $department }}</h4>
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th style="text-align:right;">Late Minutes</th>
                    <th style="text-align:right;">Undertime Minutes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $employee => $totals)
                    <tr>
                        <td>{{ $employee }}</td>
                        <td style="text-align:right;">{{ number_format($totals['late_minutes'], 2) }}</td>
                        <td style="text-align:right;">{{ number_format($totals['undertime_minutes'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p>No records found.</p>
    @endforelse
</body>
</html>
