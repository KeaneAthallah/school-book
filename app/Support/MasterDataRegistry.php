<?php

namespace App\Support;

use App\Enums\OrderSourceType;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\Courier as CourierModel;
use App\Models\Goods;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Declarative definitions for the simple master-data CRUD screens handled by
 * the generic MasterDataController. Keeping the whitelist here prevents the
 * generic resource route from being pointed at arbitrary models.
 */
class MasterDataRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        static $definitions = null;

        return $definitions ??= [
            'schools' => self::schools(),
            'publishers' => self::publishers(),
            'categories' => self::categories(),
            'books' => self::books(),
            'barang-lainnya' => self::goods(),
            'warehouses' => self::warehouses(),
            'order-sources' => self::orderSources(),
            'couriers' => self::couriers(),
            'shipping-methods' => self::shippingMethods(),
            'payment-methods' => self::paymentMethods(),
            'rebate-rules' => self::rebateRules(),
            'settings' => self::settings(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $resource): ?array
    {
        return self::all()[$resource] ?? null;
    }

    /**
     * @return array<int, array{value: mixed, label: string}>
     */
    public static function activeOptions(string $modelClass, string $labelColumn = 'name'): array
    {
        /** @var class-string<Model> $modelClass */
        return $modelClass::query()
            ->orderBy($labelColumn)
            ->get(['id', $labelColumn])
            ->map(fn (Model $model): array => [
                'value' => $model->getKey(),
                'label' => (string) $model->getAttribute($labelColumn),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function base(string $title, string $model, string $view, string $manage): array
    {
        return [
            'title' => $title,
            'model' => $model,
            'view_permission' => $view,
            'manage_permission' => $manage,
            'with' => [],
            'search' => [],
            'sort' => 'id',
            'columns' => [],
            'fields' => [],
            'rules' => fn (?Model $model): array => [],
            'prepare' => null,
            'scope' => null,
            'joins' => [],
            'values' => null,
            'save' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function schools(): array
    {
        return [
            ...self::base('Sekolah', School::class, 'schools.view', 'schools.manage'),
            'with' => ['salesUser:id,name'],
            'search' => ['name', 'npsn', 'district'],
            'sort' => 'name',
            'columns' => [
                ['key' => 'npsn', 'label' => 'NPSN'],
                ['key' => 'name', 'label' => 'Nama Sekolah'],
                ['key' => 'district', 'label' => 'Kecamatan'],
                ['key' => 'regency', 'label' => 'Kab/Kota'],
                ['key' => 'salesUser.name', 'label' => 'Sales/PIC'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
            ],
            'fields' => [
                ['name' => 'npsn', 'label' => 'NPSN', 'type' => 'text'],
                ['name' => 'name', 'label' => 'Nama Sekolah', 'type' => 'text', 'required' => true],
                ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea'],
                ['name' => 'district', 'label' => 'Kecamatan', 'type' => 'text'],
                ['name' => 'regency', 'label' => 'Kabupaten/Kota', 'type' => 'text'],
                ['name' => 'province', 'label' => 'Provinsi', 'type' => 'text'],
                ['name' => 'postal_code', 'label' => 'Kode Pos', 'type' => 'text'],
                ['name' => 'contact_person', 'label' => 'Kontak', 'type' => 'text'],
                ['name' => 'phone', 'label' => 'Telepon', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ['name' => 'principal_name', 'label' => 'Kepala Sekolah', 'type' => 'text'],
                ['name' => 'sales_user_id', 'label' => 'Sales/PIC', 'type' => 'select', 'options' => fn (): array => self::activeOptions(User::class)],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [['value' => 'active', 'label' => 'Aktif'], ['value' => 'inactive', 'label' => 'Nonaktif']]],
            ],
            'rules' => fn (?Model $model): array => [
                'npsn' => ['nullable', 'string', 'max:20', Rule::unique('schools', 'npsn')->ignore($model?->getKey())],
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'sales_user_id' => ['nullable', 'integer', 'exists:users,id'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function publishers(): array
    {
        return [
            ...self::base('Penerbit', Publisher::class, 'publishers.view', 'publishers.manage'),
            'search' => ['name', 'contact_person'],
            'sort' => 'name',
            'columns' => [
                ['key' => 'name', 'label' => 'Nama Penerbit'],
                ['key' => 'contact_person', 'label' => 'Kontak'],
                ['key' => 'phone', 'label' => 'Telepon'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Nama Penerbit', 'type' => 'text', 'required' => true],
                ['name' => 'contact_person', 'label' => 'Kontak', 'type' => 'text'],
                ['name' => 'phone', 'label' => 'Telepon', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [['value' => 'active', 'label' => 'Aktif'], ['value' => 'inactive', 'label' => 'Nonaktif']]],
            ],
            'rules' => fn (?Model $model): array => [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function categories(): array
    {
        return [
            ...self::base('Kategori Buku', BookCategory::class, 'categories.manage', 'categories.manage'),
            'search' => ['name'],
            'sort' => 'name',
            'columns' => [
                ['key' => 'name', 'label' => 'Nama Kategori'],
                ['key' => 'description', 'label' => 'Deskripsi'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Nama Kategori', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
            ],
            'rules' => fn (?Model $model): array => [
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:255'],
            ],
            'prepare' => function (Model $model, array $attributes): array {
                $attributes['slug'] = Str::slug($attributes['name']).'-'.substr(md5($attributes['name'].microtime(true)), 0, 4);

                return $attributes;
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function books(): array
    {
        return [
            ...self::base('Buku', Book::class, 'books.view', 'books.manage'),
            'with' => ['product', 'publisher:id,name', 'category:id,name'],
            'scope' => fn ($query) => $query->whereHas('product', fn ($q) => $q->where('type', 'book')),
            'joins' => [['products', 'products.id', '=', 'books.product_id']],
            'search' => ['products.code', 'products.title', 'isbn'],
            'sort' => 'products.title',
            'columns' => [
                ['key' => 'product.code', 'label' => 'Kode'],
                ['key' => 'product.title', 'label' => 'Judul'],
                ['key' => 'publisher.name', 'label' => 'Penerbit'],
                ['key' => 'category.name', 'label' => 'Kategori'],
                ['key' => 'grade', 'label' => 'Kelas'],
                ['key' => 'subject', 'label' => 'Mapel'],
                ['key' => 'product.price', 'label' => 'Harga', 'type' => 'currency'],
                ['key' => 'product.status', 'label' => 'Status', 'type' => 'status'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Kode Buku', 'type' => 'text', 'required' => true],
                ['name' => 'isbn', 'label' => 'ISBN', 'type' => 'text'],
                ['name' => 'title', 'label' => 'Judul', 'type' => 'text', 'required' => true],
                ['name' => 'publisher_id', 'label' => 'Penerbit', 'type' => 'select', 'required' => true, 'options' => fn (): array => self::activeOptions(Publisher::class)],
                ['name' => 'category_id', 'label' => 'Kategori', 'type' => 'select', 'options' => fn (): array => self::activeOptions(BookCategory::class)],
                ['name' => 'grade', 'label' => 'Kelas', 'type' => 'text'],
                ['name' => 'subject', 'label' => 'Mata Pelajaran', 'type' => 'text'],
                ['name' => 'price', 'label' => 'Harga Jual', 'type' => 'number', 'required' => true],
                ['name' => 'cost_price', 'label' => 'Harga Pokok', 'type' => 'number'],
                ['name' => 'min_stock', 'label' => 'Stok Minimum', 'type' => 'number'],
                ['name' => 'rack_location', 'label' => 'Lokasi Rak', 'type' => 'text'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [['value' => 'active', 'label' => 'Aktif'], ['value' => 'inactive', 'label' => 'Nonaktif']]],
            ],
            'rules' => fn (?Book $model): array => [
                'code' => ['required', 'string', 'max:255', Rule::unique('products', 'code')->ignore($model?->product->getKey())],
                'isbn' => ['nullable', 'string', 'max:255'],
                'title' => ['required', 'string', 'max:255'],
                'publisher_id' => ['required', 'integer', 'exists:publishers,id'],
                'category_id' => ['nullable', 'integer', 'exists:book_categories,id'],
                'grade' => ['nullable', 'string', 'max:255'],
                'subject' => ['nullable', 'string', 'max:255'],
                'price' => ['required', 'numeric', 'min:0'],
                'cost_price' => ['nullable', 'numeric', 'min:0'],
                'min_stock' => ['nullable', 'integer', 'min:0'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
            'values' => function (Book $book): array {
                $product = $book->product;

                return [
                    'code' => $product?->code,
                    'title' => $product?->title,
                    'isbn' => $book->isbn,
                    'publisher_id' => $book->publisher_id,
                    'category_id' => $book->category_id,
                    'grade' => $book->grade,
                    'subject' => $book->subject,
                    'price' => $product?->price,
                    'cost_price' => $product?->cost_price,
                    'min_stock' => $product?->min_stock,
                    'rack_location' => $product?->rack_location,
                    'status' => $product?->status,
                ];
            },
            'save' => function (array $attributes, ?Book $book): Book {
                $product = $book === null ? new Product : $book->product;
                $product->fill([
                    'type' => 'book',
                    'code' => $attributes['code'],
                    'title' => $attributes['title'],
                    'price' => $attributes['price'],
                    'cost_price' => $attributes['cost_price'] ?? 0,
                    'min_stock' => $attributes['min_stock'] ?? 0,
                    'rack_location' => $attributes['rack_location'] ?? null,
                    'status' => $attributes['status'],
                ])->save();

                $book ??= new Book;
                $book->fill([
                    'product_id' => $product->getKey(),
                    'isbn' => $attributes['isbn'] ?? null,
                    'publisher_id' => $attributes['publisher_id'],
                    'category_id' => $attributes['category_id'] ?? null,
                    'grade' => $attributes['grade'] ?? null,
                    'subject' => $attributes['subject'] ?? null,
                ])->save();

                return $book;
            },
        ];
    }

    /**
     * Barang lainnya - general goods (kipas angin, meja, ...) stored as
     * products with type = "goods" plus a goods row, flowing through
     * orders, picking, shipments and invoices like books.
     *
     * @return array<string, mixed>
     */
    private static function goods(): array
    {
        return [
            ...self::base('Barang Lainnya', Goods::class, 'goods.view', 'goods.manage'),
            'with' => ['product'],
            'scope' => fn ($query) => $query->whereHas('product', fn ($q) => $q->where('type', 'goods')),
            'joins' => [['products', 'products.id', '=', 'goods.product_id']],
            'search' => ['products.code', 'products.title'],
            'sort' => 'products.title',
            'columns' => [
                ['key' => 'product.code', 'label' => 'Kode'],
                ['key' => 'product.title', 'label' => 'Nama Barang'],
                ['key' => 'product.price', 'label' => 'Harga', 'type' => 'currency'],
                ['key' => 'product.cost_price', 'label' => 'Harga Pokok', 'type' => 'currency'],
                ['key' => 'product.min_stock', 'label' => 'Stok Minimum'],
                ['key' => 'product.status', 'label' => 'Status', 'type' => 'status'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Kode Barang', 'type' => 'text', 'required' => true],
                ['name' => 'title', 'label' => 'Nama Barang', 'type' => 'text', 'required' => true],
                ['name' => 'price', 'label' => 'Harga Jual', 'type' => 'number', 'required' => true],
                ['name' => 'cost_price', 'label' => 'Harga Pokok', 'type' => 'number'],
                ['name' => 'min_stock', 'label' => 'Stok Minimum', 'type' => 'number'],
                ['name' => 'rack_location', 'label' => 'Lokasi Rak', 'type' => 'text'],
                ['name' => 'unit', 'label' => 'Satuan', 'type' => 'text'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [['value' => 'active', 'label' => 'Aktif'], ['value' => 'inactive', 'label' => 'Nonaktif']]],
            ],
            'rules' => fn (?Goods $model): array => [
                'code' => ['required', 'string', 'max:255', Rule::unique('products', 'code')->ignore($model?->product->getKey())],
                'title' => ['required', 'string', 'max:255'],
                'price' => ['required', 'numeric', 'min:0'],
                'cost_price' => ['nullable', 'numeric', 'min:0'],
                'min_stock' => ['nullable', 'integer', 'min:0'],
                'rack_location' => ['nullable', 'string', 'max:255'],
                'unit' => ['nullable', 'string', 'max:255'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
            'values' => function (Goods $goods): array {
                $product = $goods->product;

                return [
                    'code' => $product?->code,
                    'title' => $product?->title,
                    'price' => $product?->price,
                    'cost_price' => $product?->cost_price,
                    'min_stock' => $product?->min_stock,
                    'rack_location' => $product?->rack_location,
                    'unit' => $goods->unit,
                    'status' => $product?->status,
                ];
            },
            'save' => function (array $attributes, ?Goods $goods): Goods {
                $product = $goods === null ? new Product : $goods->product;
                $product->fill([
                    'type' => 'goods',
                    'code' => $attributes['code'],
                    'title' => $attributes['title'],
                    'price' => $attributes['price'],
                    'cost_price' => $attributes['cost_price'] ?? 0,
                    'min_stock' => $attributes['min_stock'] ?? 0,
                    'rack_location' => $attributes['rack_location'] ?? null,
                    'status' => $attributes['status'],
                ])->save();

                $goods ??= new Goods;
                $goods->fill([
                    'product_id' => $product->getKey(),
                    'unit' => $attributes['unit'] ?? null,
                ])->save();

                return $goods;
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function warehouses(): array
    {
        return [
            ...self::base('Gudang', Warehouse::class, 'warehouses.manage', 'warehouses.manage'),
            'search' => ['code', 'name'],
            'sort' => 'name',
            'columns' => [
                ['key' => 'code', 'label' => 'Kode'],
                ['key' => 'name', 'label' => 'Nama Gudang'],
                ['key' => 'address', 'label' => 'Alamat'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Kode', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Nama Gudang', 'type' => 'text', 'required' => true],
                ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [['value' => 'active', 'label' => 'Aktif'], ['value' => 'inactive', 'label' => 'Nonaktif']]],
            ],
            'rules' => fn (?Model $model): array => [
                'code' => ['required', 'string', 'max:255', Rule::unique('warehouses', 'code')->ignore($model?->getKey())],
                'name' => ['required', 'string', 'max:255'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function orderSources(): array
    {
        return [
            ...self::base('Sumber Pesanan', OrderSource::class, 'order-sources.manage', 'order-sources.manage'),
            'search' => ['code', 'name'],
            'sort' => 'name',
            'columns' => [
                ['key' => 'code', 'label' => 'Kode'],
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'type', 'label' => 'Tipe', 'type' => 'enum'],
                ['key' => 'is_active', 'label' => 'Aktif', 'type' => 'boolean'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Kode', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                ['name' => 'type', 'label' => 'Tipe', 'type' => 'select', 'required' => true, 'options' => collect(OrderSourceType::options())->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])->values()->all()],
                ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox'],
            ],
            'rules' => fn (?Model $model): array => [
                'code' => ['required', 'string', 'max:255', Rule::unique('order_sources', 'code')->ignore($model?->getKey())],
                'name' => ['required', 'string', 'max:255'],
                'type' => ['required', Rule::in(OrderSourceType::values())],
                'is_active' => ['boolean'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function couriers(): array
    {
        return [
            ...self::base('Kurir', CourierModel::class, 'couriers.manage', 'couriers.manage'),
            'search' => ['code', 'name'],
            'sort' => 'name',
            'columns' => [
                ['key' => 'code', 'label' => 'Kode'],
                ['key' => 'name', 'label' => 'Nama Kurir'],
                ['key' => 'phone', 'label' => 'Telepon'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Kode', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Nama Kurir', 'type' => 'text', 'required' => true],
                ['name' => 'phone', 'label' => 'Telepon', 'type' => 'text'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [['value' => 'active', 'label' => 'Aktif'], ['value' => 'inactive', 'label' => 'Nonaktif']]],
            ],
            'rules' => fn (?Model $model): array => [
                'code' => ['required', 'string', 'max:255', Rule::unique('couriers', 'code')->ignore($model?->getKey())],
                'name' => ['required', 'string', 'max:255'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function shippingMethods(): array
    {
        return [
            ...self::base('Metode Pengiriman', ShippingMethod::class, 'couriers.manage', 'couriers.manage'),
            'with' => ['courier:id,name'],
            'search' => ['name'],
            'sort' => 'name',
            'columns' => [
                ['key' => 'name', 'label' => 'Nama Metode'],
                ['key' => 'courier.name', 'label' => 'Kurir'],
                ['key' => 'cost', 'label' => 'Biaya', 'type' => 'currency'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Nama Metode', 'type' => 'text', 'required' => true],
                ['name' => 'courier_id', 'label' => 'Kurir', 'type' => 'select', 'options' => fn (): array => self::activeOptions(CourierModel::class)],
                ['name' => 'cost', 'label' => 'Biaya', 'type' => 'number'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [['value' => 'active', 'label' => 'Aktif'], ['value' => 'inactive', 'label' => 'Nonaktif']]],
            ],
            'rules' => fn (?Model $model): array => [
                'name' => ['required', 'string', 'max:255'],
                'courier_id' => ['nullable', 'integer', 'exists:couriers,id'],
                'cost' => ['nullable', 'numeric', 'min:0'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function paymentMethods(): array
    {
        return [
            ...self::base('Metode Pembayaran', PaymentMethod::class, 'payment-methods.manage', 'payment-methods.manage'),
            'search' => ['code', 'name'],
            'sort' => 'name',
            'columns' => [
                ['key' => 'code', 'label' => 'Kode'],
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
            ],
            'fields' => [
                ['name' => 'code', 'label' => 'Kode', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [['value' => 'active', 'label' => 'Aktif'], ['value' => 'inactive', 'label' => 'Nonaktif']]],
            ],
            'rules' => fn (?Model $model): array => [
                'code' => ['required', 'string', 'max:255', Rule::unique('payment_methods', 'code')->ignore($model?->getKey())],
                'name' => ['required', 'string', 'max:255'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function rebateRules(): array
    {
        return [
            ...self::base('Aturan Rebate', RebateRule::class, 'rebate-rules.manage', 'rebate-rules.manage'),
            'with' => ['publisher:id,name'],
            'search' => ['name'],
            'sort' => 'name',
            'columns' => [
                ['key' => 'name', 'label' => 'Nama Aturan'],
                ['key' => 'publisher.name', 'label' => 'Penerbit'],
                ['key' => 'type', 'label' => 'Tipe'],
                ['key' => 'value', 'label' => 'Nilai', 'type' => 'currency'],
                ['key' => 'requires_full_payment', 'label' => 'Wajib Lunas', 'type' => 'boolean'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Nama Aturan', 'type' => 'text', 'required' => true],
                ['name' => 'publisher_id', 'label' => 'Penerbit', 'type' => 'select', 'options' => fn (): array => self::activeOptions(Publisher::class)],
                ['name' => 'type', 'label' => 'Tipe', 'type' => 'select', 'required' => true, 'options' => [['value' => 'percentage', 'label' => 'Persentase (%)'], ['value' => 'fixed', 'label' => 'Nominal Tetap']]],
                ['name' => 'value', 'label' => 'Nilai', 'type' => 'number', 'required' => true],
                ['name' => 'min_invoice_amount', 'label' => 'Minimum Invoice', 'type' => 'number'],
                ['name' => 'requires_full_payment', 'label' => 'Wajib Lunas', 'type' => 'checkbox'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [['value' => 'active', 'label' => 'Aktif'], ['value' => 'inactive', 'label' => 'Nonaktif']]],
            ],
            'rules' => fn (?Model $model): array => [
                'name' => ['required', 'string', 'max:255'],
                'publisher_id' => ['nullable', 'integer', 'exists:publishers,id'],
                'type' => ['required', Rule::in(['percentage', 'fixed'])],
                'value' => ['required', 'numeric', 'min:0'],
                'min_invoice_amount' => ['nullable', 'numeric', 'min:0'],
                'requires_full_payment' => ['boolean'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function settings(): array
    {
        return [
            ...self::base('Konfigurasi', Setting::class, 'settings.manage', 'settings.manage'),
            'search' => ['key', 'group'],
            'sort' => 'key',
            'columns' => [
                ['key' => 'group', 'label' => 'Grup'],
                ['key' => 'key', 'label' => 'Kunci'],
                ['key' => 'value', 'label' => 'Nilai'],
            ],
            'fields' => [
                ['name' => 'key', 'label' => 'Kunci', 'type' => 'text', 'required' => true],
                ['name' => 'value', 'label' => 'Nilai', 'type' => 'text'],
                ['name' => 'group', 'label' => 'Grup', 'type' => 'text', 'required' => true],
            ],
            'rules' => fn (?Model $model): array => [
                'key' => ['required', 'string', 'max:255', Rule::unique('settings', 'key')->ignore($model?->getKey())],
                'value' => ['nullable', 'string'],
                'group' => ['required', 'string', 'max:255'],
            ],
        ];
    }
}
