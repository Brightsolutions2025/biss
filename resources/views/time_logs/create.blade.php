<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Add a New Time Log') }}</h2>
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

                <!-- CSV Upload Form -->
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('time_logs.import') }}" enctype="multipart/form-data">
                            @csrf

                            <!-- Payroll Period -->
                            <div class="mb-3">
                                <label for="import_payroll_period_id" class="form-label">Payroll Period</label>
                                <select
                                    id="import_payroll_period_id"
                                    name="payroll_period_id"
                                    required
                                    class="form-select"
                                >
                                    <option value="">Select a payroll period</option>
                                    @foreach ($payrollPeriods as $period)
                                        <option value="{{ $period->id }}" {{ old('payroll_period_id') == $period->id ? 'selected' : '' }}>
                                            {{ $period->start_date }} - {{ $period->end_date }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payroll_period_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- CSV File -->
                            <div class="mb-3">
                                <label for="csv_file" class="form-label">CSV File</label>
                                <input
                                    id="csv_file"
                                    name="csv_file"
                                    type="file"
                                    accept=".csv"
                                    required
                                    class="form-control"
                                >
                                @error('csv_file')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Import') }}
                                </button>

                                <a href="javascript:history.back()" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- OPTIONAL: Restore the manual form block here later -->

            </div>
        </div>
    </div>
</x-app-layout>
