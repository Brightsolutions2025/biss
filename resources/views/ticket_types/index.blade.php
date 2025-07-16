<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">
            {{ __('Ticket Types') }}
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

                        <form method="GET" action="{{ route('ticket_types.index') }}" class="mb-4">
                            <div class="mb-3">
                                <label for="name" class="form-label">Ticket Type Name</label>
                                <input type="text" name="name" id="name"
                                       class="form-control"
                                       value="{{ request('name') }}">
                            </div>
                            <button type="submit" class="btn btn-primary">Search</button>
                        </form>

                        <h5 class="fw-bold mt-4">Add</h5>
                        <p>
                            Want to add a new ticket type? Click
                            <a href="{{ route('ticket_types.create') }}" class="link-primary">here</a>!
                        </p>

                        <h5 class="fw-bold mt-4 mb-3">List</h5>

                        @forelse ($ticketTypes as $ticketType)
                            <div class="card mb-3">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <div><strong>Name:</strong> {{ $ticketType->name }}</div>
                                        <div><strong>Company:</strong> {{ $ticketType->company->name ?? '—' }}</div>
                                        <div><strong>Description:</strong> {{ $ticketType->description ?? '—' }}</div>
                                        <div><strong>Status:</strong> 
                                            <span class="badge {{ $ticketType->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $ticketType->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('ticket_types.show', $ticketType->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="{{ route('ticket_types.edit', $ticketType->id) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                        <form method="POST" action="{{ route('ticket_types.destroy', $ticketType->id) }}" onsubmit="return confirm('Are you sure you want to delete this ticket type?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">No ticket types found.</p>
                        @endforelse

                        @if ($ticketTypes->hasPages())
                            <div class="mt-4">
                                <div class="d-flex justify-content-center">
                                    {{ $ticketTypes->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
