<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Edit Employee') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card shadow border-0">
                    <div class="card-body p-4">

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

                        <form method="POST" action="{{ route('employees.update', $employee) }}">
                            @csrf
                            @method('PATCH')

                            <input type="hidden" name="company_id" value="{{ old('company_id', $employee->company_id) }}">

                            {{-- Identification --}}
                            <h5 class="mb-3 border-bottom pb-2">Identification</h5>

                            <div class="form-floating mb-3">
                                <select class="form-select" name="user_id" id="user_id" disabled>
                                    <option value="">Select User</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ $employee->user_id == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="user_id">User</label>
                                <input type="hidden" name="user_id" value="{{ $employee->user_id }}">
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="employee_number" id="employee_number" value="{{ old('employee_number', $employee->employee_number) }}" placeholder="Employee Number">
                                <label for="employee_number">Employee Number</label>
                            </div>

                            {{-- Personal Info --}}
                            <h5 class="mt-4 mb-3 border-bottom pb-2">Personal Information</h5>

                            <div class="row g-3">
                                @foreach([
                                    'first_name' => 'First Name',
                                    'middle_name' => 'Middle Name',
                                    'last_name' => 'Last Name'
                                ] as $field => $label)
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $employee->$field) }}" placeholder="{{ $label }}">
                                            <label for="{{ $field }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" id="birth_date" name="birth_date" value="{{ old('birth_date', $employee->birth_date) }}" placeholder="Birth Date">
                                        <label for="birth_date">Birth Date</label>
                                    </div>
                                </div>
                                @foreach([
                                    'gender' => 'Gender',
                                    'civil_status' => 'Civil Status'
                                ] as $field => $label)
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $employee->$field) }}" placeholder="{{ $label }}">
                                            <label for="{{ $field }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="form-floating mt-3">
                                <input type="text" class="form-control" id="nationality" name="nationality" value="{{ old('nationality', $employee->nationality) }}" placeholder="Nationality">
                                <label for="nationality">Nationality</label>
                            </div>

                            {{-- Employment Details --}}
                            <h5 class="mt-4 mb-3 border-bottom pb-2">Employment Details</h5>

                            <div class="row g-3">
                                @foreach([
                                    'position' => 'Position',
                                    'employment_type' => 'Employment Type'
                                ] as $field => $label)
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $employee->$field) }}" placeholder="{{ $label }}">
                                            <label for="{{ $field }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" id="hire_date" name="hire_date" value="{{ old('hire_date', $employee->hire_date) }}" placeholder="Hire Date">
                                        <label for="hire_date">Hire Date</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" id="termination_date" name="termination_date" value="{{ old('termination_date', $employee->termination_date) }}" placeholder="Termination Date">
                                        <label for="termination_date">Termination Date</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                @foreach(['department_id' => $departments, 'team_id' => $teams] as $field => $options)
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select" name="{{ $field }}" id="{{ $field }}">
                                                <option value="">Select {{ ucfirst(str_replace('_id', '', $field)) }}</option>
                                                @foreach($options as $option)
                                                    <option value="{{ $option->id }}" {{ $employee->$field == $option->id ? 'selected' : '' }}>
                                                        {{ $option->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <label for="{{ $field }}">{{ ucfirst(str_replace('_id', '', $field)) }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" step="0.01" class="form-control" id="basic_salary" name="basic_salary" value="{{ old('basic_salary', $employee->basic_salary) }}" placeholder="Basic Salary">
                                        <label for="basic_salary">Basic Salary</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" name="approver_id" id="approver_id">
                                            <option value="">Select Approver</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ $employee->approver_id == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="approver_id">Approver</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-check mt-3 mb-4">
                                <input class="form-check-input" type="checkbox" name="flexible_time" id="flexible_time" value="1" {{ $employee->flexible_time ? 'checked' : '' }}>
                                <label class="form-check-label" for="flexible_time">Flexible Time</label>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" name="ot_not_convertible_to_offset" id="ot_not_convertible_to_offset" value="1" {{ $employee->ot_not_convertible_to_offset ? 'checked' : '' }}>
                                <label class="form-check-label" for="ot_not_convertible_to_offset">
                                    Overtime Not Convertible to Offset
                                </label>
                            </div>

                            {{-- Government IDs --}}
                            <h5 class="mb-3 border-bottom pb-2">Government IDs</h5>

                            <div class="row g-3">
                                @foreach([
                                    'sss_number' => 'SSS Number',
                                    'philhealth_number' => 'PhilHealth Number',
                                    'pagibig_number' => 'Pag-IBIG Number',
                                    'tin_number' => 'TIN Number'
                                ] as $field => $label)
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $employee->$field) }}" placeholder="{{ $label }}">
                                            <label for="{{ $field }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Contact --}}
                            <h5 class="mt-4 mb-3 border-bottom pb-2">Contact Information</h5>

                            <div class="row g-3">
                                @foreach([
                                    'contact_number' => 'Contact Number',
                                    'emergency_contact' => 'Emergency Contact'
                                ] as $field => $label)
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $employee->$field) }}" placeholder="{{ $label }}">
                                            <label for="{{ $field }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Address --}}
                            <div class="mt-4 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3">{{ old('address', $employee->address) }}</textarea>
                            </div>

                            {{-- Notes --}}
                            <div class="mb-4">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="3">{{ old('notes', $employee->notes) }}</textarea>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary btn-lg">Update</button>
                                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
