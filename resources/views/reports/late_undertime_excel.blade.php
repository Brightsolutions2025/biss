<table>
    <thead>
        <tr>
            <th colspan="4" style="font-weight:bold; font-size:16px;">
                {{ $company->name ?? 'Company Name' }}
            </th>
        </tr>
        <tr>
            <th colspan="4" style="font-weight:bold;">
                Late and Undertime Report
            </th>
        </tr>
        <tr>
            <th colspan="4">
                Period Covered:
                {{ \Carbon\Carbon::parse($date_from)->format('F d, Y') }}
                -
                {{ \Carbon\Carbon::parse($date_to)->format('F d, Y') }}
            </th>
        </tr>
        <tr></tr> <!-- Empty row for spacing -->

        <tr>
            <th>Department</th>
            <th>Employee</th>
            <th>Late Minutes</th>
            <th>Undertime Minutes</th>
        </tr>
    </thead>
    <tbody>
        @foreach($grouped as $department => $employees)
            @foreach($employees as $employee => $totals)
                <tr>
                    <td>{{ $department }}</td>
                    <td>{{ $employee }}</td>
                    <td>{{ $totals['late_minutes'] }}</td>
                    <td>{{ $totals['undertime_minutes'] }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
