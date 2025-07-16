<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Add a New Employee') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card shadow border-0">
                    <div class="card-body p-4">

                        {{-- Alerts --}}
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

                        <form method="POST" action="{{ route('employees.store') }}">
                            @csrf
                            <input type="hidden" name="company_id" value="{{ old('company_id', $companyId ?? '') }}">

                            {{-- Identification --}}
                            <h5 class="mb-3 border-bottom pb-2">Identification</h5>

                            <div class="form-floating mb-3">
                                <select class="form-select" name="user_id" id="user_id">
                                    <option value="">Select User</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="user_id">User</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="employee_number" id="employee_number" value="{{ old('employee_number') }}" placeholder="Employee Number">
                                <label for="employee_number">Employee Number</label>
                            </div>

                            {{-- Personal Info --}}
                            <h5 class="mt-4 mb-3 border-bottom pb-2">Personal Information</h5>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name') }}" placeholder="First Name">
                                        <label for="first_name">First Name</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="middle_name" name="middle_name" value="{{ old('middle_name') }}" placeholder="Middle Name">
                                        <label for="middle_name">Middle Name</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name') }}" placeholder="Last Name">
                                        <label for="last_name">Last Name</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" id="birth_date" name="birth_date" value="{{ old('birth_date') }}" placeholder="Birth Date">
                                        <label for="birth_date">Birth Date</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="gender" name="gender" value="{{ old('gender') }}" placeholder="Gender">
                                        <label for="gender">Gender</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="civil_status" name="civil_status" value="{{ old('civil_status') }}" placeholder="Civil Status">
                                        <label for="civil_status">Civil Status</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mt-3">
                                <input type="text" class="form-control" id="nationality" name="nationality" value="{{ old('nationality') }}" placeholder="Nationality">
                                <label for="nationality">Nationality</label>
                            </div>

                            {{-- Employment Details --}}
                            <h5 class="mt-4 mb-3 border-bottom pb-2">Employment Details</h5>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="position" name="position" value="{{ old('position') }}" placeholder="Position">
                                        <label for="position">Position</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="employment_type" name="employment_type" value="{{ old('employment_type') }}" placeholder="Employment Type">
                                        <label for="employment_type">Employment Type</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" id="hire_date" name="hire_date" value="{{ old('hire_date') }}" placeholder="Hire Date">
                                        <label for="hire_date">Hire Date</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" id="termination_date" name="termination_date" value="{{ old('termination_date') }}" placeholder="Termination Date">
                                        <label for="termination_date">Termination Date</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" name="department_id" id="department_id">
                                            <option value="">Select Department</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                                    {{ $department->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="department_id">Department</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" name="team_id" id="team_id">
                                            <option value="">Select Team</option>
                                            @foreach($teams as $team)
                                                <option value="{{ $team->id }}" {{ old('team_id') == $team->id ? 'selected' : '' }}>
                                                    {{ $team->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="team_id">Team</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" step="0.01" class="form-control" id="basic_salary" name="basic_salary" value="{{ old('basic_salary', 0) }}" placeholder="Basic Salary">
                                        <label for="basic_salary">Basic Salary</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" name="approver_id" id="approver_id">
                                            <option value="">Select Approver</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ old('approver_id') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="approver_id">Approver</label>
                                    </div>
                                </div>
                            </div>

                            {{-- Checkbox Options --}}
                            <div class="mt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="flexible_time" value="1" id="flexible_time" {{ old('flexible_time') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="flexible_time">
                                        Flexible Time
                                    </label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="ot_not_convertible_to_offset" value="1" id="ot_not_convertible_to_offset" {{ old('ot_not_convertible_to_offset') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ot_not_convertible_to_offset">
                                        Overtime is <strong>not</strong> convertible to offset
                                    </label>
                                </div>
                            </div>

                            {{-- Government IDs --}}
                            <h5 class="mt-4 mb-3 border-bottom pb-2">Government IDs</h5>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="sss_number" name="sss_number" value="{{ old('sss_number') }}" placeholder="SSS Number">
                                        <label for="sss_number">SSS Number</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="philhealth_number" name="philhealth_number" value="{{ old('philhealth_number') }}" placeholder="PhilHealth Number">
                                        <label for="philhealth_number">PhilHealth Number</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="pagibig_number" name="pagibig_number" value="{{ old('pagibig_number') }}" placeholder="Pag-IBIG Number">
                                        <label for="pagibig_number">Pag-IBIG Number</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="tin_number" name="tin_number" value="{{ old('tin_number') }}" placeholder="TIN Number">
                                        <label for="tin_number">TIN Number</label>
                                    </div>
                                </div>
                            </div>

                            {{-- Contact Info --}}
                            <h5 class="mt-4 mb-3 border-bottom pb-2">Contact Information</h5>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" placeholder="Contact Number">
                                        <label for="contact_number">Contact Number</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="emergency_contact" name="emergency_contact" value="{{ old('emergency_contact') }}" placeholder="Emergency Contact">
                                        <label for="emergency_contact">Emergency Contact</label>
                                    </div>
                                </div>
                            </div>

                            {{-- Address --}}
                            <div class="mt-4 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3">{{ old('address') }}</textarea>
                            </div>

                            {{-- Notes --}}
                            <div class="mb-4">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Save Employee') }}
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
