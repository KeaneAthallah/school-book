<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backorder_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backorder_order_id')->constrained('backorder_orders')->cascadeOnDelete();
            $table->foreignId('backorder_id')->constrained('backorders')->cascadeOnDelete();
            $table->integer('quantity');
            $table->timestamps();

            $table->index(['backorder_order_id', 'backorder_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backorder_order_items');
    }
};