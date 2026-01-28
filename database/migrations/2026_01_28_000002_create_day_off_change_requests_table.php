<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Day Off Change Requests
        Schema::create('day_off_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->enum(
                'reason_type',
                [
                    'Offset due to Extension of Work',
                    'Schedule / Day Off Adjustment'
                ]
            );
            $table->date('extension_date')->comment('Date of extension of work');
            $table->time('time_start');
            $table->time('time_end');
            $table->decimal('number_of_hours');
            $table->date('old_date')->comment('Current day off');
            $table->date('new_date')->comment('Offset / new day off');
            $table->text('reason');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('approval_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_off_change_requests');
    }
};
