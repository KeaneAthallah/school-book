<?php

namespace Database\Seeders;

use App\Enums\OrderSourceType;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\Courier;
use App\Models\OrderSource;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Publisher;
use App\Models\RebateRule;
use App\Models\School;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\Warehouse;
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

        $warehouse = Warehouse::query()->updateOrCreate(
            ['code' => 'GDG-01'],
            ['name' => 'Gudang Utama', 'address' => 'Jl. Pendidikan No. 1, Makassar', 'status' => 'active'],
        );

        $publishers = [
            ['name' => 'Erlangga', 'contact_person' => 'Andi', 'phone' => '021-5550001'],
            ['name' => 'Yudhistira', 'contact_person' => 'Budi', 'phone' => '021-5550002'],
            ['name' => 'Gramedia Edukasi', 'contact_person' => 'Citra', 'phone' => '021-5550003'],
        ];

        $publisherModels = collect($publishers)->mapWithKeys(function (array $data): array {
            $publisher = Publisher::query()->updateOrCreate(
                ['name' => $data['name']],
                [...$data, 'address' => 'Jakarta', 'status' => 'active'],
            );

            return [$data['name'] => $publisher];
        });

        $categories = ['Buku Pelajaran', 'Buku Umum', 'LKS'];
        $categoryModels = collect($categories)->mapWithKeys(function (string $name): array {
            $category = BookCategory::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );

            return [$name => $category];
        });

        $books = [
            ['code' => 'BK-MTK-7', 'title' => 'Matematika Kelas 7', 'publisher' => 'Erlangga', 'category' => 'Buku Pelajaran', 'grade' => '7', 'subject' => 'Matematika', 'price' => '95000.00', 'min_stock' => 50],
            ['code' => 'BK-IPA-8', 'title' => 'IPA Terpadu Kelas 8', 'publisher' => 'Yudhistira', 'category' => 'Buku Pelajaran', 'grade' => '8', 'subject' => 'IPA', 'price' => '105000.00', 'min_stock' => 50],
            ['code' => 'BK-BIN-9', 'title' => 'Bahasa Indonesia Kelas 9', 'publisher' => 'Gramedia Edukasi', 'category' => 'Buku Pelajaran', 'grade' => '9', 'subject' => 'Bahasa Indonesia', 'price' => '88000.00', 'min_stock' => 30],
            ['code' => 'BK-LKS-7', 'title' => 'LKS Matematika Kelas 7', 'publisher' => 'Erlangga', 'category' => 'LKS', 'grade' => '7', 'subject' => 'Matematika', 'price' => '35000.00', 'min_stock' => 100],
        ];

        foreach ($books as $data) {
            $product = Product::query()->updateOrCreate(
                ['code' => $data['code']],
                [
                    'type' => 'book',
                    'title' => $data['title'],
                    'price' => $data['price'],
                    'cost_price' => (string) ((float) $data['price'] * 0.7),
                    'min_stock' => $data['min_stock'],
                    'rack_location' => 'RAK-'.$data['grade'],
                    'status' => 'active',
                ],
            );

            Book::query()->updateOrCreate(
                ['product_id' => $product->id],
                [
                    'isbn' => '978-602-'.Str::padLeft((string) random_int(1, 99999), 5, '0'),
                    'publisher_id' => $publisherModels[$data['publisher']]->id,
                    'category_id' => $categoryModels[$data['category']]->id,
                    'grade' => $data['grade'],
                    'subject' => $data['subject'],
                ],
            );
        }

        $salesUser = User::query()->where('email', 'sales@sekolah.test')->first();

        $schools = [
            ['npsn' => '40312345', 'name' => 'SMP Negeri 1 Makassar', 'district' => 'Ujung Pandang', 'regency' => 'Kota Makassar', 'province' => 'Sulawesi Selatan'],
            ['npsn' => '40312346', 'name' => 'SMP Negeri 5 Makassar', 'district' => 'Rappocini', 'regency' => 'Kota Makassar', 'province' => 'Sulawesi Selatan'],
            ['npsn' => '40312347', 'name' => 'SMP Islam Al-Azhar', 'district' => 'Panakkukang', 'regency' => 'Kota Makassar', 'province' => 'Sulawesi Selatan'],
        ];

        foreach ($schools as $data) {
            School::query()->updateOrCreate(
                ['npsn' => $data['npsn']],
                [
                    ...$data,
                    'address' => 'Jl. Pendidikan, '.$data['district'],
                    'contact_person' => 'Kepala Sekolah',
                    'phone' => '0411-'.random_int(100000, 999999),
                    'principal_name' => 'Drs. '.Str::ucfirst(Str::lower($data['name'])),
                    'status' => 'active',
                    'sales_user_id' => $salesUser?->id,
                ],
            );
        }

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

        unset($warehouse);
    }

    private function seedSettings(): void
    {
        $settings = [
            ['key' => 'company.name', 'value' => 'PT Buku Sekolah Nusantara', 'group' => 'company'],
            ['key' => 'company.address', 'value' => 'Jl. Pendidikan No. 1, Makassar, Sulawesi Selatan', 'group' => 'company'],
            ['key' => 'company.phone', 'value' => '0411-123456', 'group' => 'company'],
            ['key' => 'finance.tax_rate', 'value' => '0', 'group' => 'finance'],
            ['key' => 'finance.invoice_due_days', 'value' => '30', 'group' => 'finance'],
            ['key' => 'finance.rebate_requires_full_payment', 'value' => '1', 'group' => 'finance'],
            ['key' => 'inventory.low_stock_notice', 'value' => '1', 'group' => 'inventory'],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
