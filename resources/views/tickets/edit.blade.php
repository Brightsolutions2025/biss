{{-- resources/views/tickets/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Edit Ticket') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

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

                <form method="POST" action="{{ route('tickets.update', $ticket->id) }}" class="card card-body" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    <!-- Subject -->
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            id="subject"
                            name="subject"
                            class="form-control"
                            value="{{ old('subject', $ticket->subject) }}"
                            required
                        >
                        @error('subject') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea
                            id="description"
                            name="description"
                            class="form-control"
                            rows="5"
                            required
                        >{{ old('description', $ticket->description) }}</textarea>
                        @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <!-- Priority -->
                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select id="priority" name="priority" class="form-control">
                            <option value="low" {{ old('priority', $ticket->priority) == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('priority', $ticket->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('priority', $ticket->priority) == 'high' ? 'selected' : '' }}>High</option>
                        </select>
                        @error('priority') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <!-- Due Date -->
                    <div class="mb-3">
                        <label for="due_at" class="form-label">Due Date</label>
                        <input
                            type="date"
                            id="due_at"
                            name="due_at"
                            class="form-control"
                            value="{{ old('due_at', optional($ticket->due_at)->format('Y-m-d')) }}"
                        >
                        @error('due_at') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <!-- Submit -->
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Update Ticket</button>
                        <a href="{{ route('tickets.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>