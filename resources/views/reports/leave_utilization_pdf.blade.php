<table>
    <thead>
        <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Year</th>

            <th class="text-right">
                Vacation Leave Opening Balance
            </th>

            <th class="text-right">
                Vacation Leave Used
            </th>

            <th class="text-right">
                Remaining
            </th>

            <th class="text-right">
                Emergency Leave Opening Balance
            </th>

            <th class="text-right">
                Emergency Leave Used
            </th>

            <th class="text-right">
                Remaining
            </th>
        </tr>
    </thead>

    <tbody>
        @foreach ($leaveBalances as $row)
            <tr>
                <td>
                    {{ $row['employee_name'] }}
                </td>

                <td>
                    {{ $row['department'] }}
                </td>

                <td class="text-center">
                    {{ $row['year'] }}
                </td>

                <td class="text-right">
                    {{ number_format(
                        $row['vacation_opening'],
                        2
                    ) }}
                </td>

                <td class="text-right">
                    {{ number_format(
                        $row['vacation_used'],
                        2
                    ) }}
                </td>

                <td class="text-right">
                    {{ number_format(
                        $row['vacation_remaining'],
                        2
                    ) }}
                </td>

                <td class="text-right">
                    {{ number_format(
                        $row['emergency_opening'],
                        2
                    ) }}
                </td>

                <td class="text-right">
                    {{ number_format(
                        $row['emergency_used'],
                        2
                    ) }}
                </td>

                <td class="text-right">
                    {{ number_format(
                        $row['emergency_remaining'],
                        2
                    ) }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>