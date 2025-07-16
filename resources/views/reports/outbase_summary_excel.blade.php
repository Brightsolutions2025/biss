<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #999;
            padding: 8px;
            text-align: left;
        }

        thead {
            background-color: #f2f2f2;
        }

        h2, h4 {
            margin: 0;
        }

        .report-header {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h2>{{ $company }}</h2>
        <h4>Outbase Request Summary Report</h4>
        <p>{{ $period }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Department</th>
                <th>Employee</th>
                <th style="text-align: right;">Outbase Count</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    <td>{{ $row['department'] }}</td>
                    <td>{{ $row['employee'] }}</td>
                    <td style="text-align: right;">{{ $row['outbase_count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
