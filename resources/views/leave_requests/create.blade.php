<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Add a New Leave Request') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

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

                <form method="POST" action="{{ route('leave_requests.store') }}" enctype="multipart/form-data">
                    @csrf

                    <!-- Employee -->
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input
                            type="text"
                            class="form-control bg-light"
                            value="{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_number }})"
                            disabled
                        >
                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                    </div>

                    <!-- Start Date -->
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input
                            id="start_date"
                            name="start_date"
                            type="date"
                            class="form-control"
                            value="{{ old('start_date') }}"
                            required
                        >
                        @error('start_date')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- End Date -->
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input
                            id="end_date"
                            name="end_date"
                            type="date"
                            class="form-control"
                            value="{{ old('end_date') }}"
                            required
                        >
                        @error('end_date')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Number of Days -->
                    <div class="mb-3">
                        <label for="number_of_days" class="form-label">Number of Days</label>
                        <input
                            id="number_of_days"
                            name="number_of_days"
                            type="number"
                            step="0.5"
                            min="0"
                            class="form-control"
                            value="{{ old('number_of_days') }}"
                            required
                        >
                        @error('number_of_days')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Reason -->
                    <div class="mb-4">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea
                            id="reason"
                            name="reason"
                            rows="4"
                            class="form-control"
                            required
                        >{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="text-danger small mt-1">{{ $message }}</div>
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
