<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backorder_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backorder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('publisher_id')->constrained()->restrictOnDelete();
            $table->integer('quantity');
            $table->timestamp('ordered_at')->nullable();
            $table->string('number')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['backorder_id', 'status']);
        });

        Schema::create('product_catalogs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('product_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_id')->constrained('product_catalogs')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['catalog_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_catalog_items');
        Schema::dropIfExists('product_catalogs');
        Schema::dropIfExists('backorder_orders');
    }
};
