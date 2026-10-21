<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rebate hanya berlaku untuk sekolah (schools.rebate_percent), yang otomatis
 * dipotong saat membuat nota pesanan. Konsep rebate berbasis penerbit/aturan
 * beserta alur klaim manual (tabel rebates) dihapus seluruhnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('rebates');

        Schema::dropIfExists('rebate_rules');
    }

    public function down(): void
    {
        Schema::create('rebate_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('publisher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('percentage');
            $table->decimal('value', 15, 2)->default(0);
            $table->decimal('min_invoice_amount', 15, 2)->nullable();
            $table->boolean('requires_full_payment')->default(true);
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('rebates', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('percentage');
            $table->decimal('base_amount', 15, 2)->default(0);
            $table->decimal('value', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status')->default('draft')->index();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });
    }
};
