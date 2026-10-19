<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->decimal('rebate_percent', 5, 2)->nullable()->after('zona_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('availability_status')->nullable()->after('status');
        });

        // Existing rows predate the column; the app always writes a status,
        // so backfill to keep the column non-null in practice.
        DB::table('order_items')->whereNull('availability_status')->update(['availability_status' => 'available']);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('availability_status');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('rebate_percent');
        });
    }
};
