{{-- resources/views/audit_logs/index.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Audit Logs for') }} {{ $company->name }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
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

                <!-- Search, Filter & Sort -->
                <h5 class="mb-3">Search</h5>
                <form method="GET" action="{{ route('audit_logs.index', $company) }}" class="mb-4">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search by Action, Model, or User</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control" placeholder="e.g. created, User, Juan">
                        </div>
                        <div class="col-md-3">
                            <label for="action" class="form-label">Action</label>
                            <select name="action" id="action" class="form-select">
                                <option value="">All</option>
                                <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Created</option>
                                <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Updated</option>
                                <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort_by" class="form-label">Sort By</label>
                            <select name="sort_by" id="sort_by" class="form-select">
                                <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Date</option>
                                <option value="action" {{ request('sort_by') == 'action' ? 'selected' : '' }}>Action</option>
                                <option value="performed_by" {{ request('sort_by') == 'performed_by' ? 'selected' : '' }}>User</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Apply</button>
                        </div>
                    </div>
                </form>

                <!-- Logs List -->
                <h5 class="mt-4">Audit Trail</h5>

                @forelse ($auditLogs as $log)
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Action:</strong> {{ ucfirst($log->action) }}<br>
                                <strong>Model:</strong> {{ $log->model_type ?? 'N/A' }} [ID: {{ $log->model_id ?? 'N/A' }}]<br>
                                <strong>User:</strong> {{ optional($log->user)->name ?? 'System' }}<br>
                                <strong>IP:</strong> {{ $log->ip_address ?? 'N/A' }}<br>
                                <strong>Screen:</strong> {{ $log->origin_screen ?? 'N/A' }}<br>
                                <strong>Context:</strong> {{ $log->context ?? 'N/A' }}<br>
                                <strong>Date:</strong> {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </div>

                            @if ($log->changes)
                                <details>
                                    <summary class="text-primary">View Changes</summary>
                                    <pre class="bg-light p-2 mt-2 rounded small">{{ json_encode($log->changes, JSON_PRETTY_PRINT) }}</pre>
                                </details>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No audit logs recorded yet.</p>
                @endforelse

                {{-- Pagination --}}
                @php
                    $currentPage = $auditLogs->currentPage();
                    $lastPage = $auditLogs->lastPage();
                    $linkCount = 5;
                    $half = floor($linkCount / 2);

                    $start = max(1, $currentPage - $half);
                    $end = min($lastPage, $start + $linkCount - 1);

                    if ($end - $start < $linkCount - 1) {
                        $start = max(1, $end - $linkCount + 1);
                    }
                @endphp

                @if ($auditLogs->hasPages())
                    <div class="mt-4">
                        <div class="d-flex justify-content-center">
                            <nav>
                                <ul class="pagination pagination-sm flex-wrap justify-content-center">
                                    <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $auditLogs->previousPageUrl() ?? '#' }}">&laquo;</a>
                                    </li>

                                    @if ($start > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $auditLogs->url(1) }}">1</a>
                                        </li>
                                        @if ($start > 2)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                    @endif

                                    @for ($i = $start; $i <= $end; $i++)
                                        <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $auditLogs->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    @if ($end < $lastPage)
                                        @if ($end < $lastPage - 1)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $auditLogs->url($lastPage) }}">{{ $lastPage }}</a>
                                        </li>
                                    @endif

                                    <li class="page-item {{ !$auditLogs->hasMorePages() ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $auditLogs->nextPageUrl() ?? '#' }}">&raquo;</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
