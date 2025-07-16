<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Edit Payroll Period') }}</h2>
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
                        <h5 class="card-title mb-4">Payroll Period Information</h5>

                        <form method="POST" action="{{ route('payroll_periods.update', $payrollPeriod) }}">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input 
                                    type="date" 
                                    id="start_date" 
                                    name="start_date" 
                                    class="form-control @error('start_date') is-invalid @enderror" 
                                    value="{{ old('start_date', \Carbon\Carbon::parse($payrollPeriod->start_date)->format('Y-m-d')) }}" 
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
                                    id="end_date" 
                                    name="end_date" 
                                    class="form-control @error('end_date') is-invalid @enderror" 
                                    value="{{ old('end_date', \Carbon\Carbon::parse($payrollPeriod->end_date)->format('Y-m-d')) }}" 
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
                                        <option value="{{ $tz }}"
                                            {{ old('timezone', $payrollPeriod->timezone ?? config('app.timezone')) == $tz ? 'selected' : '' }}>
                                            {{ $tz }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('timezone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @php
                                $dueAt = old('dtr_submission_due_at', optional(optional($payrollPeriod->dtr_submission_due_at)->timezone(config('app.timezone')))->format('Y-m-d\TH:i'));
                            @endphp

                            <div class="mb-4">
                                <label for="dtr_submission_due_at" class="form-label">DTR Submission Due</label>
                                <input 
                                    type="datetime-local" 
                                    id="dtr_submission_due_at" 
                                    name="dtr_submission_due_at" 
                                    class="form-control @error('dtr_submission_due_at') is-invalid @enderror" 
                                    value="{{ $dueAt }}"
                                >
                                @error('dtr_submission_due_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-3">
                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="{{ route('payroll_periods.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
