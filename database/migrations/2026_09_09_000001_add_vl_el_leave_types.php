<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('leave_type', 20)->default('vacation')->after('employee_id')->index();
        });

        Schema::table('leave_balances', function (Blueprint $table) {
            $table->string('leave_type', 20)->default('vacation')->after('year')->index();
        });

        // Existing requests/balances pre-date EL support, so they are treated as VL.
        DB::table('leave_requests')->whereNull('leave_type')->update(['leave_type' => 'vacation']);
        DB::table('leave_balances')->whereNull('leave_type')->update(['leave_type' => 'vacation']);

        // Establish the requested current-year entitlement for existing regular employees.
        // This intentionally does NOT add a future annual-reset scheduler.
        $year = (int) date('Y');
        $now = now();

        $regularEmployees = DB::table('employees')
            ->select('id', 'company_id')
            ->whereIn(DB::raw('LOWER(TRIM(employment_type))'), ['regular', 'regular employee'])
            ->get();

        foreach ($regularEmployees as $employee) {
            foreach (['vacation' => 12, 'emergency' => 3] as $leaveType => $credits) {
                $identity = [
                    'company_id' => $employee->company_id,
                    'employee_id' => $employee->id,
                    'year' => $year,
                    'leave_type' => $leaveType,
                ];

                $existing = DB::table('leave_balances')->where($identity)->exists();

                if (! $existing) {
                    DB::table('leave_balances')->insert($identity + [
                        'beginning_balance' => $credits,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropIndex(['leave_type']);
            $table->dropColumn('leave_type');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropIndex(['leave_type']);
            $table->dropColumn('leave_type');
        });
    }
};
