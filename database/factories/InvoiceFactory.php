<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'INV-TEST-'.Str::upper(Str::random(8)),
            'order_id' => null,
            'shipment_id' => null,
            'school_id' => School::factory(),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => InvoiceStatus::Issued,
            'subtotal' => '0.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => '0.00',
            'paid_amount' => '0.00',
            'notes' => null,
            'created_by' => null,
        ];
    }
}
