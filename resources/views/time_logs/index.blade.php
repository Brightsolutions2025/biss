<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">
            {{ __('Time Logs') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Search Form -->
                <div class="mb-4">
                    <h3 class="h5">Search</h3>
                    <form method="GET" action="{{ route('time_logs.index') }}" class="row g-3 align-items-end">

                        <div class="col-md-6">
                            <label for="employee_name" class="form-label">Employee Name</label>
                            <select name="employee_name" id="employee_name" class="form-select">
                                <option value="">-- All Employees --</option>
                                @foreach ($employeeNames as $name)
                                    <option value="{{ $name }}" {{ request('employee_name') == $name ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="department_name" class="form-label">Department</label>
                            <select name="department_name" id="department_name" class="form-select">
                                <option value="">-- All Departments --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}" {{ request('department_name') == $dept ? 'selected' : '' }}>
                                        {{ $dept }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="payroll_period_id" class="form-label">Payroll Period</label>
                            <select name="payroll_period_id" id="payroll_period_id" class="form-select">
                                <option value="">-- All Periods --</option>
                                @foreach($payrollPeriods as $period)
                                    <option value="{{ $period->id }}" {{ request('payroll_period_id') == $period->id ? 'selected' : '' }}>
                                        {{ $period->start_date }} - {{ $period->end_date }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="sort" class="form-label">Sort By</label>
                            <select name="sort" id="sort" class="form-select">
                                <option value="date_desc" {{ request('sort') == 'date_desc' ? 'selected' : '' }}>Date (Newest First)</option>
                                <option value="date_asc" {{ request('sort') == 'date_asc' ? 'selected' : '' }}>Date (Oldest First)</option>
                                <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Employee Name (A-Z)</option>
                                <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Employee Name (Z-A)</option>
                            </select>
                        </div>

                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </form>

                </div>

                <!-- Add Link -->
                <div class="mb-4">
                    <h3 class="h5">Add</h3>
                    <p>
                        Want to add a new time log? Click
                        <a href="{{ route('time_logs.create') }}" class="link-primary">here</a>!
                    </p>
                </div>

                <!-- Time Logs List -->
                <div class="mb-4">
                    <h3 class="h5">List</h3>

                    @forelse ($timeLogs as $timeLog)
                        <div class="card mb-4">
                            <div class="card-body">

                                <!-- Action Buttons -->
                                <div class="mb-3 d-flex flex-wrap gap-2">
                                    <a href="{{ route('time_logs.show', $timeLog->id) }}" class="btn btn-outline-primary btn-sm">View</a>
                                    <a href="{{ route('time_logs.edit', $timeLog->id) }}" class="btn btn-outline-success btn-sm">Edit</a>

                                    <!-- Delete -->
                                    <form method="POST" action="{{ route('time_logs.destroy', $timeLog->id) }}" class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this time log?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                    </form>

                                    <!-- Batch Delete -->
                                    <form method="POST" action="{{ route('time_logs.batch-delete') }}" class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete all time logs with this payroll period?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="payroll_period_ids[]" value="{{ $timeLog->payroll_period_id }}">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Batch Delete</button>
                                    </form>
                                </div>

                                <!-- Time Log Details -->
                                <div class="row g-3">
                                    <div class="col-md-6"><strong>Employee Name:</strong> {{ $timeLog->employee_name }}</div>
                                    <div class="col-md-6"><strong>Department:</strong> {{ $timeLog->department_name }}</div>
                                    <div class="col-md-6"><strong>Employee ID:</strong> {{ $timeLog->employee_id }}</div>
                                    <div class="col-md-6"><strong>Employee Type:</strong> {{ $timeLog->employee_type }}</div>
                                    <div class="col-md-6"><strong>Attendance Group:</strong> {{ $timeLog->attendance_group }}</div>
                                    <div class="col-md-6"><strong>Date:</strong> {{ $timeLog->date }}</div>
                                    <div class="col-md-6"><strong>Weekday:</strong> {{ $timeLog->weekday }}</div>
                                    <div class="col-md-6"><strong>Shift:</strong> {{ $timeLog->shift }}</div>
                                    <div class="col-md-6"><strong>Attendance Time:</strong> {{ $timeLog->attendance_time }}</div>
                                    <div class="col-md-6"><strong>About the Record:</strong> {{ $timeLog->about_the_record }}</div>
                                    <div class="col-md-6"><strong>Attendance Result:</strong> {{ $timeLog->attendance_result }}</div>
                                    <div class="col-md-6"><strong>Attendance Address:</strong> {{ $timeLog->attendance_address }}</div>
                                    <div class="col-md-6"><strong>Note:</strong> {{ $timeLog->note ?? '-' }}</div>
                                    <div class="col-md-6"><strong>Attendance Method:</strong> {{ $timeLog->attendance_method }}</div>
                                    <div class="col-md-6">
                                        <strong>Attendance Photo:</strong>
                                        <a href="{{ $timeLog->attendance_photo }}" target="_blank" class="text-decoration-underline">View Photo</a>
                                    </div>
                                </div>

                            </div>x
                        </div>
                    @empty
                        <div class="text-muted">No time logs recorded yet.</div>
                    @endforelse

                    @php
                        $currentPage = $timeLogs->currentPage();
                        $lastPage = $timeLogs->lastPage();
                        $linkCount = 5;
                        $half = floor($linkCount / 2);

                        $start = max(1, $currentPage - $half);
                        $end = min($lastPage, $start + $linkCount - 1);

                        if ($end - $start < $linkCount - 1) {
                            $start = max(1, $end - $linkCount + 1);
                        }
                    @endphp

                    <!-- Pagination -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-center">
                            <nav>
                                <ul class="pagination pagination-sm flex-wrap justify-content-center">

                                    <!-- Previous -->
                                    <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $timeLogs->previousPageUrl() ?? '#' }}">&laquo;</a>
                                    </li>

                                    <!-- First page & Ellipsis -->
                                    @if ($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $timeLogs->url(1) }}">1</a>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                    @endif

                                    <!-- Page Numbers -->
                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $timeLogs->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    <!-- Ellipsis & Last Page -->
                                    @if ($end < $lastPage)
                                        @if ($end < $lastPage - 1)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $timeLogs->url($lastPage) }}">{{ $lastPage }}</a>
                                        </li>
                                    @endif

                                    <!-- Next -->
                                    <li class="page-item {{ !$timeLogs->hasMorePages() ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $timeLogs->nextPageUrl() ?? '#' }}">&raquo;</a>
                                    </li>

                                </ul>
                            </nav>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            .select2-container--default .select2-selection--single {
                height: calc(2.5rem + 2px); /* Bootstrap .form-select height */
                padding: 0.375rem 0.75rem;
                border: 1px solid #ced4da;
                border-radius: 0.375rem;
            }

            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 2rem;
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 2.5rem;
                right: 10px;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                $('#employee_name').select2({
                    placeholder: 'Search employee...',
                    allowClear: true,
                    width: '100%',
                    tags: true
                });

                $('#department_name').select2({
                    placeholder: 'Select department...',
                    allowClear: true,
                    width: '100%'
                });

                $('#payroll_period_id').select2({
                    placeholder: 'Select payroll period...',
                    allowClear: true,
                    width: '100%'
                });
            });
        </script>
    @endpush
</x-app-layout>
