<table>
    <thead>
        <tr>
            <th
                colspan="9"
                style="text-align: center; font-weight: bold;"
            >
                {{ $company->name }}
            </th>
        </tr>

        <tr>
            <th
                colspan="9"
                style="text-align: center;"
            >
                Leave Utilization Summary
            </th>
        </tr>

        <tr>
            <th
                colspan="9"
                style="text-align: center;"
            >
                Period Covered: {{ $periodCovered }}
            </th>
        </tr>

        <tr>
            <td colspan="9"></td>
        </tr>

        <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Year</th>

            <th>
                Vacation Leave Opening Balance
            </th>

            <th>
                Vacation Leave Used
            </th>

            <th>
                Remaining
            </th>

            <th>
                Emergency Leave Opening Balance
            </th>

            <th>
                Emergency Leave Used
            </th>

            <th>
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

                <td>
                    {{ $row['year'] }}
                </td>

                <td>
                    {{ $row['vacation_opening'] }}
                </td>

                <td>
                    {{ $row['vacation_used'] }}
                </td>

                <td>
                    {{ $row['vacation_remaining'] }}
                </td>

                <td>
                    {{ $row['emergency_opening'] }}
                </td>

                <td>
                    {{ $row['emergency_used'] }}
                </td>

                <td>
                    {{ $row['emergency_remaining'] }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>