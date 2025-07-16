<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Edit Ticket Type') }}</h2>
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

                <form method="POST" action="{{ route('ticket_types.update', $ticketType->id) }}" class="card card-body">
                    @csrf
                    @method('PATCH')

                    <!-- Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control"
                            value="{{ old('name', $ticketType->name) }}"
                            required
                        >
                        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            class="form-control"
                            rows="3"
                        >{{ old('description', $ticketType->description) }}</textarea>
                        @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <!-- Sort Order -->
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input
                            type="number"
                            id="sort_order"
                            name="sort_order"
                            class="form-control"
                            value="{{ old('sort_order', $ticketType->sort_order) }}"
                            min="0"
                        >
                        @error('sort_order') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <!-- Is Active -->
                    <div class="mb-3">
                        <label for="is_active" class="form-label">Status</label>
                        <select
                            id="is_active"
                            name="is_active"
                            class="form-select"
                        >
                            <option value="1" {{ old('is_active', $ticketType->is_active) ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active', $ticketType->is_active) === 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('is_active') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('ticket_types.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Ticket Type</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
