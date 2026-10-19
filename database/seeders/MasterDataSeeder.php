<?php

namespace Database\Seeders;

use App\Enums\OrderSourceType;
use App\Models\BookCategory;
use App\Models\Courier;
use App\Models\Goods;
use App\Models\OrderSource;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Publisher;
use App\Models\RebateRule;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\Zona;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterDataSeeder extends Seeder
{
    /**
     * Seed reference/master data used by the business modules.
     */
    public function run(): void
    {
        $this->seedSettings();
        $this->seedZonas();

        $publishers = [
            ['name' => 'Erlangga', 'contact_person' => 'Andi', 'phone' => '021-5550001'],
            ['name' => 'Yudhistira', 'contact_person' => 'Budi', 'phone' => '021-5550002'],
            ['name' => 'Gramedia Edukasi', 'contact_person' => 'Citra', 'phone' => '021-5550003'],
        ];

        foreach ($publishers as $data) {
            Publisher::query()->updateOrCreate(
                ['name' => $data['name']],
                [...$data, 'address' => 'Jakarta', 'status' => 'active'],
            );
        }

        $categories = ['Buku Pelajaran', 'Buku Umum', 'LKS'];
        $categoryModels = collect($categories)->mapWithKeys(function (string $name): array {
            $category = BookCategory::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );

            return [$name => $category];
        });

        $orderSources = [
            ['code' => 'SIPLAH', 'name' => 'SIPLah', 'type' => OrderSourceType::Siplah, 'config' => ['adapter' => 'siplah', 'enabled' => false]],
            ['code' => 'EKATALOG', 'name' => 'e-Katalog', 'type' => OrderSourceType::Ekatalog, 'config' => ['adapter' => 'ekatalog', 'enabled' => false]],
            ['code' => 'MANUAL', 'name' => 'Pesanan Manual', 'type' => OrderSourceType::Manual, 'config' => null],
            ['code' => 'LAINNYA', 'name' => 'Sumber Lainnya', 'type' => OrderSourceType::Other, 'config' => null],
        ];

        foreach ($orderSources as $data) {
            OrderSource::query()->updateOrCreate(
                ['code' => $data['code']],
                ['name' => $data['name'], 'type' => $data['type'], 'is_active' => true, 'config' => $data['config']],
            );
        }

        $courier = Courier::query()->updateOrCreate(
            ['code' => 'JNE'],
            ['name' => 'JNE', 'phone' => '021-1500', 'status' => 'active'],
        );

        ShippingMethod::query()->updateOrCreate(
            ['name' => 'Reguler', 'courier_id' => $courier->id],
            ['cost' => '25000.00', 'status' => 'active'],
        );

        foreach ([
            ['code' => 'TRANSFER', 'name' => 'Transfer Bank'],
            ['code' => 'CASH', 'name' => 'Tunai'],
            ['code' => 'VA', 'name' => 'Virtual Account'],
        ] as $method) {
            PaymentMethod::query()->updateOrCreate(['code' => $method['code']], [...$method, 'status' => 'active']);
        }

        RebateRule::query()->updateOrCreate(
            ['name' => 'Rebate Penerbit 2%'],
            [
                'publisher_id' => null,
                'type' => 'percentage',
                'value' => '2.00',
                'min_invoice_amount' => '1000000.00',
                'requires_full_payment' => true,
                'status' => 'active',
            ],
        );

        $this->seedGoods();
    }

    private function seedGoods(): void
    {
        $goods = [
            ['code' => 'GL-001', 'title' => 'Kipas Angin', 'price' => 350000, 'unit' => 'unit'],
            ['code' => 'GL-002', 'title' => 'Meja Siswa', 'price' => 450000, 'unit' => 'unit'],
            ['code' => 'GL-003', 'title' => 'Kursi Siswa', 'price' => 250000, 'unit' => 'unit'],
            ['code' => 'GL-004', 'title' => 'Papan Tulis', 'price' => 750000, 'unit' => 'unit'],
            ['code' => 'GL-005', 'title' => 'Proyektor', 'price' => 3500000, 'unit' => 'unit'],
        ];

        foreach ($goods as $data) {
            $product = Product::query()->updateOrCreate(
                ['code' => $data['code']],
                [
                    'type' => 'goods',
                    'title' => $data['title'],
                    'price' => $data['price'],
                    'cost_price' => (int) ($data['price'] * 0.7),
                    'rack_location' => null,
                    'status' => 'active',
                ],
            );

            Goods::query()->updateOrCreate(
                ['product_id' => $product->getKey()],
                ['unit' => $data['unit']],
            );
        }
    }

    /**
     * Zones used by the per-zone selling prices on the Buku form.
     */
    private function seedZonas(): void
    {
        $zonas = [
            ['code' => 'Z3', 'name' => 'Zona 3'],
            ['code' => 'Z4', 'name' => 'Zona 4'],
            ['code' => 'Z5', 'name' => 'Zona 5'],
        ];

        foreach ($zonas as $zona) {
            Zona::query()->updateOrCreate(
                ['code' => $zona['code']],
                [...$zona, 'status' => 'active'],
            );
        }
    }

    private function seedSettings(): void
    {
        $settings = [
            ['key' => 'company.name', 'value' => 'PT Buku Sekolah Nusantara', 'group' => 'company'],
            ['key' => 'company.address', 'value' => 'Jl. Pendidikan No. 1, Makassar, Sulawesi Selatan', 'group' => 'company'],
            ['key' => 'company.phone', 'value' => '0411-123456', 'group' => 'company'],
            ['key' => 'finance.book_tax_rate', 'value' => '0.5', 'group' => 'finance'],
            ['key' => 'finance.goods_tax_rate', 'value' => '2', 'group' => 'finance'],
            ['key' => 'finance.ppn_rate', 'value' => '12', 'group' => 'finance'],
            ['key' => 'finance.invoice_due_days', 'value' => '30', 'group' => 'finance'],
            ['key' => 'finance.rebate_requires_full_payment', 'value' => '1', 'group' => 'finance'],
            ['key' => 'inventory.low_stock_notice', 'value' => '1', 'group' => 'inventory'],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
