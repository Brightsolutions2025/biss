@php
    $user = Auth::user();
@endphp

<style>
    .navbar .dropdown-menu {
        border-radius: 0.5rem;
        min-width: 220px;
    }
    .navbar .dropdown-item:hover,
    .navbar .dropdown-item:focus {
        background-color: #f0f9ff;
    }
    .navbar .dropdown-item.active {
        background-color: #e7f3ff;
    }
    .navbar .dropdown-header {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #6c757d;
        font-weight: 600;
    }
    .navbar .nav-link.active {
        font-weight: 600;
        color: #0d6efd !important;
    }
    .navbar .dropdown-menu-end {
        right: 0;
        left: auto;
    }
    .navbar .dropdown-menu {
        box-shadow: 0 .25rem .5rem rgba(0,0,0,.05);
    }
</style>
<nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top" style="z-index: 1030;">
    <div class="container-fluid">
        <!-- Brand/Logo -->
        <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 110" fill="none" width="120" height="40">
                <defs>
                    <linearGradient id="bissGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#38b6ff" />
                        <stop offset="100%" stop-color="#0077b6" />
                    </linearGradient>
                </defs>
                <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle"
                      font-family="Segoe UI, Helvetica, Arial, sans-serif" font-size="100"
                      font-weight="bold" fill="url(#bissGradient)">Biss</text>
            </svg>
        </a>

        <!-- Mobile toggle button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Collapsible nav content -->
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        Dashboard
                    </a>
                </li>

                <!-- Dropdown: Add -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="addDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Add
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="addDropdown">

                        {{-- Employees --}}
                        @if($user && ($user->hasAnyPermission(['employee.create', 'team.create', 'department.create'])))
                            <li><h6 class="dropdown-header">Employees</h6></li>
                            @if($user->hasPermission('employee.create'))
                                <li><a class="dropdown-item" href="{{ route('employees.create') }}">Employee</a></li>
                            @endif
                            @if($user->hasPermission('team.create'))
                                <li><a class="dropdown-item" href="{{ route('teams.create') }}">Team</a></li>
                            @endif
                            @if($user->hasPermission('department.create'))
                                <li><a class="dropdown-item" href="{{ route('departments.create') }}">Department</a></li>
                            @endif
                        @endif

                        {{-- Requests --}}
                        @if($user && $user->hasAnyPermission([
                            'leave_request.create', 'overtime_request.create',
                            'offset_request.create', 'outbase_request.create'
                        ]))
                            <li><h6 class="dropdown-header">Requests</h6></li>
                            @if($user->hasPermission('leave_request.create'))
                                <li><a class="dropdown-item" href="{{ route('leave_requests.create') }}">Leave</a></li>
                            @endif
                            @if($user->hasPermission('overtime_request.create'))
                                <li><a class="dropdown-item" href="{{ route('overtime_requests.create') }}">Overtime</a></li>
                            @endif
                            @if($user->hasPermission('offset_request.create'))
                                <li><a class="dropdown-item" href="{{ route('offset_requests.create') }}">Offset</a></li>
                            @endif
                            @if($user->hasPermission('outbase_request.create'))
                                <li><a class="dropdown-item" href="{{ route('outbase_requests.create') }}">Outbase</a></li>
                            @endif
                        @endif

                        {{-- Timekeeping --}}
                        @if($user && $user->hasAnyPermission([
                            'time_record.create', 'payroll_period.create', 'time_log.create'
                        ]))
                            <li><h6 class="dropdown-header">Timekeeping</h6></li>
                            @if($user->hasPermission('time_record.create'))
                                <li><a class="dropdown-item" href="{{ route('time_records.create') }}">Time Record</a></li>
                            @endif
                            @if($user->hasPermission('payroll_period.create'))
                                <li><a class="dropdown-item" href="{{ route('payroll_periods.create') }}">Payroll Period</a></li>
                            @endif
                            @if($user->hasPermission('time_log.create'))
                                <li><a class="dropdown-item" href="{{ route('time_logs.create') }}">Time Log</a></li>
                            @endif
                        @endif

                        {{-- Settings --}}
                        @if($user && $user->hasAnyPermission([
                            'shift.create', 'employee_shift.create', 'leave_balance.create'
                        ]))
                            <li><h6 class="dropdown-header">Settings</h6></li>
                            @if($user->hasPermission('shift.create'))
                                <li><a class="dropdown-item" href="{{ route('shifts.create') }}">Shift</a></li>
                            @endif
                            @if($user->hasPermission('employee_shift.create'))
                                <li><a class="dropdown-item" href="{{ route('employee_shifts.create') }}">Employee Shift</a></li>
                            @endif
                            @if($user->hasPermission('leave_balance.create'))
                                <li><a class="dropdown-item" href="{{ route('leave_balances.create') }}">Leave Balance</a></li>
                            @endif
                        @endif

                        {{-- Admin --}}
                        @if($user && $user->hasRole('admin'))
                            <li><h6 class="dropdown-header">Admin</h6></li>
                            <li><a class="dropdown-item" href="{{ route('companies.create') }}">Company</a></li>
                            <li><a class="dropdown-item" href="{{ route('users.create') }}">User</a></li>
                            <li><a class="dropdown-item" href="{{ route('roles.create') }}">Role</a></li>
                            <li><a class="dropdown-item" href="{{ route('permissions.create') }}">Permission</a></li>
                            <li><a class="dropdown-item" href="{{ route('company_users.create') }}">Company User</a></li>
                        @endif
                    </ul>
                </li>

                <!-- Repeat similar dropdowns for Lists, Others, Reports -->
                <!-- Dropdown: Lists -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="listsDropdown" data-bs-toggle="dropdown">
                        Lists
                    </a>
                    <ul class="dropdown-menu">
                        {{-- Employees --}}
                        @if(auth()->user()->hasPermission('employee.browse'))
                            <li><h6 class="dropdown-header">Employees</h6></li>
                            <li><a class="dropdown-item" href="{{ route('employees.index') }}">Employees</a></li>
                        @endif
                        @if(auth()->user()->hasPermission('team.browse'))
                            <li><a class="dropdown-item" href="{{ route('teams.index') }}">Teams</a></li>
                        @endif
                        @if(auth()->user()->hasPermission('department.browse'))
                            <li><a class="dropdown-item" href="{{ route('departments.index') }}">Departments</a></li>
                        @endif

                        {{-- Requests --}}
                        @if(auth()->user()->hasAnyPermission([
                            'leave_request.browse', 'overtime_request.browse',
                            'offset_request.browse', 'outbase_request.browse'
                        ]))
                            <li><h6 class="dropdown-header">Requests</h6></li>
                            @if(auth()->user()->hasPermission('leave_request.browse'))
                                <li><a class="dropdown-item" href="{{ route('leave_requests.index') }}">Leave Requests</a></li>
                            @endif
                            @if(auth()->user()->hasPermission('overtime_request.browse'))
                                <li><a class="dropdown-item" href="{{ route('overtime_requests.index') }}">Overtime Requests</a></li>
                            @endif
                            @if(auth()->user()->hasPermission('offset_request.browse'))
                                <li><a class="dropdown-item" href="{{ route('offset_requests.index') }}">Offset Requests</a></li>
                            @endif
                            @if(auth()->user()->hasPermission('outbase_request.browse'))
                                <li><a class="dropdown-item" href="{{ route('outbase_requests.index') }}">Outbase Requests</a></li>
                            @endif
                        @endif

                        {{-- Timekeeping --}}
                        @if(auth()->user()->hasAnyPermission([
                            'time_record.browse', 'payroll_period.browse', 'time_log.browse'
                        ]))
                            <li><h6 class="dropdown-header">Timekeeping</h6></li>
                            @if(auth()->user()->hasPermission('time_record.browse'))
                                <li><a class="dropdown-item" href="{{ route('time_records.index') }}">Time Records</a></li>
                            @endif
                            @if(auth()->user()->hasPermission('payroll_period.browse'))
                                <li><a class="dropdown-item" href="{{ route('payroll_periods.index') }}">Payroll Periods</a></li>
                            @endif
                            @if(auth()->user()->hasPermission('time_log.browse'))
                                <li><a class="dropdown-item" href="{{ route('time_logs.index') }}">Time Logs</a></li>
                            @endif
                        @endif

                        {{-- Settings --}}
                        @if(auth()->user()->hasAnyPermission([
                            'shift.browse', 'employee_shift.browse', 'leave_balance.browse'
                        ]))
                            <li><h6 class="dropdown-header">Settings</h6></li>
                            @if(auth()->user()->hasPermission('shift.browse'))
                                <li><a class="dropdown-item" href="{{ route('shifts.index') }}">Shifts</a></li>
                            @endif
                            @if(auth()->user()->hasPermission('employee_shift.browse'))
                                <li><a class="dropdown-item" href="{{ route('employee_shifts.index') }}">Employee Shifts</a></li>
                            @endif
                            @if(auth()->user()->hasPermission('leave_balance.browse'))
                                <li><a class="dropdown-item" href="{{ route('leave_balances.index') }}">Leave Balances</a></li>
                            @endif
                        @endif

                        {{-- Admin --}}
                        @if($user && (
                            $user->hasRole('admin')
                        ))
                            <li><h6 class="dropdown-header">Admin</h6></li>
                            <li><a class="dropdown-item" href="{{ route('companies.index') }}">Companies</a></li>
                            <li><a class="dropdown-item" href="{{ route('users.index') }}">Users</a></li>
                            <li><a class="dropdown-item" href="{{ route('roles.index') }}">Roles</a></li>
                            <li><a class="dropdown-item" href="{{ route('permissions.index') }}">Permissions</a></li>
                            <li><a class="dropdown-item" href="{{ route('company_users.index') }}">Company Users</a></li>
                        @endif
                    </ul>
                </li>

                <!-- Dropdown: Reports -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" data-bs-toggle="dropdown">
                        Reports
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('reports.index') }}">All</a></li>

                        @php $user = Auth::user(); @endphp

                        {{-- HR Supervisor + Admin + Department Head --}}
                        @if($user && $user->hasAnyRole(['admin', 'hr supervisor', 'department head']))
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">HR & Department Head</h6></li>

                            <li><a class="dropdown-item" href="{{ route('reports.dtr_status_by_team') }}">DTR Status by Team</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.leave_utilization') }}">Leave Utilization Summary</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.overtime_offset_comparison') }}">Overtime vs Offset Report</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.late_undertime') }}">Late and Undertime Report</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.leave_status_overview') }}">Leave Requests by Status</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.outbase_summary') }}">Outbase Request Summary</a></li>
                        @endif

                        {{-- Employee + HR Supervisor + Admin + Department Head --}}
                        @if($user && $user->hasAnyRole(['admin', 'hr supervisor', 'employee', 'department head']))
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Employee Reports</h6></li>

                            <li><a class="dropdown-item" href="{{ route('reports.offset_tracker') }}">Offset Usage and Expiry Tracker</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.leave_summary') }}">Leave Summary Report</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.overtime_history') }}">Filed Overtime Report</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.leave_timeline') }}">Approved Leaves Timeline</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.outbase_history') }}">Outbase Request Report</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.offset_summary') }}">Offset Request Summary</a></li>
                        @endif
                    </ul>
                </li>
            </ul>

            <!-- Right aligned: Company switcher and user -->
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                {{-- Company Switcher --}}
                @if($user?->companies && $user->companies->count() > 1)
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#" id="companyDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-building text-primary fs-5"></i>
                            <span class="fw-semibold small">{{ $user->preference?->company?->name ?? 'Switch Company' }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="companyDropdown">
                            <li><h6 class="dropdown-header text-primary">Switch Company</h6></li>
                            @foreach ($user->companies as $company)
                                <li>
                                    <form method="POST" action="{{ route('preferences.switchCompany') }}">
                                        @csrf
                                        <input type="hidden" name="company_id" value="{{ $company->id }}">
                                        <button type="submit"
                                            class="dropdown-item d-flex align-items-center gap-2 {{ $user->preference?->company_id === $company->id ? 'active fw-bold text-primary' : '' }}">
                                            <i class="bi bi-check-circle-fill text-success" style="opacity: {{ $user->preference?->company_id === $company->id ? 1 : 0 }};"></i>
                                            {{ $company->name }}
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @elseif($user?->preference?->company?->name)
                    <li class="nav-item">
                        <span class="nav-link d-flex align-items-center gap-1 disabled text-muted">
                            <i class="bi bi-building"></i>
                            <span class="small">{{ $user->preference->company->name }}</span>
                        </span>
                    </li>
                @endif

                {{-- User Dropdown --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 text-secondary"></i>
                        <span class="fw-semibold small">{{ $user->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('profile.edit') }}">
                                <i class="bi bi-gear text-primary"></i> Profile Settings
                            </a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                                    <i class="bi bi-box-arrow-right text-danger"></i> Log Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const desktopMinWidth = 992; // Bootstrap's lg breakpoint

        function enableHoverDropdowns() {
            const dropdowns = document.querySelectorAll('.navbar .dropdown');

            dropdowns.forEach(dropdown => {
                const toggle = dropdown.querySelector('.dropdown-toggle');
                const menu = dropdown.querySelector('.dropdown-menu');

                // Remove any previously attached handlers
                dropdown.onmouseenter = null;
                dropdown.onmouseleave = null;

                // Apply only on desktop
                if (window.innerWidth >= desktopMinWidth) {
                    dropdown.onmouseenter = () => {
                        if (!dropdown.classList.contains('show')) {
                            toggle.classList.add('show');
                            menu.classList.add('show');
                            toggle.setAttribute('aria-expanded', 'true');
                        }
                    };

                    dropdown.onmouseleave = () => {
                        toggle.classList.remove('show');
                        menu.classList.remove('show');
                        toggle.setAttribute('aria-expanded', 'false');
                    };
                }
            });
        }

        // Run on load
        enableHoverDropdowns();

        // Re-run on resize
        window.addEventListener('resize', enableHoverDropdowns);
    });
</script>
