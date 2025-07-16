<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Ticket Type Details') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

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

                <div class="card">
                    <div class="card-body">

                        @php
                            $fields = [
                                'Company'     => $ticketType->company->name ?? 'N/A',
                                'Name'        => $ticketType->name,
                                'Description' => $ticketType->description ?? '—',
                                'Sort Order'  => $ticketType->sort_order,
                                'Status'      => $ticketType->is_active ? 'Active' : 'Inactive',
                                'Created At'  => $ticketType->created_at->format('Y-m-d H:i'),
                                'Updated At'  => $ticketType->updated_at->format('Y-m-d H:i'),
                            ];
                        @endphp

                        @foreach ($fields as $label => $value)
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ $label }}</label>
                                <input type="text" class="form-control" value="{{ $value }}" disabled>
                            </div>
                        @endforeach

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 mt-4">
                            <a href="{{ route('ticket_types.edit', $ticketType->id) }}" class="btn btn-primary">Edit</a>

                            <form method="POST" action="{{ route('ticket_types.destroy', $ticketType->id) }}"
                                  onsubmit="return confirm('Are you sure you want to delete this ticket type?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>

                            <a href="{{ route('ticket_types.index') }}" class="btn btn-secondary">
                                Back
                            </a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
