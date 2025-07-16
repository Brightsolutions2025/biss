<table>
    <tr>
        <td colspan="7" style="text-align: center; font-size: 14px;">
            {{ $employees[0]['company_name'] ?? 'Company Name' }}
        </td>
    </tr>
    <tr>
        <td colspan="7" style="text-align: center; font-weight: bold; font-size: 16px;">
            Overtime vs Offset Report
        </td>
    </tr>
    <tr>
        <td colspan="7" style="text-align: center; font-size: 12px;">
            As of: {{ \Carbon\Carbon::parse($asOf)->format('F j, Y') }}
        </td>
    </tr>
</table>

<br>

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
        @foreach ($employees as $row)
            <tr>
                <td>{{ $row['employee_name'] }}</td>
                <td>{{ $row['department'] }}</td>
                <td>{{ $row['overtime_hours'] }}</td>
                <td>{{ $row['expired_hours'] }}</td>
                <td>{{ $row['valid_overtime_hours'] }}</td>
                <td>{{ $row['offset_hours'] }}</td>
                <td>{{ $row['balance'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
