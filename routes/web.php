<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BackorderController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MasterImportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PickingController;
use App\Http\Controllers\RebateController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\StockMutationController;
use App\Http\Controllers\StockSummaryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Global search + notification feed for the top navigation.
    Route::get('search', SearchController::class)->name('search');
    Route::get('notifications', NotificationController::class)->name('notifications');

    // Master data (generic CRUD driven by App\Support\MasterDataRegistry).
    Route::get('master/{resource}', [MasterDataController::class, 'index'])->name('master.index');
    Route::get('master/{resource}/create', [MasterDataController::class, 'create'])->name('master.create');
    Route::get('master/{resource}/{id}/edit', [MasterDataController::class, 'edit'])->name('master.edit');
    Route::post('master/{resource}', [MasterDataController::class, 'store'])->name('master.store');
    Route::put('master/{resource}/{id}', [MasterDataController::class, 'update'])->name('master.update');
    Route::delete('master/{resource}/{id}', [MasterDataController::class, 'destroy'])->name('master.destroy');

    // Excel import + downloadable template for catalog resources (buku & barang lainnya).
    Route::get('master/{resource}/import/template', [MasterImportController::class, 'template'])->name('master.import.template');
    Route::post('master/{resource}/import', [MasterImportController::class, 'store'])->name('master.import');

    // Orders.
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::put('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::get('orders/{order}/print', [OrderController::class, 'print'])->name('orders.print');
    Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('orders/{order}/revert-to-draft', [OrderController::class, 'revertToDraft'])->name('orders.revertToDraft');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // Goods receipts.
    Route::get('receipts', [GoodsReceiptController::class, 'index'])->name('receipts.index');
    Route::get('receipts/create', [GoodsReceiptController::class, 'create'])->name('receipts.create');
    Route::post('receipts', [GoodsReceiptController::class, 'store'])->name('receipts.store');
    Route::get('receipts/{receipt}', [GoodsReceiptController::class, 'show'])->name('receipts.show');
    Route::get('receipts/{receipt}/edit', [GoodsReceiptController::class, 'edit'])->name('receipts.edit');
    Route::put('receipts/{receipt}', [GoodsReceiptController::class, 'update'])->name('receipts.update');
    Route::post('receipts/{receipt}/confirm', [GoodsReceiptController::class, 'confirm'])->name('receipts.confirm');
    Route::delete('receipts/{receipt}', [GoodsReceiptController::class, 'destroy'])->name('receipts.destroy');

    // Picking & packing.
    Route::get('pickings', [PickingController::class, 'index'])->name('pickings.index');
    Route::post('pickings', [PickingController::class, 'store'])->name('pickings.store');
    Route::get('pickings/{picking}', [PickingController::class, 'show'])->name('pickings.show');
    Route::get('pickings/{picking}/print', [PickingController::class, 'print'])->name('pickings.print');
    Route::delete('pickings/{picking}', [PickingController::class, 'destroy'])->name('pickings.destroy');
    Route::post('pickings/{picking}/record', [PickingController::class, 'record'])->name('pickings.record');
    Route::post('pickings/{picking}/record-and-pack', [PickingController::class, 'recordAndPack'])->name('pickings.recordAndPack');
    Route::post('pickings/{picking}/pack', [PickingController::class, 'pack'])->name('pickings.pack');

    // Shipments.
    Route::get('shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::get('shipments/create', [ShipmentController::class, 'create'])->name('shipments.create');
    Route::post('shipments', [ShipmentController::class, 'store'])->name('shipments.store');
    Route::get('shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
    Route::post('shipments/{shipment}/confirm', [ShipmentController::class, 'confirm'])->name('shipments.confirm');
    Route::post('shipments/{shipment}/deliver', [ShipmentController::class, 'deliver'])->name('shipments.deliver');
    Route::get('shipments/{shipment}/print', [ShipmentController::class, 'print'])->name('shipments.print');

    // Bukti foto pengiriman (polymorphic attachments).
    Route::post('shipments/{shipment}/attachments', [ShipmentController::class, 'storeAttachment'])->name('shipments.attachments.store');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    // Invoices.
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::post('invoices/{invoice}/attachments', [InvoiceController::class, 'storePaymentProof'])->name('invoices.attachments.store');

    // Piutang (receivables).
    Route::get('receivables', [ReceivableController::class, 'index'])->name('receivables.index');
    Route::get('finance/hutang', [InvoiceController::class, 'hutang'])->name('finance.hutang.list');

    // Payments.
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
    Route::put('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
    Route::post('payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::post('payments/{payment}/reverse', [PaymentController::class, 'reverse'])->name('payments.reverse');

    // Cash loans (pinjaman tunai) - master data resource with a dedicated URL.
    Route::get('pinjaman-tunai', [MasterDataController::class, 'index'])->defaults('resource', 'cash-loans')->name('cash-loans.index');
    Route::get('pinjaman-tunai/create', [MasterDataController::class, 'create'])->defaults('resource', 'cash-loans')->name('cash-loans.create');
    Route::post('pinjaman-tunai', [MasterDataController::class, 'store'])->defaults('resource', 'cash-loans')->name('cash-loans.store');
    Route::get('pinjaman-tunai/{id}/edit', [MasterDataController::class, 'edit'])->defaults('resource', 'cash-loans')->name('cash-loans.edit');
    Route::put('pinjaman-tunai/{id}', [MasterDataController::class, 'update'])->defaults('resource', 'cash-loans')->name('cash-loans.update');
    Route::delete('pinjaman-tunai/{id}', [MasterDataController::class, 'destroy'])->defaults('resource', 'cash-loans')->name('cash-loans.destroy');

    Route::get('stock-mutations', [StockMutationController::class, 'index'])->name('stock-mutations.index');
    Route::post('stock-mutations', [StockMutationController::class, 'store'])->name('stock-mutations.store');
    Route::get('stock-mutations/{mutation}/print', [StockMutationController::class, 'print'])->name('stock-mutations.print');
    Route::get('stock-summary', [StockSummaryController::class, 'index'])->name('stock-summary.index');
    Route::get('backorders', [BackorderController::class, 'index'])->name('backorders.index');
    Route::post('backorders/order', [BackorderController::class, 'orderStore'])->name('backorders.orderStore');
    Route::get('backorders/orders/{backorderOrder}', [BackorderController::class, 'orderShow'])->name('backorders.orderShow');
    Route::get('backorders/orders/{backorderOrder}/edit', [BackorderController::class, 'orderEdit'])->name('backorders.orderEdit');
    Route::put('backorders/orders/{backorderOrder}', [BackorderController::class, 'orderUpdate'])->name('backorders.orderUpdate');
    Route::delete('backorders/orders/{backorderOrder}', [BackorderController::class, 'orderDestroy'])->name('backorders.orderDestroy');
    Route::get('backorders/orders/{backorderOrder}/print', [BackorderController::class, 'orderPrint'])->name('backorders.orderPrint');
    Route::get('backorders/unavailable', [BackorderController::class, 'unavailable'])->name('backorders.unavailable');

    // Catalogs.
    Route::get('catalogs', [CatalogController::class, 'index'])->name('catalogs.index');
    Route::get('catalogs/create', [CatalogController::class, 'create'])->name('catalogs.create');
    Route::post('catalogs', [CatalogController::class, 'store'])->name('catalogs.store');
    Route::get('catalogs/{catalog}/edit', [CatalogController::class, 'edit'])->name('catalogs.edit');
    Route::put('catalogs/{catalog}', [CatalogController::class, 'update'])->name('catalogs.update');
    Route::delete('catalogs/{catalog}', [CatalogController::class, 'destroy'])->name('catalogs.destroy');
    Route::get('catalogs/{catalog}/pdf', [CatalogController::class, 'printPdf'])->name('catalogs.printPdf');

    // Rebate sekolah (rekap dari schools.rebate_percent via nota pesanan).
    Route::get('rebates', [RebateController::class, 'index'])->name('rebates.index');

    // Reports.
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Settings.
    Route::get('settings/company', [SettingsController::class, 'editCompany'])->name('settings.company.edit');
    Route::put('settings/company', [SettingsController::class, 'updateCompany'])->name('settings.company.update');
    Route::get('settings/users', [UserController::class, 'index'])->name('users.index');
    Route::post('settings/users', [UserController::class, 'store'])->name('users.store');
    Route::put('settings/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('settings/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('settings/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::put('settings/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::get('settings/audit', [AuditLogController::class, 'index'])->name('audit.index');

    // Integrations.
    Route::get('integrations', [IntegrationController::class, 'index'])->name('integrations.index');
    Route::post('integrations/{source}/sync', [IntegrationController::class, 'sync'])->name('integrations.sync');
});
