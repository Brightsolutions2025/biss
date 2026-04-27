<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">
            {{ __('Ticket Assignments') }}
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

                        <h5 class="fw-bold mb-3">Search</h5>

                        <form method="GET" action="{{ route('ticket_assigns.index') }}" class="mb-4">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="ticket_number" class="form-label">Ticket Number</label>
                                    <input type="text"
                                           name="ticket_number"
                                           id="ticket_number"
                                           class="form-control"
                                           value="{{ request('ticket_number') }}"
                                           placeholder="e.g. TCK-0001">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="assigned_to" class="form-label">Assigned To</label>
                                    <input type="text"
                                           name="assigned_to"
                                           id="assigned_to"
                                           class="form-control"
                                           value="{{ request('assigned_to') }}"
                                           placeholder="Employee name">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="is_current" class="form-label">Assignment Status</label>
                                    <select name="is_current" id="is_current" class="form-select">
                                        <option value="">All</option>
                                        <option value="1" {{ request('is_current') === '1' ? 'selected' : '' }}>Current</option>
                                        <option value="0" {{ request('is_current') === '0' ? 'selected' : '' }}>Previous</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="{{ route('ticket_assigns.index') }}" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>

                        <h5 class="fw-bold mt-4">Add</h5>
                        <p>
                            Want to add a new ticket assignment? Click
                            <a href="{{ route('ticket_assigns.create') }}" class="link-primary">here</a>!
                        </p>

                        <h5 class="fw-bold mt-4 mb-3">List</h5>

                        @forelse ($ticketAssigns as $ticketAssign)
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                        <div>
                                            <div>
                                                <strong>Ticket No.:</strong>
                                                {{ $ticketAssign->ticket->ticket_number ?? 'N/A' }}
                                            </div>

                                            <div>
                                                <strong>Subject:</strong>
                                                {{ $ticketAssign->ticket->subject ?? 'N/A' }}
                                            </div>

                                            <div>
                                                <strong>Requestor:</strong>
                                                {{ $ticketAssign->ticket->name ?? 'N/A' }}
                                            </div>

                                            <div>
                                                <strong>Status:</strong>
                                                @php
                                                    $status = $ticketAssign->ticket->status ?? null;
                                                @endphp

                                                @if ($status === 'open')
                                                    <span class="badge bg-primary">Open</span>
                                                @elseif ($status === 'pending_approval')
                                                    <span class="badge bg-warning text-dark">Pending Approval</span>
                                                @elseif ($status === 'approved')
                                                    <span class="badge bg-info text-dark">Approved</span>
                                                @elseif ($status === 'in_progress')
                                                    <span class="badge bg-secondary">In Progress</span>
                                                @elseif ($status === 'resolved')
                                                    <span class="badge bg-success">Resolved</span>
                                                @elseif ($status === 'closed')
                                                    <span class="badge bg-dark">Closed</span>
                                                @elseif ($status === 'rejected')
                                                    <span class="badge bg-danger">Rejected</span>
                                                @else
                                                    <span class="badge bg-light text-dark">N/A</span>
                                                @endif
                                            </div>

                                            <div>
                                                <strong>Assigned To:</strong>
                                                {{ $ticketAssign->assignedTo->name ?? 'N/A' }}
                                            </div>

                                            <div>
                                                <strong>Assigned By:</strong>
                                                {{ $ticketAssign->assignedBy->name ?? 'N/A' }}
                                            </div>

                                            <div>
                                                <strong>Assigned At:</strong>
                                                {{ $ticketAssign->assigned_at ? \Carbon\Carbon::parse($ticketAssign->assigned_at)->format('M d, Y h:i A') : 'N/A' }}
                                            </div>

                                            <div>
                                                <strong>Unassigned At:</strong>
                                                {{ $ticketAssign->unassigned_at ? \Carbon\Carbon::parse($ticketAssign->unassigned_at)->format('M d, Y h:i A') : 'Still Assigned' }}
                                            </div>

                                            <div>
                                                <strong>Current Assignment:</strong>
                                                @if ($ticketAssign->is_current)
                                                    <span class="badge bg-success">Yes</span>
                                                @else
                                                    <span class="badge bg-secondary">No</span>
                                                @endif
                                            </div>

                                            <div class="mt-2">
                                                <strong>Notes:</strong>
                                                {{ $ticketAssign->notes ?: 'No notes provided.' }}
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2 flex-wrap">
                                            <a href="{{ route('ticket_assigns.show', $ticketAssign->id) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>

                                            <a href="{{ route('ticket_assigns.edit', $ticketAssign->id) }}"
                                               class="btn btn-sm btn-outline-warning">
                                                Edit
                                            </a>

                                            <form method="POST"
                                                  action="{{ route('ticket_assigns.destroy', $ticketAssign->id) }}"
                                                  onsubmit="return confirm('Are you sure you want to delete this ticket assignment?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">No ticket assignments found.</p>
                        @endforelse

                        @php
                            $currentPage = $ticketAssigns->currentPage();
                            $lastPage = $ticketAssigns->lastPage();
                            $linkCount = 5;
                            $half = floor($linkCount / 2);

                            $start = max(1, $currentPage - $half);
                            $end = min($lastPage, $start + $linkCount - 1);

                            if ($end - $start < $linkCount - 1) {
                                $start = max(1, $end - $linkCount + 1);
                            }
                        @endphp

                        @if ($ticketAssigns->hasPages())
                            <div class="mt-4">
                                <div class="d-flex justify-content-center">
                                    <nav>
                                        <ul class="pagination pagination-sm flex-wrap justify-content-center">

                                            <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                                <a class="page-link" href="{{ $ticketAssigns->previousPageUrl() ?? '#' }}">&laquo;</a>
                                            </li>

                                            @if ($start > 1)
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $ticketAssigns->url(1) }}">1</a>
                                                </li>
                                                @if ($start > 2)
                                                    <li class="page-item disabled">
                                                        <span class="page-link">…</span>
                                                    </li>
                                                @endif
                                            @endif

                                            @for ($i = $start; $i <= $end; $i++)
                                                <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                                                    <a class="page-link" href="{{ $ticketAssigns->url($i) }}">{{ $i }}</a>
                                                </li>
                                            @endfor

                                            @if ($end < $lastPage)
                                                @if ($end < $lastPage - 1)
                                                    <li class="page-item disabled">
                                                        <span class="page-link">…</span>
                                                    </li>
                                                @endif
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $ticketAssigns->url($lastPage) }}">{{ $lastPage }}</a>
                                                </li>
                                            @endif

                                            <li class="page-item {{ !$ticketAssigns->hasMorePages() ? 'disabled' : '' }}">
                                                <a class="page-link" href="{{ $ticketAssigns->nextPageUrl() ?? '#' }}">&raquo;</a>
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