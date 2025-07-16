<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">{{ __('Leave Utilization Summary') }}</h2>
    </x-slot>

    <div class="container py-4">
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Year</label>
                <select name="year" class="form-select">
                    <option value="">All Years</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Filter</button>
                <a href="{{ route('reports.leave_utilization') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <div class="card shadow-sm">
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Year</th>
                            <th class="text-end">Beginning Balance</th>
                            <th class="text-end">Used</th>
                            <th class="text-end">Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaveBalances as $row)
                            <tr>
                                <td>{{ $row['employee_name'] }}</td>
                                <td>{{ $row['department'] }}</td>
                                <td>{{ $row['year'] }}</td>
                                <td class="text-end">{{ number_format($row['beginning'], 2) }}</td>
                                <td class="text-end text-danger">{{ number_format($row['used'], 2) }}</td>
                                <td class="text-end text-success">{{ number_format($row['remaining'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">No data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <!-- Download Buttons -->
                <div class="d-flex gap-2 mb-3">
                    <a id="pdfLink"
                    href="{{ route('reports.leave_utilization.pdf', request()->only('year', 'department_id')) }}"
                    class="btn btn-outline-primary">
                        Download PDF
                    </a>

                    <a id="excelLink"
                    href="{{ route('reports.leave_utilization.excel', request()->only('year', 'department_id')) }}"
                    class="btn btn-outline-success">
                        Export Excel
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
