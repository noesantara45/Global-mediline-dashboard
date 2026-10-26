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
        Schema::table('outbound_invoices', function (Blueprint $table) {
            $table->string('sales')->nullable()->after('client_id');
            $table->string('division')->nullable()->after('sales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outbound_invoices', function (Blueprint $table) {
            $table->dropColumn(['sales', 'division']);
        });
    }
};
