<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->unique(['company_id', 'name']);
            $table->timestamps();
        });
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('number');
            $table->string('title');
            $table->unique(['company_id', 'title']);
            $table->enum(
                'type',
                [
                    '110 - Cash and Cash Equivalents',
                    '120 - Non-Cash Current Asset',
                    '150 - Non-Current Asset',
                    '210 - Current Liabilities',
                    '250 - Non-Current Liabilities',
                    '310 - Capital',
                    '320 - Share Premium',
                    '330 - Retained Earnings',
                    '340 - Other Comprehensive Income',
                    '350 - Drawing',
                    '390 - Income Summary',
                    '410 - Revenue',
                    '420 - Other Income',
                    '510 - Cost of Goods Sold',
                    '520 - Operating Expense',
                    '590 - Income Tax Expense'
                ]
            );
            $table->foreignId('line_item_id')->nullable()->constrained('line_items');
            $table->boolean('subsidiary_ledger');
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['company_id', 'name']);
        });
        Schema::create('subsidiary_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('number')->nullable();
            $table->string('name');
            $table->unique(['name', 'company_id']);
            $table->timestamps();
             
        });
        Schema::create('report_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('report');
            $table->string('section')->nullable();
            $table->string('line_item');
            $table->unique(['company_id', 'report', 'section', 'line_item']);
            $table->timestamps();
             
        });
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->unsignedBigInteger('document_type_id');
            $table->unsignedBigInteger('document_number')->nullable();
            $table->text('explanation');
            $table->timestamps();
             
            $table->foreign('document_type_id')
                ->references('id')
                ->on('documents');
            $table->nullableMorphs('journalizable');
        });
        Schema::create('postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit', 13, 2);
            $table->unsignedBigInteger('subsidiary_ledger_id')->nullable();
            $table->unsignedBigInteger('report_line_item_id')->nullable();
            $table->timestamps();
             
            $table->foreign('journal_entry_id')
                ->references('id')
                ->on('journal_entries')
                ->onDelete('cascade');
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');
            $table->foreign('subsidiary_ledger_id')
                ->references('id')
                ->on('subsidiary_ledgers');
            $table->foreign('report_line_item_id')
                ->references('id')
                ->on('report_line_items');
        });
        Schema::create('queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('category');
            $table->string('title');
            $table->text('query');
            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->onDelete('cascade'); // references `id` on `permissions` table
            $table->unique(['title', 'company_id']);
            $table->timestamps();
        });
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->unique(['name', 'company_id']);
             
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->boolean('track_quantity');
            $table->unsignedBigInteger('receivable_account_id');
            $table->unsignedBigInteger('inventory_account_id')->nullable();
            $table->unsignedBigInteger('income_account_id');
            $table->unsignedBigInteger('expense_account_id')->nullable();
            $table->unique(['name', 'company_id']);
             
            $table->foreign('receivable_account_id')
                ->references('id')
                ->on('accounts');
            $table->foreign('inventory_account_id')
                ->references('id')
                ->on('accounts');
            $table->foreign('income_account_id')
                ->references('id')
                ->on('accounts');
            $table->foreign('expense_account_id')
                ->references('id')
                ->on('accounts');
            $table->timestamps();
        });
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('supplier_id');
            $table->date('bill_date');
            $table->date('due_date');
            $table->unsignedBigInteger('bill_number');
            $table->unique(['company_id', 'supplier_id', 'bill_number']);
            $table->timestamps();
             
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');
        });
        Schema::create('bill_category_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('bill_id');
            $table->unsignedBigInteger('account_id');
            $table->text('description')->nullable();
            $table->decimal('amount', 13, 2);
            $table->decimal('input_tax', 13, 2)->default(0);
            $table->timestamps();
            $table->foreign('bill_id')
                ->references('id')
                ->on('bills')
                ->onDelete('cascade');
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');
        });
        Schema::create('bill_item_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('bill_id');
            $table->unsignedBigInteger('product_id');
            $table->text('description')->nullable();
            $table->float('quantity', 8, 2)->nullable();
            $table->decimal('amount', 13, 2);
            $table->decimal('input_tax', 13, 2)->default(0);
            $table->timestamps();
            $table->foreign('bill_id')
                ->references('id')
                ->on('bills')
                ->onDelete('cascade');
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
        });
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->unsignedBigInteger('product_id');
            $table->float('quantity', 8, 2)->nullable();
            $table->decimal('amount', 13, 2);
            $table->timestamps();
             
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
            $table->morphs('purchasable');
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->unique(['name', 'company_id']);
             
            $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('customer_id');
            $table->date('date');
            $table->date('due_date');
            $table->unsignedBigInteger('invoice_number');
            $table->timestamps();
            $table->unique(['company_id', 'invoice_number']);
             
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers');
        });
        Schema::create('invoice_item_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('product_id');
            $table->text('description')->nullable();
            $table->float('quantity', 8, 2)->nullable();
            $table->decimal('amount', 13, 2);
            $table->decimal('output_tax', 13, 2)->default(0);
            $table->timestamps();
            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->onDelete('cascade');
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
        });
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('purchase_id');
            $table->date('date');
            $table->unsignedBigInteger('product_id');
            $table->float('quantity', 8, 2)->nullable();
            $table->decimal('amount', 13, 2);
            $table->timestamps();
             
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
            $table->foreign('purchase_id')
                ->references('id')
                ->on('purchases')
                ->onDelete('cascade');
            $table->morphs('salable');
        });
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->date('date');
            $table->timestamps();
             
            $table->morphs('transactable');
        });
        Schema::create('sales_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('customer_id');
            $table->date('date');
            $table->unsignedBigInteger('number');
            $table->unique(['company_id', 'number']);
            $table->unsignedBigInteger('account_id');
            $table->timestamps();
             
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers');
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');
        });
        Schema::create('sales_receipt_item_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('sales_receipt_id');
            $table->unsignedBigInteger('product_id');
            $table->text('description')->nullable();
            $table->float('quantity', 8, 2)->nullable();
            $table->decimal('amount', 13, 2);
            $table->decimal('output_tax', 13, 2)->default(0);
            $table->timestamps();
            $table->foreign('sales_receipt_id')
                ->references('id')
                ->on('sales_receipts')
                ->onDelete('cascade');
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
        });
        Schema::create('received_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('customer_id');
            $table->date('date');
            $table->unsignedBigInteger('number');
            $table->unique(['company_id', 'number']);
            $table->unsignedBigInteger('account_id');
            $table->timestamps();
             
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers');
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');
        });
        Schema::create('received_payment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('received_payment_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 13, 2);
            $table->timestamps();
             
            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices');
            $table->foreign('received_payment_id')
                ->references('id')
                ->on('received_payments')
                ->onDelete('cascade');
        });
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('invoice_id');
            $table->date('date');
            $table->unsignedBigInteger('number');
            $table->timestamps();
            $table->unique(['company_id', 'number']);
             
            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices');
        });
        Schema::create('credit_note_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('credit_note_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('product_id');
            $table->text('description')->nullable();
            $table->float('quantity', 8, 2)->nullable();
            $table->decimal('amount', 13, 2);
            $table->decimal('output_tax', 13, 2)->default(0);
            $table->timestamps();
            $table->foreign('credit_note_id')
                ->references('id')
                ->on('credit_notes')
                ->onDelete('cascade');
            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices');
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
        });
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('sale_id');
            $table->date('date');
            $table->unsignedBigInteger('product_id');
            $table->float('quantity', 8, 2)->nullable();
            $table->decimal('amount', 13, 2);
            $table->timestamps();
             
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
            $table->foreign('sale_id')
                ->references('id')
                ->on('sales')
                ->onDelete('cascade');
            $table->morphs('returnable_sale');
        });
        Schema::create('supplier_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('supplier_id');
            $table->morphs('purchasable');
            $table->date('date');
            $table->unsignedBigInteger('number');
            $table->unique(['company_id', 'supplier_id', 'purchasable_type',  'number'],
                'uniq_supplier_credit_com_sup_type_num'
            );
            $table->timestamps();
             
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');
        });
        Schema::create('supplier_credit_clines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('supplier_credit_id');
            $table->morphs('purchasable');
            $table->unsignedBigInteger('account_id');
            $table->text('description')->nullable();
            $table->decimal('amount', 13, 2);
            $table->decimal('input_tax', 13, 2)->default(0);
            $table->timestamps();
            $table->foreign('supplier_credit_id')
                ->references('id')
                ->on('supplier_credits')
                ->onDelete('cascade');
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');
        });
        Schema::create('supplier_credit_ilines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('supplier_credit_id');
            $table->morphs('purchasable');
            $table->unsignedBigInteger('product_id');
            $table->text('description')->nullable();
            $table->float('quantity', 8, 2)->nullable();
            $table->decimal('amount', 13, 2);
            $table->decimal('input_tax', 13, 2)->default(0);
            $table->timestamps();
            $table->foreign('supplier_credit_id')
                ->references('id')
                ->on('supplier_credits')
                ->onDelete('cascade');
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
        });
        Schema::create('purc_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->unsignedBigInteger('product_id');
            $table->float('quantity', 8, 2)->nullable();
            $table->decimal('amount', 13, 2);
            $table->timestamps();
             
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
            $table->morphs('returnablepurc');
            $table->morphs('purchasable');
        });
        Schema::create('inventory_qty_adjs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->unsignedBigInteger('number');
            $table->unsignedBigInteger('account_id');
            $table->unique(['company_id', 'number']);
            $table->timestamps();
             
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');
        });
        Schema::create('inventory_qty_adj_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('inventory_qty_adj_id');
            $table->unsignedBigInteger('product_id');
            $table->text('description')->nullable();
            $table->float('change_in_qty', 8, 2)->nullable();
            $table->timestamps();
            $table->foreign('inventory_qty_adj_id')
                ->references('id')
                ->on('inventory_qty_adjs')
                ->onDelete('cascade');
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
        });
        Schema::create('cash_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('account_id');
            $table->date('date');
            $table->unsignedBigInteger('number');
            $table->text('memo')->nullable();
            $table->unique(['company_id', 'number']);
            $table->timestamps();
             
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');
        });
        Schema::create('cash_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('cash_receipt_id');
            $table->unsignedBigInteger('subsidiary_ledger_id');
            $table->unsignedBigInteger('account_id');
            $table->text('description')->nullable();
            $table->decimal('amount', 13, 2);
            $table->decimal('output_tax', 13, 2)->default(0);
            $table->timestamps();
            $table->foreign('cash_receipt_id')
                ->references('id')
                ->on('cash_receipts')
                ->onDelete('cascade');
            $table->foreign('subsidiary_ledger_id')
                ->references('id')
                ->on('subsidiary_ledgers');
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');
        });

        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('supplier_id');
            $table->date('date');
            $table->unsignedBigInteger('number');
            $table->unique(['company_id', 'supplier_id', 'number']);
            $table->unsignedBigInteger('account_id');
            $table->timestamps();
             
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');
        });
        Schema::create('bill_payment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('bill_payment_id');
            $table->unsignedBigInteger('bill_id');
            $table->decimal('amount', 13, 2);
            $table->timestamps();
             
            $table->foreign('bill_id')
                ->references('id')
                ->on('bills');
            $table->foreign('bill_payment_id')
                ->references('id')
                ->on('bill_payments')
                ->onDelete('cascade');
        });
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('supplier_id');
            $table->date('date');
            $table->unsignedBigInteger('number');
            $table->unique(['company_id', 'number']);
            $table->unsignedBigInteger('account_id');
            $table->timestamps();
             
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');
        });
        Schema::create('cheque_category_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('cheque_id');
            $table->unsignedBigInteger('account_id');
            $table->text('description')->nullable();
            $table->decimal('amount', 13, 2);
            $table->decimal('input_tax', 13, 2)->default(0);
            $table->timestamps();
            $table->foreign('cheque_id')
                ->references('id')
                ->on('cheques')
                ->onDelete('cascade');
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');
        });
        Schema::create('cheque_item_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('cheque_id');
            $table->unsignedBigInteger('product_id');
            $table->text('description')->nullable();
            $table->float('quantity', 8, 2)->nullable();
            $table->decimal('amount', 13, 2);
            $table->decimal('input_tax', 13, 2)->default(0);
            $table->timestamps();
            $table->foreign('cheque_id')
                ->references('id')
                ->on('cheques')
                ->onDelete('cascade');
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheque_item_lines');
        Schema::dropIfExists('cheque_category_lines');
        Schema::dropIfExists('cheques');
        Schema::dropIfExists('bill_payment_lines');
        Schema::dropIfExists('bill_payments');
        Schema::dropIfExists('cash_receipt_lines');
        Schema::dropIfExists('cash_receipts');
        Schema::dropIfExists('inventory_qty_adj_lines');
        Schema::dropIfExists('inventory_qty_adjs');
        Schema::dropIfExists('purc_returns');
        Schema::dropIfExists('supplier_credit_ilines');
        Schema::dropIfExists('supplier_credit_clines');
        Schema::dropIfExists('supplier_credits');
        Schema::dropIfExists('sales_returns');
        Schema::dropIfExists('credit_note_lines');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('received_payment_lines');
        Schema::dropIfExists('received_payments');
        Schema::dropIfExists('sales_receipt_item_lines');
        Schema::dropIfExists('sales_receipts');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('invoice_item_lines');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('bill_item_lines');
        Schema::dropIfExists('bill_category_lines');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('queries');
        Schema::dropIfExists('postings');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('report_line_items');
        Schema::dropIfExists('subsidiary_ledgers');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('line_items');
    }

};
