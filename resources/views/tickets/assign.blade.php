<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">Assign Ticket #{{ $ticket->ticket_number }}</h2>
    </x-slot>

    <div class="container py-4">
        <form method="POST" action="{{ route('tickets.assign.update', $ticket->id) }}">
            @csrf

            <div class="mb-3">
                <label for="assigned_to" class="form-label">Assign To</label>
                <select name="assigned_to" id="assigned_to" class="form-select" required>
                    <option value="">-- Select Person --</option>
                    @foreach ($assignees as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('assigned_to') <div class="text-danger">{{ $message }}</div> @enderror
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Assign Ticket</button>
                <a href="{{ route('tickets.show', $ticket->id) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>