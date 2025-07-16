<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Add a New Payroll Period') }}</h2>
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
                        <h5 class="card-title mb-4">Payroll Period Details</h5>

                        <form method="POST" action="{{ route('payroll_periods.store') }}">
                            @csrf

                            {{-- Hidden Company ID (optional) --}}
                            {{-- 
                            <input type="hidden" name="company_id" value="{{ old('company_id', session('active_company_id')) }}">
                            --}}

                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input 
                                    type="date" 
                                    class="form-control @error('start_date') is-invalid @enderror" 
                                    id="start_date" 
                                    name="start_date" 
                                    value="{{ old('start_date') }}" 
                                    required
                                >
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="end_date" class="form-label">End Date</label>
                                <input 
                                    type="date" 
                                    class="form-control @error('end_date') is-invalid @enderror" 
                                    id="end_date" 
                                    name="end_date" 
                                    value="{{ old('end_date') }}" 
                                    required
                                >
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="timezone" class="form-label">Timezone</label>
                                <select 
                                    class="form-select @error('timezone') is-invalid @enderror" 
                                    name="timezone" 
                                    id="timezone"
                                >
                                    @foreach(timezone_identifiers_list() as $tz)
                                        <option value="{{ $tz }}" {{ old('timezone', config('app.timezone')) == $tz ? 'selected' : '' }}>
                                            {{ $tz }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('timezone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="dtr_submission_due_at" class="form-label">DTR Submission Due</label>
                                <input 
                                    type="datetime-local" 
                                    class="form-control @error('dtr_submission_due_at') is-invalid @enderror" 
                                    id="dtr_submission_due_at" 
                                    name="dtr_submission_due_at" 
                                    value="{{ old('dtr_submission_due_at') }}"
                                >
                                @error('dtr_submission_due_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Add') }}
                                </button>

                                <a href="javascript:history.back()" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
