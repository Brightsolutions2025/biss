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
        <h2 class="h4 fw-semibold text-dark">{{ __('Outbase Request Summary') }}</h2>
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
                        <label class="form-label fw-semibold">Location</label>
                        <select name="location" class="form-select select2">
                            <option value="">All</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc }}" @selected(request('location') == $loc)>
                                    {{ $loc }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Sort By</label>
                        <select name="sort" class="form-select">
                            <option value="">Default</option>
                            <option value="employee_asc" @selected(request('sort') == 'employee_asc')>Employee A–Z</option>
                            <option value="employee_desc" @selected(request('sort') == 'employee_desc')>Employee Z–A</option>
                            <option value="count_asc" @selected(request('sort') == 'count_asc')>Least Outbase</option>
                            <option value="count_desc" @selected(request('sort') == 'count_desc')>Most Outbase</option>
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
        <div class="d-flex gap-2 mb-3">
            <a id="pdfLink"
                href="{{ route('reports.outbase_summary.pdf', request()->only('date_from', 'date_to', 'department_id', 'employee_id', 'location', 'sort')) }}"
                class="btn btn-outline-primary">
                Download PDF
            </a>

            <a id="excelLink"
                href="{{ route('reports.outbase_summary.excel', request()->only('date_from', 'date_to', 'department_id', 'employee_id', 'location', 'sort')) }}"
                class="btn btn-outline-success">
                Export Excel
            </a>
        </div>

        {{-- Report Table --}}
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Employee</th>
                                <th class="text-end">Outbase Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $row)
                                <tr>
                                    <td>{{ $row['department'] }}</td>
                                    <td>{{ $row['employee'] }}</td>
                                    <td class="text-end">{{ $row['outbase_count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Styles & Scripts --}}
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
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
