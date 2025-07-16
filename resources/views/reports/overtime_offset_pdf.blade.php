<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Overtime vs Offset Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }

        .text-center {
            text-align: center;
        }

        .fw-bold {
            font-weight: bold;
        }

        .mt-2 { margin-top: 0.5rem; }
        .mt-4 { margin-top: 1rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1.5rem; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            border: 1px solid #666;
            padding: 6px;
            text-align: right;
        }

        th:first-child, td:first-child,
        th:nth-child(2), td:nth-child(2) {
            text-align: left;
        }

        thead {
            background-color: #f0f0f0;
        }

        .note {
            font-size: 11px;
            color: #555;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <p class="text-center mb-2">
        {{ $employees[0]['company_name'] ?? 'Company Name' }}
    </p>
    <h2 class="text-center fw-bold">Overtime vs Offset Report</h2>
    <p class="text-center mb-4">
        <strong>As of:</strong> {{ \Carbon\Carbon::parse($asOf)->format('F j, Y') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Total OT</th>
                <th>Expired OT</th>
                <th>Valid OT</th>
                <th>Offset Used</th>
                <th>Remaining Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($employees as $row)
                <tr>
                    <td>{{ $row['employee_name'] }}</td>
                    <td>{{ $row['department'] }}</td>
                    <td>{{ number_format($row['overtime_hours'], 2) }}</td>
                    <td>{{ number_format($row['expired_hours'], 2) }}</td>
                    <td>{{ number_format($row['valid_overtime_hours'], 2) }}</td>
                    <td>{{ number_format($row['offset_hours'], 2) }}</td>
                    <td>{{ number_format($row['balance'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="note">
        Note: Remaining Balance = Valid Overtime – Offset Used. Expired OT is not usable.
    </p>

</body>
</html>
