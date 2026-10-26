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
        if (! Schema::hasColumn('suppliers', 'ocr_subtotal_keyword')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->string('ocr_subtotal_keyword')->nullable()->after('ocr_date_keyword');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('suppliers', 'ocr_subtotal_keyword')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropColumn('ocr_subtotal_keyword');
            });
        }
    }
};
