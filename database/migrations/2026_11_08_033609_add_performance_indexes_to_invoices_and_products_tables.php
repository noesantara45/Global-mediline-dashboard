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
        Schema::table('outbound_invoices', function (Blueprint $table): void {
            $table->index(['status', 'issue_date'], 'outbound_invoices_status_issue_date_idx');
            $table->index(['status', 'due_date'], 'outbound_invoices_status_due_date_idx');
            $table->index('issue_date', 'outbound_invoices_issue_date_idx');
            $table->index('division', 'outbound_invoices_division_idx');
        });

        Schema::table('inbound_invoices', function (Blueprint $table): void {
            $table->index(['status', 'issue_date'], 'inbound_invoices_status_issue_date_idx');
            $table->index(['status', 'due_date'], 'inbound_invoices_status_due_date_idx');
            $table->index('issue_date', 'inbound_invoices_issue_date_idx');
        });

        Schema::table('outbound_invoice_items', function (Blueprint $table): void {
            $table->index('item_name', 'outbound_invoice_items_item_name_idx');
        });

        Schema::table('inbound_invoice_items', function (Blueprint $table): void {
            $table->index('item_name', 'inbound_invoice_items_item_name_idx');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->index('name', 'products_name_idx');
            $table->index('brand', 'products_brand_idx');
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->index('type', 'clients_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outbound_invoices', function (Blueprint $table): void {
            $table->dropIndex('outbound_invoices_status_issue_date_idx');
            $table->dropIndex('outbound_invoices_status_due_date_idx');
            $table->dropIndex('outbound_invoices_issue_date_idx');
            $table->dropIndex('outbound_invoices_division_idx');
        });

        Schema::table('inbound_invoices', function (Blueprint $table): void {
            $table->dropIndex('inbound_invoices_status_issue_date_idx');
            $table->dropIndex('inbound_invoices_status_due_date_idx');
            $table->dropIndex('inbound_invoices_issue_date_idx');
        });

        Schema::table('outbound_invoice_items', function (Blueprint $table): void {
            $table->dropIndex('outbound_invoice_items_item_name_idx');
        });

        Schema::table('inbound_invoice_items', function (Blueprint $table): void {
            $table->dropIndex('inbound_invoice_items_item_name_idx');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_name_idx');
            $table->dropIndex('products_brand_idx');
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->dropIndex('clients_type_idx');
        });
    }
};
