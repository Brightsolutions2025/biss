<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Employee Shifts') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

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
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Search</h5>
                        <form method="GET" action="{{ route('employee_shifts.index') }}">
                            <div class="mb-3">
                                <label for="employee_name" class="form-label">Employee Name</label>
                                <input 
                                    type="text" 
                                    id="employee_name" 
                                    name="employee_name" 
                                    class="form-control" 
                                    value="{{ request('employee_name') }}" 
                                    placeholder="Enter employee name">
                            </div>
                            <button type="submit" class="btn btn-primary">Search</button>
                        </form>
                    </div>
                </div>

                <!-- Assign Shift -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Assign Shift</h5>
                        <p class="card-text">
                            Want to assign a shift to an employee? Click 
                            <a href="{{ route('employee_shifts.create') }}" class="text-decoration-underline">here</a>!
                        </p>
                    </div>
                </div>

                <!-- List Section -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Shift Assignments</h5>

                        @forelse ($employeeShifts as $employeeShift)
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start flex-wrap">
                                    <div>
                                        <strong>{{ $employeeShift->employee->last_name }}, {{ $employeeShift->employee->first_name }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            ({{ $employeeShift->employee->employee_number }})
                                        </small>
                                        <div class="text-secondary mt-1">
                                            Shift: <strong>{{ $employeeShift->shift->name }}</strong>
                                            ({{ \Carbon\Carbon::parse($employeeShift->shift->time_in)->format('h:i A') }} - 
                                            {{ \Carbon\Carbon::parse($employeeShift->shift->time_out)->format('h:i A') }})
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
                                        <a href="{{ route('employee_shifts.show', $employeeShift->id) }}" class="btn btn-outline-primary me-2">View</a>
                                        <a href="{{ route('employee_shifts.edit', $employeeShift->id) }}" class="btn btn-outline-warning me-2">Edit</a>
                                        <form method="POST" action="{{ route('employee_shifts.destroy', $employeeShift->id) }}" onsubmit="return confirm('Are you sure you want to delete this assignment?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">No employee shift assignments recorded yet.</p>
                        @endforelse

                        <!-- Pagination -->
                        @php
                            $currentPage = $employeeShifts->currentPage();
                            $lastPage = $employeeShifts->lastPage();
                            $linkCount = 5;
                            $half = floor($linkCount / 2);

                            $start = max(1, $currentPage - $half);
                            $end = min($lastPage, $start + $linkCount - 1);

                            if ($end - $start < $linkCount - 1) {
                                $start = max(1, $end - $linkCount + 1);
                            }
                        @endphp

                        @if ($employeeShifts->hasPages())
                            <div class="mt-4">
                                <div class="d-flex justify-content-center">
                                    <nav>
                                        <ul class="pagination pagination-sm flex-wrap justify-content-center">

                                            <!-- Previous -->
                                            <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                                <a class="page-link" href="{{ $employeeShifts->previousPageUrl() ?? '#' }}">&laquo;</a>
                                            </li>

                                            <!-- First page & Ellipsis -->
                                            @if ($start > 1)
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $employeeShifts->url(1) }}">1</a>
                                                </li>
                                                @if ($start > 2)
                                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                                @endif
                                            @endif

                                            <!-- Page Numbers -->
                                            @for ($i = $start; $i <= $end; $i++)
                                                <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                                    <a class="page-link" href="{{ $employeeShifts->url($i) }}">{{ $i }}</a>
                                                </li>
                                            @endfor

                                            <!-- Ellipsis & Last Page -->
                                            @if ($end < $lastPage)
                                                @if ($end < $lastPage - 1)
                                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                                @endif
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $employeeShifts->url($lastPage) }}">{{ $lastPage }}</a>
                                                </li>
                                            @endif

                                            <!-- Next -->
                                            <li class="page-item {{ !$employeeShifts->hasMorePages() ? 'disabled' : '' }}">
                                                <a class="page-link" href="{{ $employeeShifts->nextPageUrl() ?? '#' }}">&raquo;</a>
                                            </li>

                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
