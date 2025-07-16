<table>
    <thead>
        <tr>
            <th colspan="4" style="text-align: center; font-weight: bold;">
                {{ auth()->user()->preference->company->name ?? 'Company Name' }}
            </th>
        </tr>
        <tr>
            <th colspan="4" style="text-align: center;">
                DTR Status Report by Team
            </th>
        </tr>
        <tr>
            <th colspan="4" style="text-align: center;">
                Period Covered: {{ \Carbon\Carbon::parse($payrollPeriod->start_date)->format('F d, Y') }}
                -
                {{ \Carbon\Carbon::parse($payrollPeriod->end_date)->format('F d, Y') }}
            </th>
        </tr>
        <tr><td colspan="4"></td></tr> <!-- spacer row -->
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
