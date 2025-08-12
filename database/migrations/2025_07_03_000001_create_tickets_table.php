<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Ticket Types
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);  // for ordering ticket types
            $table->boolean('is_active')->default(true);        // enable/disable types
            $table->timestamps();
        });

        // Tickets
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // Custom ticket number (e.g., TCK-000001)
            $table->string('ticket_number')->unique();

            // Ownership
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('set null');

            // Type and category
            $table->foreignId('ticket_type_id')->constrained('ticket_types')->onDelete('restrict');

            // Ticket info
            $table->string('subject');
            $table->text('description')->nullable();

            // SLA tracking
            $table->timestamp('due_at')->nullable();       // SLA deadline
            $table->timestamp('resolved_at')->nullable();  // actual resolution time

            // Priority and status
            $table->enum('status', [
                'open', 'pending_approval', 'approved', 'in_progress',
                'resolved', 'closed', 'rejected'
            ])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');

            // Assignment
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();

            // Approval flow
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Attachments
            $table->json('attachments')->nullable(); // Store file paths or metadata

            // Audit trail
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_types');
    }
};
