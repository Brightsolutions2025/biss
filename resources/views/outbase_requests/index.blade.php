<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">{{ __('Outbase Requests') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                
                {{-- Flash Messages --}}
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

                {{-- Filter Form --}}
                <div class="mb-4">
                    <h5 class="mb-3">Search</h5>
                    <form method="GET" action="{{ route('outbase_requests.index') }}" class="row g-3 align-items-end">

                        {{-- Employee --}}
                        <div class="col-md-4">
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

                        {{-- Status --}}
                        <div class="col-md-4">
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

                        {{-- Date Range --}}
                        <div class="col-md-4">
                            <label class="form-label">Date Range</label>
                            <div class="d-flex gap-2">
                                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </form>
                </div>



                {{-- Add New --}}
                <div class="mb-4">
                    <h5>Submit</h5>
                    <p>Need to file an outbase request? Click 
                        <a href="{{ route('outbase_requests.create') }}" class="link-primary">here</a>!
                    </p>
                </div>

                {{-- Request List --}}
                <div>
                    <h5 class="mb-3">List</h5>

                    @forelse ($outbaseRequests as $request)
                        <div class="card mb-3">
                            <div class="card-body d-flex flex-column flex-md-row justify-content-between">
                                <div>
                                    <h6 class="card-title mb-1">
                                        {{ $request->employee->first_name }} {{ $request->employee->last_name }}
                                        ({{ $request->employee->employee_number }})
                                    </h6>
                                    <p class="mb-1">
                                        {{ \Carbon\Carbon::parse($request->date)->toFormattedDateString() }}<br>
                                        {{ $request->time_start }} to {{ $request->time_end }}
                                    </p>
                                    <p class="mb-1"><strong>Location:</strong> {{ $request->location }}</p>
                                    <span class="badge 
                                        {{ $request->status == 'approved' ? 'bg-success' : 
                                           ($request->status == 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                </div>
                                <div class="d-flex flex-column flex-md-row align-items-start gap-2 mt-3 mt-md-0">
                                    <a href="{{ route('outbase_requests.show', $request) }}" class="btn btn-outline-primary btn-sm">View</a>
                                    <a href="{{ route('outbase_requests.edit', $request) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                                    <form method="POST" action="{{ route('outbase_requests.destroy', $request) }}" onsubmit="return confirm('Are you sure you want to delete this request?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No outbase requests recorded yet.</p>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if ($outbaseRequests->lastPage() > 1)
                    @php
                        $currentPage = $outbaseRequests->currentPage();
                        $lastPage = $outbaseRequests->lastPage();
                        $linkCount = 5;
                        $half = floor($linkCount / 2);
                        $start = max(1, $currentPage - $half);
                        $end = min($lastPage, $start + $linkCount - 1);
                        if ($end - $start < $linkCount - 1) {
                            $start = max(1, $end - $linkCount + 1);
                        }
                    @endphp
                    <div class="mt-4 d-flex justify-content-center">
                        <nav>
                            <ul class="pagination pagination-sm flex-wrap justify-content-center">
                                <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ $outbaseRequests->previousPageUrl() ?? '#' }}">&laquo;</a>
                                </li>
                                @if ($start > 1)
                                    <li class="page-item"><a class="page-link" href="{{ $outbaseRequests->url(1) }}">1</a></li>
                                    @if ($start > 2)
                                        <li class="page-item disabled"><span class="page-link">…</span></li>
                                    @endif
                                @endif
                                @for ($i = $start; $i <= $end; $i++)
                                    <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                        <a class="page-link" href="{{ $outbaseRequests->url($i) }}">{{ $i }}</a>
                                    </li>
                                @endfor
                                @if ($end < $lastPage)
                                    @if ($end < $lastPage - 1)
                                        <li class="page-item disabled"><span class="page-link">…</span></li>
                                    @endif
                                    <li class="page-item"><a class="page-link" href="{{ $outbaseRequests->url($lastPage) }}">{{ $lastPage }}</a></li>
                                @endif
                                <li class="page-item {{ !$outbaseRequests->hasMorePages() ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ $outbaseRequests->nextPageUrl() ?? '#' }}">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                @endif
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
            });
        </script>
    @endpush

</x-app-layout>
