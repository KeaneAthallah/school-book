<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            // Nomor kwitansi. Satu invoice boleh punya beberapa kwitansi
            // sesuai cicilan pembayaran dari sekolah.
            $table->string('receipt_number')->nullable()->unique()->after('amount');
        });

        Schema::table('schools', function (Blueprint $table) {
            // NIP kepala sekolah, tercetak pada baris tanda tangan kwitansi.
            $table->string('principal_nip')->nullable()->after('principal_name');
        });
    }

    public function down(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropUnique(['receipt_number']);
            $table->dropColumn('receipt_number');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('principal_nip');
        });
    }
};
