<x-app-layout>
    @php
        $user = auth()->user();
        $company = $user->preference->company ?? null;
    @endphp

    <x-slot name="header">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <h2 class="h4 text-dark">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="container py-5">

        {{-- 🔍 Filter Form --}}
        <form method="GET" action="{{ url()->current() }}" class="row g-3 mb-4 align-items-end">
            <div class="col-auto">
                <label for="start_date" class="form-label small text-uppercase fw-semibold">{{ __('Start Date') }}</label>
                <input type="date" id="start_date" name="start_date" class="form-control"
                    value="{{ request('start_date', now()->startOfMonth()->toDateString()) }}">
            </div>

            <div class="col-auto">
                <label for="end_date" class="form-label small text-uppercase fw-semibold">{{ __('End Date') }}</label>
                <input type="date" id="end_date" name="end_date" class="form-control"
                    value="{{ request('end_date', now()->toDateString()) }}">
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
            </div>
        </form>

        {{-- 📊 Dashboard Cards --}}
        <div class="row g-4">
            {{-- 📊 Employee Cards --}}
            @if($user->hasAnyRole(['employee']))
                @php
                    $employeeCards = [
                        ['Leave Balance (days)', $employeeLeaveBalance ?? 0],
                        ['OT Hours Available for Offset', $employeeOffsetEligibleOtHours ?? 0],
                        ['Upcoming Leaves', $employeeUpcomingLeaves ?? 0],
                        ['Filed Overtime (hrs)', $employeeFiledOtHours ?? 0],
                        ['Late Time-ins', $employeeLateCount ?? 0],
                        ['Undertime Records', $employeeUndertimeCount ?? 0],
                    ];
                @endphp
                @foreach($employeeCards as [$title, $value])
                    <div class="col-sm-6 col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title text-muted small">{{ $title }}</h6>
                                <h3 class="fw-bold">{{ $value }}</h3>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

            {{-- 📊 HR Supervisor / Admin Cards --}}
            @if($user->hasAnyRole(['admin', 'hr supervisor']))
                @php
                    $adminCards = [
                        ['Pending Leave Requests', $pendingLeaveRequests ?? 0],
                        ['Pending OT Requests', $pendingOvertimeRequests ?? 0],
                        ['Pending Offset Requests', $pendingOffsetRequests ?? 0],
                        ['Pending Outbase Requests', $pendingOutbaseRequests ?? 0],
                        ['Pending Time Records', $pendingTimeRecords ?? 0],
                        ['Total OT Hours (Period)', $monthlyOtHours ?? 0],
                    ];
                @endphp
                @foreach($adminCards as [$title, $value])
                    <div class="col-sm-6 col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title text-muted small">{{ $title }}</h6>
                                <h3 class="fw-bold">{{ $value }}</h3>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- ➕ Employee Request Actions --}}
        @if($user->hasAnyRole(['employee']))
            <div class="mt-5 mb-4"> {{-- Adds space above --}}
                <div class="card">
                    <div class="card-header fw-bold text-uppercase small">
                        {{ __('Employee Requests') }}
                    </div>
                    <div class="card-body">
                        <div class="row gy-3 gx-4">
                            {{-- Leave --}}
                            <div class="col-md-6 col-lg-4 d-flex gap-2">
                                <a href="{{ route('leave_requests.create') }}" class="btn btn-outline-primary flex-fill">
                                    + Leave Request
                                </a>
                                <a href="{{ route('leave_requests.index') }}" class="btn btn-outline-secondary flex-fill">
                                    📄 View
                                </a>
                            </div>

                            {{-- Overtime --}}
                            <div class="col-md-6 col-lg-4 d-flex gap-2">
                                <a href="{{ route('overtime_requests.create') }}" class="btn btn-outline-primary flex-fill">
                                    + Overtime Request
                                </a>
                                <a href="{{ route('overtime_requests.index') }}" class="btn btn-outline-secondary flex-fill">
                                    📄 View
                                </a>
                            </div>

                            {{-- Offset --}}
                            <div class="col-md-6 col-lg-4 d-flex gap-2">
                                <a href="{{ route('offset_requests.create') }}" class="btn btn-outline-primary flex-fill">
                                    + Offset Request
                                </a>
                                <a href="{{ route('offset_requests.index') }}" class="btn btn-outline-secondary flex-fill">
                                    📄 View
                                </a>
                            </div>

                            {{-- Outbase --}}
                            <div class="col-md-6 col-lg-4 d-flex gap-2">
                                <a href="{{ route('outbase_requests.create') }}" class="btn btn-outline-primary flex-fill">
                                    + Outbase Request
                                </a>
                                <a href="{{ route('outbase_requests.index') }}" class="btn btn-outline-secondary flex-fill">
                                    📄 View
                                </a>
                            </div>

                            {{-- Time Records --}}
                            <div class="col-md-6 col-lg-4 d-flex gap-2">
                                <a href="{{ route('time_records.create') }}" class="btn btn-outline-primary flex-fill">
                                    + Time Record
                                </a>
                                <a href="{{ route('time_records.index') }}" class="btn btn-outline-info flex-fill">
                                    🕒 View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- 🕒 Pending Requests Overview --}}
        @if($user->hasAnyRole(['employee']))
            <div class="mt-5">
                <div class="card">
                    <div class="card-header fw-bold text-uppercase small">
                        {{ __('Pending Requests') }}
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Employee</th>
                                    <th>Date Filed</th>
                                    <th>Details</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Time Records --}}
                                @foreach($pendingTimeRecordList ?? [] as $record)
                                    <tr>
                                        <td>Time Record</td>
                                        <td>{{ $record->employee->user->name }}</td>
                                        <td>{{ $record->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $record->payrollPeriod->start_date }} to {{ $record->payrollPeriod->end_date }}</td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                        <td><a href="{{ route('time_records.show', $record) }}" class="btn btn-sm btn-outline-info">View</a></td>
                                    </tr>
                                @endforeach

                                {{-- Leave Requests --}}
                                @foreach($pendingLeaveRequestList ?? [] as $request)
                                    <tr>
                                        <td>Leave</td>
                                        <td>{{ $request->employee->user->name }}</td>
                                        <td>{{ $request->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $request->start_date }} to {{ $request->end_date }}</td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                        <td><a href="{{ route('leave_requests.show', $request) }}" class="btn btn-sm btn-outline-info">View</a></td>
                                    </tr>
                                @endforeach

                                {{-- Overtime Requests --}}
                                @foreach($pendingOvertimeRequestList ?? [] as $request)
                                    <tr>
                                        <td>Overtime</td>
                                        <td>{{ $request->employee->user->name }}</td>
                                        <td>{{ $request->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $request->date }} ({{ $request->number_of_hours }} hrs)</td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                        <td><a href="{{ route('overtime_requests.show', $request) }}" class="btn btn-sm btn-outline-info">View</a></td>
                                    </tr>
                                @endforeach

                                {{-- Offset Requests --}}
                                @foreach($pendingOffsetRequestList ?? [] as $request)
                                    <tr>
                                        <td>Offset</td>
                                        <td>{{ $request->employee->user->name }}</td>
                                        <td>{{ $request->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $request->date }} ({{ $request->number_of_hours }} hrs)</td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                        <td><a href="{{ route('offset_requests.show', $request) }}" class="btn btn-sm btn-outline-info">View</a></td>
                                    </tr>
                                @endforeach

                                {{-- Outbase Requests --}}
                                @foreach($pendingOutbaseRequestList ?? [] as $request)
                                    <tr>
                                        <td>Outbase</td>
                                        <td>{{ $request->employee->user->name }}</td>
                                        <td>{{ $request->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $request->date }} ({{ $request->time_start }} - {{ $request->time_end }})</td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                        <td><a href="{{ route('outbase_requests.show', $request) }}" class="btn btn-sm btn-outline-info">View</a></td>
                                    </tr>
                                @endforeach

                                @if(
                                    ($pendingTimeRecordList ?? collect())->isEmpty() &&
                                    ($pendingLeaveRequestList ?? collect())->isEmpty() &&
                                    ($pendingOvertimeRequestList ?? collect())->isEmpty() &&
                                    ($pendingOffsetRequestList ?? collect())->isEmpty() &&
                                    ($pendingOutbaseRequestList ?? collect())->isEmpty()
                                )
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No pending requests.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- 📝 Requests for Approval Overview --}}
        @if($user->hasAnyRole(['employee']))
            <div class="mt-5">
                <div class="card">
                    <div class="card-header fw-bold text-uppercase small">
                        {{ __('Requests for Your Approval') }}
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Employee</th>
                                    <th>Date Filed</th>
                                    <th>Details</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Time Records --}}
                                @foreach($forApprovalTimeRecordList ?? [] as $record)
                                    <tr>
                                        <td>Time Record</td>
                                        <td>{{ $record->employee->user->name }}</td>
                                        <td>{{ $record->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $record->payrollPeriod->start_date }} to {{ $record->payrollPeriod->end_date }}</td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                        <td><a href="{{ route('time_records.show', $record) }}" class="btn btn-sm btn-outline-info">View</a></td>
                                    </tr>
                                @endforeach

                                {{-- Leave Requests --}}
                                @foreach($forApprovalLeaveRequestList ?? [] as $request)
                                    <tr>
                                        <td>Leave</td>
                                        <td>{{ $request->employee->user->name }}</td>
                                        <td>{{ $request->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $request->start_date }} to {{ $request->end_date }}</td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                        <td><a href="{{ route('leave_requests.show', $request) }}" class="btn btn-sm btn-outline-info">View</a></td>
                                    </tr>
                                @endforeach

                                {{-- Overtime Requests --}}
                                @foreach($forApprovalOvertimeRequestList ?? [] as $request)
                                    <tr>
                                        <td>Overtime</td>
                                        <td>{{ $request->employee->user->name }}</td>
                                        <td>{{ $request->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $request->date }} ({{ $request->number_of_hours }} hrs)</td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                        <td><a href="{{ route('overtime_requests.show', $request) }}" class="btn btn-sm btn-outline-info">View</a></td>
                                    </tr>
                                @endforeach

                                {{-- Offset Requests --}}
                                @foreach($forApprovalOffsetRequestList ?? [] as $request)
                                    <tr>
                                        <td>Offset</td>
                                        <td>{{ $request->employee->user->name }}</td>
                                        <td>{{ $request->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $request->date }} ({{ $request->number_of_hours }} hrs)</td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                        <td><a href="{{ route('offset_requests.show', $request) }}" class="btn btn-sm btn-outline-info">View</a></td>
                                    </tr>
                                @endforeach

                                {{-- Outbase Requests --}}
                                @foreach($forApprovalOutbaseRequestList ?? [] as $request)
                                    <tr>
                                        <td>Outbase</td>
                                        <td>{{ $request->employee->user->name }}</td>
                                        <td>{{ $request->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $request->date }} ({{ $request->time_start }} - {{ $request->time_end }})</td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                        <td><a href="{{ route('outbase_requests.show', $request) }}" class="btn btn-sm btn-outline-info">View</a></td>
                                    </tr>
                                @endforeach

                                @if(
                                    ($forApprovalTimeRecordList ?? collect())->isEmpty() &&
                                    ($forApprovalLeaveRequestList ?? collect())->isEmpty() &&
                                    ($forApprovalOvertimeRequestList ?? collect())->isEmpty() &&
                                    ($forApprovalOffsetRequestList ?? collect())->isEmpty() &&
                                    ($forApprovalOutbaseRequestList ?? collect())->isEmpty()
                                )
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No requests awaiting your approval.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- 📈 Charts --}}
        @if($company && $user->hasAnyRole(['admin', 'hr supervisor']))
            <div class="row g-4 mt-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header fw-bold">{{ __('Leave Requests Status') }}</div>
                        <div class="card-body">
                            <canvas id="leaveChart" style="height:320px"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header fw-bold">{{ __('Employees per Department') }}</div>
                        <div class="card-body">
                            <canvas id="departmentChart" style="height:320px"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- 📊 Chart JS --}}
    <script>
        @if($company && $user->hasAnyRole(['admin', 'hr supervisor']))
        new Chart(document.getElementById('leaveChart'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Approved', 'Rejected'],
                datasets: [{
                    data: [
                        {{ $leaveStats['pending'] ?? 0 }},
                        {{ $leaveStats['approved'] ?? 0 }},
                        {{ $leaveStats['rejected'] ?? 0 }}
                    ],
                    backgroundColor: ['#facc15', '#22c55e', '#ef4444']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        new Chart(document.getElementById('departmentChart'), {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_keys($departmentEmployeeCounts ?? [])) !!},
                datasets: [{
                    label: 'Employees',
                    data: {!! json_encode(array_values($departmentEmployeeCounts ?? [])) !!},
                    backgroundColor: '#38bdf8'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
        @endif

        @if($user->hasAnyRole(['employee']))
        new Chart(document.getElementById('employeeLeaveTypeChart'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_keys($employeeLeaveStats ?? [])) !!},
                datasets: [{
                    data: {!! json_encode(array_values($employeeLeaveStats ?? [])) !!},
                    backgroundColor: ['#38bdf8', '#a78bfa', '#f87171', '#facc15', '#34d399', '#fb923c']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        new Chart(document.getElementById('employeeLogChart'), {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_keys($employeeLogStats ?? [])) !!},
                datasets: [{
                    label: 'Hours Worked',
                    data: {!! json_encode(array_values($employeeLogStats ?? [])) !!},
                    backgroundColor: '#22c55e'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
        @endif
    </script>
</x-app-layout>
