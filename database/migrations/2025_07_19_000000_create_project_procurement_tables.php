<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('industry')->nullable();
            $table->string('tin')->nullable();
            $table->string('category')->nullable();
            $table->enum('client_type', ['corporate', 'government', 'individual'])->nullable();
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('rating')->nullable(); // out of 5 (or 10 if preferred)
            $table->boolean('is_active')->default(true);
            $table->string('payment_terms')->nullable();
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('position')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('linkedin_url')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->enum('project_type', ['internal', 'external', 'client-based'])->default('external');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status');
            $table->decimal('budget', 15, 2)->nullable();
            $table->string('priority')->nullable();
            $table->foreignId('project_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('locked_budget')->default(false);
            $table->decimal('budget_buffer', 15, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('completion_date_actual')->nullable();
            $table->string('risk_level')->nullable(); // low, medium, high
            $table->json('tags')->nullable();
            $table->timestamps();
        });

        Schema::create('estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Foreign Keys
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('customer_id')->nullable()->constrained('clients')->nullOnDelete(); // for "Customer *"

            // Core Details
            $table->string('estimate_number')->unique(); // Consider auto-generating in app layer
            $table->string('title')->nullable(); // "Title"
            $table->date('date_prepared'); // "Date *"

            // Status & Workflow
            $table->string('status')->default('pending'); // ex: for approval workflows
            $table->string('approval_status')->default('for_submission'); // "Cost Estimate Approval Status"
            $table->string('next_approver_role')->nullable(); // "Next Approver Role"
            $table->string('reason_for_return')->nullable(); // "Reason For Return"

            // Financial Summary
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0); // Keep for backward compatibility
            $table->string('currency', 3)->default('PHP'); // Use ISO currency code

            // Sales Info
            $table->foreignId('sales_rep_id')->nullable()->constrained('users')->nullOnDelete(); // "Sales Rep"
            $table->string('conforme')->nullable(); // "Conforme *"

            // Classification
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete(); // "Department"
            $table->string('location')->nullable();

            $table->date('valid_until')->nullable();

            // ASF
            $table->decimal('asf_percent', 5, 2)->default(0); // "ASF %"

            // Notes
            $table->text('remarks')->nullable(); // "Memo"

            $table->timestamps();
        });

        Schema::create('estimate_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('estimate_id')->constrained()->onDelete('cascade');

            // Core Item Details
            $table->text('item_description');
            $table->decimal('quantity', 12, 2);
            $table->decimal('frequency', 12, 2)->default(1); // "Frq"
            $table->string('unit');

            // Pricing Details
            $table->decimal('unit_price', 15, 2);
            $table->decimal('amount', 15, 2); // quantity × frequency × unit_price

            // ASF & Tax
            $table->decimal('asf_percent', 5, 2)->default(0); // "ASF %"
            $table->decimal('asf_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('gross_amount', 15, 2)->default(0); // amount + ASF + tax

            $table->text('remarks')->nullable();

            $table->timestamps();
        });

        Schema::create('bill_of_quantities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('boq_number')->unique();
            $table->date('date_prepared');
            $table->foreignId('prepared_by')->constrained('users');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency')->default('PHP');
            $table->string('status')->default('draft'); // draft, submitted, approved, etc.
            $table->text('remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('boq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('bill_of_quantities_id')->constrained()->onDelete('cascade');
            $table->text('item_description');
            $table->decimal('quantity', 12, 2);
            $table->string('unit');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->text('remarks')->nullable();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('tin')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->enum('vendor_type', ['supplier', 'subcontractor', 'consultant'])->nullable();
            $table->boolean('is_accredited')->default(false);
            $table->date('accreditation_expiry')->nullable();
            $table->enum('status', ['active', 'blacklisted'])->default('active');
            $table->timestamps();
        });

        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('pr_number')->unique()->nullable();
            $table->string('pr_type')->nullable();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete(); // if departments exist
            $table->date('date_requested');
            $table->string('status');
            $table->date('needed_by')->nullable();
            $table->text('purpose')->nullable();
            $table->enum('urgency', ['low', 'medium', 'high'])->default('medium');
            $table->text('priority_reason')->nullable();
            $table->boolean('is_converted_to_po')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_request_id')->constrained()->onDelete('cascade');
            $table->text('item_description');
            $table->decimal('quantity', 12, 2);
            $table->string('unit');
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->string('requested_brand')->nullable();
            $table->text('specification')->nullable();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->constrained();
            $table->string('po_number')->unique();
            $table->date('date_issued');
            $table->string('status');
            $table->string('delivery_status')->nullable();
            $table->date('delivery_date')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_status')->nullable(); // unpaid, paid, partial
            $table->date('expected_delivery_date')->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->text('item_description');
            $table->decimal('quantity', 12, 2);
            $table->string('unit');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->decimal('delivered_quantity', 12, 2)->nullable();
            $table->boolean('is_fully_delivered')->default(false);
        });

        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained();
            $table->enum('type', [
                'payment',         // Standard payment to vendor or employee
                'cash_advance',    // Advance money given before actual expense
                'reimbursement',   // Reimbursing out-of-pocket expenses
            ])->default('payment');
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->date('date_requested');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference_no')->nullable();
            $table->string('status');
            $table->string('category')->nullable();
            $table->text('remarks')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_request_id')->constrained()->onDelete('cascade');

            // You can include any of the following depending on your use case:
            $table->string('description'); // Description of the item or service
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0); // Optional computed column
            $table->string('account_code')->nullable(); // For accounting categorization if needed
            $table->text('remarks')->nullable();

            $table->timestamps();
        });

        Schema::create('cash_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users');
            $table->date('date_requested');
            $table->date('release_date')->nullable(); // actual release date
            $table->date('liquidation_due_date')->nullable(); // optional: auto-calculate from release_date + policy days
            $table->decimal('amount', 15, 2);
            $table->string('purpose')->nullable();
            $table->string('status')->default('pending'); // pending, released, partially_liquidated, fully_liquidated, overdue
            $table->text('remarks')->nullable();
            $table->decimal('liquidated_amount', 15, 2)->default(0);
            $table->decimal('outstanding_balance', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('liquidations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('cash_advance_id')->constrained()->onDelete('cascade');
            $table->foreignId('submitted_by')->constrained('users');
            $table->date('submitted_at');
            $table->string('invoice_number')->nullable();
            $table->decimal('liquidated_amount', 15, 2); // amount of this specific submission
            $table->decimal('running_total', 15, 2); // sum of all submitted liquidations (updated programmatically)
            $table->decimal('remaining_balance', 15, 2); // balance left to liquidate
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('remarks')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('liquidation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('liquidation_id')->constrained()->onDelete('cascade');
            $table->string('expense_category');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('date_incurred');
            $table->string('receipt_number')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->nullableMorphs('model');
            $table->foreignId('approved_by')->constrained('users');
            $table->string('status'); // pending, approved, rejected
            $table->integer('sequence')->nullable(); // for multi-step workflows
            $table->integer('level')->default(1); // for multilevel workflows
            $table->boolean('is_final_approval')->default(false);
            $table->dateTime('approved_at')->nullable();
            $table->string('approval_type')->nullable(); // financial, legal, etc.
            $table->foreignId('delegated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('action'); // created, updated, deleted
            $table->nullableMorphs('model');
            $table->json('changes')->nullable(); // store before/after values
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('context')->nullable();
            $table->string('origin_screen')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('liquidation_items');
        Schema::dropIfExists('liquidations');
        Schema::dropIfExists('cash_advances');
        Schema::dropIfExists('payment_request_items');
        Schema::dropIfExists('payment_requests');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('boq_items');
        Schema::dropIfExists('bill_of_quantities');
        Schema::dropIfExists('estimate_items');
        Schema::dropIfExists('estimates');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('clients');
    }
};
