<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->boolean('leave_with_pay')
                ->default(false)
                ->after('rejection_reason')
                ->comment('Indicates whether the leave is with pay');
        });
        DB::table('leave_requests')->update(['leave_with_pay' => true]);
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('leave_with_pay');
        });
    }
};
