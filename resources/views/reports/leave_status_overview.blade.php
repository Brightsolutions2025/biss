<x-app-layout>
    <style>
        .select2-container {
            width: 100% !important;
        }

        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
            display: flex;
            align-items: center;
        }

        .select2-container--bootstrap-5 .select2-selection__rendered {
            line-height: 1.5;
            padding-left: 0;
        }
    </style>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">{{ __('Leave Requests by Status') }}</h2>
    </x-slot>

    <div class="container py-4">
        {{-- Filter Form --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date From</label>
                        <input type="date" name="date_from" class="form-control"
                            value="{{ request('date_from', now()->startOfMonth()->toDateString()) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date To</label>
                        <input type="date" name="date_to" class="form-control"
                            value="{{ request('date_to', now()->endOfMonth()->toDateString()) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Department</label>
                        <select name="department_id" class="form-select select2">
                            <option value="">All</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Employee</label>
                        <select name="employee_id" class="form-select select2">
                            <option value="">All</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" @selected(request('employee_id') == $emp->id)>
                                    {{ $emp->user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Approver</label>
                        <select name="approver_id" class="form-select select2">
                            <option value="">All</option>
                            @foreach(\App\Models\User::whereIn('id', \App\Models\LeaveRequest::distinct()->pluck('approver_id'))->get() as $approver)
                                <option value="{{ $approver->id }}" @selected(request('approver_id') == $approver->id)>
                                    {{ $approver->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 ms-auto text-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel-fill me-1"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Download Buttons --}}
        <div class="d-flex gap-2 mb-3 justify-content-end">
            <a href="{{ route('reports.leave_status_overview.pdf', request()->only('date_from', 'date_to', 'department_id', 'employee_id', 'approver_id')) }}"
                class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF
            </a>

            <a href="{{ route('reports.leave_status_overview.excel', request()->only('date_from', 'date_to', 'department_id', 'employee_id', 'approver_id')) }}"
                class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel
            </a>
        </div>

        {{-- Report Output --}}
        @forelse($statusCounts as $department => $employeeData)
            <div class="card mb-4">
                <div class="card-header fw-bold bg-light">{{ $department }}</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="w-25">Employee</th>
                                    <th class="w-25 text-end text-warning">Pending</th>
                                    <th class="w-25 text-end text-success">Approved</th>
                                    <th class="w-25 text-end text-danger">Rejected</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employeeData as $employee => $counts)
                                    <tr>
                                        <td class="w-25">{{ $employee }}</td>
                                        <td class="w-25 text-end">{{ $counts['pending'] }}</td>
                                        <td class="w-25 text-end">{{ $counts['approved'] }}</td>
                                        <td class="w-25 text-end">{{ $counts['rejected'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info">No leave requests found for the selected criteria.</div>
        @endforelse
    </div>

    @push('styles')
        {{-- Optional Bootstrap Icons --}}
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            .select2-container--bootstrap-5 .select2-selection {
                min-height: 38px;
                padding: 0.375rem 0.75rem;
                border: 1px solid #ced4da;
                border-radius: 0.375rem;
            }
            .select2-container--bootstrap-5 .select2-selection__rendered {
                line-height: 1.5;
            }
            .select2-container {
                width: 100% !important;
                z-index: 9999;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                $('.select2').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Select an option',
                    allowClear: true
                });
            });
        </script>
    @endpush

</x-app-layout>
