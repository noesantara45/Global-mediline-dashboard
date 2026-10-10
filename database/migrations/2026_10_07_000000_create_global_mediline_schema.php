<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Master Data
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['Rumah Sakit', 'Klinik', 'Apotek', 'Lainnya'])->default('Rumah Sakit');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_id')->nullable()->comment('NPWP');
            $table->integer('top_days')->default(0)->comment('Term of Payment in days');
            
            // OCR Template Settings
            $table->string('ocr_invoice_keyword')->nullable();
            $table->string('ocr_date_keyword')->nullable();
            $table->string('ocr_subtotal_keyword')->nullable();
            $table->string('ocr_total_keyword')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Inbound (Pembelian langsung ke Supplier)
        Schema::create('inbound_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('issue_date');
            $table->date('due_date');
            $table->enum('status', ['Unpaid', 'Partial', 'Paid', 'Cancelled'])->default('Unpaid');
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0)->comment('PPN');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inbound_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbound_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        // 3. Outbound (Penjualan langsung ke Client)
        Schema::create('outbound_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('issue_date');
            $table->date('due_date');
            $table->enum('status', ['Unpaid', 'Partial', 'Paid', 'Cancelled'])->default('Unpaid');
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0)->comment('PPN 11%');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('outbound_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_invoice_items');
        Schema::dropIfExists('outbound_invoices');
        Schema::dropIfExists('inbound_invoice_items');
        Schema::dropIfExists('inbound_invoices');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('clients');
    }
};
