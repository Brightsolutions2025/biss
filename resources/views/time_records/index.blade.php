<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">
            {{ __('Time Records') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <div class="card shadow-sm">
                    <div class="card-body">

                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
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

                        {{-- Filter Form --}}
                        <div class="mb-4">
                            <h5 class="mb-3">Search</h5>
                            <form method="GET" action="{{ route('time_records.index') }}" class="row g-3 align-items-end">

                                <div class="col-md-3">
                                    <label for="employee_id" class="form-label">Employee</label>
                                    <select name="employee_id" id="employee_id" class="form-select">
                                        <option value="">-- All Employees --</option>
                                        @foreach ($employeeList as $employee)
                                            <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                                {{ $employee->first_name }} {{ $employee->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="">-- All Statuses --</option>
                                        @foreach(['pending', 'approved', 'rejected'] as $status)
                                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                                {{ ucfirst($status) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="payroll_period_id" class="form-label">Payroll Period</label>
                                    <select name="payroll_period_id" id="payroll_period_id" class="form-select">
                                        <option value="">-- All Periods --</option>
                                        @foreach ($payrollPeriods as $period)
                                            <option value="{{ $period->id }}" {{ request('payroll_period_id') == $period->id ? 'selected' : '' }}>
                                                {{ $period->start_date }} - {{ $period->end_date }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary">Search</button>
                                </div>
                            </form>
                        </div>


                        <h5 class="fw-bold mt-4">Add</h5>
                        <p>
                            Want to add a new time record? Click
                            <a href="{{ route('time_records.create') }}" class="link-primary">here</a>!
                        </p>

                        <h5 class="fw-bold mt-4 mb-3">List</h5>

                        @forelse ($timeRecords as $timeRecord)
                            <div class="card mb-3">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <div><strong>Employee:</strong> {{ $timeRecord->employee->last_name }}, {{ $timeRecord->employee->first_name }}</div>
                                        <div><strong>Period:</strong> {{ $timeRecord->payrollPeriod->start_date }} – {{ $timeRecord->payrollPeriod->end_date }}</div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('time_records.show', $timeRecord->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="{{ route('time_records.edit', $timeRecord->id) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                        <form method="POST" action="{{ route('time_records.destroy', $timeRecord->id) }}" onsubmit="return confirm('Are you sure you want to delete this time record?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">No time records found.</p>
                        @endforelse

                        @php
                            $currentPage = $timeRecords->currentPage();
                            $lastPage = $timeRecords->lastPage();
                            $linkCount = 5;
                            $half = floor($linkCount / 2);

                            $start = max(1, $currentPage - $half);
                            $end = min($lastPage, $start + $linkCount - 1);

                            if ($end - $start < $linkCount - 1) {
                                $start = max(1, $end - $linkCount + 1);
                            }
                        @endphp

                        @if ($timeRecords->hasPages())
                            <div class="mt-4">
                                <div class="d-flex justify-content-center">
                                    <nav>
                                        <ul class="pagination pagination-sm flex-wrap justify-content-center">

                                            <!-- Previous -->
                                            <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                                <a class="page-link" href="{{ $timeRecords->previousPageUrl() ?? '#' }}">&laquo;</a>
                                            </li>

                                            <!-- First page & Ellipsis -->
                                            @if ($start > 1)
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $timeRecords->url(1) }}">1</a>
                                                </li>
                                                @if ($start > 2)
                                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                                @endif
                                            @endif

                                            <!-- Page Numbers -->
                                            @for ($i = $start; $i <= $end; $i++)
                                                <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                                    <a class="page-link" href="{{ $timeRecords->url($i) }}">{{ $i }}</a>
                                                </li>
                                            @endfor

                                            <!-- Ellipsis & Last Page -->
                                            @if ($end < $lastPage)
                                                @if ($end < $lastPage - 1)
                                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                                @endif
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $timeRecords->url($lastPage) }}">{{ $lastPage }}</a>
                                                </li>
                                            @endif

                                            <!-- Next -->
                                            <li class="page-item {{ !$timeRecords->hasMorePages() ? 'disabled' : '' }}">
                                                <a class="page-link" href="{{ $timeRecords->nextPageUrl() ?? '#' }}">&raquo;</a>
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
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            .select2-container--default .select2-selection--single {
                height: calc(2.5rem + 2px);
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
                $('#employee_id').select2({
                    placeholder: 'Select employee...',
                    allowClear: true,
                    width: '100%'
                });
                $('#status').select2({
                    placeholder: 'Select status...',
                    allowClear: true,
                    width: '100%'
                });
                $('#payroll_period_id').select2({
                    placeholder: 'Select period...',
                    allowClear: true,
                    width: '100%'
                });
            });
        </script>
    @endpush


</x-app-layout>
