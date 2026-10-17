# Checklist Cakupan — Buku Panduan ARKARA PM

Dokumen ini memetakan **setiap halaman aplikasi** (route, view, izin, peran) ke **bagian
buku** dan ke **screenshot** yang harus ada. Berfungsi ganda sebagai alat verifikasi QA:
setiap baris harus punya bagian buku dan minimal satu gambar, atau alasan eksplisit
mengapa tidak dapat diambil.

- Sumber: `routes/web.php` (80 route), `app/Support/MasterDataRegistry.php` (13 resource),
  `resources/views/partials/sidebar.blade.php`, `config/rbac.php` (6 peran, 55 izin).
- Verifikasi langsung terhadap aplikasi berjalan (`php artisan serve`, MySQL `db_school`).
- Nama file gambar memakai kebab-case di `docs/panduan/screens/`.

## Legenda

- **Peran**: peran minimum yang memiliki izin melihat halaman.
- **Gambar**: nama file di `screens/` (`—` = tidak perlu).
- **Status**: `rencana` (akan diambil), `wajib` (kritis), `opsional`.

---

## A. Autentikasi & Kerangka Aplikasi

| Halaman | Route | Peran | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Halaman masuk | `GET /login` | publik | Bagian II | `01-login.png` | wajib |
| Gagal login (validasi) | `POST /login` | publik | Bagian II | `02-login-error.png` | rencana |
| Beranda pengalih | `GET /` | publik | Bagian II | — | — |
| Keluar (logout) | `POST /logout` | semua | Bagian II | — | — |

## B. Kerangka Antarmuka (semua halaman)

| Elemen | Sumber | Bagian buku | Gambar | Status |
|---|---|---|---|---|
| Sidebar navigasi penuh | `partials/sidebar.blade.php` | Bagian I, II | `10-sidebar.png` | wajib |
| Topbar + pencarian global | `partials/topbar.blade.php` | Bagian II | `11-topbar.png` | wajib |
| Dialog pencarian global (hasil) | `GET /search?q=` | Bagian II | `12-global-search.png` | wajib |
| Panel notifikasi | `GET /notifications` | Bagian II | `13-notifications.png` | wajib |
| Menu akun | `partials/topbar.blade.php` | Bagian II | `14-user-menu.png` | rencana |
| Pengalih tema (gelap) | `components/theme-switcher.blade.php` | Bagian II | `15-dark-sample.png` | rencana |

## C. Dashboard per Peran (6)

| Peran | View | Bagian buku | Gambar | Status |
|---|---|---|---|---|
| Admin | `dashboard/admin.blade.php` | Bagian III, IV | `20-dashboard-admin.png` | wajib |
| Operator Pesanan | `dashboard/operator.blade.php` | Bagian III | `21-dashboard-operator.png` | wajib |
| Gudang | `dashboard/warehouse.blade.php` | Bagian III | `22-dashboard-gudang.png` | wajib |
| Keuangan | `dashboard/finance.blade.php` | Bagian III | `23-dashboard-keuangan.png` | wajib |
| Sales / PIC | `dashboard/sales.blade.php` | Bagian III | `24-dashboard-sales.png` | wajib |
| Pimpinan | `dashboard/executive.blade.php` | Bagian III | `25-dashboard-pimpinan.png` | wajib |
| Akses terbatas | `dashboard/limited.blade.php` | Bagian III | `26-dashboard-limited.png` | opsional |

## D. Master Data (13 resource, generic CRUD)

Registry: `app/Support/MasterDataRegistry.php`. Route: `master.index/create/store/edit/update/destroy`.

| Resource | Judul | Izin | Bagian buku | Gambar index | Gambar form | Status |
|---|---|---|---|---|---|---|
| `schools` | Sekolah | `schools.*` | Bagian IV | `30-master-schools.png` | `31-master-schools-form.png` | wajib |
| `publishers` | Penerbit | `publishers.*` | Bagian IV | `32-master-publishers.png` | `33-master-publishers-form.png` | rencana |
| `categories` | Kategori Buku | `categories.manage` | Bagian IV | `34-master-categories.png` | `35-master-categories-form.png` | rencana |
| `books` | Buku | `books.*` | Bagian IV | `36-master-books.png` | `37-master-books-form.png` | wajib |
| `barang-lainnya` | Barang Lainnya | `goods.*` | Bagian IV | `38-master-goods.png` | `39-master-goods-form.png` | wajib |
| `warehouses` | Gudang | `warehouses.manage` | Bagian IV | `40-master-warehouses.png` | `41-master-warehouses-form.png` | rencana |
| `order-sources` | Sumber Pesanan | `order-sources.manage` | Bagian IV | `42-master-order-sources.png` | `43-master-order-sources-form.png` | rencana |
| `couriers` | Kurir | `couriers.manage` | Bagian IV | `44-master-couriers.png` | `45-master-couriers-form.png` | rencana |
| `shipping-methods` | Metode Pengiriman | `couriers.manage` | Bagian IV | `46-master-shipping-methods.png` | `47-master-shipping-methods-form.png` | rencana |
| `payment-methods` | Metode Pembayaran | `payment-methods.manage` | Bagian IV | `48-master-payment-methods.png` | `49-master-payment-methods-form.png` | rencana |
| `rebate-rules` | Aturan Rebate | `rebate-rules.manage` | Bagian IV | `50-master-rebate-rules.png` | `51-master-rebate-rules-form.png` | rencana |
| `settings` | Konfigurasi | `settings.manage` | Bagian IV | `52-master-settings.png` | `53-master-settings-form.png` | rencana |
| `cash-loans` | Pinjaman Tunai | `loans.*` | Bagian IV | `54-master-cash-loans.png` | `55-master-cash-loans-form.png` | rencana |

| Aksi destruktif | Sumber | Bagian buku | Gambar | Status |
|---|---|---|---|---|
| Dialog konfirmasi hapus | `master/partials/row-actions.blade.php` | Bagian IV | — (dialog `confirm()` bawaan peramban) | tidak diambil |
| Hapus ditolak (data terpakai) | `MasterDataController@destroy` | Bagian IV, VII | `57-master-delete-error.png` (pesan galat) | rencana |

> Catatan: **Zona** tidak memiliki layar CRUD sendiri. Daftar zona (Zona 3, 4, 5) disediakan
> oleh seeder; harga per zona diisi pada formulir **Buku**, dan zona dipilih per pesanan
> lewat pemilih *Zona Harga*. Izin `zonas.manage` ada di `config/rbac.php` tetapi belum
> diekspos di menu. Didokumentasikan di Bagian 4.5 dan Lampiran C.

## E. Pesanan (Orders)

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Daftar pesanan | `GET /orders` | `orders.view` | Bagian IV | `60-orders-index.png` | wajib |
| Filter sumber/status | `GET /orders?source=&status=` | `orders.view` | Bagian IV | `61-orders-filter.png` | rencana |
| Buat pesanan | `GET /orders/create` | `orders.manage` | Bagian IV | `62-orders-create.png` | wajib |
| Pemilih barang (item) | `orders/partials/item-book.blade.php` | `orders.manage` | Bagian IV | `63-orders-item-picker.png` | rencana |
| Validasi gagal | `POST /orders` | `orders.manage` | Bagian IV, VII | `64-orders-validation.png` | rencana |
| Detail (siap) | `GET /orders/{order}` | `orders.view` | Bagian IV | `65-orders-show.png` | wajib |
| Detail (terkirim) | `GET /orders/{order}` | `orders.view` | Bagian IV | `66-orders-show-shipped.png` | rencana |
| Aksi konfirmasi & pembatalan | `POST /orders/{order}/confirm`, `/cancel` | `orders.confirm`, `orders.cancel` | Bagian IV | — (dialog `confirm()` bawaan peramban) | tidak diambil |
| Nota pesanan (cetak) | `GET /orders/{order}/print` | `orders.view` | Bagian V | `69-orders-print.png` | rencana |

## F. Penerimaan Barang (Goods Receipts)

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Daftar penerimaan | `GET /receipts` | `receipts.view` | Bagian IV | `70-receipts-index.png` | wajib |
| Buat penerimaan | `GET /receipts/create` | `receipts.manage` | Bagian IV | `71-receipts-create.png` | wajib |
| Detail + konfirmasi | `GET /receipts/{receipt}` | `receipts.view` | Bagian IV | `72-receipts-show.png` | wajib |
| Aksi konfirmasi | `POST /receipts/{receipt}/confirm` | `receipts.confirm` | Bagian IV | `72-receipts-show.png` (tampak tombol) | tidak diambil |

## G. Stok

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Stok barang | `GET /stock` | `stock.view` | Bagian IV | `80-stock-index.png` | wajib |
| Modal penyesuaian | `POST /stock/adjust` | `stock.adjust` | Bagian IV | `81-stock-adjust.png` | wajib |
| Penyesuaian stok negatif | `POST /stock/adjust` | `stock.adjust` | Bagian IV, VII | `82-stock-adjust-error.png` | rencana |
| Mutasi stok | `GET /stock/movements` | `stock.view` | Bagian IV | `83-stock-movements.png` | wajib |

## H. Picking & Packing

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Daftar picking | `GET /pickings` | `fulfillment.view` | Bagian IV | `90-pickings-index.png` | wajib |
| Detail (belum ready) | `GET /pickings/{picking}` | `fulfillment.view` | Bagian IV | `91-pickings-show-draft.png` | wajib |
| Detail (packing) | `GET /pickings/{picking}` | `fulfillment.view` | Bagian IV | `92-pickings-show-picked.png` | wajib |
| Aksi selesai packing | `POST /pickings/{picking}/pack` | `fulfillment.pack` | Bagian IV | `92-pickings-show-picked.png` (tampak tombol) | tidak diambil |
| Detail (siap kirim) | `GET /pickings/{picking}` | `fulfillment.view` | Bagian IV | `94-pickings-show-packed.png` | opsional |

## I. Pengiriman (Shipments)

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Daftar pengiriman | `GET /shipments` | `shipments.view` | Bagian IV | `100-shipments-index.png` | wajib |
| Buat — langkah 1 | `GET /shipments/create` | `shipments.manage` | Bagian IV | `101-shipments-create-1.png` | wajib |
| Buat — langkah 2 | `GET /shipments/create?order_id=` | `shipments.manage` | Bagian IV | `102-shipments-create-2.png` | rencana |
| Detail + bukti foto | `GET /shipments/{shipment}` | `shipments.view` | Bagian IV | `103-shipments-show.png` | wajib |
| Form konfirmasi + unggah foto wajib | `POST /shipments/{shipment}/confirm` | `shipments.confirm` | Bagian IV | `104-shipments-confirm-form.png` (potongan form) | wajib |
| Surat jalan (cetak) | `GET /shipments/{shipment}/print` | `shipments.view` | Bagian IV, V | `105-shipments-print.png` | wajib |

## J. Invoice

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Daftar invoice | `GET /invoices` | `invoices.view` | Bagian IV | `110-invoices-index.png` | wajib |
| Detail invoice | `GET /invoices/{invoice}` | `invoices.view` | Bagian IV | `111-invoices-show.png` | wajib |
| Cetak invoice | `GET /invoices/{invoice}/print` | `invoices.view` | Bagian IV, VI | `112-invoices-print.png` | wajib |
| Unggah bukti bayar | `POST /invoices/{invoice}/attachments` | `invoices.manage` | Bagian IV | `113-invoices-proof.png` | rencana |
| Aksi batalkan invoice | `POST /invoices/{invoice}/cancel` | `invoices.manage` | Bagian IV, VII | `111-invoices-show.png` (tampak tombol) | tidak diambil |

## K. Piutang & Hutang

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Piutang (aging) | `GET /receivables` | `invoices.view` | Bagian IV | `120-receivables.png` | wajib |
| Barang belum dikirim | `GET /finance/hutang` | `invoices.view` | Bagian IV | `121-hutang.png` | wajib |

## L. Pembayaran

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Riwayat pembayaran | `GET /payments` | `payments.view` | Bagian IV | `130-payments-index.png` | wajib |
| Modal catat pembayaran | `POST /payments` | `payments.manage` | Bagian IV | `131-payments-record.png` | wajib |
| Aksi verifikasi | `POST /payments/{payment}/verify` | `payments.verify` | Bagian IV | `130-payments-index.png` (kolom Aksi) | tidak diambil |
| Aksi pembalikan | `POST /payments/{payment}/reverse` | `payments.manage` | Bagian IV | `130-payments-index.png` (kolom Aksi) | tidak diambil |

## M. Rebate / Cashback

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Daftar rebate | `GET /rebates` | `rebates.view` | Bagian IV | `140-rebates-index.png` | wajib |
| Modal ajukan rebate | `POST /rebates` | `rebates.request` | Bagian IV | `141-rebates-request.png` | wajib |
| Aksi (setujui/tolak/bayar) | `finance/rebates/partials/actions.blade.php` | `rebates.approve`/`pay` | Bagian IV | `142-rebates-actions.png` | rencana |

## N. Laporan (9 tipe + ekspor)

| Tipe | Nilai `type` | Bagian buku | Gambar | Status |
|---|---|---|---|---|
| Penjualan per Sekolah | `sales_by_school` | Bagian VI | `150-reports-sales-by-school.png` | wajib |
| Penjualan per Penerbit | `sales_by_publisher` | Bagian VI | `151-reports-sales-by-publisher.png` | rencana |
| Penjualan per Barang | `sales_by_book` | Bagian VI | `152-reports-sales-by-book.png` | wajib |
| Ringkasan Pesanan | `order_summary` | Bagian VI | `153-reports-orders-summary.png` | rencana |
| Piutang per Sekolah | `receivables` | Bagian VI | `154-reports-receivables.png` | rencana |
| Invoice Jatuh Tempo | `overdue_invoices` | Bagian VI | `155-reports-overdue.png` | rencana |
| Stok per Barang | `stock` | Bagian VI | `156-reports-stock.png` | rencana |
| Stok Menipis | `low_stock` | Bagian VI | `157-reports-low-stock.png` | rencana |
| Back Order | `backorders` | Bagian VI | `158-reports-backorders.png` | rencana |
| Halaman laporan | `GET /reports` | Bagian VI | `149-reports-index.png` | wajib |
| Ekspor CSV | `GET /reports/export` | Bagian VI | — | — |

> Nilai `type` persis diverifikasi dari `ReportController` saat penulisan Bagian VI.

## O. Pengaturan

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Profil perusahaan | `GET /settings/company` | `settings.manage` | Bagian IV | `160-settings-company.png` | wajib |
| Daftar pengguna | `GET /settings/users` | `users.view` | Bagian IV | `161-settings-users.png` | wajib |
| Tambah pengguna | `POST /settings/users` | `users.manage` | Bagian IV | `162-settings-user-create.png` | rencana |
| Peran & izin | `GET /settings/roles` | `roles.manage` | Bagian III, IV | `163-settings-roles.png` | wajib |
| Sunting peran | `PUT /settings/roles/{role}` | `roles.manage` | Bagian IV | `164-settings-role-edit.png` | rencana |
| Audit log | `GET /settings/audit` | `audit.view` | Bagian IV | `165-settings-audit.png` | wajib |
| Integrasi & import | `GET /integrations` | `integrations.view` | Bagian IV | `166-integrations.png` | wajib |

## P. Bagian Buku Lain (tanpa halaman khusus)

| Bagian buku | Sumber | Gambar |
|---|---|---|
| Bagian I — Kenal Aplikasi | narasi | `20-dashboard-admin.png` |
| Bagian V — Alur end-to-end | gabungan modul | `69-orders-print.png`, `105-shipments-print.png`, `112-invoices-print.png` |
| Bagian VII — Pemecahan masalah | pesan galat dari service layer | `02-login-error.png`, `64-orders-validation.png`, `82-stock-adjust-error.png`, `114-invoices-cancel-error.png` |
| Bagian VIII — Keamanan | `config/rbac.php`, `settings/audit` | `163-settings-roles.png`, `165-settings-audit.png` |
| Lampiran | glosarium, matriks izin, referensi cepat | `163-settings-roles.png` |

---

## Halaman yang tidak diambil gambarnya (alasan)

| Halaman | Alasan |
|---|---|
| `GET /` (beranda pengalih) | Hanya mengalihkan ke `/login` atau `/dashboard`; bukan layar bermakna. |
| `POST`-only endpoints (confirm/verify/pack/dsb.) | Tidak punya tampilan sendiri; efek visualnya diambil melalui dialog pada halaman detail. |
| `GET /storage/{path}` | Penyajian berkas statis. |
| `GET /up` | Endpoint health-check. |
| `_boost/browser-logs` | Endpoint diagnostik pengembangan. |
| CRUD Zona | Tidak ada layar CRUD; zona disediakan seeder dan harganya diisi pada formulir Buku. |
| Dialog `confirm()` bawaan peramban | Aksi konfirmasi (konfirmasi pesanan, penerimaan, packing, pengiriman, verifikasi/pembalikan pembayaran, hapus data) memakai `window.confirm()` native. Dialog ini digambar oleh peramban, bukan oleh halaman, sehingga tidak dapat dipotret Playwright. Yang dipotret adalah tombol/baris aksinya. |

---

## Verifikasi Akhir

Diperiksa terhadap aplikasi yang berjalan pada 17 Oktober 2026.

| Ukuran | Hasil |
|---|---|
| Tangkapan layar diambil | **89** berkas di `screens/` |
| Gambar dipakai di buku | **88** berkas unik, **96** pemunculan gambar bernomor |
| Gambar diambil tetapi tidak dipakai | 1 (`57b-user-delete-blocked.png`) — alasan di `QA.md` §2.1 |
| Berkas hilang yang dirujuk buku | 0 (diperiksa otomatis oleh `render.mjs`) |
| Bagian buku | 9 bab + 8 lampiran |
| Halaman PDF | **147** halaman A4 |
| Nomor halaman Daftar Isi cocok dengan isi | 9 dari 9 judul yang diuji |
| Halaman kosong | 0 |
| Isi keluar dari tepi halaman | 0 |

### Status per kelompok

| Kelompok | Jumlah | Punya bagian buku | Punya gambar | Status |
|---|---|---|---|---|
| Dashboard peran | 6 | 6 | 6 | Lulus |
| Data induk | 13 | 13 | 13 | Lulus |
| Modul operasional | 12 | 12 | 12 | Lulus |
| Jenis laporan | 9 | 9 | 9 | Lulus |
| Dokumen cetak | 3 | 3 | 3 | Lulus |

### Baris checklist yang berakhir &ldquo;tidak diambil&rdquo;

Seluruh baris bertanda `tidak diambil` bergantung pada kotak penegasan bawaan peramban dan
tidak dapat dipotret oleh perkakas otomatis. Dalam buku, keadaan tersebut disampaikan melalui
teks langkah dan tangkapan tombol/baris aksinya, bukan melalui gambar palsu. Rinciannya ada
di `QA.md` §3.
