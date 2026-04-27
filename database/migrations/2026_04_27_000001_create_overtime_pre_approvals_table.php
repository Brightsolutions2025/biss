<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('overtime_pre_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('employee_id')
                ->constrained()
                ->onDelete('cascade');

            $table->date('date');
            $table->time('planned_time_start');
            $table->time('planned_time_end');
            $table->decimal('estimated_number_of_hours', 8, 2);

            $table->text('reason');
            $table->text('planned_tasks')
                ->nullable()
                ->comment('Specific tasks or deliverables to be done during overtime');

            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled

            $table->foreignId('approver_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->date('approval_date')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
            $table->index(['company_id', 'date']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_pre_approvals');
    }
};
