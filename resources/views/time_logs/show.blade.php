<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Time Log Details') }}</h2>
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

                        @php
                            $fields = [
                                'Payroll Period'      => $timeLog->payrollPeriod->start_date . ' - ' . $timeLog->payrollPeriod->end_date,
                                'Company'             => $timeLog->company->name,
                                'Employee Name'       => $timeLog->employee_name,
                                'Department Name'     => $timeLog->department_name,
                                'Employee ID'         => $timeLog->employee_id,
                                'Employee Type'       => $timeLog->employee_type,
                                'Attendance Group'    => $timeLog->attendance_group,
                                'Date'                => $timeLog->date,
                                'Weekday'             => $timeLog->weekday,
                                'Shift'               => $timeLog->shift,
                                'Attendance Time'     => $timeLog->attendance_time,
                                'About the Record'    => $timeLog->about_the_record,
                                'Attendance Result'   => $timeLog->attendance_result,
                                'Attendance Address'  => $timeLog->attendance_address,
                                'Note'                => $timeLog->note,
                                'Attendance Method'   => $timeLog->attendance_method,
                            ];
                        @endphp

                        @foreach ($fields as $label => $value)
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ $label }}</label>
                                <input type="text" class="form-control" value="{{ $value }}" disabled>
                            </div>
                        @endforeach

                        <!-- Attendance Photo -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Attendance Photo</label><br>
                            <img src="{{ $timeLog->attendance_photo }}" alt="Attendance Photo" class="img-thumbnail" style="max-width: 200px;">
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 mt-4">
                            <a href="{{ route('time_logs.edit', $timeLog->id) }}" class="btn btn-primary">Edit</a>

                            <form method="POST" action="{{ route('time_logs.destroy', $timeLog->id) }}"
                                  onsubmit="return confirm('Are you sure you want to delete this time log?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Delete</button>
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
