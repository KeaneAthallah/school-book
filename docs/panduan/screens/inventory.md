# Inventaris Screenshot — ARKARA PM

Dibuat otomatis oleh `scripts/capture.mjs` pada 2026-10-17. Semua gambar diambil dari aplikasi
yang berjalan pada data demo (akun `@sekolah.test`), bukan mockup.

Total gambar: **88**. Gagal/tidak tersedia: **1**.

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
| 21 | `40-master-warehouses.png` | Bagian IV — Master Data | `GET /master/warehouses` | admin |
| 22 | `42-master-order-sources.png` | Bagian IV — Master Data | `GET /master/order-sources` | admin |
| 23 | `44-master-couriers.png` | Bagian IV — Master Data | `GET /master/couriers` | admin |
| 24 | `46-master-shipping-methods.png` | Bagian IV — Master Data | `GET /master/shipping-methods` | admin |
| 25 | `48-master-payment-methods.png` | Bagian IV — Master Data | `GET /master/payment-methods` | admin |
| 26 | `50-master-rebate-rules.png` | Bagian IV — Master Data | `GET /master/rebate-rules` | admin |
| 27 | `52-master-settings.png` | Bagian IV — Master Data | `GET /master/settings` | admin |
| 28 | `54-master-cash-loans.png` | Bagian IV — Master Data | `GET /master/cash-loans` | admin |
| 29 | `31-master-schools-form.png` | Bagian IV — Master Data | `GET /master/schools/create` | admin |
| 30 | `37-master-books-form.png` | Bagian IV — Master Data | `GET /master/books/create` | admin |
| 31 | `39-master-goods-form.png` | Bagian IV — Master Data | `GET /master/barang-lainnya/create` | admin |
| 32 | `33-master-publishers-form.png` | Bagian IV — Master Data | `GET /master/publishers/create` | admin |
| 33 | `55-master-cash-loans-form.png` | Bagian IV — Master Data | `GET /master/cash-loans/create` | admin |
| 34 | `57-master-delete-error.png` | Bagian IV, VII — Master Data | `DELETE /master/publishers/1` | admin |
| 35 | `60-orders-index.png` | Bagian IV — Pesanan | `GET /orders` | admin |
| 36 | `61-orders-filter.png` | Bagian IV — Pesanan | `GET /orders?source=&status=` | admin |
| 37 | `62-orders-create.png` | Bagian IV — Pesanan | `GET /orders/create` | admin |
| 38 | `63-orders-item-picker.png` | Bagian IV — Pesanan | `orders/partials/item-book.blade.php` | admin |
| 39 | `64-orders-validation.png` | Bagian IV, VII — Pesanan | `POST /orders` | admin |
| 40 | `65-orders-show.png` | Bagian IV — Pesanan | `GET /orders/{order}` | admin |
| 41 | `66-orders-show-shipped.png` | Bagian IV — Pesanan | `GET /orders/{order}` | admin |
| 42 | `69-orders-print.png` | Bagian V — Dokumen cetak | `GET /orders/{order}/print` | admin |
| 43 | `70-receipts-index.png` | Bagian IV — Penerimaan Barang | `GET /receipts` | admin |
| 44 | `71-receipts-create.png` | Bagian IV — Penerimaan Barang | `GET /receipts/create` | admin |
| 45 | `72-receipts-show.png` | Bagian IV — Penerimaan Barang | `GET /receipts/{receipt}` | admin |
| 46 | `72b-receipts-show-draft.png` | Bagian IV — Penerimaan Barang | `GET /receipts/{receipt}` | admin |
| 47 | `80-stock-index.png` | Bagian IV — Stok | `GET /stock` | admin |
| 48 | `81-stock-adjust.png` | Bagian IV — Stok | `POST /stock/adjust` | admin |
| 49 | `82-stock-adjust-error.png` | Bagian IV, VII — Stok | `POST /stock/adjust` | admin |
| 50 | `83-stock-movements.png` | Bagian IV — Stok | `GET /stock/movements` | admin |
| 51 | `90-pickings-index.png` | Bagian IV — Picking | `GET /pickings` | admin |
| 52 | `91-pickings-show-draft.png` | Bagian IV — Picking | `GET /pickings/{picking}` | admin |
| 53 | `92-pickings-show-picked.png` | Bagian IV — Picking | `GET /pickings/{picking}` | admin |
| 54 | `100-shipments-index.png` | Bagian IV — Pengiriman | `GET /shipments` | admin |
| 55 | `101-shipments-create-1.png` | Bagian IV — Pengiriman | `GET /shipments/create` | admin |
| 56 | `102-shipments-create-2.png` | Bagian IV — Pengiriman | `GET /shipments/create?order_id=` | admin |
| 57 | `103-shipments-show.png` | Bagian IV — Pengiriman | `GET /shipments/{shipment}` | admin |
| 58 | `105-shipments-print.png` | Bagian IV/V — Dokumen cetak | `GET /shipments/{shipment}/print` | admin |
| 59 | `110-invoices-index.png` | Bagian IV — Invoice | `GET /invoices` | admin |
| 60 | `111-invoices-show.png` | Bagian IV — Invoice | `GET /invoices/{invoice}` | admin |
| 61 | `112-invoices-print.png` | Bagian IV/VI — Dokumen cetak | `GET /invoices/{invoice}/print` | admin |
| 62 | `120-receivables.png` | Bagian IV — Piutang | `GET /receivables` | admin |
| 63 | `121-hutang.png` | Bagian IV — Piutang | `GET /finance/hutang` | admin |
| 64 | `130-payments-index.png` | Bagian IV — Pembayaran | `GET /payments` | admin |
| 65 | `131-payments-record.png` | Bagian IV — Pembayaran | `POST /payments` | admin |
| 66 | `140-rebates-index.png` | Bagian IV — Rebate | `GET /rebates` | admin |
| 67 | `141-rebates-request.png` | Bagian IV — Rebate | `POST /rebates` | admin |
| 68 | `142-rebates-actions.png` | Bagian IV — Rebate | `finance/rebates/partials/actions.blade.php` | admin |
| 69 | `149-reports-index.png` | Bagian VI — Laporan | `GET /reports` | admin |
| 70 | `150-reports-sales-by-school.png` | Bagian VI — Laporan | `GET /reports?type=sales_by_school` | admin |
| 71 | `151-reports-sales-by-publisher.png` | Bagian VI — Laporan | `GET /reports?type=sales_by_publisher` | admin |
| 72 | `152-reports-sales-by-book.png` | Bagian VI — Laporan | `GET /reports?type=sales_by_book` | admin |
| 73 | `153-reports-orders-summary.png` | Bagian VI — Laporan | `GET /reports?type=order_summary` | admin |
| 74 | `154-reports-receivables.png` | Bagian VI — Laporan | `GET /reports?type=receivables` | admin |
| 75 | `155-reports-overdue.png` | Bagian VI — Laporan | `GET /reports?type=overdue_invoices` | admin |
| 76 | `156-reports-stock.png` | Bagian VI — Laporan | `GET /reports?type=stock` | admin |
| 77 | `157-reports-low-stock.png` | Bagian VI — Laporan | `GET /reports?type=low_stock` | admin |
| 78 | `158-reports-backorders.png` | Bagian VI — Laporan | `GET /reports?type=backorders` | admin |
| 79 | `160-settings-company.png` | Bagian IV — Pengaturan | `GET /settings/company` | admin |
| 80 | `161-settings-users.png` | Bagian IV — Pengaturan | `GET /settings/users` | admin |
| 81 | `162-settings-user-create.png` | Bagian IV — Pengaturan | `POST /settings/users` | admin |
| 82 | `163-settings-roles.png` | Bagian III/IV — Peran & Izin | `GET /settings/roles` | admin |
| 83 | `164-settings-role-edit.png` | Bagian IV — Peran & Izin | `PUT /settings/roles/{role}` | admin |
| 84 | `165-settings-audit.png` | Bagian IV/VIII — Audit Log | `GET /settings/audit` | admin |
| 85 | `166-integrations.png` | Bagian IV — Integrasi | `GET /integrations` | admin |
| 86 | `57b-user-delete-blocked.png` | Bagian IV, VII — Pengaturan | `DELETE /settings/users/1` | admin |
| 87 | `103b-shipments-show-unconfirmed.png` | Bagian IV — Pengiriman | `GET /shipments/{shipment}` | admin |
| 88 | `104-shipments-confirm-form.png` | Bagian IV — Pengiriman | `POST /shipments/{shipment}/confirm` | admin |

## Gambar yang tidak dapat diambil

| Berkas | Alasan |
|---|---|
| — | Semua gambar berhasil diambil. |
