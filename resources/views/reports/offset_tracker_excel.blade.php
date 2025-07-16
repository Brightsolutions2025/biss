<table>
    <thead>
        <tr>
            <th colspan="7" style="font-weight: bold; font-size: 16px;">{{ $companyName ?? 'Company Name' }}</th>
        </tr>
        <tr>
            <th colspan="7" style="font-weight: bold; font-size: 14px;">Offset Usage and Expiry Tracker</th>
        </tr>
        <tr>
            <th colspan="7">
                <strong>Period Covered:</strong> {{ $periodText ?? 'All Dates' }}
            </th>
        </tr>
        <tr>
            <th>Employee</th>
            <th>Overtime Date</th>
            <th>Approved Hours</th>
            <th>Used Hours</th>
            <th>Remaining</th>
            <th>Expiry Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($offsetData as $row)
            <tr>
                <td>{{ $row['employee_name'] }}</td>
                <td>{{ $row['date'] }}</td>
                <td>{{ number_format($row['approved_hours'], 2) }}</td>
                <td>{{ number_format($row['used_hours'], 2) }}</td>
                <td>{{ number_format($row['remaining_hours'], 2) }}</td>
                <td>{{ $row['expires_at'] ?? 'N/A' }}</td>
                <td>
                    @if ($row['expired'] && $row['remaining_hours'] > 0)
                        Expired
                    @elseif ($row['remaining_hours'] <= 0)
                        Fully Used
                    @else
                        Active
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
