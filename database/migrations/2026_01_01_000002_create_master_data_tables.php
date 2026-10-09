<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publishers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('book_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('npsn', 20)->nullable()->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('district')->nullable();
            $table->string('regency')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('principal_name')->nullable();
            $table->string('status')->default('active')->index();
            $table->foreignId('sales_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('isbn')->nullable();
            $table->string('title');
            $table->foreignId('publisher_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('book_categories')->nullOnDelete();
            $table->string('grade')->nullable();
            $table->string('subject')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->integer('min_stock')->default(0);
            $table->string('rack_location')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->index(['publisher_id', 'status']);
        });

        Schema::create('order_sources', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->default('manual')->index();
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('courier_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('cost', 15, 2)->default(0);
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('rebate_rules', function (Blueprint $table) {
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

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('rebate_rules');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('couriers');
        Schema::dropIfExists('order_sources');
        Schema::dropIfExists('books');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('book_categories');
        Schema::dropIfExists('publishers');
    }
};
