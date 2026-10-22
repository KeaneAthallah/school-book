# Checklist Cakupan — Buku Panduan ARKARA PM

Dokumen ini memetakan **setiap halaman aplikasi** (route, view, izin, peran) ke **bagian
buku** dan ke **screenshot** yang harus ada. Berfungsi ganda sebagai alat verifikasi QA:
setiap baris harus punya bagian buku dan minimal satu gambar, atau alasan eksplisit
mengapa tidak dapat diambil.

- Sumber: `routes/web.php` (**112 route** terdaftar, termasuk route bawaan kerangka),
  `app/Support/MasterDataRegistry.php` (**11 resource**), `resources/views/partials/sidebar.blade.php`,
  `config/rbac.php` (**6 peran, 52 izin**).
- Verifikasi langsung terhadap aplikasi berjalan (`php artisan serve`, MySQL `db_school`).
- Nama file gambar memakai kebab-case di `docs/panduan/screens/`.
- Angka pada tabel verifikasi diambil dari `docs/panduan/screens/inventory.md` dan
  `docs/panduan/build/build-report.json`.

## Legenda

- **Peran**: peran minimum yang memiliki izin melihat halaman.
- **Gambar**: nama file di `screens/` (`—` = tidak perlu).
- **Status**: `wajib` (kritis), `opsional`, atau `tidak diambil` (alasannya dijelaskan).

---

## A. Autentikasi & Kerangka Aplikasi

| Halaman | Route | Peran | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Halaman masuk | `GET /login` | publik | 2.2–2.3 | `01-login.png` | wajib |
| Gagal login (validasi) | `POST /login` | publik | 2.4, 8.1 | `02-login-error.png` | wajib |
| Beranda pengalih | `GET /` | publik | — | — | tidak diambil |
| Keluar (logout) | `POST /logout` | semua | 2.8, 9.3 | — | tidak diambil |
| Ganti kata sandi | `PUT /settings/users/{user}` (profil sendiri) | semua | 2.9 | — | tidak diambil |

## B. Kerangka Antarmuka (semua halaman)

| Elemen | Sumber | Bagian buku | Gambar | Status |
|---|---|---|---|---|
| Kerangka lengkap | `layouts/app.blade.php` | 1.3, 2.5 | `10-shell.png` | wajib |
| Sidebar navigasi penuh | `partials/sidebar.blade.php` | 2.6 | `10-sidebar.png` | wajib |
| Topbar + pencarian global | `partials/topbar.blade.php` | 2.7 | `11-topbar.png` | wajib |
| Dialog pencarian global (hasil) | `GET /search?q=` | 2.7 | `12-global-search.png` | wajib |
| Panel notifikasi | `GET /notifications` | 2.7 | `13-notifications.png` | wajib |
| Menu akun | `partials/topbar.blade.php` | 2.7–2.8 | `14-user-menu.png` | wajib |
| Pengalih tema (gelap) | `components/theme-switcher.blade.php` | 2.7 | `15-dark-sample.png` | opsional |

## C. Dashboard per Peran (6 + akses terbatas)

| Peran | View | Bagian buku | Gambar | Status |
|---|---|---|---|---|
| Admin | `dashboard/admin.blade.php` | 3.2, 4.1 | `20-dashboard-admin.png` | wajib |
| Operator Pesanan | `dashboard/operator.blade.php` | 3.3 | `21-dashboard-operator.png` | wajib |
| Gudang | `dashboard/warehouse.blade.php` | 3.4 | `22-dashboard-gudang.png` | wajib |
| Keuangan | `dashboard/finance.blade.php` | 3.5 | `23-dashboard-keuangan.png` | wajib |
| Sales / PIC | `dashboard/sales.blade.php` | 3.6 | `24-dashboard-sales.png` | wajib |
| Pimpinan | `dashboard/executive.blade.php` | 3.7 | `25-dashboard-pimpinan.png` | wajib |
| Akses terbatas | `dashboard/limited.blade.php` | 3.1 | — | tidak diambil |

## D. Master Data (11 resource, CRUD generik)

Registry: `app/Support/MasterDataRegistry.php`. Route: `master.index/create/store/edit/update/destroy/import`.

| Resource | Judul | Izin | Bagian buku | Gambar index | Gambar form | Status |
|---|---|---|---|---|---|---|
| `schools` | Sekolah | `schools.*` | 4.2 | `30-master-schools.png` | `31-master-schools-form.png` | wajib |
| `publishers` | Penerbit | `publishers.*` | 4.3 | `32-master-publishers.png` | `33-master-publishers-form.png` | wajib |
| `categories` | Kategori Buku | `categories.manage` | 4.4 | `34-master-categories.png` | — | wajib |
| `books` | Buku | `books.*` | 4.5 | `36-master-books.png` | `37-master-books-form.png` | wajib |
| `barang-lainnya` | Barang Lainnya | `goods.*` | 4.6 | `38-master-goods.png` | `39-master-goods-form.png` | wajib |
| `order-sources` | Sumber Pesanan | `order-sources.manage` | 4.8 | `42-master-order-sources.png` | — | wajib |
| `couriers` | Kurir | `couriers.manage` | 4.9 | `44-master-couriers.png` | — | wajib |
| `shipping-methods` | Metode Pengiriman | `couriers.manage` | 4.9 | `46-master-shipping-methods.png` | — | wajib |
| `payment-methods` | Metode Pembayaran | `payment-methods.manage` | 4.10 | `48-master-payment-methods.png` | — | wajib |
| `settings` | Konfigurasi | `settings.manage` | 4.13 | `52-master-settings.png` | — | wajib |
| `cash-loans` | Pinjaman Tunai (data induk) | `loans.*` | 4.12 | `54-master-cash-loans.png` | `55-master-cash-loans-form.png` | wajib |

| Aksi destruktif / impor | Sumber | Bagian buku | Gambar | Status |
|---|---|---|---|---|
| Dialog konfirmasi hapus | `master/partials/row-actions.blade.php` | 4.2 | — (dialog `confirm()` bawaan peramban) | tidak diambil |
| Hapus ditolak (data terpakai) | `MasterDataController@destroy` | 4.2, 8.11 | `57-master-delete-error.png` | wajib |
| Impor data induk | `GET /master/{resource}/import` | 5.11 | — (bagian formulir impor) | tidak diambil |
| Unduh templat impor | `GET /master/{resource}/import/template` | 5.11 | — (unduhan berkas) | tidak diambil |

> Catatan: **Gudang** dan **Aturan Rebate** bukan lagi resource data induk. Gudang dipilih
> langsung pada dokumen penerimaan; rebate dihitung dari kolom **Rebate (%)** pada data
> sekolah. **Zona** tidak memiliki layar CRUD sendiri: daftar zona disediakan seeder,
> harga per zona diisi pada formulir **Buku**, dan zona dipilih per pesanan lewat pemilih
> *Zona Harga*. Izin `zonas.manage` ada di `config/rbac.php` tetapi tidak diekspos di menu.
> Didokumentasikan di 4.5 dan Lampiran C.

## E. Katalog

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Daftar katalog | `GET /catalogs` | `catalogs.view` | 4.7 | `170-catalogs-index.png` | wajib |
| Buat katalog | `GET /catalogs/create` | `catalogs.manage` | 4.7 | `171-catalogs-create.png` | wajib |
| Sunting katalog | `GET /catalogs/{catalog}/edit` | `catalogs.manage` | 4.7 | `172-catalogs-edit.png` | wajib |
| Unduh PDF katalog | `GET /catalogs/{catalog}/pdf` | `catalogs.view` | 4.7 | — (dokumen unduhan) | tidak diambil |

## F. Pesanan (Orders)

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Daftar pesanan | `GET /orders` | `orders.view` | 5.1 | `60-orders-index.png` | wajib |
| Filter sumber/status | `GET /orders?source=&status=` | `orders.view` | 5.1 | `61-orders-filter.png` | wajib |
| Buat pesanan | `GET /orders/create` | `orders.manage` | 5.1 | `62-orders-create.png` | wajib |
| Pemilih barang (item) | `orders/partials/item-book.blade.php` | `orders.manage` | 5.1 | `63-orders-item-picker.png` | wajib |
| Validasi gagal | `POST /orders` | `orders.manage` | 5.1, 8.4 | `64-orders-validation.png` | wajib |
| Detail (siap) | `GET /orders/{order}` | `orders.view` | 5.1 | `65-orders-show.png` | wajib |
| Detail (terkirim) | `GET /orders/{order}` | `orders.view` | 5.1 | `66-orders-show-shipped.png` | wajib |
| Sunting pesanan | `GET /orders/{order}/edit` | `orders.manage` | 5.1 | — (formulir sama dengan buat) | tidak diambil |
| Kembalikan ke draft | `POST /orders/{order}/revert-to-draft` | `orders.manage` | 5.1 | — (tombol pada detail) | tidak diambil |
| Aksi konfirmasi & pembatalan | `POST /orders/{order}/confirm`, `/cancel` | `orders.confirm`, `orders.cancel` | 5.1 | — (dialog `confirm()` bawaan peramban) | tidak diambil |
| Nota pesanan (cetak) | `GET /orders/{order}/print` | `orders.view` | 5.1, 6 | `69-orders-print.png` | wajib |
| Surat pesanan ke penerbit | `GET /orders/{order}/print-surat` | `orders.view` | 5.1, 6 | `69b-orders-print-surat.png` | wajib |

## G. Penerimaan Barang (Goods Receipts)

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Daftar penerimaan | `GET /receipts` | `receipts.view` | 5.2 | `70-receipts-index.png` | wajib |
| Buat penerimaan | `GET /receipts/create` | `receipts.manage` | 5.2 | `71-receipts-create.png` | wajib |
| Detail (dikonfirmasi) | `GET /receipts/{receipt}` | `receipts.view` | 5.2 | `72-receipts-show.png` | wajib |
| Detail (draft) | `GET /receipts/{receipt}` | `receipts.view` | 5.2 | `72b-receipts-show-draft.png` | wajib |
| Sunting penerimaan | `GET /receipts/{receipt}/edit` | `receipts.manage` | 5.2 | — (formulir sama dengan buat) | tidak diambil |
| Aksi konfirmasi | `POST /receipts/{receipt}/confirm` | `receipts.confirm` | 5.2 | `72b-receipts-show-draft.png` (tampak tombol) | tidak diambil |

## H. Rangkuman Stok & Mutasi Stok

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Rangkuman stok | `GET /stock-summary` | `procurement.view` | 5.3 | `80-stock-summary.png` | wajib |
| Mutasi stok | `GET /stock-mutations` | `fulfillment.view` | 5.3 | `81-stock-mutations.png` | wajib |
| Catat mutasi | `POST /stock-mutations` | `fulfillment.pick` | 5.3 | `81-stock-mutations.png` (kartu Catat Mutasi) | tidak diambil |
| Cetak mutasi stok | `GET /stock-mutations/{mutation}/print` | `fulfillment.view` | 5.3, 6 | `82-stock-mutation-print.png` | wajib |

> Tidak ada lagi layar penyesuaian stok manual (`/stock`, `POST /stock/adjust`) maupun
> halaman `stock/movements` lama. Koreksi stok dilakukan lewat dokumen penerimaan atau
> pengiriman agar jejak mutasi tetap utuh.

## I. Back Order

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Daftar back order | `GET /backorders` | `procurement.view` | 5.13 | `85-backorders-index.png` | wajib |
| Rekap item tidak tersedia | `GET /backorders/unavailable` | `procurement.view` | 5.13 | `86-backorders-unavailable.png` | wajib |
| Detail pesanan ke penerbit | `GET /backorders/orders/{backorderOrder}` | `procurement.view` | 5.13 | `87-backorders-order.png` | wajib |
| Sunting pesanan ke penerbit | `GET /backorders/orders/{backorderOrder}/edit` | `procurement.manage` | 5.13 | — (formulir sama dengan detail) | tidak diambil |
| Cetak pesanan ke penerbit | `GET /backorders/orders/{backorderOrder}/print` | `procurement.view` | 5.13, 6 | `88-backorders-print.png` | wajib |

## J. Picking & Packing

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Daftar picking | `GET /pickings` | `fulfillment.view` | 5.4 | `90-pickings-index.png` | wajib |
| Detail (belum siap) | `GET /pickings/{picking}` | `fulfillment.view` | 5.4 | `91-pickings-show-draft.png` | wajib |
| Detail (sedang picking) | `GET /pickings/{picking}` | `fulfillment.view` | 5.4 | `92-pickings-show-picked.png` | wajib |
| Catat hasil picking | `POST /pickings/{picking}/record` | `fulfillment.pick` | 5.4 | `92-pickings-show-picked.png` (tampak formulir) | tidak diambil |
| Selesai picking & packing | `POST /pickings/{picking}/pack`, `/record-and-pack` | `fulfillment.pack` | 5.4 | `92-pickings-show-picked.png` (tampak tombol) | tidak diambil |
| Cetak daftar picking | `GET /pickings/{picking}/print` | `fulfillment.view` | 5.4 | — (dokumen cetak) | tidak diambil |

## K. Pengiriman (Shipments)

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Daftar pengiriman | `GET /shipments` | `shipments.view` | 5.5 | `100-shipments-index.png` | wajib |
| Buat — langkah 1 | `GET /shipments/create` | `shipments.manage` | 5.5 | `101-shipments-create-1.png` | wajib |
| Buat — langkah 2 | `GET /shipments/create?order_id=` | `shipments.manage` | 5.5 | `102-shipments-create-2.png` | wajib |
| Buat — langkah 2 terisi | `GET /shipments/create?order_id=` | `shipments.manage` | 5.5 | `102b-shipments-create-2-filled.png` | wajib |
| Detail (draft) | `GET /shipments/{shipment}` | `shipments.view` | 5.5 | `103b-shipments-show-unconfirmed.png` | wajib |
| Detail (terkirim) + bukti foto | `GET /shipments/{shipment}` | `shipments.view` | 5.5 | `103-shipments-show.png` | wajib |
| Form konfirmasi + unggah foto wajib | `POST /shipments/{shipment}/confirm` | `shipments.confirm` | 5.5 | `104-shipments-confirm-form.png` | wajib |
| Unggah bukti tambahan | `POST /shipments/{shipment}/attachments` | `shipments.manage` | 5.5 | — (bagian formulir detail) | tidak diambil |
| Tandai terkirim | `POST /shipments/{shipment}/deliver` | `shipments.confirm` | 5.5 | `103-shipments-show.png` (tampak tombol) | tidak diambil |
| Surat jalan (cetak) | `GET /shipments/{shipment}/print` | `shipments.view` | 5.5, 6 | `105-shipments-print.png` | wajib |

## L. Invoice

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Daftar invoice | `GET /invoices` | `invoices.view` | 5.6 | `110-invoices-index.png` | wajib |
| Detail invoice | `GET /invoices/{invoice}` | `invoices.view` | 5.6 | `111-invoices-show.png` | wajib |
| Cetak invoice | `GET /invoices/{invoice}/print` | `invoices.view` | 5.6, 6 | `112-invoices-print.png` | wajib |
| Unggah bukti bayar | `POST /invoices/{invoice}/attachments` | `invoices.manage` | 5.6 | — (bagian formulir detail) | tidak diambil |
| Aksi batalkan invoice | `POST /invoices/{invoice}/cancel` | `invoices.manage` | 5.6, 8.8 | `111-invoices-show.png` (tampak tombol) | tidak diambil |

## M. Piutang & Barang Belum Dikirim

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Piutang (aging) | `GET /receivables` | `invoices.view` | 5.7 | `120-receivables.png` | wajib |
| Barang belum dikirim | `GET /finance/hutang` | `invoices.view` | 5.7 | `121-hutang.png` | wajib |

## N. Pembayaran

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Riwayat pembayaran | `GET /payments` | `payments.view` | 5.8 | `130-payments-index.png` | wajib |
| Catat pembayaran | `POST /payments` | `payments.manage` | 5.8 | `131-payments-record.png` | wajib |
| Sunting pembayaran | `GET /payments/{payment}/edit` | `payments.manage` | 5.8 | `132-payments-edit.png` | wajib |
| Kwitansi alokasi | `GET /payments/{payment}/allocations/{allocation}/receipt` | `payments.view` | 5.8, 6 | `133-payments-receipt.png` | wajib |
| Aksi verifikasi | `POST /payments/{payment}/verify` | `payments.verify` | 5.8 | `130-payments-index.png` (kolom Aksi) | tidak diambil |
| Aksi pembalikan | `POST /payments/{payment}/reverse` | `payments.manage` | 5.8 | `130-payments-index.png` (kolom Aksi) | tidak diambil |

## O. Rebate / Cashback

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Rekap rebate sekolah | `GET /rebates` | `rebates.view` | 4.11, 5.9 | `140-rebates-index.png` | wajib |

> Rebate bersifat **otomatis**: nilai dihitung dari `schools.rebate_percent` terhadap
> invoice yang sudah lunas. Tidak ada lagi pengajuan, persetujuan, pembayaran, atau nomor
> `RBT`; karena itu tidak ada formulir/modal rebate yang perlu dipotret.

## P. Pinjaman Tunai

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Daftar pinjaman | `GET /pinjaman-tunai` | `loans.view` | 4.12 | `54-master-cash-loans.png` | wajib |
| Buat pinjaman | `GET /pinjaman-tunai/create` | `loans.manage` | 4.12 | `55-master-cash-loans-form.png` | wajib |
| Detail pinjaman | `GET /pinjaman-tunai/{id}` | `loans.view` | 4.12 | — (ringkasan pada daftar) | tidak diambil |
| Sunting pinjaman | `GET /pinjaman-tunai/{id}/edit` | `loans.manage` | 4.12 | — (formulir sama dengan buat) | tidak diambil |
| Hapus pinjaman | `DELETE /pinjaman-tunai/{id}` | `loans.manage` | 4.12 | — (dialog `confirm()` bawaan peramban) | tidak diambil |

## Q. Laporan (7 tipe + ekspor)

| Tipe | Nilai `type` | Bagian buku | Gambar | Status |
|---|---|---|---|---|
| Penjualan per Sekolah | `sales_by_school` | 7.2 | `150-reports-sales-by-school.png` | wajib |
| Penjualan per Penerbit | `sales_by_publisher` | 7.3 | `151-reports-sales-by-publisher.png` | wajib |
| Penjualan per Barang | `sales_by_book` | 7.4 | `152-reports-sales-by-book.png` | wajib |
| Ringkasan Pesanan | `order_summary` | 7.5 | `153-reports-orders-summary.png` | wajib |
| Piutang per Sekolah | `receivables` | 7.6 | `154-reports-receivables.png` | wajib |
| Invoice Jatuh Tempo | `overdue_invoices` | 7.7 | `155-reports-overdue.png` | wajib |
| Back Order | `backorders` | 7.8 | `158-reports-backorders.png` | wajib |
| Halaman laporan | `GET /reports` | 7.1 | `149-reports-index.png` | wajib |
| Ekspor CSV | `GET /reports/export` | 7.1 | — (unduhan berkas) | tidak diambil |

> Laporan **Stok per Barang** dan **Stok Menipis** sudah tidak ada. Posisi stok dilihat di
> **Pengadaan › Rangkuman Stok**, sedangkan kekurangan barang dilihat di **Back Order**.
> Buku (Bab 7) dan Daftar Isi sudah disesuaikan menjadi tujuh laporan.

## R. Pengaturan, Pengguna, Peran & Audit

| Halaman | Route | Izin | Bagian buku | Gambar | Status |
|---|---|---|---|---|---|
| Profil perusahaan | `GET /settings/company` | `settings.manage` | 5.10 | `160-settings-company.png` | wajib |
| Daftar pengguna | `GET /settings/users` | `users.view` | 5.10 | `161-settings-users.png` | wajib |
| Tambah pengguna | `POST /settings/users` | `users.manage` | 5.10 | `162-settings-user-create.png` | wajib |
| Hapus pengguna ditolak | `DELETE /settings/users/{user}` | `users.manage` | 5.10, 8.11 | `57b-user-delete-blocked.png` | opsional |
| Peran & izin | `GET /settings/roles` | `roles.manage` | 3.1, 5.10 | `163-settings-roles.png` | wajib |
| Sunting peran | `PUT /settings/roles/{role}` | `roles.manage` | 3.1, 5.10 | `164-settings-role-edit.png` | wajib |
| Audit log | `GET /settings/audit` | `audit.view` | 5.10, 9.4 | `165-settings-audit.png` | wajib |
| Pengaturan lembur | `GET /settings/overtimes` | `settings.manage` | 5.10 | `167-settings-overtimes.png` | wajib |
| Simpan/ubah lembur | `POST/PUT /settings/overtimes` | `settings.manage` | 5.10 | — (bagian formulir halaman) | tidak diambil |
| Integrasi & import | `GET /integrations` | `integrations.view` | 5.11 | `166-integrations.png` | wajib |
| Sinkronisasi sumber | `POST /integrations/{source}/sync` | `integrations.manage` | 5.11 | — (tombol pada halaman integrasi) | tidak diambil |
| Unduh lampiran | `GET /attachments/{attachment}` | sesuai modul | — | — (penyajian berkas) | tidak diambil |

## S. Bagian Buku Lain (tanpa halaman khusus)

| Bagian buku | Sumber | Gambar |
|---|---|---|
| Bagian I — Kenal Aplikasi | narasi | `20-dashboard-admin.png`, `10-shell.png` |
| Bagian II — Memulai | `layouts`, `partials` | `01-login.png`, `11-topbar.png` |
| Bagian III — Peran & Izin | `config/rbac.php` | `163-settings-roles.png` |
| Bagian V — Alur end-to-end | gabungan modul | `69-orders-print.png`, `105-shipments-print.png`, `112-invoices-print.png`, `133-payments-receipt.png` |
| Bagian VII — Pemecahan masalah | pesan galat dari service layer | `02-login-error.png`, `64-orders-validation.png`, `57-master-delete-error.png` |
| Bagian VIII — Keamanan | `config/rbac.php`, `settings/audit` | `163-settings-roles.png`, `165-settings-audit.png` |
| Lampiran | glosarium, matriks izin, referensi cepat | `163-settings-roles.png` |

---

## Halaman yang tidak diambil gambarnya (alasan)

| Halaman | Alasan |
|---|---|
| `GET /` (beranda pengalih) | Hanya mengalihkan ke `/login` atau `/dashboard`; bukan layar bermakna. |
| `POST`-only endpoints (confirm/verify/pack/deliver/sync/dsb.) | Tidak punya tampilan sendiri; efek visualnya diambil melalui tombol atau dialog pada halaman detail. |
| `GET /storage/{path}`, `GET /attachments/{attachment}` | Penyajian berkas statis. |
| `GET /up` | Endpoint health-check. |
| `_boost/browser-logs` | Endpoint diagnostik pengembangan. |
| CRUD Zona | Tidak ada layar CRUD; zona disediakan seeder dan harganya diisi pada formulir Buku. |
| Formulir sunting yang identik dengan formulir buat | Tampilan sama dengan gambar formulir buat; duplikasi gambar tidak menambah informasi. |
| Dialog `confirm()` bawaan peramban | Aksi konfirmasi (konfirmasi pesanan, penerimaan, packing, pengiriman, verifikasi/pembalikan pembayaran, hapus data) memakai `window.confirm()` native. Dialog ini digambar oleh peramban, bukan oleh halaman, sehingga tidak dapat dipotret Playwright. Yang dipotret adalah tombol/baris aksinya. |

---

## Verifikasi Akhir

Diperiksa terhadap aplikasi yang berjalan pada 23 Oktober 2026.

| Ukuran | Hasil |
|---|---|
| Route terdaftar | **112** (`php artisan route:list --except-vendor`) |
| Data induk (registry) | **11** resource |
| Izin | **52** izin, **6** peran (`config/rbac.php`) |
| Tangkapan layar diambil | **93** berkas di `screens/` |
| Gambar dipakai di buku | **92** berkas unik, **101** pemunculan gambar bernomor |
| Gambar diambil tetapi tidak dipakai | 1 (`57b-user-delete-blocked.png`) — tetap disimpan sebagai bukti QA |
| Berkas hilang yang dirujuk buku | 0 (diperiksa otomatis oleh `render.mjs`) |
| Bagian buku | 9 bab + 8 lampiran |
| Halaman PDF | **155** (15,4 MB) |
| Halaman kosong | 0 |
| Isi keluar dari tepi halaman | 0 |

### Status per kelompok

| Kelompok | Jumlah | Punya bagian buku | Punya gambar | Status |
|---|---|---|---|---|
| Dashboard peran | 6 | 6 | 6 | Lulus |
| Data induk | 11 | 11 | 11 | Lulus |
| Modul operasional | 13 | 13 | 13 | Lulus |
| Jenis laporan | 7 | 7 | 7 | Lulus |
| Dokumen cetak | 5 | 5 | 5 | Lulus |

### Baris checklist yang berakhir "tidak diambil"

Seluruh baris bertanda `tidak diambil` bergantung pada kotak penegasan bawaan peramban,
berkas unduhan, endpoint tanpa tampilan, atau formulir yang identik dengan gambar lain.
Dalam buku, keadaan tersebut disampaikan melalui teks langkah dan tangkapan tombol/baris
aksinya, bukan melalui gambar palsu. Rinciannya ada di `QA.md` §3.
