<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Zona harga yang dipakai saat pesanan dibuat. Disimpan agar form
            // edit dapat mengembalikan pilihan zona yang sama.
            $table->foreignId('zona_id')->nullable()->after('school_id')->constrained('zonas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zona_id');
        });
    }
};
