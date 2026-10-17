<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->foreignId('zona_id')->nullable()->after('sales_user_id')->constrained()->nullOnDelete();
        });

        Schema::table('books', function (Blueprint $table) {
            $table->string('classification')->nullable()->after('subject');
        });

        Schema::table('goods', function (Blueprint $table) {
            $table->string('store_name')->nullable()->after('unit');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->default(0)->after('discount_amount');
            $table->decimal('dpp_amount', 15, 2)->default(0)->after('tax_amount');
            $table->decimal('ppn_amount', 15, 2)->default(0)->after('dpp_amount');
            $table->decimal('pph_amount', 15, 2)->default(0)->after('ppn_amount');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->nullable()->after('discount_amount');
            $table->decimal('dpp_amount', 15, 2)->default(0)->after('tax_amount');
            $table->decimal('ppn_amount', 15, 2)->default(0)->after('dpp_amount');
            $table->decimal('pph_amount', 15, 2)->default(0)->after('ppn_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'dpp_amount', 'ppn_amount', 'pph_amount']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'dpp_amount', 'ppn_amount', 'pph_amount']);
        });

        Schema::table('goods', function (Blueprint $table) {
            $table->dropColumn('store_name');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('classification');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zona_id');
        });
    }
};
