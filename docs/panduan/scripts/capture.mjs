/**
 * capture.mjs — ARKARA PM screenshot pipeline.
 *
 * Drives the running Laravel app with Playwright (system Edge) and writes
 * authentic screenshots to docs/panduan/screens/, plus an inventory.md that
 * maps every figure back to its route, role and permission.
 *
 * Usage:
 *   php artisan serve --host=127.0.0.1 --port=8000     # terminal 1
 *   node docs/panduan/scripts/capture.mjs              # terminal 2
 *
 * Env: APP_URL (default http://127.0.0.1:8000)
 */
import { chromium } from 'file:///D:/school-book/node_modules/playwright-core/index.mjs';
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const BASE = process.env.APP_URL || 'http://127.0.0.1:8000';
const OUT = 'D:/school-book/docs/panduan/screens';
const VIEWPORT = { width: 1440, height: 900 };
const PASSWORD = 'password';

mkdirSync(OUT, { recursive: true });

const figures = [];
const failures = [];

const record = (file, meta) => figures.push({ file: `${file}.png`, ...meta, capturedAt: new Date().toISOString() });

async function shot(page, name, meta = {}, { full = true, clip = null } = {}) {
  const target = join(OUT, `${name}.png`);
  try {
    await page.waitForTimeout(meta.settle ?? 220);
    const options = { path: target, fullPage: clip ? false : full };
    if (clip) options.clip = clip;
    await page.screenshot(options);
    record(name, meta);
    console.log('  ok  ', name);
  } catch (error) {
    failures.push({ file: `${name}.png`, reason: error.message, ...meta });
    console.log('  FAIL', name, '-', error.message);
  }
}

async function start(client) {
  const page = await client.newPage();
  await page.addInitScript(() => {
    const css = '*,*::before,*::after{transition:none!important;animation:none!important;caret-color:transparent!important}';
    const inject = () => {
      const style = document.createElement('style');
      style.textContent = css;
      (document.head || document.documentElement).appendChild(style);
    };
    if (document.head) inject();
    else document.addEventListener('DOMContentLoaded', inject);
  });
  return page;
}

async function go(page, url, settle = 250) {
  await page.goto(BASE + url, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
  await page.waitForFunction(() => !!window.Alpine, { timeout: 5000 }).catch(() => {});
  await page.waitForTimeout(settle);
}

async function login(page, email) {
  await page.goto(BASE + '/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name=email]', email);
  await page.fill('input[name=password]', PASSWORD);
  await Promise.all([
    page.waitForURL('**/dashboard', { timeout: 25000 }),
    page.click('button[type=submit]'),
  ]);
  await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
  await page.waitForTimeout(200);
}

async function openModal(page, name) {
  await page.evaluate((n) => window.dispatchEvent(new CustomEvent('open-modal', { detail: n })), name);
  await page.waitForTimeout(400);
}

const browser = await chromium.launch({ channel: 'msedge', headless: true });

// ─────────────────────────────────────────────────────────────── A. Auth (public)
{
  const client = await browser.newContext({ viewport: VIEWPORT, deviceScaleFactor: 2, locale: 'id-ID' });
  const page = await start(client);

  await go(page, '/login');
  await shot(page, '01-login', { section: 'Bagian II — Masuk', route: 'GET /login', role: 'publik' }, { full: false });

  await page.fill('input[name=email]', 'admin@sekolah.test');
  await page.fill('input[name=password]', 'kata-sandi-salah');
  await page.click('button[type=submit]');
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(300);
  await shot(page, '02-login-error', { section: 'Bagian II, VII — Masuk', route: 'POST /login', role: 'publik' }, { full: false });

  await client.close();
}

// ─────────────────────────────────────────────────────────────── B. Role dashboards (6 logins)
const ROLES = [
  { key: 'admin', email: 'admin@sekolah.test', file: '20-dashboard-admin' },
  { key: 'operator', email: 'operator@sekolah.test', file: '21-dashboard-operator' },
  { key: 'gudang', email: 'gudang@sekolah.test', file: '22-dashboard-gudang' },
  { key: 'keuangan', email: 'keuangan@sekolah.test', file: '23-dashboard-keuangan' },
  { key: 'sales', email: 'sales@sekolah.test', file: '24-dashboard-sales' },
  { key: 'pimpinan', email: 'pimpinan@sekolah.test', file: '25-dashboard-pimpinan' },
];

for (const role of ROLES) {
  const client = await browser.newContext({ viewport: VIEWPORT, deviceScaleFactor: 2, locale: 'id-ID' });
  const page = await start(client);
  try {
    await login(page, role.email);
    const title = await page.locator('h1').first().textContent().catch(() => '');
    // Dashboards are long; capture the viewport so the figure stays legible in
    // print instead of becoming a page-tall image.
    await shot(page, role.file, { section: `Bagian III/IV — Dashboard ${role.key}`, route: 'GET /dashboard', role: role.key, heading: (title || '').trim() }, { full: false });

    if (role.key === 'admin') {
      const sidebarWidth = await page.evaluate(() => {
        const el = document.querySelector('aside > div');
        return el ? Math.round(el.getBoundingClientRect().width) : 272;
      });

      // Full shell (sidebar + topbar) and the sidebar alone.
      await shot(page, '10-shell', { section: 'Bagian I/II — Kerangka aplikasi', route: 'GET /dashboard', role: role.key }, { full: false });
      await shot(page, '10-sidebar', { section: 'Bagian I/II — Sidebar', route: 'partials/sidebar.blade.php', role: role.key }, { clip: { x: 0, y: 0, width: sidebarWidth, height: VIEWPORT.height } });
      await shot(page, '11-topbar', { section: 'Bagian II — Topbar', route: 'partials/topbar.blade.php', role: role.key }, { clip: { x: sidebarWidth, y: 0, width: VIEWPORT.width - sidebarWidth, height: 64 } });

      // Global search (Ctrl+K) with live results.
      await page.keyboard.press('Control+k');
      await page.waitForTimeout(200);
      await page.locator('input[placeholder^="Cari pesanan"]').fill('TK');
      await page.waitForTimeout(1200);
      await shot(page, '12-global-search', { section: 'Bagian II — Pencarian global', route: 'GET /search?q=', role: role.key }, { full: false });
      await page.keyboard.press('Escape');
      await page.waitForTimeout(200);

      // Notifications panel.
      await page.click('button[aria-label="Notifikasi"]');
      await page.waitForTimeout(900);
      await shot(page, '13-notifications', { section: 'Bagian II — Notifikasi', route: 'GET /notifications', role: role.key }, { full: false });
      await page.keyboard.press('Escape');
      await page.waitForTimeout(200);

      // Account menu.
      await page.click('button[aria-label="Menu akun"]');
      await page.waitForTimeout(300);
      await shot(page, '14-user-menu', { section: 'Bagian II — Menu akun', route: 'partials/topbar.blade.php', role: role.key }, { full: false });
      await page.keyboard.press('Escape');

      // Dark theme sample.
      await page.evaluate(() => localStorage.setItem('bookflow.theme', 'dark'));
      await go(page, '/reports?type=sales_by_book');
      await shot(page, '15-dark-sample', { section: 'Bagian II — Tema gelap', route: 'GET /reports', role: role.key }, { full: false });
      await page.evaluate(() => localStorage.setItem('bookflow.theme', 'light'));
    }
  } catch (error) {
    failures.push({ file: role.file + '.png', reason: error.message, role: role.key });
    console.log('  FAIL', role.key, '-', error.message);
  }
  await client.close();
}

// ─────────────────────────────────────────────────────────────── C. Admin deep dive
const client = await browser.newContext({ viewport: VIEWPORT, deviceScaleFactor: 2, locale: 'id-ID' });
const page = await start(client);
await login(page, 'admin@sekolah.test');

const ADMIN = { role: 'admin' };

// C1. Master data — all 13 indexes
const MASTER = [
  ['schools', '30-master-schools'],
  ['publishers', '32-master-publishers'],
  ['categories', '34-master-categories'],
  ['books', '36-master-books'],
  ['barang-lainnya', '38-master-goods'],
  ['warehouses', '40-master-warehouses'],
  ['order-sources', '42-master-order-sources'],
  ['couriers', '44-master-couriers'],
  ['shipping-methods', '46-master-shipping-methods'],
  ['payment-methods', '48-master-payment-methods'],
  ['rebate-rules', '50-master-rebate-rules'],
  ['settings', '52-master-settings'],
  ['cash-loans', '54-master-cash-loans'],
];

for (const [resource, file] of MASTER) {
  await go(page, `/master/${resource}`);
  const heading = (await page.locator('h1').first().textContent().catch(() => '')) || '';
  await shot(page, file, { section: 'Bagian IV — Master Data', route: `GET /master/${resource}`, ...ADMIN, heading: heading.trim() });
}

// C2. Master data — create forms (Sekolah, Buku, Barang Lainnya, Penerbit, Pinjaman Tunai)
const FORMS = [
  ['schools', '31-master-schools-form'],
  ['books', '37-master-books-form'],
  ['barang-lainnya', '39-master-goods-form'],
  ['publishers', '33-master-publishers-form'],
  ['cash-loans', '55-master-cash-loans-form'],
];

for (const [resource, file] of FORMS) {
  await go(page, `/master/${resource}/create`);
  await shot(page, file, { section: 'Bagian IV — Master Data', route: `GET /master/${resource}/create`, ...ADMIN });
}

// C2b. Delete blocked because the record is referenced (publisher 1 has books; FK RESTRICT).
try {
  await go(page, '/master/publishers');
  await page.evaluate(() => {
    const form = [...document.querySelectorAll('form')].find((f) => /\/master\/publishers\/1$/.test(f.getAttribute('action') || ''));
    if (form) form.submit();
  });
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(500);
  await shot(page, '57-master-delete-error', { section: 'Bagian IV, VII — Master Data', route: 'DELETE /master/publishers/1', ...ADMIN }, { full: false });
} catch (error) {
  failures.push({ file: '57-master-delete-error.png', reason: error.message });
}

// C3. Orders
await go(page, '/orders');
await shot(page, '60-orders-index', { section: 'Bagian IV — Pesanan', route: 'GET /orders', ...ADMIN });

await go(page, '/orders?source=SIPLAH&status=fully_shipped');
await shot(page, '61-orders-filter', { section: 'Bagian IV — Pesanan', route: 'GET /orders?source=&status=', ...ADMIN });

await go(page, '/orders/create');
await shot(page, '62-orders-create', { section: 'Bagian IV — Pesanan', route: 'GET /orders/create', ...ADMIN });

// Item combobox open.
try {
  await page.locator('input[placeholder="Cari/pilih barang..."]').first().click();
  await page.locator('input[placeholder="Cari/pilih barang..."]').first().fill('matematika');
  await page.waitForTimeout(600);
  await shot(page, '63-orders-item-picker', { section: 'Bagian IV — Pesanan', route: 'orders/partials/item-book.blade.php', ...ADMIN }, { full: false });
} catch (error) {
  failures.push({ file: '63-orders-item-picker.png', reason: error.message });
}

// Validation error state (submit the blank form).
try {
  await go(page, '/orders/create');
  await page.click('button[type=submit]:has-text("Simpan Pesanan")');
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(500);
  await shot(page, '64-orders-validation', { section: 'Bagian IV, VII — Pesanan', route: 'POST /orders', ...ADMIN });
} catch (error) {
  failures.push({ file: '64-orders-validation.png', reason: error.message });
}

await go(page, '/orders/1');
await shot(page, '65-orders-show', { section: 'Bagian IV — Pesanan', route: 'GET /orders/{order}', ...ADMIN });

await go(page, '/orders/9');
await shot(page, '66-orders-show-shipped', { section: 'Bagian IV — Pesanan', route: 'GET /orders/{order}', ...ADMIN });

await go(page, '/orders/1/print');
await shot(page, '69-orders-print', { section: 'Bagian V — Dokumen cetak', route: 'GET /orders/{order}/print', ...ADMIN });

// C4. Goods receipts
await go(page, '/receipts');
await shot(page, '70-receipts-index', { section: 'Bagian IV — Penerimaan Barang', route: 'GET /receipts', ...ADMIN });

await go(page, '/receipts/create');
await shot(page, '71-receipts-create', { section: 'Bagian IV — Penerimaan Barang', route: 'GET /receipts/create', ...ADMIN });

await go(page, '/receipts/5');
await shot(page, '72-receipts-show', { section: 'Bagian IV — Penerimaan Barang', route: 'GET /receipts/{receipt}', ...ADMIN });

await go(page, '/receipts/6');
await shot(page, '72b-receipts-show-draft', { section: 'Bagian IV — Penerimaan Barang', route: 'GET /receipts/{receipt}', ...ADMIN });

// C5. Stock
await go(page, '/stock');
await shot(page, '80-stock-index', { section: 'Bagian IV — Stok', route: 'GET /stock', ...ADMIN });

await openModal(page, 'adjust-stock');
await shot(page, '81-stock-adjust', { section: 'Bagian IV — Stok', route: 'POST /stock/adjust', ...ADMIN }, { full: false });

// Negative stock guard: product 3 has 386 units in warehouse 3.
try {
  await page.selectOption('select[name=product_id]', '3');
  await page.selectOption('select[name=warehouse_id]', { index: 0 });
  await page.fill('input[name=quantity]', '-100000');
  await page.fill('input[name=reason]', 'Contoh penyesuaian keliru');
  await page.click('form[action$="/stock/adjust"] button[type=submit]');
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(500);
  await openModal(page, 'adjust-stock');
  await shot(page, '82-stock-adjust-error', { section: 'Bagian IV, VII — Stok', route: 'POST /stock/adjust', ...ADMIN }, { full: false });
} catch (error) {
  failures.push({ file: '82-stock-adjust-error.png', reason: error.message });
}

await go(page, '/stock/movements');
await shot(page, '83-stock-movements', { section: 'Bagian IV — Stok', route: 'GET /stock/movements', ...ADMIN });

// C6. Picking & packing
await go(page, '/pickings');
await shot(page, '90-pickings-index', { section: 'Bagian IV — Picking', route: 'GET /pickings', ...ADMIN });

await go(page, '/pickings/1');
await shot(page, '91-pickings-show-draft', { section: 'Bagian IV — Picking', route: 'GET /pickings/{picking}', ...ADMIN });

await go(page, '/pickings/2');
await shot(page, '92-pickings-show-picked', { section: 'Bagian IV — Picking', route: 'GET /pickings/{picking}', ...ADMIN });

// C7. Shipments
await go(page, '/shipments');
await shot(page, '100-shipments-index', { section: 'Bagian IV — Pengiriman', route: 'GET /shipments', ...ADMIN });

await go(page, '/shipments/create');
await shot(page, '101-shipments-create-1', { section: 'Bagian IV — Pengiriman', route: 'GET /shipments/create', ...ADMIN });

try {
  await page.selectOption('select[name=order_id]', { index: 1 });
  await page.click('button[type=submit]:has-text("Lanjutkan")');
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(400);
  await shot(page, '102-shipments-create-2', { section: 'Bagian IV — Pengiriman', route: 'GET /shipments/create?order_id=', ...ADMIN });
} catch (error) {
  failures.push({ file: '102-shipments-create-2.png', reason: error.message });
}

await go(page, '/shipments/1');
await shot(page, '103-shipments-show', { section: 'Bagian IV — Pengiriman', route: 'GET /shipments/{shipment}', ...ADMIN });

// Mandatory proof-photo confirm form (only present while a shipment is unconfirmed).
try {
  const confirmForm = page.locator('form[action$="/confirm"]').first();
  if (await confirmForm.count()) {
    await confirmForm.screenshot({ path: join(OUT, '104-shipments-confirm-form.png') });
    record('104-shipments-confirm-form', { section: 'Bagian IV — Pengiriman', route: 'POST /shipments/{shipment}/confirm', ...ADMIN });
    console.log('  ok   104-shipments-confirm-form');
  } else {
    failures.push({ file: '104-shipments-confirm-form.png', reason: 'Tidak ada pengiriman yang belum dikonfirmasi pada data demo (confirmed_at terisi untuk semua baris).' });
  }
} catch (error) {
  failures.push({ file: '104-shipments-confirm-form.png', reason: error.message });
}

await go(page, '/shipments/1/print');
await shot(page, '105-shipments-print', { section: 'Bagian IV/V — Dokumen cetak', route: 'GET /shipments/{shipment}/print', ...ADMIN });

// C8. Invoices
await go(page, '/invoices');
await shot(page, '110-invoices-index', { section: 'Bagian IV — Invoice', route: 'GET /invoices', ...ADMIN });

await go(page, '/invoices/1');
await shot(page, '111-invoices-show', { section: 'Bagian IV — Invoice', route: 'GET /invoices/{invoice}', ...ADMIN });

await go(page, '/invoices/1/print');
await shot(page, '112-invoices-print', { section: 'Bagian IV/VI — Dokumen cetak', route: 'GET /invoices/{invoice}/print', ...ADMIN });

// C9. Receivables / hutang
await go(page, '/receivables');
await shot(page, '120-receivables', { section: 'Bagian IV — Piutang', route: 'GET /receivables', ...ADMIN });

await go(page, '/finance/hutang');
await shot(page, '121-hutang', { section: 'Bagian IV — Piutang', route: 'GET /finance/hutang', ...ADMIN });

// C10. Payments
await go(page, '/payments');
await shot(page, '130-payments-index', { section: 'Bagian IV — Pembayaran', route: 'GET /payments', ...ADMIN });

await openModal(page, 'record-payment');
await shot(page, '131-payments-record', { section: 'Bagian IV — Pembayaran', route: 'POST /payments', ...ADMIN }, { full: false });

// C11. Rebates
await go(page, '/rebates');
await shot(page, '140-rebates-index', { section: 'Bagian IV — Rebate', route: 'GET /rebates', ...ADMIN });

await openModal(page, 'request-rebate');
await shot(page, '141-rebates-request', { section: 'Bagian IV — Rebate', route: 'POST /rebates', ...ADMIN }, { full: false });

// Inline row actions (Setujui / Tolak / Bayar) live in the right-hand column.
try {
  const table = page.locator('table').first();
  const box = await table.boundingBox();
  if (box) {
    const width = 280;
    await page.screenshot({
      path: join(OUT, '142-rebates-actions.png'),
      clip: { x: Math.max(0, box.x + box.width - width), y: box.y, width, height: Math.min(box.height, VIEWPORT.height - Math.max(0, box.y)) },
    });
    record('142-rebates-actions', { section: 'Bagian IV — Rebate', route: 'finance/rebates/partials/actions.blade.php', ...ADMIN });
    console.log('  ok   142-rebates-actions');
  } else {
    throw new Error('tabel rebate tidak ditemukan');
  }
} catch (error) {
  failures.push({ file: '142-rebates-actions.png', reason: error.message });
}

// C12. Reports
await go(page, '/reports');
await shot(page, '149-reports-index', { section: 'Bagian VI — Laporan', route: 'GET /reports', ...ADMIN });

const REPORTS = [
  ['sales_by_school', '150-reports-sales-by-school'],
  ['sales_by_publisher', '151-reports-sales-by-publisher'],
  ['sales_by_book', '152-reports-sales-by-book'],
  ['order_summary', '153-reports-orders-summary'],
  ['receivables', '154-reports-receivables'],
  ['overdue_invoices', '155-reports-overdue'],
  ['stock', '156-reports-stock'],
  ['low_stock', '157-reports-low-stock'],
  ['backorders', '158-reports-backorders'],
];

for (const [type, file] of REPORTS) {
  await go(page, `/reports?type=${type}`);
  await shot(page, file, { section: 'Bagian VI — Laporan', route: `GET /reports?type=${type}`, ...ADMIN }, { full: false });
}

// C13. Settings & integrations
await go(page, '/settings/company');
await shot(page, '160-settings-company', { section: 'Bagian IV — Pengaturan', route: 'GET /settings/company', ...ADMIN });

await go(page, '/settings/users');
await shot(page, '161-settings-users', { section: 'Bagian IV — Pengaturan', route: 'GET /settings/users', ...ADMIN });

await openModal(page, 'create-user');
await shot(page, '162-settings-user-create', { section: 'Bagian IV — Pengaturan', route: 'POST /settings/users', ...ADMIN }, { full: false });

await go(page, '/settings/roles');
await shot(page, '163-settings-roles', { section: 'Bagian III/IV — Peran & Izin', route: 'GET /settings/roles', ...ADMIN });

try {
  await page.locator('button:has-text("Gudang")').first().click();
  await page.waitForTimeout(500);
  await shot(page, '164-settings-role-edit', { section: 'Bagian IV — Peran & Izin', route: 'PUT /settings/roles/{role}', ...ADMIN }, { full: false });
} catch (error) {
  failures.push({ file: '164-settings-role-edit.png', reason: error.message });
}

await go(page, '/settings/audit');
await shot(page, '165-settings-audit', { section: 'Bagian IV/VIII — Audit Log', route: 'GET /settings/audit', ...ADMIN });

await go(page, '/integrations');
await shot(page, '166-integrations', { section: 'Bagian IV — Integrasi', route: 'GET /integrations', ...ADMIN });

// Delete blocked — admin cannot delete their own account (403, non-destructive).
try {
  await go(page, '/settings/users');
  const blocked = await page.evaluate(async () => {
    const form = [...document.querySelectorAll('form')].find((f) => /\/settings\/users\/1$/.test(f.getAttribute('action') || ''));
    if (!form) return false;
    form.submit();
    return true;
  });
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(400);
  if (blocked) await shot(page, '57b-user-delete-blocked', { section: 'Bagian IV, VII — Pengaturan', route: 'DELETE /settings/users/1', ...ADMIN }, { full: false });
} catch (error) {
  failures.push({ file: '57b-user-delete-blocked.png', reason: error.message });
}

// ─────────────────────────────────────────────────────────────── D. Optional: create a shipment (MUTATES demo data)
// Guarded by CAPTURE_MUTATIONS=1 so a plain re-run stays read-only. The
// shipment created here is demo data; roll it back with
// `php artisan db:seed --class=DemoDataResetSeeder`.
const mutations = [];

if (process.env.CAPTURE_MUTATIONS === '1') {
  try {
    // Prefer an unconfirmed shipment that already exists so re-runs stay
    // idempotent; only create one when the demo data has none.
    let target = null;
    await go(page, '/shipments?status=draft');
    const existing = page.locator('table tbody tr a[href*="/shipments/"]').first();
    if (await existing.count()) {
      // Hrefs may be absolute (app URL); reduce to a path before navigating.
      target = (await existing.getAttribute('href')).replace(/^https?:\/\/[^/]+/, '');
      mutations.push(`Memakai pengiriman yang belum dikonfirmasi: ${target}`);
    }

    if (!target) {
      await go(page, '/shipments/create');
      const orderValue = await page.locator('select[name=order_id] option').nth(1).getAttribute('value');
      const orderLabel = await page.locator('select[name=order_id] option').nth(1).textContent();

      if (orderValue) {
        await page.selectOption('select[name=order_id]', orderValue);
        await page.click('button[type=submit]:has-text("Lanjutkan")');
        await page.waitForLoadState('networkidle').catch(() => {});
        await page.waitForTimeout(400);
        await shot(page, '102b-shipments-create-2-filled', { section: 'Bagian IV — Pengiriman', route: 'GET /shipments/create?order_id=', ...ADMIN });

        const submit = page.locator('button[type=submit]:has-text("Simpan Pengiriman")');
        if ((await submit.count()) && (await submit.isEnabled())) {
          await Promise.all([page.waitForLoadState('networkidle').catch(() => {}), submit.click()]);
          await page.waitForTimeout(700);
          target = page.url().replace(BASE, '');
          mutations.push(`Membuat pengiriman baru dari pesanan ${orderLabel?.trim()} → ${target}`);
        }
      }
    }

    if (target) {
      await go(page, target);
      await shot(page, '103b-shipments-show-unconfirmed', { section: 'Bagian IV — Pengiriman', route: 'GET /shipments/{shipment}', ...ADMIN });

      const confirmForm = page.locator('form[action$="/confirm"]').first();
      if (await confirmForm.count()) {
        // Page-header band: title + mandatory proof-photo input + Konfirmasi Kirim.
        await page.screenshot({ path: join(OUT, '104-shipments-confirm-form.png'), clip: { x: 0, y: 0, width: VIEWPORT.width, height: 210 } });
        record('104-shipments-confirm-form', { section: 'Bagian IV — Pengiriman', route: 'POST /shipments/{shipment}/confirm', ...ADMIN });
        console.log('  ok   104-shipments-confirm-form');
      } else {
        failures.push({ file: '104-shipments-confirm-form.png', reason: 'Pengiriman tidak menampilkan form konfirmasi (sudah dikonfirmasi).' });
      }
    }
  } catch (error) {
    failures.push({ file: 'bonus-shipment.png', reason: error.message });
    console.log('  FAIL bonus shipment -', error.message);
  }

  writeFileSync(
    join(OUT, 'mutations.md'),
    ['# Mutasi Data Demo saat Penangkapan Gambar', '', ...mutations.map((m) => `- ${m}`), '', 'Pulihkan dengan: `php artisan db:seed --class=DemoDataResetSeeder`', ''].join('\n'),
  );
}

await client.close();
await browser.close();

// ─────────────────────────────────────────────────────────────── Inventory
const now = new Date().toISOString().slice(0, 10);
const lines = [
  '# Inventaris Screenshot — ARKARA PM',
  '',
  `Dibuat otomatis oleh \`scripts/capture.mjs\` pada ${now}. Semua gambar diambil dari aplikasi`,
  'yang berjalan pada data demo (akun `@sekolah.test`), bukan mockup.',
  '',
  `Total gambar: **${figures.length}**. Gagal/tidak tersedia: **${failures.length}**.`,
  '',
  '| # | Berkas | Bagian buku | Route | Peran |',
  '|---|---|---|---|---|',
];

figures.forEach((figure, index) => {
  lines.push(`| ${index + 1} | \`${figure.file}\` | ${figure.section || ''} | \`${figure.route || ''}\` | ${figure.role || ''} |`);
});

// A figure that failed once may succeed later (e.g. the optional mutation
// pass); only report it as missing if it never landed on disk.
const captured = new Set(figures.map((figure) => figure.file));
const missing = failures.filter((failure) => !captured.has(failure.file));

lines.push('', '## Gambar yang tidak dapat diambil', '', '| Berkas | Alasan |', '|---|---|');
if (missing.length === 0) lines.push('| — | Semua gambar berhasil diambil. |');
missing.forEach((failure) => lines.push(`| \`${failure.file}\` | ${String(failure.reason).split('\n')[0]} |`));

writeFileSync(join(OUT, 'inventory.md'), lines.join('\n') + '\n');

console.log(`\nSelesai. ${figures.length} gambar, ${failures.length} gagal.`);
console.log(`Inventaris: ${join(OUT, 'inventory.md')}`);
