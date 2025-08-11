<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">
            {{ __('Tickets') }}
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

                        <form method="GET" action="{{ route('tickets.index') }}" class="mb-4">
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" name="subject" id="subject"
                                       class="form-control"
                                       value="{{ request('subject') }}">
                            </div>
                            <button type="submit" class="btn btn-primary">Search</button>
                        </form>

                        <h5 class="fw-bold mt-4">Create</h5>
                        <p>
                            Need to create a new ticket? Click
                            <a href="{{ route('tickets.create') }}" class="link-primary">here</a>!
                        </p>

                        <h5 class="fw-bold mt-4 mb-3">List</h5>

                        @forelse ($tickets as $ticket)
                            <div class="card mb-3">
                                 <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <div><strong>Ticket #:</strong> {{ $ticket->ticket_number }}</div>
                                        <div><strong>Subject:</strong> {{ $ticket->subject }}</div>
                                        <div><strong>Type:</strong> {{ $ticket->ticketType->name ?? '—' }}</div>
                                        <div><strong>Company:</strong> {{ $ticket->company->name ?? '—' }}</div>
                                        <div><strong>Department:</strong> {{ $ticket->department->name ?? '—' }}</div>
                                        <div><strong>Team:</strong> {{ $ticket->team->name ?? '—' }}</div>
                                        <div><strong>Status:</strong>
                                            <span class="badge bg-secondary text-capitalize">{{ str_replace('_', ' ', $ticket->status) }}</span>
                                        </div>
                                        <div><strong>Priority:</strong>
                                            <span class="badge bg-warning text-dark text-capitalize">{{ $ticket->priority }}</span>
                                        </div>
                                        <div><strong>Due:</strong> {{ $ticket->due_at ? $ticket->due_at->format('Y-m-d H:i') : '—' }}</div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('tickets.show', $ticket->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="{{ route('tickets.edit', $ticket->id) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                        <form method="POST" action="{{ route('tickets.destroy', $ticket->id) }}" onsubmit="return confirm('Are you sure you want to delete this ticket?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">No tickets found.</p>
                        @endforelse

                        @if ($tickets->hasPages())
                            <div class="mt-4">
                                <div class="d-flex justify-content-center">
                                    {{ $tickets->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
