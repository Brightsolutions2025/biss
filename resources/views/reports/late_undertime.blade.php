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
        <h2 class="h4 fw-semibold text-dark">{{ __('Late and Undertime Report') }}</h2>
    </x-slot>

    <div class="container py-4">
        <form method="GET" class="row g-2 mb-4">
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
                    @foreach(\App\Models\Department::where('company_id', auth()->user()->preference->company_id)->get() as $dept)
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
                    @foreach(\App\Models\Employee::with('user')->where('company_id', auth()->user()->preference->company_id)->get() as $emp)
                        <option value="{{ $emp->id }}" @selected(request('employee_id') == $emp->id)>
                            {{ $emp->user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        <!-- Download Buttons -->
        <div class="d-flex gap-2 mb-3">
            <a id="pdfLink"
                href="{{ route('reports.late_undertime.pdf', request()->only('date_from', 'date_to', 'department_id', 'employee_id')) }}"
                class="btn btn-outline-primary">
                Download PDF
            </a>

            <a id="excelLink"
                href="{{ route('reports.late_undertime.excel', request()->only('date_from', 'date_to', 'department_id', 'employee_id')) }}"
                class="btn btn-outline-success">
                Export Excel
            </a>
        </div>

        @forelse($grouped as $department => $employees)
            <div class="card mb-4">
                <div class="card-header fw-bold bg-light">{{ $department }}</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th class="text-end">Late Minutes</th>
                                    <th class="text-end">Undertime Minutes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employees as $employeeName => $totals)
                                    <tr>
                                        <td>{{ $employeeName }}</td>
                                        <td class="text-end text-danger">{{ number_format($totals['late_minutes'], 2) }}</td>
                                        <td class="text-end text-warning">{{ number_format($totals['undertime_minutes'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info">No late or undertime records found for the selected period.</div>
        @endforelse
    </div>
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            .select2-container--bootstrap-5 .select2-selection {
                min-height: 38px;
                padding: 0.375rem 0.75rem;
                border: 1px solid #ced4da;
                border-radius: 0.375rem;
            }

            /* Fix absolute positioning in modals or form layouts */
            .select2-container {
                z-index: 1055;
            }
        </style>
    @endpush
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                $('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'Select an option',
                    allowClear: true
                });
            });
        </script>
    @endpush
</x-app-layout>
