<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Add Outbase Request') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">

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

                <form method="POST" action="{{ route('outbase_requests.store') }}" enctype="multipart/form-data">
                    @csrf

                    <!-- Employee Name (disabled) -->
                    <div class="mb-3">
                        <label for="employee_name" class="form-label">Employee</label>
                        <input
                            id="employee_name"
                            name="employee_name"
                            type="text"
                            class="form-control-plaintext"
                            value="{{ old('employee_name', $employee->first_name . ' ' . $employee->last_name) }}"
                            readonly
                        >
                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                    </div>

                    <!-- Date -->
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input
                            id="date"
                            name="date"
                            type="date"
                            class="form-control @error('date') is-invalid @enderror"
                            value="{{ old('date') }}"
                            required
                        >
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Time Start -->
                    <div class="mb-3">
                        <label for="time_start" class="form-label">Time Start</label>
                        <input
                            id="time_start"
                            name="time_start"
                            type="time"
                            class="form-control @error('time_start') is-invalid @enderror"
                            value="{{ old('time_start') }}"
                            required
                        >
                        @error('time_start')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Time End -->
                    <div class="mb-3">
                        <label for="time_end" class="form-label">Time End</label>
                        <input
                            id="time_end"
                            name="time_end"
                            type="time"
                            class="form-control @error('time_end') is-invalid @enderror"
                            value="{{ old('time_end') }}"
                            required
                        >
                        @error('time_end')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Location -->
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input
                            id="location"
                            name="location"
                            type="text"
                            class="form-control @error('location') is-invalid @enderror"
                            value="{{ old('location') }}"
                            required
                        >
                        @error('location')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Reason -->
                    <div class="mb-4">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea
                            id="reason"
                            name="reason"
                            rows="3"
                            class="form-control @error('reason') is-invalid @enderror"
                            required
                        >{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Attachments -->
                    <div class="mb-4">
                        <label for="files" class="form-label">Supporting Documents (optional)</label>
                        <input
                            id="files"
                            name="files[]"
                            type="file"
                            class="form-control"
                            multiple
                        >
                        @error('files.*')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Submit -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            {{ __('Submit') }}
                        </button>

                        <a href="javascript:history.back()" class="btn btn-secondary">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
