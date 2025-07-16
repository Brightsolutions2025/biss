<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Edit Shift') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">

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
                        <form method="POST" action="{{ route('shifts.update', $shift) }}" id="edit-shift-form">
                            @csrf
                            @method('PATCH')

                            <!-- Shift Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Shift Name</label>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    class="form-control"
                                    value="{{ old('name', $shift->name) }}"
                                    required
                                >
                                @error('name')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Time In -->
                            <div class="mb-3">
                                <label for="time_in" class="form-label">Time In</label>
                                <input
                                    id="time_in"
                                    name="time_in"
                                    type="time"
                                    class="form-control"
                                    value="{{ old('time_in', $shift->time_in) }}"
                                    required
                                >
                                @error('time_in')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Time Out -->
                            <div class="mb-3">
                                <label for="time_out" class="form-label">Time Out</label>
                                <input
                                    id="time_out"
                                    name="time_out"
                                    type="time"
                                    class="form-control"
                                    value="{{ old('time_out', $shift->time_out) }}"
                                    required
                                >
                                @error('time_out')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Is Night Shift -->
                            <div class="form-check mb-4">
                                <input type="hidden" name="is_night_shift" value="0">
                                <input
                                    type="checkbox"
                                    name="is_night_shift"
                                    value="1"
                                    class="form-check-input"
                                    id="is_night_shift"
                                    {{ old('is_night_shift', $shift->is_night_shift) ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="is_night_shift">Is Night Shift</label>
                                @error('is_night_shift')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Buttons -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="{{ route('shifts.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
