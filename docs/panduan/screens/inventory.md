# Inventaris Screenshot — ARKARA PM

Dibuat otomatis oleh `scripts/capture.mjs` pada 2026-10-23. Semua gambar diambil dari aplikasi
yang berjalan pada data demo (akun `@sekolah.test`), bukan mockup.

Total gambar: **93**. Gagal/tidak tersedia: **0**.

| # | Berkas | Bagian buku | Route | Peran |
|---|---|---|---|---|
| 1 | `01-login.png` | Bagian II — Masuk | `GET /login` | publik |
| 2 | `02-login-error.png` | Bagian II, VII — Masuk | `POST /login` | publik |
| 3 | `20-dashboard-admin.png` | Bagian III/IV — Dashboard admin | `GET /dashboard` | admin |
| 4 | `10-shell.png` | Bagian I/II — Kerangka aplikasi | `GET /dashboard` | admin |
| 5 | `10-sidebar.png` | Bagian I/II — Sidebar | `partials/sidebar.blade.php` | admin |
| 6 | `11-topbar.png` | Bagian II — Topbar | `partials/topbar.blade.php` | admin |
| 7 | `12-global-search.png` | Bagian II — Pencarian global | `GET /search?q=` | admin |
| 8 | `13-notifications.png` | Bagian II — Notifikasi | `GET /notifications` | admin |
| 9 | `14-user-menu.png` | Bagian II — Menu akun | `partials/topbar.blade.php` | admin |
| 10 | `15-dark-sample.png` | Bagian II — Tema gelap | `GET /reports` | admin |
| 11 | `21-dashboard-operator.png` | Bagian III/IV — Dashboard operator | `GET /dashboard` | operator |
| 12 | `22-dashboard-gudang.png` | Bagian III/IV — Dashboard gudang | `GET /dashboard` | gudang |
| 13 | `23-dashboard-keuangan.png` | Bagian III/IV — Dashboard keuangan | `GET /dashboard` | keuangan |
| 14 | `24-dashboard-sales.png` | Bagian III/IV — Dashboard sales | `GET /dashboard` | sales |
| 15 | `25-dashboard-pimpinan.png` | Bagian III/IV — Dashboard pimpinan | `GET /dashboard` | pimpinan |
| 16 | `30-master-schools.png` | Bagian IV — Master Data | `GET /master/schools` | admin |
| 17 | `32-master-publishers.png` | Bagian IV — Master Data | `GET /master/publishers` | admin |
| 18 | `34-master-categories.png` | Bagian IV — Master Data | `GET /master/categories` | admin |
| 19 | `36-master-books.png` | Bagian IV — Master Data | `GET /master/books` | admin |
| 20 | `38-master-goods.png` | Bagian IV — Master Data | `GET /master/barang-lainnya` | admin |
| 21 | `42-master-order-sources.png` | Bagian IV — Master Data | `GET /master/order-sources` | admin |
| 22 | `44-master-couriers.png` | Bagian IV — Master Data | `GET /master/couriers` | admin |
| 23 | `46-master-shipping-methods.png` | Bagian IV — Master Data | `GET /master/shipping-methods` | admin |
| 24 | `48-master-payment-methods.png` | Bagian IV — Master Data | `GET /master/payment-methods` | admin |
| 25 | `52-master-settings.png` | Bagian IV — Master Data | `GET /master/settings` | admin |
| 26 | `54-master-cash-loans.png` | Bagian IV — Master Data | `GET /master/cash-loans` | admin |
| 27 | `31-master-schools-form.png` | Bagian IV — Master Data | `GET /master/schools/create` | admin |
| 28 | `37-master-books-form.png` | Bagian IV — Master Data | `GET /master/books/create` | admin |
| 29 | `39-master-goods-form.png` | Bagian IV — Master Data | `GET /master/barang-lainnya/create` | admin |
| 30 | `33-master-publishers-form.png` | Bagian IV — Master Data | `GET /master/publishers/create` | admin |
| 31 | `55-master-cash-loans-form.png` | Bagian IV — Master Data | `GET /master/cash-loans/create` | admin |
| 32 | `57-master-delete-error.png` | Bagian IV, VII — Master Data | `DELETE /master/publishers/4` | admin |
| 33 | `60-orders-index.png` | Bagian IV — Pesanan | `GET /orders` | admin |
| 34 | `61-orders-filter.png` | Bagian IV — Pesanan | `GET /orders?source=&status=` | admin |
| 35 | `62-orders-create.png` | Bagian IV — Pesanan | `GET /orders/create` | admin |
| 36 | `63-orders-item-picker.png` | Bagian IV — Pesanan | `orders/partials/item-book.blade.php` | admin |
| 37 | `64-orders-validation.png` | Bagian IV, VII — Pesanan | `POST /orders` | admin |
| 38 | `65-orders-show.png` | Bagian IV — Pesanan | `GET /orders/{order}` | admin |
| 39 | `69-orders-print.png` | Bagian V — Dokumen cetak | `GET /orders/{order}/print` | admin |
| 40 | `69b-orders-print-surat.png` | Bagian V — Dokumen cetak | `GET /orders/{order}/print-surat` | admin |
| 41 | `66-orders-show-shipped.png` | Bagian IV — Pesanan | `GET /orders/{order}` | admin |
| 42 | `70-receipts-index.png` | Bagian IV — Penerimaan Barang | `GET /receipts` | admin |
| 43 | `71-receipts-create.png` | Bagian IV — Penerimaan Barang | `GET /receipts/create` | admin |
| 44 | `72-receipts-show.png` | Bagian IV — Penerimaan Barang | `GET /receipts/{receipt}` | admin |
| 45 | `72b-receipts-show-draft.png` | Bagian IV — Penerimaan Barang | `GET /receipts/{receipt}` | admin |
| 46 | `80-stock-summary.png` | Bagian IV — Stok | `GET /stock-summary` | admin |
| 47 | `81-stock-mutations.png` | Bagian IV — Stok | `GET /stock-mutations` | admin |
| 48 | `82-stock-mutation-print.png` | Bagian IV/V — Stok | `GET /stock-mutations/{mutation}/print` | admin |
| 49 | `90-pickings-index.png` | Bagian IV — Picking | `GET /pickings` | admin |
| 50 | `91-pickings-show-draft.png` | Bagian IV — Picking | `GET /pickings/{picking}` | admin |
| 51 | `92-pickings-show-picked.png` | Bagian IV — Picking | `GET /pickings/{picking}` | admin |
| 52 | `85-backorders-index.png` | Bagian IV — Pengadaan | `GET /backorders` | admin |
| 53 | `86-backorders-unavailable.png` | Bagian IV — Pengadaan | `GET /backorders/unavailable` | admin |
| 54 | `87-backorders-order.png` | Bagian IV — Pengadaan | `GET /backorders/orders/{backorderOrder}` | admin |
| 55 | `88-backorders-print.png` | Bagian IV/V — Pengadaan | `GET /backorders/orders/{backorderOrder}/print` | admin |
| 56 | `100-shipments-index.png` | Bagian IV — Pengiriman | `GET /shipments` | admin |
| 57 | `101-shipments-create-1.png` | Bagian IV — Pengiriman | `GET /shipments/create` | admin |
| 58 | `102-shipments-create-2.png` | Bagian IV — Pengiriman | `GET /shipments/create?order_id=` | admin |
| 59 | `102b-shipments-create-2-filled.png` | Bagian IV — Pengiriman | `GET /shipments/create?order_id=` | admin |
| 60 | `103-shipments-show.png` | Bagian IV — Pengiriman | `GET /shipments/{shipment}` | admin |
| 61 | `103b-shipments-show-unconfirmed.png` | Bagian IV — Pengiriman | `GET /shipments/{shipment}` | admin |
| 62 | `104-shipments-confirm-form.png` | Bagian IV — Pengiriman | `POST /shipments/{shipment}/confirm` | admin |
| 63 | `105-shipments-print.png` | Bagian IV/V — Dokumen cetak | `GET /shipments/{shipment}/print` | admin |
| 64 | `110-invoices-index.png` | Bagian IV — Invoice | `GET /invoices` | admin |
| 65 | `111-invoices-show.png` | Bagian IV — Invoice | `GET /invoices/{invoice}` | admin |
| 66 | `112-invoices-print.png` | Bagian IV/VI — Dokumen cetak | `GET /invoices/{invoice}/print` | admin |
| 67 | `120-receivables.png` | Bagian IV — Piutang | `GET /receivables` | admin |
| 68 | `121-hutang.png` | Bagian IV — Piutang | `GET /finance/hutang` | admin |
| 69 | `130-payments-index.png` | Bagian IV — Pembayaran | `GET /payments` | admin |
| 70 | `131-payments-record.png` | Bagian IV — Pembayaran | `POST /payments` | admin |
| 71 | `132-payments-edit.png` | Bagian IV — Pembayaran | `GET /payments/{payment}/edit` | admin |
| 72 | `133-payments-receipt.png` | Bagian IV/V — Pembayaran | `GET /payments/{payment}/allocations/{allocation}/receipt` | admin |
| 73 | `140-rebates-index.png` | Bagian IV — Rebate | `GET /rebates` | admin |
| 74 | `149-reports-index.png` | Bagian VI — Laporan | `GET /reports` | admin |
| 75 | `150-reports-sales-by-school.png` | Bagian VI — Laporan | `GET /reports?type=sales_by_school` | admin |
| 76 | `151-reports-sales-by-publisher.png` | Bagian VI — Laporan | `GET /reports?type=sales_by_publisher` | admin |
| 77 | `152-reports-sales-by-book.png` | Bagian VI — Laporan | `GET /reports?type=sales_by_book` | admin |
| 78 | `153-reports-orders-summary.png` | Bagian VI — Laporan | `GET /reports?type=order_summary` | admin |
| 79 | `154-reports-receivables.png` | Bagian VI — Laporan | `GET /reports?type=receivables` | admin |
| 80 | `155-reports-overdue.png` | Bagian VI — Laporan | `GET /reports?type=overdue_invoices` | admin |
| 81 | `158-reports-backorders.png` | Bagian VI — Laporan | `GET /reports?type=backorders` | admin |
| 82 | `160-settings-company.png` | Bagian IV — Pengaturan | `GET /settings/company` | admin |
| 83 | `161-settings-users.png` | Bagian IV — Pengaturan | `GET /settings/users` | admin |
| 84 | `162-settings-user-create.png` | Bagian IV — Pengaturan | `POST /settings/users` | admin |
| 85 | `163-settings-roles.png` | Bagian III/IV — Peran & Izin | `GET /settings/roles` | admin |
| 86 | `164-settings-role-edit.png` | Bagian IV — Peran & Izin | `PUT /settings/roles/{role}` | admin |
| 87 | `165-settings-audit.png` | Bagian IV/VIII — Audit Log | `GET /settings/audit` | admin |
| 88 | `167-settings-overtimes.png` | Bagian IV — Pengaturan | `GET /settings/overtimes` | admin |
| 89 | `166-integrations.png` | Bagian IV — Integrasi | `GET /integrations` | admin |
| 90 | `170-catalogs-index.png` | Bagian IV — Katalog | `GET /catalogs` | admin |
| 91 | `171-catalogs-create.png` | Bagian IV — Katalog | `GET /catalogs/create` | admin |
| 92 | `172-catalogs-edit.png` | Bagian IV — Katalog | `GET /catalogs/{catalog}/edit` | admin |
| 93 | `57b-user-delete-blocked.png` | Bagian IV, VII — Pengaturan | `DELETE /settings/users/{user}` | admin |

## Gambar yang tidak dapat diambil

| Berkas | Alasan |
|---|---|
| — | Semua gambar berhasil diambil. |
