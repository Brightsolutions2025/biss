<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Companies table
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();

            // Add these fields for offset validity duration
            $table->unsignedInteger('offset_valid_after_days')->default(90)
                ->comment('Maximum days after an OT date for valid offset');
            $table->unsignedInteger('offset_valid_before_days')->default(41)
                ->comment('Maximum days before an OT date for valid offset');

            $table->timestamps();
            $table->comment('Stores company information');
        });

        // company_user pivot table
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');

            $table->timestamps();

            $table->unique(['company_id', 'user_id']);

            $table->foreign('company_id', 'fk_company_user_company')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            $table->foreign('user_id', 'fk_company_user_user')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->comment('Associates users with companies');
        });

        // Roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete()->index()->comment('Company reference');
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'name']);
            $table->comment('Stores roles within a company');
        });

        // Permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index()->comment('Company reference');
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'name']);

            $table->foreign('company_id', 'fk_permissions_company')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            $table->comment('Stores permissions within a company');
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');

            $table->timestamps();

            $table->primary(['company_id', 'role_id', 'user_id']);

            $table->foreign('company_id', 'fk_role_user_company')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            $table->foreign('role_id', 'fk_role_user_role')
                  ->references('id')->on('roles')
                  ->onDelete('cascade');

            $table->foreign('user_id', 'fk_role_user_user')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->index(['company_id', 'role_id', 'user_id']);

            $table->comment('Associates users with roles in a company');
        });

        // permission_role pivot table
        Schema::create('permission_role', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');

            $table->timestamps();

            $table->primary(['company_id', 'permission_id', 'role_id']);

            $table->foreign('company_id', 'fk_permission_role_company')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            $table->foreign('permission_id', 'fk_permission_role_permission')
                  ->references('id')->on('permissions')
                  ->onDelete('cascade');

            $table->foreign('role_id', 'fk_permission_role_role')
                  ->references('id')->on('roles')
                  ->onDelete('cascade');

            $table->index(['company_id', 'permission_id', 'role_id']);

            $table->comment('Associates permissions with roles in a company');
        });

        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index()->comment('User reference');
            $table->unsignedBigInteger('company_id')->nullable()->index()->comment('Selected company');

            $table->json('preferences')->nullable()->comment('Other user preferences');
            $table->timestamps();

            $table->unique('user_id'); // Ensure one preference record per user

            // Foreign keys with custom names
            $table->foreign('user_id', 'fk_user_preferences_user')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('company_id', 'fk_user_preferences_company')
                  ->references('id')->on('companies')
                  ->onDelete('set null');

            $table->comment('Stores user-selected preferences like active company');
        });
        
        // Departments
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('head_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // Teams (subset of departments)
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade'); // fix
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Employees (HRIS) with department_id and team_id
        Schema::create('employees', function (Blueprint $table) {
            // Identification & Relationships
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('employee_number')->unique();

            // Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('nationality')->nullable();

            // Employment Details
            $table->string('position')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('set null');
            $table->string('employment_type')->nullable();
            $table->boolean('flexible_time')->default(false);
            $table->boolean('ot_not_convertible_to_offset')->default(false);
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->decimal('basic_salary', 10, 2)->default(0);

            // Government IDs
            $table->string('sss_number')->nullable();
            $table->string('philhealth_number')->nullable();
            $table->string('pagibig_number')->nullable();
            $table->string('tin_number')->nullable();

            // Contact & Address
            $table->text('address')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('emergency_contact')->nullable();

            // Miscellaneous
            $table->text('notes')->nullable();

            // Timestamps
            $table->timestamps();
            $table->unique(['user_id', 'company_id']);
        });

        // Shifts
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->time('time_in');
            $table->time('time_out');
            $table->boolean('is_night_shift')->default(false);
            $table->timestamps();
        });

        // Employee Shifts
        Schema::create('employee_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->dateTime('dtr_submission_due_at')->nullable();
            $table->dateTime('reminder_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('payroll_period_id');
            $table->string('employee_name');
            $table->string('department_name');
            $table->string('employee_id');
            $table->string('employee_type');
            $table->string('attendance_group');
            $table->date('date');
            $table->string('weekday');
            $table->string('shift');
            $table->dateTime('attendance_time');
            $table->string('about_the_record');
            $table->string('attendance_result');
            $table->string('attendance_address');
            $table->string('note');
            $table->string('attendance_method');
            $table->string('attendance_photo');
            $table->timestamps();
            $table->foreign('payroll_period_id')
                ->references('id')
                ->on('payroll_periods')
                ->onDelete('cascade');
        });

        // Overtime Requests
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('time_start');
            $table->time('time_end');
            $table->decimal('number_of_hours');
            $table->text('reason');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('approval_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->date('expires_at')->nullable()->comment('OT expiration date for offset usage');
            $table->timestamps();
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('beginning_balance')->default(0);
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('number_of_days');
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('approval_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // Outbase Requests (Field Work Authorization Form)
        Schema::create('outbase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('time_start');
            $table->time('time_end');
            $table->string('location');
            $table->text('reason');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('approval_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // Offset Requests (Use overtime hours for undertime or leave)
        Schema::create('offset_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('date'); // date of undertime or leave to offset
            $table->text('project_or_event_description');
            $table->time('time_start');
            $table->time('time_end');
            $table->decimal('number_of_hours', 5, 2);
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('approval_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('offset_overtime', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('offset_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('overtime_request_id')->constrained()->onDelete('cascade');
            $table->decimal('used_hours', 5, 2); // e.g., 1.5 hours
            $table->timestamps();
        });

        // DTR (Daily Time Record) summary table
        Schema::create('time_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('restrict');
            $table->foreignId('payroll_period_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('approval_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'employee_id', 'payroll_period_id']);
        });

        // DTR (Daily Time Record) summary table
        Schema::create('time_record_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('time_record_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->decimal('late_minutes', 5, 2)->default(0);
            $table->decimal('undertime_minutes', 5, 2)->default(0);
            $table->time('overtime_time_start')->nullable();
            $table->time('overtime_time_end')->nullable();
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->time('offset_time_start')->nullable();
            $table->time('offset_time_end')->nullable();
            $table->decimal('offset_hours', 5, 2)->default(0);
            $table->time('outbase_time_start')->nullable();
            $table->time('outbase_time_end')->nullable();
            $table->decimal('leave_days', 5, 2)->default(0);
            $table->decimal('remaining_leave_credits', 5, 2)->nullable();
            $table->boolean('leave_with_pay')->default(false);
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->morphs('fileable'); // adds fileable_id and fileable_type
            $table->string('file_path');
            $table->string('file_name')->nullable(); // original uploaded name
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Drop pivot and main tables in reverse order
        Schema::dropIfExists('files');
        Schema::dropIfExists('time_record_lines');
        Schema::dropIfExists('time_records');
        Schema::dropIfExists('offset_requests');
        Schema::dropIfExists('outbase_requests');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('overtime_requests');
        Schema::dropIfExists('time_logs');
        Schema::dropIfExists('employee_shifts');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('company_user');
        Schema::dropIfExists('companies');
    }
};
