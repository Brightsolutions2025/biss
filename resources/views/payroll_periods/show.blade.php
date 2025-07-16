<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Payroll Period Details') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">

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
                        <div class="mb-3">
                            <label class="form-label">Company</label>
                            <input type="text" class="form-control" value="{{ $payrollPeriod->company->name }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($payrollPeriod->start_date)->toFormattedDateString() }}" disabled>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">End Date</label>
                            <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($payrollPeriod->end_date)->toFormattedDateString() }}" disabled>
                        </div>

                        @if ($payrollPeriod->dtr_submission_due_at)
                            <div class="mb-3">
                                <label class="form-label">DTR Submission Due</label>
                                <input type="text" class="form-control" 
                                    value="{{ $payrollPeriod->dtr_submission_due_at->timezone(config('app.timezone'))->format('F j, Y g:i A') }}" 
                                    disabled>
                            </div>
                        @endif

                        @if ($payrollPeriod->reminder_sent_at)
                            <div class="mb-4">
                                <label class="form-label">Reminder Sent At</label>
                                <input type="text" class="form-control" 
                                    value="{{ $payrollPeriod->reminder_sent_at->timezone(config('app.timezone'))->format('F j, Y g:i A') }}" 
                                    disabled>
                            </div>
                        @endif

                        <div class="d-flex gap-2">
                            <a href="{{ route('payroll_periods.edit', $payrollPeriod->id) }}" class="btn btn-outline-primary">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('payroll_periods.destroy', $payrollPeriod->id) }}" onsubmit="return confirm('Are you sure you want to delete this payroll period?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger">
                                    Delete
                                </button>
                            </form>
                            <a href="javascript:history.back()" class="btn btn-secondary">
                                Back
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
