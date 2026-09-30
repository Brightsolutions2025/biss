<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">{{ __('Leave Summary Report') }}</h2>
    </x-slot>

    <div class="container py-4">
        <!-- Year Filter -->
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Year</label>
                <select name="year" class="form-select" onchange="this.form.submit()">
                    @for ($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </form>

        <!-- Summary Table -->
        <div class="table-responsive mb-5">
            <h5 class="fw-bold">Summary</h5>
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Team</th>
                        <th>Approver</th>
                        <th>Beginning Balance</th>
                        <th>Used</th>
                        <th>Remaining</th>
                        <th>Utilization (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaveBalances as $row)
                        <tr>
                            <td>{{ $row['employee_name'] }}</td>
                            <td>{{ $row['department_name'] ?? '—' }}</td>
                            <td>{{ $row['team_name'] ?? '—' }}</td>
                            <td>{{ $row['approver_name'] ?? '—' }}</td>
                            <td>{{ number_format($row['beginning_balance'], 2) }}</td>
                            <td>{{ number_format($row['used'], 2) }}</td>
                            <td>{{ number_format($row['remaining'], 2) }}</td>
                            <td>{{ number_format($row['utilization'], 1) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Detailed Used Leaves Table -->
        <div>
            <h5 class="fw-bold">Approved Leave Requests ({{ $year }})</h5>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Number of Days</th>
                            <th>Reason</th>
                            <th>Approval Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leaveDetails as $leave)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($leave->start_date)->toFormattedDateString() }}</td>
                                <td>{{ \Carbon\Carbon::parse($leave->end_date)->toFormattedDateString() }}</td>
                                <td>{{ number_format($leave->number_of_days, 2) }}</td>
                                <td>{{ $leave->reason }}</td>
                                <td>{{ $leave->approval_date ? \Carbon\Carbon::parse($leave->approval_date)->toFormattedDateString() : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No approved leave records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <!-- Download Buttons -->
                <div class="d-flex gap-2 mb-3">
                    <a id="pdfLink"
                        href="{{ route('reports.leave_summary.pdf', request()->only('year')) }}"
                        class="btn btn-outline-primary">
                        Download PDF
                    </a>

                    <a id="excelLink"
                        href="{{ route('reports.leave_summary.excel', request()->only('year')) }}"
                        class="btn btn-outline-success">
                        Export Excel
                    </a>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
