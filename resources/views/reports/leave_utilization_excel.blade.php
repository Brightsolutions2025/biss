<table>
    <thead>
        <tr>
            <th colspan="6" style="text-align: center; font-weight: bold;">{{ $company->name }}</th>
        </tr>
        <tr>
            <th colspan="6" style="text-align: center;">Leave Utilization Summary</th>
        </tr>
        <tr>
            <th colspan="6" style="text-align: center;">Period Covered: {{ $periodCovered }}</th>
        </tr>
        <tr><td colspan="6"></td></tr>
        <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Year</th>
            <th>Beginning Balance</th>
            <th>Used</th>
            <th>Remaining</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($leaveBalances as $row)
            <tr>
                <td>{{ $row['employee_name'] }}</td>
                <td>{{ $row['department'] }}</td>
                <td>{{ $row['year'] }}</td>
                <td>{{ $row['beginning'] }}</td>
                <td>{{ $row['used'] }}</td>
                <td>{{ $row['remaining'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
