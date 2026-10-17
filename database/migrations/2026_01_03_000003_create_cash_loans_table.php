<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_loans', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('person_name');
            $table->string('school_name')->nullable();
            $table->date('loan_date');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->index(['status', 'loan_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_loans');
    }
};
