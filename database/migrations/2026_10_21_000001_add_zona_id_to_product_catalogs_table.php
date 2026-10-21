<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_catalogs', function (Blueprint $table) {
            // The zone whose zone-specific price is printed for every product
            // in the catalog. Null falls back to each product's base price.
            $table->foreignId('zona_id')->nullable()->after('type')->constrained('zonas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_catalogs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zona_id');
        });
    }
};
